<?php

namespace App\Http\Controllers;

use App\Support\StreamUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class StreamProxyController extends Controller
{
    public function __invoke(Request $request, string $encodedUrl)
    {
        $url = $this->decodeUrl($encodedUrl);

        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid stream URL.');
        }

        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            abort(Response::HTTP_BAD_REQUEST, 'Unsupported stream URL scheme.');
        }

        $signature = (string) $request->query('sig', '');

        if ($signature !== '' && ! StreamUrl::hasValidProxySignature($url, $signature)) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid stream URL signature.');
        }

        if ($scheme === 'http') {
            return $this->proxyInsecureStream($url);
        }

        return redirect()->away($url);
    }

    private function proxyInsecureStream(string $url): Response
    {
        try {
            $response = Http::withOptions(['stream' => true])
                ->timeout(0)
                ->connectTimeout(10)
                ->withHeaders([
                    'Accept' => '*/*',
                    'User-Agent' => 'RifiMediaStreamProxy/1.0',
                ])
                ->get($url);
        } catch (ConnectionException) {
            abort(Response::HTTP_BAD_GATEWAY, 'Stream source could not be reached.');
        }

        if (! $response->successful()) {
            return response(
                'Stream source returned HTTP '.$response->status().'.',
                Response::HTTP_BAD_GATEWAY,
                [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate',
                    'Access-Control-Allow-Origin' => '*',
                ]
            );
        }

        $contentType = StreamUrl::contentTypeFor($url, $response->header('Content-Type'));

        if (StreamUrl::isLikelyPlaylistUrl($url)) {
            $body = $response->body();
            $rewritten = StreamUrl::rewritePlaylist($body, $url);

            return response($rewritten, Response::HTTP_OK, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $body = $response->toPsrResponse()->getBody();

        return response()->stream(function () use ($body): void {
            while (! $body->eof()) {
                echo $body->read(1024 * 64);

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            }
        }, Response::HTTP_OK, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Access-Control-Allow-Origin' => '*',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function decodeUrl(string $encodedUrl): ?string
    {
        $normalized = strtr($encodedUrl, '-_', '+/');

        $padding = strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);

        return $decoded !== false ? $decoded : null;
    }
}

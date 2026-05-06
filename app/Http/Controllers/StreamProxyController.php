<?php

namespace App\Http\Controllers;

use App\Support\StreamUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StreamProxyController extends Controller
{
    public function __construct(
        private readonly \App\Services\UrlSafetyService $urlSafetyService,
    ) {
    }

    public function __invoke(Request $request, string $encodedUrl): Response
    {
        $url = StreamUrl::decodeProxyUrl($encodedUrl);

        abort_if(
            $url === null || ! StreamUrl::hasValidProxySignature($url, (string) $request->query('sig')),
            HttpResponse::HTTP_FORBIDDEN
        );

        $url = StreamUrl::browserSafe($url) ?? $url;
        $this->urlSafetyService->assertSafeForImport($url);

        $headers = [
            'Accept' => 'application/x-mpegURL, application/vnd.apple.mpegurl, video/mp2t, video/*, */*',
            'User-Agent' => $request->userAgent() ?: 'RiFiMediaTV/1.0',
        ];

        if ($request->headers->has('Range')) {
            $headers['Range'] = (string) $request->headers->get('Range');
        }

        $upstream = Http::timeout(60)
            ->connectTimeout(15)
            ->withHeaders($headers)
            ->withOptions([
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => true,
                ],
            ])
            ->get($url);

        abort_unless($upstream->successful(), $upstream->status());

        $contentType = $upstream->header('Content-Type', 'application/octet-stream');
        $body = (string) $upstream->body();
        $isPlaylist = StreamUrl::isPlaylist($url, $contentType, $body);

        if ($isPlaylist) {
            $body = StreamUrl::rewritePlaylist($body, $url);
            $contentType = 'application/vnd.apple.mpegurl; charset=utf-8';
        }

        $responseHeaders = [
            'Content-Type' => $contentType,
            'Cache-Control' => $isPlaylist ? 'public, max-age=15' : 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*',
        ];

        foreach (['Accept-Ranges', 'Content-Range'] as $header) {
            if ($upstream->header($header)) {
                $responseHeaders[$header] = $upstream->header($header);
            }
        }

        return response($body, $upstream->status(), $responseHeaders);
    }
}

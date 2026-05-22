<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        return redirect()->away($url);
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

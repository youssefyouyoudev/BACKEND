<?php

namespace App\Http\Controllers;

use App\Support\StreamUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StreamProxyController extends Controller
{
    public function __invoke(Request $request, string $encodedUrl): RedirectResponse
    {
        $url = $this->decodeUrl($encodedUrl);

        abort_if(
            $url === null || ! filter_var($url, FILTER_VALIDATE_URL),
            Response::HTTP_BAD_REQUEST,
            'Invalid stream URL.'
        );

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        abort_if(
            ! in_array($scheme, ['http', 'https'], true),
            Response::HTTP_BAD_REQUEST,
            'Unsupported stream URL scheme.'
        );

        Log::info('[RiFiProxy] Redirecting stream instead of proxying through PHP', [
            'url' => StreamUrl::masked($url),
        ]);

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

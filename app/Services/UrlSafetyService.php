<?php

namespace App\Services;

class UrlSafetyService
{
    public function __construct(
        private readonly StreamingPolicy $streamingPolicy,
    ) {}

    public function assertSafeForImport(string $url): void
    {
        $this->streamingPolicy->assertPlaylistUrlAllowed($url);
    }
}

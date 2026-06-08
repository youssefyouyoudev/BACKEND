<?php

$domains = static fn (string $value): array => array_values(array_filter(array_map(
    static fn (string $domain): string => strtolower(trim($domain)),
    explode(',', $value)
)));

return [
    'enable_external_streams' => (bool) env('STREAMING_ENABLE_EXTERNAL_STREAMS', false),
    'require_admin_approval' => (bool) env('STREAMING_REQUIRE_ADMIN_APPROVAL', true),
    'allowed_stream_domains' => $domains((string) env('STREAMING_ALLOWED_DOMAINS', '')),
    'allowed_playlist_domains' => $domains((string) env('STREAMING_ALLOWED_PLAYLIST_DOMAINS', env('STREAMING_ALLOWED_DOMAINS', ''))),
    'resolve_dns' => (bool) env('STREAMING_RESOLVE_DNS', true),
    'allowed_upload_types' => ['m3u', 'm3u8', 'txt'],
    'max_stream_duration' => (int) env('STREAMING_MAX_DURATION_SECONDS', 21600),
    'max_file_size_kb' => (int) env('STREAMING_MAX_FILE_SIZE_KB', 10240),
    'max_playlist_download_kb' => (int) env('STREAMING_MAX_PLAYLIST_DOWNLOAD_KB', 5120),
    'max_channels_per_import' => (int) env('STREAMING_MAX_CHANNELS_PER_IMPORT', 5000),
    'public_catalog_limit' => (int) env('STREAMING_PUBLIC_CATALOG_LIMIT', 500),
    'bridge_enabled' => (bool) env('STREAMING_BRIDGE_ENABLED', false),
];

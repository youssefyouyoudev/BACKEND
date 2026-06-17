<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('iptv_items')
            ->whereNotNull('stream_url')
            ->where('stream_url', '!=', '')
            ->orderBy('id')
            ->chunkById(250, function ($items): void {
                $now = now();
                $existingItemIds = DB::table('iptv_item_sources')
                    ->whereIn('iptv_item_id', $items->pluck('id'))
                    ->pluck('iptv_item_id')
                    ->all();

                $rows = $items
                    ->reject(fn (object $item): bool => in_array($item->id, $existingItemIds, true))
                    ->map(function (object $item) use ($now): array {
                        $type = $item->stream_type ?: $this->detectStreamType($item->stream_url);

                        return [
                            'iptv_item_id' => $item->id,
                            'label' => 'Legacy primary',
                            'url' => Crypt::encryptString($item->stream_url),
                            'type' => $type,
                            'quality_label' => $item->quality_label ?: 'Auto',
                            'priority' => 1,
                            'is_active' => true,
                            'health_status' => 'unknown',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->values()
                    ->all();

                if ($rows !== []) {
                    DB::table('iptv_item_sources')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        DB::table('iptv_item_sources')
            ->where('label', 'Legacy primary')
            ->delete();
    }

    private function detectStreamType(string $url): string
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return match (true) {
            str_ends_with($path, '.m3u8') => 'hls',
            str_ends_with($path, '.ts') => 'mpegts',
            str_ends_with($path, '.mp4') => 'mp4',
            default => 'auto',
        };
    }
};

<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelStream;
use App\Models\IptvItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class StreamHealthController extends Controller
{
    public function __invoke(): View
    {
        $stats = Cache::remember('admin:stream-health-summary', now()->addSeconds(30), function (): array {
            return [
                'iptv_total' => IptvItem::query()->where('type', IptvItem::TYPE_LIVE)->count(),
                'iptv_public' => IptvItem::query()->publicLive()->count(),
                'iptv_failed' => IptvItem::query()->where('health_status', 'offline')->count(),
                'channel_sources' => ChannelStream::query()->count(),
                'channel_sources_failed' => ChannelStream::query()->where('health_status', 'offline')->count(),
                'top_failing_items' => IptvItem::query()
                    ->select(['id', 'name', 'health_status', 'last_checked_at'])
                    ->where('health_status', 'offline')
                    ->latest('last_checked_at')
                    ->limit(10)
                    ->get(),
                'top_failing_sources' => ChannelStream::query()
                    ->with('channel:id,name')
                    ->select(['id', 'channel_id', 'health_status', 'last_checked_at'])
                    ->where('health_status', 'offline')
                    ->latest('last_checked_at')
                    ->limit(10)
                    ->get(),
            ];
        });

        return view('admin.stream-health.index', [
            'stats' => $stats,
            'server' => [
                'load' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
                'memory_limit' => ini_get('memory_limit'),
                'php_sapi' => PHP_SAPI,
            ],
        ]);
    }
}

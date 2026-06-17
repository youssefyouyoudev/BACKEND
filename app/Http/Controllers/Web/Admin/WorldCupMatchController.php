<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\AssignWorldCupIptvItemRequest;
use App\Http\Requests\Web\Admin\StoreWorldCupMatchRequest;
use App\Http\Requests\Web\Admin\UpdateWorldCupMatchRequest;
use App\Models\Channel;
use App\Models\IptvItem;
use App\Models\WorldCupMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorldCupMatchController extends Controller
{
    public function index(Request $request): View
    {
        $matches = WorldCupMatch::query()
            ->with([
                'selectedChannel.playlist',
                'selectedChannel.category',
                'selectedIptvItem.playlist',
                'iptvItems.playlist',
                'iptvItems.category',
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('home_team', 'like', "%{$search}%")
                        ->orWhere('away_team', 'like', "%{$search}%")
                        ->orWhere('group_name', 'like', "%{$search}%")
                        ->orWhere('commentator', 'like', "%{$search}%")
                        ->orWhere('channel_name_manual', 'like', "%{$search}%")
                        ->orWhereHas('selectedChannel', fn (Builder $channelQuery) => $channelQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('selectedIptvItem', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('iptvItems', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('group'), fn (Builder $query) => $query->where('group_name', $request->string('group')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('broadcast_status', $request->string('status')))
            ->when($request->boolean('missing_channel'), fn (Builder $query) => $query
                ->whereDoesntHave('iptvItems')
                ->whereNull('selected_iptv_item_id')
                ->whereNull('selected_channel_id')
                ->whereNull('channel_name_manual'))
            ->when($request->boolean('missing_commentator'), fn (Builder $query) => $query->whereNull('commentator'))
            ->when($request->boolean('missing_live_link'), function (Builder $query): void {
                $query->where(function (Builder $missingQuery): void {
                    $missingQuery->where('is_live_link_enabled', false)
                        ->orWhere(function (Builder $linkQuery): void {
                            $linkQuery
                                ->whereDoesntHave('iptvItems')
                                ->whereNull('selected_iptv_item_id')
                                ->whereNull('selected_channel_id')
                                ->whereNull('live_url_manual');
                        });
                });
            })
            ->when($request->boolean('featured'), fn (Builder $query) => $query->featured())
            ->orderBy('kickoff_at', $request->string('sort')->toString() === 'desc' ? 'desc' : 'asc')
            ->paginate(24)
            ->withQueryString();

        return view('admin.world-cup-matches.index', [
            'matches' => $matches,
            'groups' => $this->groups(),
            'statuses' => WorldCupMatch::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.world-cup-matches.create', [
            'worldCupMatch' => new WorldCupMatch([
                'competition' => 'FIFA World Cup 2026',
                'stage' => 'Group Stage',
                'broadcast_status' => 'to_confirm',
            ]),
            'channels' => $this->channels(),
            'iptvItems' => $this->publicIptvItems()->orderBy('name')->limit(300)->get(),
            'groups' => $this->groups(),
            'statuses' => WorldCupMatch::STATUSES,
        ]);
    }

    public function store(StoreWorldCupMatchRequest $request): RedirectResponse
    {
        $match = DB::transaction(function () use ($request): WorldCupMatch {
            $validated = $request->validated();
            $rows = $validated['match_iptv_items'] ?? [];
            unset($validated['match_iptv_items']);

            $match = WorldCupMatch::query()->create($validated);
            $this->syncMatchIptvItems($match, $rows);

            return $match;
        });

        return redirect()->route('admin.world-cup-matches.edit', $match)->with('status', __('World Cup match created.'));
    }

    public function edit(WorldCupMatch $worldCupMatch): View
    {
        return view('admin.world-cup-matches.edit', [
            'worldCupMatch' => $worldCupMatch->load([
                'selectedChannel.playlist',
                'selectedChannel.category',
                'selectedIptvItem.playlist',
                'selectedIptvItem.category',
                'iptvItems.category',
                'iptvItems.playlist',
            ]),
            'channels' => $this->channels(),
            'iptvItems' => $this->iptvItemOptions($worldCupMatch),
            'groups' => $this->groups(),
            'statuses' => WorldCupMatch::STATUSES,
        ]);
    }

    public function update(UpdateWorldCupMatchRequest $request, WorldCupMatch $worldCupMatch): RedirectResponse
    {
        DB::transaction(function () use ($request, $worldCupMatch): void {
            $validated = $request->validated();
            $rows = $validated['match_iptv_items'] ?? [];
            unset($validated['match_iptv_items']);

            $worldCupMatch->update($validated);
            $this->syncMatchIptvItems($worldCupMatch, $rows);
        });

        return back()->with('status', __('World Cup match updated.'));
    }

    public function destroy(WorldCupMatch $worldCupMatch): RedirectResponse
    {
        $worldCupMatch->delete();

        return redirect()->route('admin.world-cup-matches.index')->with('status', __('World Cup match deleted.'));
    }

    public function quickUpdate(Request $request, WorldCupMatch $worldCupMatch): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in([
                'toggle_featured',
                'toggle_live',
                'enable_live',
                'disable_live',
                'mark_live',
                'mark_ended',
                'clear_channel',
                'clear_live_url',
            ])],
        ]);

        match ($data['action']) {
            'toggle_featured' => $worldCupMatch->update(['is_featured' => ! $worldCupMatch->is_featured]),
            'toggle_live' => $worldCupMatch->update(['is_live_link_enabled' => ! $worldCupMatch->is_live_link_enabled]),
            'enable_live' => $worldCupMatch->update(['is_live_link_enabled' => true]),
            'disable_live' => $worldCupMatch->update(['is_live_link_enabled' => false]),
            'mark_live' => $worldCupMatch->update([
                'is_live_link_enabled' => true,
                'broadcast_status' => 'live',
            ]),
            'mark_ended' => $worldCupMatch->update([
                'is_live_link_enabled' => false,
                'broadcast_status' => 'ended',
            ]),
            'clear_channel' => $worldCupMatch->update(['selected_channel_id' => null]),
            'clear_live_url' => $worldCupMatch->update([
                'live_url_manual' => null,
                'use_manual_live_url' => false,
                'is_live_link_enabled' => false,
            ]),
        };

        return back()->with('status', __('Match updated.'));
    }

    public function autoEndOld(): RedirectResponse
    {
        Artisan::call('matches:auto-end-old');

        return back()->with('status', trim(Artisan::output()) ?: __('Old matches checked.'));
    }

    public function iptvItems(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim()->toString();

        $items = $this->publicIptvItems()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('group_title', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (IptvItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category?->name ?: $item->group_title ?: 'General',
                'quality' => $item->qualityLabel(),
                'logo' => $item->logo,
            ]);

        return response()->json(['items' => $items]);
    }

    public function assignIptvItem(
        AssignWorldCupIptvItemRequest $request,
        WorldCupMatch $worldCupMatch
    ): JsonResponse {
        $itemId = $request->validated('iptv_item_id');
        $item = $itemId ? $this->publicIptvItems()->findOrFail($itemId) : null;

        if ($item) {
            $isAssigned = $worldCupMatch->iptvItems()->whereKey($item->id)->exists();

            $isAssigned
                ? $worldCupMatch->iptvItems()->detach($item->id)
                : $worldCupMatch->iptvItems()->syncWithoutDetaching([$item->id]);

            if ($isAssigned && $worldCupMatch->selected_iptv_item_id === $item->id) {
                $worldCupMatch->update(['selected_iptv_item_id' => null]);
            }
        } else {
            $worldCupMatch->iptvItems()->detach();
            $worldCupMatch->update(['selected_iptv_item_id' => null]);
        }

        $worldCupMatch->load(['iptvItems.category', 'iptvItems.playlist']);
        $assignments = $worldCupMatch->iptvItems
            ->map(fn (IptvItem $assignedItem): array => [
                'id' => $assignedItem->id,
                'name' => $assignedItem->name,
                'category' => $assignedItem->category?->name ?: $assignedItem->group_title ?: 'General',
                'quality' => $assignedItem->qualityLabel(),
            ])
            ->values();

        $worldCupMatch->update([
            'is_live_link_enabled' => $assignments->isNotEmpty(),
        ]);

        return response()->json([
            'message' => $item
                ? ($isAssigned ? 'IPTV channel removed.' : 'IPTV channel added.')
                : 'All IPTV channels removed.',
            'assignments' => $assignments,
            'watch_available_at' => $worldCupMatch->watch_available_at?->toIso8601String(),
            'is_watch_window_open' => $worldCupMatch->is_watch_window_open,
        ]);
    }

    public function updateIptvItem(
        Request $request,
        WorldCupMatch $worldCupMatch,
        IptvItem $item
    ): RedirectResponse {
        abort_unless($worldCupMatch->iptvItems()->whereKey($item->getKey())->exists(), 404);

        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'is_recommended' => ['nullable', 'boolean'],
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
            'channel_name' => ['nullable', 'string', 'max:160'],
            'stream_title' => ['nullable', 'string', 'max:160'],
            'stream_type' => ['nullable', Rule::in(['hls', 'mpegts', 'mp4', 'iframe', 'other'])],
            'quality' => ['nullable', Rule::in(['SD', 'HD', 'FHD', '4K'])],
            'language' => ['nullable', 'string', 'max:60'],
            'commentator' => ['nullable', 'string', 'max:120'],
            'server_label' => ['nullable', 'string', 'max:80'],
            'health_status' => ['nullable', Rule::in(['unknown', 'online', 'offline'])],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $worldCupMatch->iptvItems()->updateExistingPivot($item->getKey(), [
            'is_active' => $request->boolean('is_active'),
            'is_recommended' => $request->boolean('is_recommended'),
            'priority' => $data['priority'],
            'channel_name' => $data['channel_name'] ?? null,
            'stream_title' => $data['stream_title'] ?? null,
            'stream_type' => $data['stream_type'] ?? null,
            'quality' => $data['quality'] ?? null,
            'language' => $data['language'] ?? null,
            'commentator' => $data['commentator'] ?? null,
            'server_label' => $data['server_label'] ?? null,
            'health_status' => $data['health_status'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('status', __('Match watch link updated.'));
    }

    private function channels()
    {
        return Channel::query()
            ->with(['category', 'playlist'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function iptvItemOptions(WorldCupMatch $worldCupMatch)
    {
        $selectedIds = collect([
            $worldCupMatch->selected_iptv_item_id,
            ...$worldCupMatch->iptvItems->pluck('id')->all(),
        ])->filter()->unique()->values();

        return $this->publicIptvItems()
            ->when($selectedIds->isNotEmpty(), fn (Builder $query) => $query->orWhereIn('id', $selectedIds))
            ->orderBy('name')
            ->limit(300)
            ->get();
    }

    private function syncMatchIptvItems(WorldCupMatch $worldCupMatch, array $rows): void
    {
        $sync = collect($rows)
            ->filter(fn (array $row): bool => filled($row['iptv_item_id'] ?? null))
            ->keyBy(fn (array $row): int => (int) $row['iptv_item_id'])
            ->mapWithKeys(function (array $row, int $itemId): array {
                return [$itemId => [
                    'is_active' => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'priority' => (int) ($row['priority'] ?? 1),
                    'channel_name' => $row['channel_name'] ?? null,
                    'stream_title' => $row['stream_title'] ?? null,
                    'stream_type' => $row['stream_type'] ?? null,
                    'quality' => $row['quality'] ?? null,
                    'language' => $row['language'] ?? null,
                    'commentator' => $row['commentator'] ?? null,
                    'server_label' => $row['server_label'] ?? null,
                    'is_recommended' => filter_var($row['is_recommended'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'health_status' => $row['health_status'] ?? null,
                    'starts_at' => $row['starts_at'] ?? null,
                    'expires_at' => $row['expires_at'] ?? null,
                ]];
            })
            ->all();

        $worldCupMatch->iptvItems()->sync($sync);
    }

    private function publicIptvItems(): Builder
    {
        return IptvItem::query()
            ->publicLive()
            ->whereHas('playlist', fn (Builder $playlistQuery) => $playlistQuery
                ->where('is_public', true)
                ->whereNotNull('approved_at'))
            ->with(['category:id,name', 'playlist:id,is_public,approved_at']);
    }

    private function groups(): array
    {
        return collect(range('A', 'L'))->map(fn (string $group): string => "Group {$group}")->all();
    }
}

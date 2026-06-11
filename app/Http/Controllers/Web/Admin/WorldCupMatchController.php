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
            'groups' => $this->groups(),
            'statuses' => WorldCupMatch::STATUSES,
        ]);
    }

    public function store(StoreWorldCupMatchRequest $request): RedirectResponse
    {
        $match = WorldCupMatch::query()->create($request->validated());

        return redirect()->route('admin.world-cup-matches.edit', $match)->with('status', __('World Cup match created.'));
    }

    public function edit(WorldCupMatch $worldCupMatch): View
    {
        return view('admin.world-cup-matches.edit', [
            'worldCupMatch' => $worldCupMatch->load(['selectedChannel.playlist', 'selectedChannel.category']),
            'channels' => $this->channels(),
            'groups' => $this->groups(),
            'statuses' => WorldCupMatch::STATUSES,
        ]);
    }

    public function update(UpdateWorldCupMatchRequest $request, WorldCupMatch $worldCupMatch): RedirectResponse
    {
        $worldCupMatch->update($request->validated());

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
            'action' => ['required', Rule::in(['toggle_featured', 'toggle_live', 'clear_channel', 'clear_live_url'])],
        ]);

        match ($data['action']) {
            'toggle_featured' => $worldCupMatch->update(['is_featured' => ! $worldCupMatch->is_featured]),
            'toggle_live' => $worldCupMatch->update(['is_live_link_enabled' => ! $worldCupMatch->is_live_link_enabled]),
            'clear_channel' => $worldCupMatch->update(['selected_channel_id' => null]),
            'clear_live_url' => $worldCupMatch->update([
                'live_url_manual' => null,
                'use_manual_live_url' => false,
                'is_live_link_enabled' => false,
            ]),
        };

        return back()->with('status', __('Match updated.'));
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

    private function channels()
    {
        return Channel::query()
            ->with(['category', 'playlist'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class WorldCupMatch extends Model
{
    use HasFactory;

    public const STATUSES = [
        'to_confirm',
        'confirmed',
        'live',
        'finished',
        'postponed',
        'cancelled',
    ];

    protected $fillable = [
        'match_number',
        'competition',
        'stage',
        'group_name',
        'home_team',
        'away_team',
        'home_team_code',
        'away_team_code',
        'home_flag',
        'away_flag',
        'venue',
        'city',
        'country',
        'kickoff_at',
        'morocco_kickoff_at',
        'local_kickoff_at',
        'local_timezone',
        'commentator',
        'selected_channel_id',
        'selected_iptv_item_id',
        'channel_name_manual',
        'broadcaster',
        'live_url_manual',
        'use_manual_live_url',
        'is_live_link_enabled',
        'broadcast_status',
        'is_featured',
        'admin_notes',
        'source_name',
        'source_url',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'match_number' => 'integer',
            'kickoff_at' => 'datetime',
            'morocco_kickoff_at' => 'datetime',
            'local_kickoff_at' => 'datetime',
            'use_manual_live_url' => 'boolean',
            'is_live_link_enabled' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function selectedChannel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'selected_channel_id');
    }

    public function selectedIptvItem(): BelongsTo
    {
        return $this->belongsTo(IptvItem::class, 'selected_iptv_item_id');
    }

    public function iptvItems(): BelongsToMany
    {
        return $this->belongsToMany(IptvItem::class, 'world_cup_match_iptv_item')
            ->withTimestamps();
    }

    public function getPublicChannelNameAttribute(): string
    {
        $iptvNames = $this->assignedIptvItems()
            ->pluck('name')
            ->filter();

        return $iptvNames->isNotEmpty()
            ? $iptvNames->implode(' / ')
            : ($this->selectedIptvItem?->name
            ?: $this->selectedChannel?->clean_display_name
            ?: $this->channel_name_manual
            ?: 'Channel to be confirmed');
    }

    public function getPublicWatchUrlAttribute(): ?string
    {
        return $this->public_watch_links->first()['url'] ?? null;
    }

    /**
     * @return Collection<int, array{name: string, url: string, external: bool}>
     */
    public function getPublicWatchLinksAttribute(): Collection
    {
        if (! $this->is_live_link_enabled) {
            return collect();
        }

        $iptvItems = $this->assignedIptvItems();

        if ($iptvItems->isNotEmpty()) {
            if (! $this->isWatchWindowOpen()) {
                return collect();
            }

            return $iptvItems
                ->filter(fn (IptvItem $item): bool => $this->isPublicLiveIptvItem($item))
                ->map(fn (IptvItem $item): array => [
                    'name' => $item->name,
                    'url' => route('watch.item', $item),
                    'external' => false,
                ])
                ->values();
        }

        if ($this->use_manual_live_url && filled($this->live_url_manual)) {
            return collect([[
                'name' => $this->channel_name_manual ?: 'Watch Live',
                'url' => $this->live_url_manual,
                'external' => true,
            ]]);
        }

        $channel = $this->selectedChannel;
        $playlist = $channel?->playlist;

        if (! $channel?->is_active || blank($channel->stream_url)) {
            return collect();
        }

        if (! $playlist?->is_public || ! $playlist->approved_at) {
            return collect();
        }

        return collect([[
            'name' => $channel->clean_display_name,
            'url' => route('channels.show', $channel->slug ?: $channel->id),
            'external' => false,
        ]]);
    }

    public function getUsesExternalWatchUrlAttribute(): bool
    {
        return $this->assignedIptvItems()->isEmpty()
            && ! $this->selected_iptv_item_id
            && $this->use_manual_live_url
            && filled($this->live_url_manual);
    }

    public function getWatchAvailableAtAttribute(): ?Carbon
    {
        return $this->kickoff_at?->copy()->subMinutes(30);
    }

    public function getIsWatchWindowOpenAttribute(): bool
    {
        return $this->isWatchWindowOpen();
    }

    public function scopeGroupStage(Builder $query): Builder
    {
        return $query->where('stage', 'Group Stage');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('kickoff_at', '>=', now())->orderBy('kickoff_at');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->whereNotNull('kickoff_at');
    }

    private function isWatchWindowOpen(): bool
    {
        return ! $this->watch_available_at || now()->greaterThanOrEqualTo($this->watch_available_at);
    }

    /**
     * @return Collection<int, IptvItem>
     */
    private function assignedIptvItems(): Collection
    {
        $items = $this->relationLoaded('iptvItems')
            ? $this->iptvItems
            : $this->iptvItems()->with('playlist')->get();

        if ($items->isEmpty() && $this->selectedIptvItem) {
            return collect([$this->selectedIptvItem]);
        }

        return $items;
    }

    private function isPublicLiveIptvItem(IptvItem $item): bool
    {
        return $item->is_active
            && $item->is_public
            && ! $item->is_adult
            && $item->type === IptvItem::TYPE_LIVE
            && filled($item->stream_url)
            && $item->playlist?->is_public
            && filled($item->playlist?->approved_at);
    }
}

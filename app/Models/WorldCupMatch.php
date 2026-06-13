<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class WorldCupMatch extends Model
{
    use HasFactory;

    public const MOROCCO_TIMEZONE = 'Africa/Casablanca';

    public const STATUSES = [
        'to_confirm',
        'scheduled',
        'live',
        'ended',
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
        'watch_opens_at',
        'watch_expires_at',
        'commentator',
        'selected_channel_id',
        'selected_iptv_item_id',
        'channel_name_manual',
        'broadcaster',
        'live_url_manual',
        'player_type',
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
            'kickoff_at' => 'immutable_datetime',
            'morocco_kickoff_at' => 'immutable_datetime',
            'local_kickoff_at' => 'immutable_datetime',
            'watch_opens_at' => 'immutable_datetime',
            'watch_expires_at' => 'immutable_datetime',
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
            ->withPivot([
                'is_active',
                'priority',
                'channel_name',
                'stream_title',
                'stream_type',
                'quality',
                'language',
                'commentator',
                'server_label',
                'is_recommended',
                'health_status',
                'last_checked_at',
                'starts_at',
                'expires_at',
            ])
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
        if (! $this->is_live_link_enabled || ! $this->isWatchOpen()) {
            return collect();
        }

        $hasSource = $this->availableWatchItems()->isNotEmpty()
            || ($this->use_manual_live_url && filled($this->live_url_manual))
            || ($this->selectedChannel?->is_active && filled($this->selectedChannel?->stream_url));

        if (! $hasSource) {
            return collect();
        }

        return collect([[
            'name' => 'Watch Match',
            'url' => route('matches.watch', $this),
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

    public function getKickoffAtMoroccoAttribute(): ?CarbonImmutable
    {
        if ($this->morocco_kickoff_at) {
            return $this->asMoroccoWallClock($this->morocco_kickoff_at);
        }

        return $this->kickoff_at?->setTimezone(self::MOROCCO_TIMEZONE);
    }

    public function getWatchOpensAtAttribute(): ?CarbonImmutable
    {
        if ($this->attributes['watch_opens_at'] ?? null) {
            return $this->asMoroccoWallClock($this->getAttributeFromArray('watch_opens_at'));
        }

        return $this->kickoff_at_morocco?->subHour();
    }

    public function getExpectedEndsAtAttribute(): ?CarbonImmutable
    {
        return $this->kickoff_at_morocco?->addHours(2);
    }

    public function getWatchExpiresAtAttribute(): ?CarbonImmutable
    {
        if ($this->attributes['watch_expires_at'] ?? null) {
            return $this->asMoroccoWallClock($this->getAttributeFromArray('watch_expires_at'));
        }

        return $this->kickoff_at_morocco?->addHours(3);
    }

    public function getWatchAvailableAtAttribute(): ?CarbonImmutable
    {
        return $this->watch_opens_at;
    }

    public function getIsWatchWindowOpenAttribute(): bool
    {
        return $this->isWatchOpen();
    }

    public function scopeGroupStage(Builder $query): Builder
    {
        return $query->where('stage', 'Group Stage');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('kickoff_at', '>=', now('UTC'))->orderBy('kickoff_at');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->whereNotNull('kickoff_at');
    }

    public function isWatchOpen(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now(self::MOROCCO_TIMEZONE);

        return $this->watch_opens_at !== null
            && $this->watch_expires_at !== null
            && $now->betweenIncluded($this->watch_opens_at, $this->watch_expires_at);
    }

    public function isWatchUpcoming(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now(self::MOROCCO_TIMEZONE);

        return $this->watch_opens_at !== null && $now->lessThan($this->watch_opens_at);
    }

    public function isWatchExpired(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now(self::MOROCCO_TIMEZONE);

        return $this->watch_expires_at !== null && $now->greaterThan($this->watch_expires_at);
    }

    public function watchStatus(?CarbonImmutable $now = null): string
    {
        if ($this->isWatchExpired($now)) {
            return 'expired';
        }

        if ($this->isWatchOpen($now)) {
            return 'open';
        }

        return 'opens_soon';
    }

    /**
     * @return Collection<int, IptvItem>
     */
    public function availableWatchItems(?CarbonImmutable $now = null): Collection
    {
        $now ??= CarbonImmutable::now(self::MOROCCO_TIMEZONE);

        if (! $this->is_live_link_enabled || ! $this->isWatchOpen($now)) {
            return collect();
        }

        return $this->assignedIptvItems()
            ->filter(function (IptvItem $item) use ($now): bool {
                $startsAt = $item->pivot?->starts_at
                    ? CarbonImmutable::parse($item->pivot->starts_at, 'UTC')->setTimezone(self::MOROCCO_TIMEZONE)
                    : null;
                $expiresAt = $item->pivot?->expires_at
                    ? CarbonImmutable::parse($item->pivot->expires_at, 'UTC')->setTimezone(self::MOROCCO_TIMEZONE)
                    : null;

                return ($item->pivot?->is_active ?? true)
                    && (! $startsAt || $startsAt->lessThanOrEqualTo($now))
                    && (! $expiresAt || $expiresAt->greaterThanOrEqualTo($now))
                    && $this->isPublicLiveIptvItem($item);
            })
            ->sort(function (IptvItem $left, IptvItem $right): int {
                $selected = (int) ($right->getKey() === $this->selected_iptv_item_id)
                    <=> (int) ($left->getKey() === $this->selected_iptv_item_id);

                if ($selected !== 0) {
                    return $selected;
                }

                $recommended = (int) ($right->pivot?->is_recommended ?? false)
                    <=> (int) ($left->pivot?->is_recommended ?? false);

                if ($recommended !== 0) {
                    return $recommended;
                }

                $priority = (int) ($left->pivot?->priority ?? 0)
                    <=> (int) ($right->pivot?->priority ?? 0);

                if ($priority !== 0) {
                    return $priority;
                }

                return $this->qualityRank($right) <=> $this->qualityRank($left);
            })
            ->values();
    }

    /**
     * @return Collection<int, IptvItem>
     */
    private function assignedIptvItems(): Collection
    {
        $items = $this->relationLoaded('iptvItems')
            ? $this->iptvItems
            : $this->iptvItems()->with('playlist')->get();

        if ($this->selectedIptvItem && ! $items->contains(fn (IptvItem $item): bool => $item->is($this->selectedIptvItem))) {
            $items->prepend($this->selectedIptvItem);
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

    private function qualityRank(IptvItem $item): int
    {
        return match (strtoupper((string) ($item->pivot?->quality ?: $item->qualityLabel()))) {
            '4K', 'UHD' => 4,
            'FHD', '1080P' => 3,
            'HD', '720P' => 2,
            default => 1,
        };
    }

    private function asMoroccoWallClock(mixed $value): CarbonImmutable
    {
        $formatted = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : CarbonImmutable::parse($value)->format('Y-m-d H:i:s');

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $formatted, self::MOROCCO_TIMEZONE);
    }
}

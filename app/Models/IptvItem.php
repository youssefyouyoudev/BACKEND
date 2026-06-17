<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IptvItem extends Model
{
    use HasFactory;

    public const TYPE_LIVE = 'live';

    public const TYPE_MOVIE = 'movie';

    public const TYPE_SERIES = 'series';

    protected $fillable = [
        'playlist_id',
        'category_id',
        'type',
        'external_id',
        'name',
        'normalized_name',
        'stream_url',
        'logo',
        'tvg_id',
        'tvg_name',
        'group_title',
        'extension',
        'stream_type',
        'quality_label',
        'language',
        'country',
        'rating',
        'description',
        'year',
        'is_adult',
        'is_active',
        'is_public',
        'is_featured',
        'health_status',
        'last_checked_at',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'is_adult' => 'boolean',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'last_checked_at' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IptvCategory::class, 'category_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(IptvItemSource::class)
            ->orderBy('priority')
            ->orderBy('id');
    }

    public function activeSources(): HasMany
    {
        return $this->sources()->where('is_active', true);
    }

    public function worldCupMatches(): BelongsToMany
    {
        return $this->belongsToMany(WorldCupMatch::class, 'world_cup_match_iptv_item')
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

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopePublicLive(Builder $query): Builder
    {
        return $query
            ->visible()
            ->published()
            ->where('type', self::TYPE_LIVE)
            ->where('is_adult', false)
            ->whereNotNull('stream_url')
            ->where('stream_url', '!=', '');
    }

    public function qualityLabel(): string
    {
        if (filled($this->quality_label) && $this->quality_label !== 'Auto') {
            return $this->quality_label;
        }

        if (preg_match('/\b(?:4K|UHD|2160P)\b/i', $this->name) === 1) {
            return '4K';
        }

        if (preg_match('/\b(?:FHD|FULL[\s._-]*HD|1080P)\b/i', $this->name) === 1) {
            return 'FHD';
        }

        if (preg_match('/\b(?:HD|720P)\b/i', $this->name) === 1) {
            return 'HD';
        }

        return 'SD';
    }

    public function scopeCuratedSports(Builder $query): Builder
    {
        $keywords = collect(config('sports_channels.networks', []))
            ->flatMap(fn (array $network): array => $network['keywords'] ?? [])
            ->filter()
            ->unique()
            ->values();

        return $query->where(function (Builder $query) use ($keywords): void {
            foreach ($keywords as $keyword) {
                $pattern = '%'.$keyword.'%';

                $query->orWhere('name', 'like', $pattern)
                    ->orWhere('group_title', 'like', $pattern)
                    ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                        ->where('name', 'like', $pattern));
            }
        });
    }

    public function scopeCuratedSportsOrder(Builder $query): Builder
    {
        $bindings = [];
        $cases = [];

        foreach (config('sports_channels.networks', []) as $priority => $network) {
            foreach ($network['keywords'] ?? [] as $keyword) {
                $cases[] = 'WHEN LOWER(name) LIKE ? OR LOWER(COALESCE(group_title, \'\')) LIKE ? THEN '.(int) $priority;
                $bindings[] = '%'.mb_strtolower($keyword).'%';
                $bindings[] = '%'.mb_strtolower($keyword).'%';
            }
        }

        if ($cases !== []) {
            $query->orderByRaw('CASE '.implode(' ', $cases).' ELSE 999 END', $bindings);
        }

        return $query->orderBy('name');
    }

    public function scopeBeinSports(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('name', 'like', '%bein%')
                ->orWhere('group_title', 'like', '%bein%')
                ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                    ->where('name', 'like', '%bein%'));
        });
    }

    public static function isAdultName(?string $value): bool
    {
        $value = mb_strtolower((string) $value);

        return str_contains($value, 'adult')
            || str_contains($value, 'xxx')
            || str_contains($value, '18+')
            || str_contains($value, 'porn');
    }

    public function primaryStreamUrl(): ?string
    {
        $source = $this->relationLoaded('sources')
            ? $this->sources->firstWhere('is_active', true)
            : $this->activeSources()->first();

        return $source?->url ?: $this->stream_url;
    }
}

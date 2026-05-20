<?php

namespace App\Models;

use App\Support\StreamUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'playlist_id',
        'tvg_id',
        'name',
        'normalized_name',
        'slug',
        'logo',
        'group_title',
        'category_id',
        'stream_url',
        'stream_type',
        'is_live',
        'stream_hash',
        'channel_identity_hash',
        'is_active',
        'sort_order',
        'is_featured',
        'featured_rank',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_live' => 'boolean',
            'is_featured' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getCleanDisplayNameAttribute(): string
    {
        $name = (string) $this->name;
        $name = (string) preg_replace('/^\s*(?:\|([A-Z]{2,4})\|\s*)+/iu', '', $name);
        $name = (string) preg_replace('/\b(?:UHD|FHD|HD|SD|4K|VIP)\b/iu', '', $name);
        $name = (string) preg_replace('/\s+/u', ' ', trim($name));

        return $name !== '' ? $name : $this->name;
    }

    /**
     * Lightweight presentation tags extracted from raw IPTV names.
     *
     * The original channel name stays untouched for admin/editing and exact
     * duplicate grouping; these tags are only for public UI polish.
     *
     * @return array<int, string>
     */
    public function getDisplayTagsAttribute(): array
    {
        $name = (string) $this->name;
        $tags = [];

        if (preg_match_all('/\|([A-Z]{2,4})\|/iu', $name, $languageMatches)) {
            $tags = array_merge($tags, array_map('strtoupper', $languageMatches[1]));
        }

        if (preg_match_all('/\b(UHD|FHD|HD|SD|4K|VIP)\b/iu', $name, $qualityMatches)) {
            $tags = array_merge($tags, array_map('strtoupper', $qualityMatches[1]));
        }

        return array_values(array_unique($tags));
    }

    public function getQualityLabelAttribute(): string
    {
        $tags = $this->display_tags;

        foreach (['4K', 'UHD', 'FHD', 'HD', 'SD'] as $quality) {
            if (in_array($quality, $tags, true)) {
                return $quality;
            }
        }

        return 'HD';
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function favoredByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function currentProgram()
    {
        return $this->hasOne(Program::class)
            ->where('start_time', '<=', now())
            ->where('end_time', '>', now())
            ->latestOfMany('start_time');
    }

    /**
     * All server URLs available for this channel, ordered by priority ascending
     * (lower number = tried first).
     */
    public function streams(): HasMany
    {
        return $this->hasMany(ChannelStream::class)->orderBy('priority');
    }

    public function failoverLogs(): HasMany
    {
        return $this->hasMany(FailoverLog::class)->latest('occurred_at');
    }

    /**
     * Return an ordered array of active stream URLs for the JS failover player.
     *
     * @return Collection<int, array{url: string, type: string, label: ?string}>
     */
    public function getActiveStreamSourcesAttribute(): Collection
    {
        // If we have dedicated streams rows, use those.
        if ($this->relationLoaded('streams') && $this->streams->isNotEmpty()) {
            return $this->streams
                ->where('is_active', true)
                ->values()
                ->map(fn (ChannelStream $s) => [
                    'url'   => StreamUrl::proxied($s->stream_url),
                    'type'  => $s->stream_type,
                    'label' => $s->label,
                    'source_code' => $s->source_code,
                    'server_name' => $s->server_name,
                    'server_region' => $s->server_region,
                    'quality' => $s->quality,
                    'health_status' => $s->health_status,
                ]);
        }

        // Fallback: legacy single stream_url column.
        if ($this->stream_url) {
            return collect([[
                'url'   => StreamUrl::proxied($this->stream_url),
                'type'  => $this->stream_type ?? 'stream',
                'label' => 'Server 1',
            ]]);
        }

        return collect();
    }

    public function getCategoryAttribute(): ?string
    {
        return $this->group_title;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('playlist', fn (Builder $playlistQuery) => $playlistQuery->visibleTo($user));
    }

    public function scopeCanonical(Builder $query): Builder
    {
        return $query->whereNotExists(function ($subQuery): void {
            $subQuery->selectRaw('1')
                ->from('channels as canonical_channels')
                ->where('canonical_channels.is_active', true)
                ->whereRaw("COALESCE(NULLIF(canonical_channels.normalized_name, ''), LOWER(TRIM(canonical_channels.name))) = COALESCE(NULLIF(channels.normalized_name, ''), LOWER(TRIM(channels.name)))")
                ->whereColumn('canonical_channels.id', '<', 'channels.id');
        });
    }

    public static function normalizeName(?string $name): string
    {
        $normalized = (string) preg_replace('/\s+/u', ' ', trim((string) $name));

        return mb_strtolower($normalized);
    }
}

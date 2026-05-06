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
        'logo',
        'group_title',
        'stream_url',
        'stream_type',
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
            'is_featured' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
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
                'type'  => $this->stream_type ?? 'hls',
                'label' => 'Primary',
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
}

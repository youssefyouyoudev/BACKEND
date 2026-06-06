<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'stream_url',
        'logo',
        'tvg_id',
        'group_title',
        'extension',
        'rating',
        'description',
        'year',
        'is_adult',
        'is_active',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'is_adult' => 'boolean',
            'is_active' => 'boolean',
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

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function isAdultName(?string $value): bool
    {
        $value = mb_strtolower((string) $value);

        return str_contains($value, 'adult')
            || str_contains($value, 'xxx')
            || str_contains($value, '18+')
            || str_contains($value, 'porn');
    }
}

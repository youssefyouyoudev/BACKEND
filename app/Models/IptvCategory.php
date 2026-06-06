<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IptvCategory extends Model
{
    use HasFactory;

    public const TYPE_LIVE = 'live';
    public const TYPE_MOVIE = 'movie';
    public const TYPE_SERIES = 'series';

    protected $fillable = [
        'playlist_id',
        'type',
        'external_id',
        'name',
        'sort_order',
    ];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(IptvItem::class, 'category_id');
    }
}

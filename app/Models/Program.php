<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'title',
        'start_time',
        'end_time',
        'description',
        'rating',
        'language',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('start_time', '<=', now())
            ->where('end_time', '>', now());
    }

    public function scopeWindow(Builder $query, mixed $start, mixed $end): Builder
    {
        return $query->where('end_time', '>', $start)
            ->where('start_time', '<', $end);
    }
}

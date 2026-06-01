<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'stream_url',
        'stream_hash',
        'stream_type',
        'priority',
        'is_active',
        'label',
        'source_code',
        'server_name',
        'server_region',
        'quality',
        'health_status',
        'latency_ms',
        'response_code',
        'failure_count',
        'success_count',
        'last_error',
        'last_checked_at',
        'last_success_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority'  => 'integer',
            'latency_ms' => 'integer',
            'response_code' => 'integer',
            'failure_count' => 'integer',
            'success_count' => 'integer',
            'last_checked_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function statusChecks()
    {
        return $this->hasMany(StreamServerStatus::class);
    }

    public function latestStatus()
    {
        return $this->hasOne(StreamServerStatus::class)->latestOfMany('checked_at');
    }
}

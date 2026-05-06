<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamServerStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_stream_id',
        'status',
        'probe_type',
        'http_status',
        'latency_ms',
        'bytes_received',
        'message',
        'diagnostics',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'diagnostics' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(ChannelStream::class, 'channel_stream_id');
    }
}

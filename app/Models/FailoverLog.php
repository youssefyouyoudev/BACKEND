<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailoverLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'from_channel_stream_id',
        'to_channel_stream_id',
        'event_type',
        'severity',
        'rule_code',
        'message',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function fromStream(): BelongsTo
    {
        return $this->belongsTo(ChannelStream::class, 'from_channel_stream_id');
    }

    public function toStream(): BelongsTo
    {
        return $this->belongsTo(ChannelStream::class, 'to_channel_stream_id');
    }
}

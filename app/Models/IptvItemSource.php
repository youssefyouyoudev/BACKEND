<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IptvItemSource extends Model
{
    protected $fillable = [
        'iptv_item_id',
        'label',
        'url',
        'type',
        'quality_label',
        'priority',
        'is_active',
        'health_status',
        'latency_ms',
        'response_code',
        'failure_count',
        'success_count',
        'last_error',
        'last_checked_at',
        'last_success_at',
    ];

    protected $hidden = ['url'];

    protected $attributes = [
        'type' => 'auto',
        'quality_label' => 'Auto',
        'priority' => 1,
        'is_active' => true,
        'health_status' => 'unknown',
    ];

    protected function casts(): array
    {
        return [
            'url' => 'encrypted',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'latency_ms' => 'integer',
            'response_code' => 'integer',
            'failure_count' => 'integer',
            'success_count' => 'integer',
            'last_checked_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(IptvItem::class, 'iptv_item_id');
    }
}

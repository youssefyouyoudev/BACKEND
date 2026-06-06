<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentalLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pin_hash',
        'locked_category_keywords',
    ];

    protected function casts(): array
    {
        return [
            'locked_category_keywords' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

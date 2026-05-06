<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Playlist extends Model
{
    use HasFactory;

    public const SOURCE_TYPE_FILE = 'file';

    public const SOURCE_TYPE_URL = 'url';

    protected $fillable = [
        'user_id',
        'name',
        'source_type',
        'source_url',
        'file_path',
        'original_filename',
        'stored_path',
        'status',
        'last_synced_at',
        'is_public',
        'approved_by_admin',
        'approved_at',
        'import_summary',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'approved_at' => 'datetime',
            'is_public' => 'boolean',
            'import_summary' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_admin');
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function getCategoryCountAttribute(): int
    {
        return $this->channels()
            ->whereNotNull('group_title')
            ->distinct('group_title')
            ->count('group_title');
    }

    public function getChannelCountAttribute(): int
    {
        return $this->channels()->count();
    }

    public function getResolvedFilePathAttribute(): ?string
    {
        return $this->file_path ?: $this->stored_path;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('user_id', $user->id)
                ->orWhere(function (Builder $publicQuery): void {
                    $publicQuery->where('is_public', true)
                        ->whereNotNull('approved_at');
                });
        });
    }
}

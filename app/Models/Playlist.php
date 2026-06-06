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

    public const INPUT_TYPE_M3U_URL = 'm3u_url';

    public const INPUT_TYPE_XTREAM = 'xtream';

    public const INPUT_TYPE_UPLOAD = 'upload';

    public const INPUT_TYPE_REMOTE_URL = self::INPUT_TYPE_M3U_URL;

    public const INPUT_TYPE_UPLOAD_FILE = self::INPUT_TYPE_UPLOAD;

    public const INPUT_TYPE_ACTIVE_CODE = 'active_code';

    public const SOURCE_TYPE_FILE = 'file';

    public const SOURCE_TYPE_URL = 'url';

    protected $fillable = [
        'user_id',
        'name',
        'input_type',
        'source_type',
        'm3u_url',
        'source_url',
        'server_url',
        'username',
        'password',
        'output',
        'file_path',
        'active_code',
        'original_filename',
        'stored_path',
        'status',
        'last_synced_at',
        'is_public',
        'approved_by_admin',
        'approved_at',
        'import_summary',
        'imported_channels_count',
        'imported_movies_count',
        'imported_series_count',
        'last_imported_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'last_imported_at' => 'datetime',
            'approved_at' => 'datetime',
            'is_public' => 'boolean',
            'import_summary' => 'array',
            'password' => 'encrypted',
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

    public function iptvCategories(): HasMany
    {
        return $this->hasMany(IptvCategory::class);
    }

    public function iptvItems(): HasMany
    {
        return $this->hasMany(IptvItem::class);
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

    public function getMaskedM3uUrlAttribute(): ?string
    {
        return self::maskSensitiveUrl($this->m3u_url ?: $this->source_url);
    }

    public function setM3uUrlAttribute(?string $value): void
    {
        $this->attributes['m3u_url'] = $value;
        $this->attributes['source_url'] = $value;
    }

    public function getInputTypeAttribute(?string $value): string
    {
        if ($value) {
            return $value;
        }

        return $this->source_type === self::SOURCE_TYPE_FILE
            ? self::INPUT_TYPE_UPLOAD
            : self::INPUT_TYPE_M3U_URL;
    }

    public static function maskSensitiveUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $query);

        foreach (['user', 'pass', 'username', 'password', 'token'] as $sensitiveKey) {
            foreach (array_keys($query) as $key) {
                if (strtolower((string) $key) === $sensitiveKey) {
                    $query[$key] = '****';
                }
            }
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $user = $parts['user'] ?? null;
        $pass = isset($parts['pass']) ? ':'.$parts['pass'] : '';
        $auth = $user ? $user.$pass.'@' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';
        $maskedQuery = collect($query)
            ->map(function (mixed $value, string|int $key): string {
                $encodedKey = rawurlencode((string) $key);

                if ($value === '****') {
                    return $encodedKey.'=****';
                }

                return $encodedKey.'='.rawurlencode((string) $value);
            })
            ->implode('&');

        return $scheme.$auth.$host.$port.$path.($maskedQuery !== '' ? '?'.$maskedQuery : '').$fragment;
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

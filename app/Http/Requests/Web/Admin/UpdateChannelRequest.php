<?php

namespace App\Http\Requests\Web\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'playlist_id' => ['required', 'exists:playlists,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:140'],
            'slug' => ['nullable', 'string', 'max:160', Rule::unique('channels', 'slug')->ignore($this->route('channel'))],
            'logo' => ['nullable', 'url', 'max:2048'],
            'country' => ['nullable', 'string', 'max:120'],
            'aliases' => ['nullable', 'string', 'max:2000'],
            'stream_url' => ['required', 'url', 'max:4096'],
            'stream_type' => ['required', Rule::in(['hls', 'dash', 'mp4', 'mpegts', 'stream'])],
            'is_active' => ['nullable', 'boolean'],
            'is_live' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'featured_rank' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $this->boolean('is_active');
        $validated['is_live'] = $this->boolean('is_live');
        $validated['is_featured'] = $this->boolean('is_featured');
        $validated['aliases'] = collect(preg_split('/\r\n|\r|\n|,/', (string) ($validated['aliases'] ?? '')))
            ->map(fn (string $alias): string => trim($alias))
            ->filter()
            ->unique()
            ->values()
            ->all() ?: null;

        return $validated;
    }
}

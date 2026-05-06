<?php

namespace App\Http\Requests\Playlist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePlaylistUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'playlist_file' => ['required', File::types(['txt', 'm3u', 'm3u8'])->max(50 * 1024)],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}

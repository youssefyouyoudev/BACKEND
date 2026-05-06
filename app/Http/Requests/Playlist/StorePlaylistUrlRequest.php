<?php

namespace App\Http\Requests\Playlist;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaylistUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'source_url' => ['required', 'string', 'url:http,https', 'max:2048'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}

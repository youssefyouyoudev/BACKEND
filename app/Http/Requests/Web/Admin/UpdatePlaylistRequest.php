<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Playlist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UpdatePlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $playlist = $this->route('playlist');

        return [
            'name' => ['required', 'string', 'max:120'],
            'm3u_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
                Rule::unique('playlists', 'source_url')->ignore($playlist instanceof Playlist ? $playlist->id : null),
            ],
            'playlist_file' => [
                'nullable',
                File::types(['m3u', 'm3u8', 'txt'])->max(5 * 1024),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (filled($this->input('m3u_url')) && $this->hasFile('playlist_file')) {
                $validator->errors()->add('playlist_file', 'Choose either a playlist URL or a file upload, not both.');
            }
        });
    }
}

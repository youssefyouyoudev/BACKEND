<?php

namespace App\Http\Requests\Web\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;

class StorePlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'm3u_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
                Rule::unique('playlists', 'source_url'),
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
            $hasUrl = filled($this->input('m3u_url'));
            $hasFile = $this->hasFile('playlist_file');

            if (! $hasUrl && ! $hasFile) {
                $validator->errors()->add('m3u_url', 'Provide a playlist URL or upload an M3U file.');
            }

            if ($hasUrl && $hasFile) {
                $validator->errors()->add('playlist_file', 'Choose either a playlist URL or a file upload, not both.');
            }
        });
    }
}

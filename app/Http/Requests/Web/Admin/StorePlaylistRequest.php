<?php

namespace App\Http\Requests\Web\Admin;

use App\Rules\AllowedStreamingUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StorePlaylistRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('input_type')) {
            return;
        }

        $this->merge([
            'input_type' => $this->hasFile('playlist_file') ? 'upload' : 'm3u_url',
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'input_type' => ['required', Rule::in(['m3u_url', 'remote_url', 'xtream', 'upload', 'upload_file', 'active_code'])],
            'm3u_url' => [
                Rule::requiredIf(in_array($this->input('input_type'), ['m3u_url', 'remote_url'], true)),
                'nullable',
                'url:http,https',
                'max:2048',
                new AllowedStreamingUrl(playlist: true),
                Rule::unique('playlists', 'source_url'),
            ],
            'server_url' => [
                Rule::requiredIf($this->input('input_type') === 'xtream'),
                'nullable',
                'url:http,https',
                'max:2048',
                new AllowedStreamingUrl(playlist: true),
            ],
            'username' => [
                Rule::requiredIf($this->input('input_type') === 'xtream'),
                'nullable',
                'string',
                'max:255',
            ],
            'password' => [
                Rule::requiredIf($this->input('input_type') === 'xtream'),
                'nullable',
                'string',
                'max:255',
            ],
            'output' => ['nullable', Rule::in(['mpegts', 'hls'])],
            'playlist_file' => [
                Rule::requiredIf(in_array($this->input('input_type'), ['upload', 'upload_file'], true)),
                'nullable',
                File::types(config('streaming.allowed_upload_types', ['m3u', 'm3u8', 'txt']))
                    ->max((int) config('streaming.max_file_size_kb', 10240)),
            ],
            'active_code' => [
                Rule::requiredIf($this->input('input_type') === 'active_code'),
                'nullable',
                'alpha_num',
                'between:4,64',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $inputType = $this->input('input_type');
            $hasUrl = filled($this->input('m3u_url'));
            $hasFile = $this->hasFile('playlist_file');

            if (in_array($inputType, ['m3u_url', 'remote_url', 'xtream'], true) && $hasFile) {
                $validator->errors()->add('playlist_file', 'File uploads are only available in Upload M3U File mode.');
            }

            if (in_array($inputType, ['upload', 'upload_file'], true) && $hasUrl) {
                $validator->errors()->add('m3u_url', 'Remote URLs are only available in Remote M3U URL or Active Code mode.');
            }

            if ($inputType === 'active_code' && $hasFile) {
                $validator->errors()->add('playlist_file', 'File uploads are not available in Active Code mode.');
            }
        });
    }
}

<?php

namespace App\Http\Requests\Web\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIptvItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'url:http,https', 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:iptv_categories,id'],
            'tvg_id' => ['nullable', 'string', 'max:255'],
            'tvg_name' => ['nullable', 'string', 'max:255'],
            'quality_label' => ['required', Rule::in(['Auto', 'SD', 'HD', 'FHD', '4K'])],
            'stream_type' => ['required', Rule::in(['auto', 'hls', 'mpegts', 'mp4'])],
            'language' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:8'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests\Web\Admin;

use App\Rules\AllowedStreamingUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIptvItemSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'url:http,https', 'max:4096', new AllowedStreamingUrl],
            'type' => ['required', Rule::in(['auto', 'hls', 'mpegts', 'mp4'])],
            'quality_label' => ['required', Rule::in(['Auto', 'SD', 'HD', 'FHD', '4K'])],
            'priority' => ['required', 'integer', 'between:1,999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

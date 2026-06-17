<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\AdSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.placement_key' => ['required', Rule::in(array_keys(AdSetting::PLACEMENTS))],
            'settings.*.enabled' => ['nullable', 'boolean'],
            'settings.*.script_code' => ['nullable', 'string', 'max:50000'],
            'settings.*.direct_link_url' => ['nullable', 'url:http,https', 'max:2048'],
            'settings.*.device' => ['required', Rule::in(['all', 'mobile', 'desktop'])],
            'settings.*.frequency_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'settings.*.max_per_session' => ['required', 'integer', 'min:0', 'max:20'],
            'settings.*.test_mode' => ['nullable', 'boolean'],
        ];
    }
}

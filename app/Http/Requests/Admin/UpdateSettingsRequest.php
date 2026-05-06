<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'legal_notice' => ['required', 'string', 'max:4000'],
            'homepage_featured_groups' => ['nullable', 'array'],
            'homepage_featured_groups.*' => ['string', 'max:80'],
            'allow_public_playlists' => ['required', 'boolean'],
            'allow_url_imports' => ['required', 'boolean'],
            'brand_tagline' => ['required', 'string', 'max:160'],
            'maintenance_banner' => ['nullable', 'string', 'max:240'],
        ];
    }
}

<?php

namespace App\Http\Requests\Web\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'channel_id' => ['required', 'exists:channels,id'],
            'title' => ['required', 'string', 'max:160'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'description' => ['nullable', 'string', 'max:1000'],
            'rating' => ['nullable', 'string', 'max:16'],
            'language' => ['nullable', 'string', 'max:10'],
        ];
    }
}

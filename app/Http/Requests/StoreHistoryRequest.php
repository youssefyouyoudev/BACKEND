<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'channel_id' => ['required', 'integer', 'exists:channels,id'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }
}

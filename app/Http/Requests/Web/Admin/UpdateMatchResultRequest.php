<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\WorldCupMatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatchResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'winner_side' => ['required', Rule::in(['home', 'away'])],
            'home_score' => ['nullable', 'integer', 'min:0'],
            'away_score' => ['nullable', 'integer', 'min:0'],
            'home_penalties' => ['nullable', 'integer', 'min:0'],
            'away_penalties' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(WorldCupMatch::RESULT_STATUSES)],
        ];
    }
}

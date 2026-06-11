<?php

namespace App\Http\Requests\Web\Admin;

use Illuminate\Validation\Rule;

class UpdateWorldCupMatchRequest extends StoreWorldCupMatchRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['match_number'] = [
            'nullable',
            'integer',
            'min:1',
            Rule::unique('world_cup_matches', 'match_number')->ignore($this->route('world_cup_match')),
        ];

        return $rules;
    }
}

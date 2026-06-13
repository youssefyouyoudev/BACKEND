<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\WorldCupMatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorldCupMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'match_number' => ['nullable', 'integer', 'min:1', Rule::unique('world_cup_matches', 'match_number')],
            'competition' => ['required', 'string', 'max:160'],
            'stage' => ['required', 'string', 'max:80'],
            'group_name' => ['nullable', 'string', 'max:20'],
            'home_team' => ['required', 'string', 'max:120'],
            'away_team' => ['required', 'string', 'max:120', 'different:home_team'],
            'home_team_code' => ['nullable', 'string', 'max:12'],
            'away_team_code' => ['nullable', 'string', 'max:12'],
            'home_flag' => ['nullable', 'url:http,https', 'max:2048'],
            'away_flag' => ['nullable', 'url:http,https', 'max:2048'],
            'venue' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'kickoff_at' => ['nullable', 'date'],
            'morocco_kickoff_at' => ['nullable', 'date'],
            'local_kickoff_at' => ['nullable', 'date'],
            'local_timezone' => ['nullable', 'timezone'],
            'watch_opens_at' => ['nullable', 'date'],
            'watch_expires_at' => ['nullable', 'date', 'after:watch_opens_at'],
            'selected_channel_id' => ['nullable', 'exists:channels,id'],
            'channel_name_manual' => ['nullable', 'string', 'max:120'],
            'broadcaster' => ['nullable', 'string', 'max:120'],
            'commentator' => ['nullable', 'string', 'max:120'],
            'live_url_manual' => ['nullable', 'url:http,https', 'max:2048'],
            'use_manual_live_url' => ['nullable', 'boolean'],
            'is_live_link_enabled' => ['nullable', 'boolean'],
            'broadcast_status' => ['required', Rule::in(WorldCupMatch::STATUSES)],
            'is_featured' => ['nullable', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'source_name' => ['nullable', 'string', 'max:160'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $validated['use_manual_live_url'] = $this->boolean('use_manual_live_url');
        $validated['is_live_link_enabled'] = $this->boolean('is_live_link_enabled');
        $validated['is_featured'] = $this->boolean('is_featured');

        return $validated;
    }
}

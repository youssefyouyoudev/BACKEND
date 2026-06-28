<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\WorldCupMatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'selected_iptv_item_id' => ['nullable', 'exists:iptv_items,id'],
            'channel_name_manual' => ['nullable', 'string', 'max:120'],
            'broadcaster' => ['nullable', 'string', 'max:120'],
            'commentator' => ['nullable', 'string', 'max:120'],
            'stream_links' => ['nullable', 'array'],
            'stream_links.*.label' => ['nullable', 'string', 'max:120'],
            'stream_links.*.url' => ['nullable', 'url:http,https', 'max:2048'],
            'stream_links.*.type' => ['nullable', Rule::in(['iframe', 'hls', 'mpegts', 'mp4', 'other'])],
            'live_url_manual' => ['nullable', 'required_if:use_manual_live_url,1', 'url:http,https', 'max:2048'],
            'player_type' => ['nullable', Rule::in(['auto', 'iframe', 'videojs', 'external_embed'])],
            'use_manual_live_url' => ['nullable', 'boolean'],
            'is_live_link_enabled' => ['nullable', 'boolean'],
            'broadcast_status' => ['required', Rule::in(WorldCupMatch::STATUSES)],
            'is_featured' => ['nullable', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'source_name' => ['nullable', 'string', 'max:160'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'match_iptv_items' => ['nullable', 'array'],
            'match_iptv_items.*.iptv_item_id' => ['nullable', 'exists:iptv_items,id'],
            'match_iptv_items.*.is_active' => ['nullable', 'boolean'],
            'match_iptv_items.*.priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'match_iptv_items.*.channel_name' => ['nullable', 'string', 'max:160'],
            'match_iptv_items.*.stream_title' => ['nullable', 'string', 'max:160'],
            'match_iptv_items.*.stream_type' => ['nullable', Rule::in(['hls', 'mpegts', 'mp4', 'iframe', 'other'])],
            'match_iptv_items.*.quality' => ['nullable', Rule::in(['SD', 'HD', 'FHD', '4K'])],
            'match_iptv_items.*.language' => ['nullable', 'string', 'max:60'],
            'match_iptv_items.*.commentator' => ['nullable', 'string', 'max:120'],
            'match_iptv_items.*.server_label' => ['nullable', 'string', 'max:80'],
            'match_iptv_items.*.is_recommended' => ['nullable', 'boolean'],
            'match_iptv_items.*.health_status' => ['nullable', Rule::in(['online', 'offline', 'unknown'])],
            'match_iptv_items.*.starts_at' => ['nullable', 'date'],
            'match_iptv_items.*.expires_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $streamLinks = $this->input('stream_links');

        if (! is_string($streamLinks)) {
            return;
        }

        $streamLinks = trim($streamLinks);

        if ($streamLinks === '') {
            $this->merge(['stream_links' => null]);

            return;
        }

        $decoded = json_decode($streamLinks, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                $decoded = collect($decoded)
                    ->map(function (mixed $row): mixed {
                        if (! is_array($row)) {
                            return $row;
                        }

                        if (($row['url'] ?? null) === '') {
                            $row['url'] = null;
                        }

                        return $row;
                    })
                    ->all();
            }

            $this->merge(['stream_links' => $decoded]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('is_live_link_enabled')) {
                return;
            }

            $hasManualUrl = $this->boolean('use_manual_live_url') && filled($this->input('live_url_manual'));
            $hasSelectedIptv = filled($this->input('selected_iptv_item_id'));
            $hasActivePivotItem = collect($this->input('match_iptv_items', []))
                ->contains(fn (mixed $row): bool => is_array($row)
                    && filled($row['iptv_item_id'] ?? null)
                    && filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN));

            if (! $hasManualUrl && ! $hasSelectedIptv && ! $hasActivePivotItem) {
                $validator->errors()->add(
                    'is_live_link_enabled',
                    __('Enable live player requires a manual URL, selected IPTV item, or at least one active match server.')
                );
            }
        });
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $validated['player_type'] = $validated['player_type'] ?? 'iframe';
        $validated['use_manual_live_url'] = $this->boolean('use_manual_live_url');
        $validated['is_live_link_enabled'] = $this->boolean('is_live_link_enabled');
        $validated['is_featured'] = $this->boolean('is_featured');

        return $validated;
    }
}

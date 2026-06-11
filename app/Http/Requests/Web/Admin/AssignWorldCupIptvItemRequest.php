<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\IptvItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignWorldCupIptvItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'iptv_item_id' => [
                'nullable',
                'integer',
                Rule::exists('iptv_items', 'id')->where(function ($query): void {
                    $query
                        ->where('type', IptvItem::TYPE_LIVE)
                        ->where('is_active', true)
                        ->where('is_public', true)
                        ->where('is_adult', false)
                        ->whereNotNull('stream_url')
                        ->where('stream_url', '!=', '');
                }),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $itemId = $this->integer('iptv_item_id');

                if (! $itemId || $validator->errors()->has('iptv_item_id')) {
                    return;
                }

                $isPublicApproved = IptvItem::query()
                    ->publicLive()
                    ->whereKey($itemId)
                    ->whereHas('playlist', fn ($query) => $query
                        ->where('is_public', true)
                        ->whereNotNull('approved_at'))
                    ->exists();

                if (! $isPublicApproved) {
                    $validator->errors()->add('iptv_item_id', __('Select a public active IPTV channel from an approved playlist.'));
                }
            },
        ];
    }
}

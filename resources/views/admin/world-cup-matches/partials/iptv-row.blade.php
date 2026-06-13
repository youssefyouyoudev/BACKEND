@php
    $prefix = "match_iptv_items[{$rowIndex}]";
    $rowId = "match-iptv-{$rowIndex}";
    $selectedRowItemId = (string) ($row['iptv_item_id'] ?? '');
@endphp

<article class="wc-match-iptv-row" data-match-iptv-row>
    <div class="wc-match-iptv-row__head">
        <strong>{{ __("IPTV server") }}</strong>
        <button class="button button--danger" type="button" data-remove-match-iptv-row>{{ __("Remove") }}</button>
    </div>
    <div class="wc-match-iptv-row__grid">
        <div class="field form-grid__wide">
            <label for="{{ $rowId }}-iptv-item">{{ __("IPTV item") }}</label>
            <input type="search" placeholder="{{ __("Search item in this row...") }}" data-local-select-search="{{ $rowId }}-iptv-item">
            <select id="{{ $rowId }}-iptv-item" name="{{ $prefix }}[iptv_item_id]" size="5">
                <option value="">{{ __("Choose IPTV item") }}</option>
                @foreach($iptvItems as $item)
                    @php($itemContext = collect([$item->qualityLabel(), $item->category?->name ?? $item->group_title, $item->playlist?->name])->filter()->implode(' - '))
                    <option
                        value="{{ $item->id }}"
                        data-search="{{ str($item->name.' '.$itemContext)->lower() }}"
                        @selected($selectedRowItemId === (string) $item->id)
                    >{{ $item->name }}{{ $itemContext ? ' - '.$itemContext : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="{{ $rowId }}-priority">{{ __("Priority") }}</label><input id="{{ $rowId }}-priority" type="number" min="0" max="999" name="{{ $prefix }}[priority]" value="{{ $row['priority'] ?? 1 }}"></div>
        <div class="field"><label for="{{ $rowId }}-channel-name">{{ __("Channel name") }}</label><input id="{{ $rowId }}-channel-name" name="{{ $prefix }}[channel_name]" maxlength="160" value="{{ $row['channel_name'] ?? '' }}"></div>
        <div class="field"><label for="{{ $rowId }}-stream-title">{{ __("Stream title") }}</label><input id="{{ $rowId }}-stream-title" name="{{ $prefix }}[stream_title]" maxlength="160" value="{{ $row['stream_title'] ?? '' }}"></div>
        <div class="field"><label for="{{ $rowId }}-stream-type">{{ __("Stream type") }}</label><select id="{{ $rowId }}-stream-type" name="{{ $prefix }}[stream_type]"><option value="">{{ __("Auto") }}</option>@foreach(['hls', 'mpegts', 'mp4', 'iframe', 'other'] as $type)<option value="{{ $type }}" @selected(($row['stream_type'] ?? '') === $type)>{{ strtoupper($type) }}</option>@endforeach</select></div>
        <div class="field"><label for="{{ $rowId }}-quality">{{ __("Quality") }}</label><select id="{{ $rowId }}-quality" name="{{ $prefix }}[quality]"><option value="">{{ __("Auto") }}</option>@foreach(['SD', 'HD', 'FHD', '4K'] as $quality)<option value="{{ $quality }}" @selected(($row['quality'] ?? '') === $quality)>{{ $quality }}</option>@endforeach</select></div>
        <div class="field"><label for="{{ $rowId }}-language">{{ __("Language") }}</label><input id="{{ $rowId }}-language" name="{{ $prefix }}[language]" maxlength="60" value="{{ $row['language'] ?? '' }}"></div>
        <div class="field"><label for="{{ $rowId }}-commentator">{{ __("Commentator") }}</label><input id="{{ $rowId }}-commentator" name="{{ $prefix }}[commentator]" maxlength="120" value="{{ $row['commentator'] ?? '' }}"></div>
        <div class="field"><label for="{{ $rowId }}-server-label">{{ __("Server label") }}</label><input id="{{ $rowId }}-server-label" name="{{ $prefix }}[server_label]" maxlength="80" value="{{ $row['server_label'] ?? '' }}" placeholder="{{ __("Server 1") }}"></div>
        <div class="field"><label for="{{ $rowId }}-health">{{ __("Health status") }}</label><select id="{{ $rowId }}-health" name="{{ $prefix }}[health_status]"><option value="">{{ __("Unknown") }}</option>@foreach(['online', 'offline', 'unknown'] as $health)<option value="{{ $health }}" @selected(($row['health_status'] ?? '') === $health)>{{ str($health)->headline() }}</option>@endforeach</select></div>
        <div class="field"><label for="{{ $rowId }}-starts">{{ __("Starts at") }}</label><input id="{{ $rowId }}-starts" type="datetime-local" name="{{ $prefix }}[starts_at]" value="{{ $row['starts_at'] ?? '' }}"></div>
        <div class="field"><label for="{{ $rowId }}-expires">{{ __("Expires at") }}</label><input id="{{ $rowId }}-expires" type="datetime-local" name="{{ $prefix }}[expires_at]" value="{{ $row['expires_at'] ?? '' }}"></div>
    </div>
    <div class="toggle-row">
        <label class="checkbox-field"><input type="checkbox" name="{{ $prefix }}[is_active]" value="1" @checked($row['is_active'] ?? true)><span>{{ __("Active") }}</span></label>
        <label class="checkbox-field"><input type="checkbox" name="{{ $prefix }}[is_recommended]" value="1" @checked($row['is_recommended'] ?? false)><span>{{ __("Recommended") }}</span></label>
    </div>
</article>

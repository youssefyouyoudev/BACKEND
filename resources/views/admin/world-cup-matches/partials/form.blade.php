@php($selectedChannelId = (string) old('selected_channel_id', $worldCupMatch->selected_channel_id))

<div class="wc-match-form">
    <section class="surface-card">
        <div class="surface-card__header"><div><p class="surface-card__eyebrow">1. Match info</p><h2>Teams and group</h2></div></div>
        <div class="form-grid">
            <div class="field"><label for="match_number">Match number</label><input id="match_number" type="number" min="1" name="match_number" value="{{ old('match_number', $worldCupMatch->match_number) }}"></div>
            <div class="field"><label for="group_name">Group</label><select id="group_name" name="group_name"><option value="">Select group</option>@foreach($groups as $group)<option value="{{ $group }}" @selected(old('group_name', $worldCupMatch->group_name) === $group)>{{ $group }}</option>@endforeach</select></div>
            <div class="field form-grid__wide"><label for="competition">Competition</label><input id="competition" name="competition" required maxlength="160" value="{{ old('competition', $worldCupMatch->competition) }}"></div>
            <div class="field form-grid__wide"><label for="stage">Stage</label><input id="stage" name="stage" required maxlength="80" value="{{ old('stage', $worldCupMatch->stage) }}"></div>
            <div class="field"><label for="home_team">Home team</label><input id="home_team" name="home_team" required maxlength="120" value="{{ old('home_team', $worldCupMatch->home_team) }}"></div>
            <div class="field"><label for="away_team">Away team</label><input id="away_team" name="away_team" required maxlength="120" value="{{ old('away_team', $worldCupMatch->away_team) }}"></div>
            <div class="field"><label for="home_team_code">Home code</label><input id="home_team_code" name="home_team_code" maxlength="12" value="{{ old('home_team_code', $worldCupMatch->home_team_code) }}"></div>
            <div class="field"><label for="away_team_code">Away code</label><input id="away_team_code" name="away_team_code" maxlength="12" value="{{ old('away_team_code', $worldCupMatch->away_team_code) }}"></div>
        </div>
    </section>

    <section class="surface-card">
        <div class="surface-card__header"><div><p class="surface-card__eyebrow">2. Time and venue</p><h2>Kickoff details</h2></div></div>
        <div class="form-grid">
            <div class="field"><label for="kickoff_at">Kickoff UTC</label><input id="kickoff_at" type="datetime-local" name="kickoff_at" value="{{ old('kickoff_at', $worldCupMatch->kickoff_at?->format('Y-m-d\TH:i')) }}"></div>
            <div class="field"><label for="morocco_kickoff_at">Morocco kickoff</label><input id="morocco_kickoff_at" type="datetime-local" name="morocco_kickoff_at" value="{{ old('morocco_kickoff_at', $worldCupMatch->morocco_kickoff_at?->format('Y-m-d\TH:i')) }}"></div>
            <div class="field"><label for="local_kickoff_at">Stadium-local kickoff</label><input id="local_kickoff_at" type="datetime-local" name="local_kickoff_at" value="{{ old('local_kickoff_at', $worldCupMatch->local_kickoff_at?->format('Y-m-d\TH:i')) }}"></div>
            <div class="field"><label for="local_timezone">Local timezone</label><input id="local_timezone" name="local_timezone" value="{{ old('local_timezone', $worldCupMatch->local_timezone) }}" placeholder="America/New_York"></div>
            <div class="field"><label for="venue">Venue</label><input id="venue" name="venue" maxlength="160" value="{{ old('venue', $worldCupMatch->venue) }}"></div>
            <div class="field"><label for="city">City</label><input id="city" name="city" maxlength="120" value="{{ old('city', $worldCupMatch->city) }}"></div>
            <div class="field"><label for="country">Country</label><input id="country" name="country" maxlength="120" value="{{ old('country', $worldCupMatch->country) }}"></div>
            <div class="field"><label for="sort_order">Sort order</label><input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $worldCupMatch->sort_order) }}"></div>
        </div>
    </section>

    <section class="surface-card">
        <div class="surface-card__header"><div><p class="surface-card__eyebrow">3. Broadcast setup</p><h2>Channel and commentator</h2></div></div>
        <div class="field form-grid__wide wc-channel-picker" data-channel-picker>
            <label for="channel_search">Search existing channels</label>
            <input id="channel_search" type="search" placeholder="Type beIN, Arryadia, HD, Arabic..." data-channel-search>
            <select id="selected_channel_id" name="selected_channel_id" size="7" data-channel-select>
                <option value="">No selected channel</option>
                @foreach($channels as $channel)
                    @php($channelContext = collect([$channel->quality_label, $channel->category?->name ?? $channel->group_title, $channel->country, $channel->playlist?->name])->filter()->implode(' - '))
                    <option
                        value="{{ $channel->id }}"
                        data-search="{{ str($channel->name.' '.$channelContext)->lower() }}"
                        data-logo="{{ $channel->logo }}"
                        @selected($selectedChannelId === (string) $channel->id)
                    >{{ $channel->name }}{{ $channelContext ? ' - '.$channelContext : '' }}</option>
                @endforeach
            </select>
            <small class="field__hint">This links the match to an existing channel. It never edits or merges the channel.</small>
            <div class="wc-channel-preview" data-channel-preview>
                @if($worldCupMatch->selectedChannel)
                    <img src="{{ $worldCupMatch->selectedChannel->logo ?: asset('brand/rifi-logo.png') }}" alt="">
                    <span><strong>{{ $worldCupMatch->selectedChannel->name }}</strong><small>{{ route('channels.show', $worldCupMatch->selectedChannel->slug ?: $worldCupMatch->selectedChannel->id) }}</small></span>
                @endif
            </div>
        </div>
        <div class="form-grid">
            <div class="field"><label for="channel_name_manual">Manual channel fallback</label><input id="channel_name_manual" name="channel_name_manual" maxlength="120" value="{{ old('channel_name_manual', $worldCupMatch->channel_name_manual) }}" placeholder="Channel to be confirmed"></div>
            <div class="field"><label for="broadcaster">Broadcaster</label><input id="broadcaster" name="broadcaster" maxlength="120" value="{{ old('broadcaster', $worldCupMatch->broadcaster) }}"></div>
            <div class="field"><label for="commentator">Commentator</label><input id="commentator" name="commentator" maxlength="120" value="{{ old('commentator', $worldCupMatch->commentator) }}" placeholder="Commentator to be confirmed"></div>
            <div class="field"><label for="broadcast_status">Broadcast status</label><select id="broadcast_status" name="broadcast_status" required>@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('broadcast_status', $worldCupMatch->broadcast_status) === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
        </div>
    </section>

    <section class="surface-card">
        <div class="surface-card__header"><div><p class="surface-card__eyebrow">4. Watch link</p><h2>Public playback</h2></div></div>
        <div class="legal-callout"><strong>Rights reminder</strong><p>Only add links you own or are allowed to publish.</p></div>
        <div class="field"><label for="live_url_manual">Manual live URL</label><input id="live_url_manual" type="url" name="live_url_manual" maxlength="2048" value="{{ old('live_url_manual', $worldCupMatch->live_url_manual) }}" placeholder="https://approved.example.com/watch"></div>
        <div class="toggle-row">
            <label class="checkbox-field"><input type="checkbox" name="is_live_link_enabled" value="1" @checked(old('is_live_link_enabled', $worldCupMatch->is_live_link_enabled))><span>Enable Watch Live publicly</span></label>
            <label class="checkbox-field"><input type="checkbox" name="use_manual_live_url" value="1" @checked(old('use_manual_live_url', $worldCupMatch->use_manual_live_url))><span>Use manual URL instead of selected channel</span></label>
            <label class="checkbox-field"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $worldCupMatch->is_featured))><span>Featured match</span></label>
        </div>
    </section>

    <section class="surface-card">
        <div class="surface-card__header"><div><p class="surface-card__eyebrow">5. Admin notes</p><h2>Internal context</h2></div></div>
        <div class="field"><label for="admin_notes">Notes (never public)</label><textarea id="admin_notes" name="admin_notes" rows="5" maxlength="5000">{{ old('admin_notes', $worldCupMatch->admin_notes) }}</textarea></div>
        <div class="form-grid">
            <div class="field"><label for="source_name">Source name</label><input id="source_name" name="source_name" maxlength="160" value="{{ old('source_name', $worldCupMatch->source_name) }}"></div>
            <div class="field"><label for="source_url">Source URL</label><input id="source_url" type="url" name="source_url" maxlength="2048" value="{{ old('source_url', $worldCupMatch->source_url) }}"></div>
        </div>
    </section>
</div>

<div class="wc-sticky-save">
    <button class="button button--primary" type="submit">{{ $submitLabel }}</button>
    <a class="button button--ghost" href="{{ route('admin.world-cup-matches.index') }}">Back to matches</a>
</div>

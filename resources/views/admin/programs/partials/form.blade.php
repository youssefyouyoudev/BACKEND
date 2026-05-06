<div class="form-grid">
    <div class="field">
        <label for="channel_id">Channel</label>
        <select id="channel_id" name="channel_id" required>
            @foreach($channels as $channel)
                <option value="{{ $channel->id }}" @selected((int) old('channel_id', $program->channel_id ?? '') === $channel->id)>{{ $channel->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="title">Program title</label>
        <input id="title" name="title" value="{{ old('title', $program->title ?? '') }}" required maxlength="160" placeholder="Evening headlines">
    </div>
    <div class="field">
        <label for="start_time">Start</label>
        <input id="start_time" type="datetime-local" name="start_time" value="{{ old('start_time', isset($program) ? $program->start_time?->format('Y-m-d\\TH:i') : now()->format('Y-m-d\\TH:i')) }}" required>
    </div>
    <div class="field">
        <label for="end_time">End</label>
        <input id="end_time" type="datetime-local" name="end_time" value="{{ old('end_time', isset($program) ? $program->end_time?->format('Y-m-d\\TH:i') : now()->addHour()->format('Y-m-d\\TH:i')) }}" required>
    </div>
    <div class="field">
        <label for="rating">Rating</label>
        <input id="rating" name="rating" value="{{ old('rating', $program->rating ?? '') }}" maxlength="16" placeholder="TV-G">
    </div>
    <div class="field">
        <label for="language">Language</label>
        <input id="language" name="language" value="{{ old('language', $program->language ?? 'en') }}" maxlength="10">
    </div>
    <div class="field form-grid__wide">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" maxlength="1000">{{ old('description', $program->description ?? '') }}</textarea>
    </div>
</div>

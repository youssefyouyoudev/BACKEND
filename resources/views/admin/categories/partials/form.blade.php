<div class="field">
    <label for="name">{{ __("Name") }}</label>
    <input id="name" name="name" value="{{ old('name', $category->name ?? '') }}" required maxlength="80" placeholder="{{ __("Sports") }}">
</div>
<div class="field">
    <label for="slug">{{ __("Slug") }}</label>
    <input id="slug" name="slug" value="{{ old('slug', $category->slug ?? '') }}" maxlength="100" placeholder="{{ __("sports") }}">
</div>
<div class="field">
    <label for="color">{{ __("Accent color") }}</label>
    <input id="color" name="color" value="{{ old('color', $category->color ?? '#76db3a') }}" required maxlength="24" placeholder="{{ __("#76db3a") }}">
</div>
<div class="field">
    <label for="icon">{{ __("Icon label") }}</label>
    <input id="icon" name="icon" value="{{ old('icon', $category->icon ?? '') }}" maxlength="80" placeholder="{{ __("Trophy") }}">
</div>
<div class="field">
    <label for="sort_order">{{ __("Sort order") }}</label>
    <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
</div>
<label class="checkbox-field">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
    <span>{{ __("Visible in public catalog") }}</span>
</label>

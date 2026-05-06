<div class="field">
    <label for="name">Name</label>
    <input id="name" name="name" value="{{ old('name', $category->name ?? '') }}" required maxlength="80" placeholder="Sports">
</div>
<div class="field">
    <label for="slug">Slug</label>
    <input id="slug" name="slug" value="{{ old('slug', $category->slug ?? '') }}" maxlength="100" placeholder="sports">
</div>
<div class="field">
    <label for="color">Accent color</label>
    <input id="color" name="color" value="{{ old('color', $category->color ?? '#76db3a') }}" required maxlength="24" placeholder="#76db3a">
</div>
<div class="field">
    <label for="icon">Icon label</label>
    <input id="icon" name="icon" value="{{ old('icon', $category->icon ?? '') }}" maxlength="80" placeholder="Trophy">
</div>
<div class="field">
    <label for="sort_order">Sort order</label>
    <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
</div>
<label class="checkbox-field">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
    <span>Visible in public catalog</span>
</label>

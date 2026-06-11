@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __("Catalog taxonomy") }}</p>
        <h1>{{ __("Channel categories.") }}</h1>
        <p class="page-header__copy">{{ __("Organize the TV wall into scan-friendly rails for sports, news, movies, kids, and premium packs.") }}</p>
    </div>
</section>

<section class="admin-grid">
    <article class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">{{ __("Create") }}</p>
                <h2>{{ __("New category") }}</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="form-card form-card--embedded">
            @csrf
            @include('admin.categories.partials.form')
            <button class="button button--primary" type="submit">{{ __("Create category") }}</button>
        </form>
    </article>

    <article class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">{{ __("Design tokens") }}</p>
                <h2>{{ __("Suggested palette") }}</h2>
            </div>
        </div>
        <div class="tv-swatch-grid">
            @foreach(['#76db3a', '#38bdf8', '#f59e0b', '#ef4444', '#a78bfa', '#14b8a6'] as $color)
                <span style="--swatch: {{ $color }}">{{ $color }}</span>
            @endforeach
        </div>
    </article>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">{{ __("Browse") }}</p>
            <h2>{{ __("Stored categories") }}</h2>
        </div>
    </div>

    <div class="table-shell">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __("Name") }}</th>
                    <th>{{ __("Slug") }}</th>
                    <th>{{ __("Channels") }}</th>
                    <th>{{ __("Status") }}</th>
                    <th class="text-end">{{ __("Actions") }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td><strong style="color: {{ $category->color }}">{{ $category->name }}</strong></td>
                        <td>{{ $category->slug }}</td>
                        <td>{{ number_format($category->channels_count) }}</td>
                        <td><span class="status-pill status-pill--{{ $category->is_active ? 'ready' : 'failed' }}">{{ $category->is_active ? __('Active') : __('Hidden') }}</span></td>
                        <td class="text-end">
                            <div class="admin-actions">
                                <a class="button button--ghost" href="{{ route('admin.categories.edit', $category) }}">{{ __("Edit") }}</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button--ghost" type="submit">{{ __("Delete") }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ __("No categories yet.") }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $categories->links() }}
</section>
@endsection

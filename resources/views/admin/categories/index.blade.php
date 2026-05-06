@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">Catalog taxonomy</p>
        <h1>Channel categories.</h1>
        <p class="page-header__copy">Organize the TV wall into scan-friendly rails for sports, news, movies, kids, and premium packs.</p>
    </div>
</section>

<section class="admin-grid">
    <article class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">Create</p>
                <h2>New category</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="form-card form-card--embedded">
            @csrf
            @include('admin.categories.partials.form')
            <button class="button button--primary" type="submit">Create category</button>
        </form>
    </article>

    <article class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">Design tokens</p>
                <h2>Suggested palette</h2>
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
            <p class="surface-card__eyebrow">Browse</p>
            <h2>Stored categories</h2>
        </div>
    </div>

    <div class="table-shell">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Channels</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td><strong style="color: {{ $category->color }}">{{ $category->name }}</strong></td>
                        <td>{{ $category->slug }}</td>
                        <td>{{ number_format($category->channels_count) }}</td>
                        <td><span class="status-pill status-pill--{{ $category->is_active ? 'ready' : 'failed' }}">{{ $category->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td class="text-end">
                            <div class="admin-actions">
                                <a class="button button--ghost" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button--ghost" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $categories->links() }}
</section>
@endsection

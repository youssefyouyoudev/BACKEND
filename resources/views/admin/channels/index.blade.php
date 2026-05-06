@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">TV operations</p>
        <h1>Channels and streams.</h1>
        <p class="page-header__copy">Manage live status, featured placement, logos, categories, and primary HLS sources.</p>
    </div>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">Create</p>
            <h2>Add channel</h2>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.channels.store') }}" class="form-card form-card--embedded">
        @csrf
        @include('admin.channels.partials.form')
        <button class="button button--primary" type="submit">Create channel</button>
    </form>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">Library</p>
            <h2>Channel inventory</h2>
        </div>
    </div>

    <div class="table-shell">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Category</th>
                    <th>Current Program</th>
                    <th>Status</th>
                    <th>Programs</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channels as $channel)
                    <tr>
                        <td>
                            <div class="table-channel">
                                <img src="{{ $channel->logo ?: asset('brand/rifi-logo.png') }}" alt="" loading="lazy">
                                <span>
                                    <strong>{{ $channel->name }}</strong>
                                    <small>{{ $channel->slug }}</small>
                                </span>
                            </div>
                        </td>
                        <td>{{ $channel->category?->name ?? $channel->group_title ?? 'General' }}</td>
                        <td>{{ $channel->currentProgram?->title ?? 'No current program' }}</td>
                        <td><span class="status-pill status-pill--{{ $channel->is_active && $channel->is_live ? 'ready' : 'failed' }}">{{ $channel->is_active && $channel->is_live ? 'On air' : 'Offline' }}</span></td>
                        <td>{{ number_format($channel->programs_count) }}</td>
                        <td class="text-end">
                            <div class="admin-actions">
                                <a class="button button--ghost" href="{{ route('channels.show', $channel) }}">Watch</a>
                                <a class="button button--ghost" href="{{ route('admin.channels.edit', $channel) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.channels.destroy', $channel) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button--ghost" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No channels yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $channels->links() }}
</section>
@endsection

@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __("TV operations") }}</p>
        <h1>{{ __("Channels and streams.") }}</h1>
        <p class="page-header__copy">{{ __("Manage live status, featured placement, logos, categories, and primary HLS sources.") }}</p>
    </div>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">{{ __("Create") }}</p>
            <h2>{{ __("Add channel") }}</h2>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.channels.store') }}" class="form-card form-card--embedded">
        @csrf
        @include('admin.channels.partials.form')
        <button class="button button--primary" type="submit">{{ __("Create channel") }}</button>
    </form>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">{{ __("Library") }}</p>
            <h2>{{ __("Channel inventory") }}</h2>
        </div>
    </div>

    <div class="table-shell">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __("Channel") }}</th>
                    <th>{{ __("Category") }}</th>
                    <th>{{ __("Current Program") }}</th>
                    <th>{{ __("Status") }}</th>
                    <th>{{ __("Programs") }}</th>
                    <th class="text-end">{{ __("Actions") }}</th>
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
                        <td>{{ $channel->category?->name ?? $channel->group_title ?? __('General') }}</td>
                        <td>{{ $channel->currentProgram?->title ?? __('No current program') }}</td>
                        <td><span class="status-pill status-pill--{{ $channel->is_active && $channel->is_live ? 'ready' : 'failed' }}">{{ $channel->is_active && $channel->is_live ? __('On air') : __('Offline') }}</span></td>
                        <td>{{ number_format($channel->programs_count) }}</td>
                        <td class="text-end">
                            <div class="admin-actions">
                                <a class="button button--ghost" href="{{ route('channels.show', $channel) }}">{{ __("Watch") }}</a>
                                <a class="button button--ghost" href="{{ route('admin.channels.edit', $channel) }}">{{ __("Edit") }}</a>
                                <form method="POST" action="{{ route('admin.channels.destroy', $channel) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button--ghost" type="submit">{{ __("Delete") }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ __("No channels yet.") }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $channels->links() }}
</section>
@endsection

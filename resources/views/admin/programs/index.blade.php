@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __("EPG control") }}</p>
        <h1>{{ __("Program guide.") }}</h1>
        <p class="page-header__copy">{{ __("Create the timeline data shown below the live player.") }}</p>
    </div>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">{{ __("Create") }}</p>
            <h2>{{ __("New program") }}</h2>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.programs.store') }}" class="form-card form-card--embedded">
        @csrf
        @include('admin.programs.partials.form')
        <button class="button button--primary" type="submit">{{ __("Create program") }}</button>
    </form>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">{{ __("Schedule") }}</p>
            <h2>{{ __("Published programs") }}</h2>
        </div>
    </div>
    <div class="table-shell">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __("Program") }}</th>
                    <th>{{ __("Channel") }}</th>
                    <th>{{ __("Window") }}</th>
                    <th>{{ __("Status") }}</th>
                    <th class="text-end">{{ __("Actions") }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $program)
                    <tr>
                        <td><strong>{{ $program->title }}</strong><span class="table-subtle">{{ $program->description }}</span></td>
                        <td>{{ $program->channel?->name }}</td>
                        <td>{{ $program->start_time->format('M d H:i') }} - {{ $program->end_time->format('H:i') }}</td>
                        <td><span class="status-pill status-pill--{{ $program->start_time <= now() && $program->end_time > now() ? 'ready' : 'pending' }}">{{ $program->start_time <= now() && $program->end_time > now() ? __("Now") : __("Scheduled") }}</span></td>
                        <td class="text-end">
                            <div class="admin-actions">
                                <a class="button button--ghost" href="{{ route('admin.programs.edit', $program) }}">{{ __("Edit") }}</a>
                                <form method="POST" action="{{ route('admin.programs.destroy', $program) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button--ghost" type="submit">{{ __("Delete") }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ __("No programs yet.") }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $programs->links() }}
</section>
@endsection

@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">EPG control</p>
        <h1>Program guide.</h1>
        <p class="page-header__copy">Create the timeline data shown below the live player.</p>
    </div>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">Create</p>
            <h2>New program</h2>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.programs.store') }}" class="form-card form-card--embedded">
        @csrf
        @include('admin.programs.partials.form')
        <button class="button button--primary" type="submit">Create program</button>
    </form>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">Schedule</p>
            <h2>Published programs</h2>
        </div>
    </div>
    <div class="table-shell">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Channel</th>
                    <th>Window</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $program)
                    <tr>
                        <td><strong>{{ $program->title }}</strong><span class="table-subtle">{{ $program->description }}</span></td>
                        <td>{{ $program->channel?->name }}</td>
                        <td>{{ $program->start_time->format('M d H:i') }} - {{ $program->end_time->format('H:i') }}</td>
                        <td><span class="status-pill status-pill--{{ $program->start_time <= now() && $program->end_time > now() ? 'ready' : 'pending' }}">{{ $program->start_time <= now() && $program->end_time > now() ? 'Now' : 'Scheduled' }}</span></td>
                        <td class="text-end">
                            <div class="admin-actions">
                                <a class="button button--ghost" href="{{ route('admin.programs.edit', $program) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.programs.destroy', $program) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button--ghost" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No programs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $programs->links() }}
</section>
@endsection

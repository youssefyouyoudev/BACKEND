@extends('layouts.admin')

@section('content')
    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">Edit playlist</p>
                <h1>{{ $playlist->name }}</h1>
            </div>
            <a href="{{ route('admin.playlists.index') }}" class="button button--ghost">Back</a>
        </div>

        @include('admin.playlists.partials.form', [
            'action' => route('admin.playlists.update', $playlist),
            'method' => 'PUT',
            'button' => 'Save & Reimport',
        ])
    </section>
@endsection

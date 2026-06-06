@extends('layouts.admin')

@section('content')
    <section class="surface-card">
        <div class="surface-card__header">
            <div>
                <p class="surface-card__eyebrow">Add playlist</p>
                <h1>New IPTV source</h1>
            </div>
            <a href="{{ route('admin.playlists.index') }}" class="button button--ghost">Back</a>
        </div>

        @include('admin.playlists.partials.form', [
            'action' => route('admin.playlists.store'),
            'button' => 'Save & Import',
        ])
    </section>
@endsection

@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __("Edit channel") }}</p>
        <h1>{{ $channel->name }}</h1>
    </div>
</section>

<section class="surface-card">
    <form method="POST" action="{{ route('admin.channels.update', $channel) }}" class="form-card form-card--embedded">
        @csrf
        @method('PUT')
        @include('admin.channels.partials.form')
        <button class="button button--primary" type="submit">{{ __("Save channel") }}</button>
    </form>
</section>
@endsection

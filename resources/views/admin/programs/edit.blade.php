@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">Edit program</p>
        <h1>{{ $program->title }}</h1>
    </div>
</section>

<section class="surface-card">
    <form method="POST" action="{{ route('admin.programs.update', $program) }}" class="form-card form-card--embedded">
        @csrf
        @method('PUT')
        @include('admin.programs.partials.form')
        <button class="button button--primary" type="submit">Save program</button>
    </form>
</section>
@endsection

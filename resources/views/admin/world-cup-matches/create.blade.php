@extends('layouts.admin')

@section('content')
<section class="page-header"><div><p class="page-header__eyebrow">Tournament operations</p><h1>Add World Cup match.</h1></div></section>
<form method="POST" action="{{ route('admin.world-cup-matches.store') }}">
    @csrf
    @include('admin.world-cup-matches.partials.form', ['submitLabel' => 'Create match'])
</form>
@endsection

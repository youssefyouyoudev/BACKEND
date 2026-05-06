@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">Edit category</p>
        <h1>{{ $category->name }}</h1>
    </div>
</section>

<section class="surface-card">
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="form-card form-card--embedded">
        @csrf
        @method('PUT')
        @include('admin.categories.partials.form')
        <button class="button button--primary" type="submit">Save category</button>
    </form>
</section>
@endsection

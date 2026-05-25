@extends('layouts.app')

@section('title', $page['title'].' | RifiMedia')
@section('description', $page['title'].' updates and entertainment discovery on RifiMedia.')

@section('content')
<div class="rm-page rm-media-platform-page">
    <x-coming-soon :title="$page['title']" :description="$page['description']" :features="$page['features']" />
</div>
@endsection

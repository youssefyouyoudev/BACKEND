@extends('layouts.app')

@section('title', $page['title'].' Coming Soon | RifiMedia')
@section('description', $page['title'].' is coming soon on RifiMedia. We are preparing a better entertainment experience.')

@section('content')
<div class="rm-page rm-media-platform-page">
    <x-coming-soon :title="$page['title']" :description="$page['description']" :features="$page['features']" />
</div>
@endsection

@extends('layouts.app')

@section('title', __('Page not found | RiFiTV'))
@section('robots', 'noindex,nofollow')

@section('content')
    <section class="rm-page">
        <x-empty-state
            :title="__('Page not found')"
            :message="__('The page you requested does not exist or may have moved.')"
            :action="__('Back to home')"
            :href="route('home')"
        />
    </section>
@endsection

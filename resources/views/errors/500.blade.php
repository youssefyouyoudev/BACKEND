@extends('layouts.app')

@section('title', __('Server error | RiFiTV'))
@section('robots', 'noindex,nofollow')
@section('ads', 'disabled')

@section('content')
    <section class="rm-page">
        <x-empty-state
            :title="__('Something went wrong')"
            :message="__('A server error occurred. Please try again later.')"
            :action="__('Back to home')"
            :href="route('home')"
        />
    </section>
@endsection

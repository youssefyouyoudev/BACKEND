@extends('layouts.app')

@section('title', __("World Cup 2026 Live TV Receiver | RiFi Media TV"))
@section('description', __("Watch public live TV channels in the RiFi Media TV World Cup 2026 inspired receiver with search, quality selection, and automatic reconnect."))
@section('image', asset('assets/images/promo/rifitv-world-football-2026-1122.webp'))

@section('content')
<header class="wc-live-page-header">
    <div>
        <span class="wc-badge wc-live-badge"><i></i> {{ __("Live TV · مباشر") }}</span>
        <h1 class="wc-title">{{ __("Your Matchday Receiver") }}</h1>
        <p>{{ __("Choose a channel, select the best quality, and enjoy a smooth broadcast experience.") }}</p>
    </div>
    <div dir="rtl">
        <strong>جهاز استقبال يوم المباراة</strong>
        <span>اختر القناة والجودة واستمتع بالبث</span>
    </div>
</header>

<x-ad-slot name="channels_after_hero" type="banner" compact />

<div
    class="rm-receiver wc-live-receiver"
    x-data="liveTvPage({
        initialChannels: @js($initialChannels),
        initialChannelId: @js(request()->integer('channel') ?: null),
        initialCategory: @js(request()->string('category')->toString()),
        fallbackLogo: @js(asset('brand/rifi-logo.png')),
    })"
    x-init="init"
>
    <section class="rm-receiver-player" x-ref="playerSection" aria-label="{{ __("Live TV player") }}">
        <div class="rm-receiver-player__screen" x-ref="playerStage">
            <video x-ref="video" x-show="!showPlayerFallback" controls playsinline autoplay muted></video>
            <x-video-premium-ticker />

            <div class="rm-receiver-player__shade" x-show="!showPlayerFallback"></div>
            <div class="rm-receiver-player__identity" x-show="activeGroup && !showPlayerFallback" x-cloak>
                <img :src="activeGroup?.logo || fallbackLogo" alt="" x-on:error="$event.target.src = fallbackLogo">
                <div>
                    <span><i></i> {{ __("Live now") }}</span>
                    <h2 x-text="activeGroup?.name"></h2>
                    <p>
                        <b x-text="window.rifiT(activeGroup?.category || '')"></b>
                        <template x-if="activeGroup?.country"><em x-text="window.rifiT(activeGroup.country)"></em></template>
                        <strong x-text="activeVariant?.quality"></strong>
                    </p>
                </div>
            </div>

            <div class="rm-receiver-loading" x-show="loadingPlayer" x-cloak>
                <span></span><span></span><span></span>
                <p>{{ __("Starting channel...") }}</p>
            </div>

            <div class="rm-receiver-reconnect" x-show="reconnecting && !showPlayerFallback" x-cloak role="status" aria-live="polite">
                <i></i>
                <strong x-text="reconnectMessage || window.rifiT('Reconnecting...')"></strong>
                <span>إعادة الاتصال بالبث المباشر...</span>
            </div>

            <div class="rm-receiver-error" x-show="playerError" x-cloak>
                <x-icon name="signal" />
                <strong x-text="playerErrorMessage"></strong>
                <div>
                    <button type="button" @click="refreshStream">{{ __("Try Again / حاول مرة أخرى") }}</button>
                    <button type="button" x-show="externalPlayerUrl" @click="openExternalPlayer">{{ __("External player") }}</button>
                </div>
            </div>

            <div x-show="showPlayerFallback" x-cloak>
                @include('partials.no-channel-fallback')
            </div>

            <div class="rm-receiver-quality" x-show="activeGroup?.qualityOptions?.length && !showPlayerFallback" x-cloak>
                <template x-for="variant in activeGroup?.qualityOptions || []" :key="variant.quality">
                    <button
                        type="button"
                        :class="{ 'is-active': activeVariant?.id === variant.id }"
                        @click="attemptedVariantIds = []; playVariant(variant)"
                        x-text="variant.quality"
                    ></button>
                </template>
            </div>
        </div>

        <div class="rm-receiver-controls">
            <button type="button" @click="stepChannel(-1)" title="{{ __("Previous channel") }}"><span>↑</span> {{ __("Previous") }}</button>
            <button type="button" @click="stepChannel(1)" title="{{ __("Next channel") }}"><span>↓</span> {{ __("Next") }}</button>
            <button type="button" :class="{ 'is-active': isFavorite }" @click="toggleFavorite"><x-icon name="star" /><span x-text="window.rifiT(isFavorite ? 'Saved' : 'Favorite')"></span></button>
            <button type="button" @click="refreshStream"><x-icon name="signal" /> {{ __("Refresh") }}</button>
            <button type="button" @click="openExternalPlayer" :disabled="!externalPlayerUrl"><x-icon name="arrow-up-right" /> {{ __("External") }}</button>
            <button type="button" @click="fullscreen"><x-icon name="tv" /> {{ __("Fullscreen") }}</button>
        </div>
    </section>

    <section class="rm-receiver-browser" id="channels" aria-label="{{ __("Channel browser") }}">
        <div class="rm-receiver-toolbar">
            <label class="rm-receiver-search">
                <x-icon name="search" />
                <input x-ref="search" type="search" x-model="search" placeholder="{{ __("Search channels / ابحث عن قناة") }}" aria-label="{{ __("Search live TV channels") }}">
                <kbd>/</kbd>
            </label>
            <div class="rm-receiver-view-toggle" aria-label="{{ __("Channel view") }}">
                <button type="button" :class="{ 'is-active': viewMode === 'grid' }" @click="viewMode = 'grid'">{{ __("Grid") }}</button>
                <button type="button" :class="{ 'is-active': viewMode === 'table' }" @click="viewMode = 'table'">{{ __("Table") }}</button>
            </div>
            <span class="rm-receiver-count">
                <i :class="{ 'is-loading': loadingCatalog }"></i>
                <b x-text="filteredGroups.length.toLocaleString()"></b> {{ __("channels") }}
            </span>
        </div>

        <div class="rm-receiver-categories" role="tablist" aria-label="{{ __("Channel categories") }}">
            <template x-for="category in categories" :key="category">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeCategory === category"
                    :class="{ 'is-active': activeCategory === category }"
                    @click="setCategory(category)"
                    x-text="categoryLabel(category)"
                ></button>
            </template>
        </div>

        <div class="rm-receiver-empty" x-show="!loadingCatalog && filteredGroups.length === 0" x-cloak>
            @include('partials.no-channel-fallback', ['compact' => true])
        </div>

        <div class="rm-receiver-grid" x-show="viewMode === 'grid' && filteredGroups.length" x-cloak>
            <template x-for="(group, index) in filteredGroups" :key="group.id">
                <article
                    class="rm-receiver-card"
                    :class="{ 'is-playing': activeGroup?.id === group.id }"
                    tabindex="0"
                    @focus="focusedIndex = index"
                    @keydown.enter.prevent="watchGroup(group)"
                >
                    <button type="button" class="rm-receiver-card__watch" @click="watchGroup(group)" :aria-label="`${window.rifiT('Watch now')}: ${group.name || window.rifiT('Unknown group')}`">
                        <img :src="group.logo || fallbackLogo" :alt="group.name || @js(__('live.group_name'))" loading="lazy" x-on:error="$event.target.src = fallbackLogo">
                        <span><x-icon name="play" /></span>
                    </button>
                    <div class="rm-receiver-card__copy">
                        <small x-text="window.rifiT(group.category)"></small>
                        <h2 x-text="group.name"></h2>
                        <p x-show="group.country || group.language">
                            <span x-text="window.rifiT(group.country || group.language)"></span>
                        </p>
                    </div>
                    <div class="rm-receiver-card__qualities">
                        <template x-for="variant in group.qualityOptions" :key="variant.quality">
                            <button type="button" @click="watchGroup(group, variant)" x-text="variant.quality"></button>
                        </template>
                    </div>
                </article>
            </template>
        </div>

        <div class="rm-receiver-table-wrap" x-show="viewMode === 'table' && filteredGroups.length" x-cloak>
            <table class="rm-receiver-table">
                <thead><tr><th>{{ __("Channel") }}</th><th>{{ __("Category") }}</th><th>{{ __("Quality options") }}</th><th>{{ __("Actions") }}</th></tr></thead>
                <tbody>
                    <template x-for="(group, index) in filteredGroups" :key="group.id">
                        <tr :class="{ 'is-playing': activeGroup?.id === group.id }">
                            <td><img :src="group.logo || fallbackLogo" alt="" x-on:error="$event.target.src = fallbackLogo"><strong x-text="group.name"></strong></td>
                            <td x-text="window.rifiT(group.category)"></td>
                            <td><span class="rm-receiver-table__qualities"><template x-for="variant in group.qualityOptions" :key="variant.quality"><button type="button" @click="watchGroup(group, variant)" x-text="variant.quality"></button></template></span></td>
                            <td><button type="button" class="rm-receiver-watch-btn" @focus="focusedIndex = index" @click="watchGroup(group)">{{ __("Watch") }}</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <div class="rm-receiver-toast" x-show="toastMessage" x-transition.opacity x-cloak role="status" x-text="toastMessage"></div>
</div>
@endsection

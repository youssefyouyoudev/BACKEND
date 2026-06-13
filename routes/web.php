<?php

use App\Http\Controllers\ComingSoonController;
use App\Http\Controllers\FootballController;
use App\Http\Controllers\SportsController;
use App\Http\Controllers\StreamBridgeController;
use App\Http\Controllers\StreamProxyController;
use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Web\Admin\ChannelManagementController as AdminChannelController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\IptvItemController as AdminIptvItemController;
use App\Http\Controllers\Web\Admin\PlaylistController as AdminPlaylistController;
use App\Http\Controllers\Web\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Web\Admin\WorldCupMatchController as AdminWorldCupMatchController;
use App\Http\Controllers\Web\ChannelController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LiveTvController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\MatchWatchController;
use App\Http\Controllers\Web\SportsPageController;
use App\Http\Controllers\Web\WatchController;
use App\Http\Controllers\Web\WorldCupController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/lang/{locale}', LocaleController::class)
    ->whereIn('locale', ['en', 'ar'])
    ->name('language.switch');
Route::get('/sports', SportsController::class)->name('sports.index');
Route::get('/world-cup', [WorldCupController::class, 'index'])->name('world-cup.index');
Route::get('/world-cup/group-stage', [WorldCupController::class, 'index'])->name('world-cup.group-stage');
Route::get('/match/{worldCupMatch}/watch', [MatchWatchController::class, 'show'])
    ->name('matches.watch');
Route::get('/match/{worldCupMatch}/embed', [MatchWatchController::class, 'embed'])
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('matches.embed');
Route::get('/match/{worldCupMatch}/watch-link/manual', [MatchWatchController::class, 'manualWatchLink'])
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('matches.watch-link.manual');
Route::get('/match/{worldCupMatch}/watch-channel/{channel}', [MatchWatchController::class, 'watchChannel'])
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('matches.watch-channel');
Route::get('/match/{worldCupMatch}/watch-link/{item}', [MatchWatchController::class, 'watchLink'])
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('matches.watch-link');
Route::get('/watch-link/{worldCupMatch}/{item}/play', [MatchWatchController::class, 'watchLink'])
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('watch-links.play');
Route::get('/sports/football', [FootballController::class, 'index'])->name('sports.football');
Route::get('/sports/football/event/{eventId}', [FootballController::class, 'event'])
    ->whereNumber('eventId')
    ->name('sports.football.event');
Route::get('/football', [FootballController::class, 'index'])->name('football.index');
Route::get('/football/event/{eventId}', [FootballController::class, 'event'])
    ->whereNumber('eventId')
    ->name('football.event');
Route::prefix('football/api')->middleware('throttle:api')->group(function (): void {
    Route::get('/today', [FootballController::class, 'today'])->name('football.api.today');
    Route::get('/date', [FootballController::class, 'byDate'])->name('football.api.date');
    Route::get('/upcoming', [FootballController::class, 'upcoming'])->name('football.api.upcoming');
    Route::get('/results', [FootballController::class, 'results'])->name('football.api.results');
    Route::get('/event/{eventId}', [FootballController::class, 'event'])->whereNumber('eventId')->name('football.api.event');
    Route::get('/event/{eventId}/tv', [FootballController::class, 'eventTv'])->whereNumber('eventId')->name('football.api.event-tv');
    Route::get('/channel-match-debug', [FootballController::class, 'matchChannelDebug'])->name('football.api.channel-match-debug');
});
Route::prefix('api/football')->middleware('throttle:api')->group(function (): void {
    Route::get('/today', [FootballController::class, 'today'])->name('api.football.today');
    Route::get('/date', [FootballController::class, 'byDate'])->name('api.football.date');
    Route::get('/upcoming', [FootballController::class, 'upcoming'])->name('api.football.upcoming');
    Route::get('/results', [FootballController::class, 'results'])->name('api.football.results');
    Route::get('/event/{eventId}', [FootballController::class, 'event'])->whereNumber('eventId')->name('api.football.event');
    Route::get('/event/{eventId}/tv', [FootballController::class, 'eventTv'])->whereNumber('eventId')->name('api.football.event-tv');
});
Route::get('/movies', ComingSoonController::class)->defaults('section', 'movies')->name('movies');
Route::get('/tv-shows', ComingSoonController::class)->defaults('section', 'tv-shows')->name('tv-shows');
Route::get('/anime', ComingSoonController::class)->defaults('section', 'anime')->name('anime');
Route::get('/news', [SportsPageController::class, 'news'])->name('news.index');
Route::get('/news/{slug}', [SportsPageController::class, 'article'])->name('news.show');
Route::permanentRedirect('/scores', '/sports/football')->name('scores');
Route::permanentRedirect('/live-scores', '/sports/football')->name('live-scores');
Route::permanentRedirect('/fixtures', '/sports/football')->name('fixtures');
Route::permanentRedirect('/matches', '/sports/football')->name('matches.index');
Route::get('/leagues', [SportsPageController::class, 'leagues'])->name('leagues.index');
Route::get('/leagues/{slug}', [SportsPageController::class, 'league'])->name('leagues.show');
Route::get('/teams', [SportsPageController::class, 'teams'])->name('teams.index');
Route::get('/teams/{slug}', [SportsPageController::class, 'team'])->name('teams.show');
Route::get('/matches/{slug}', [SportsPageController::class, 'match'])->name('matches.show');
Route::get('/standings', [SportsPageController::class, 'standings'])->name('standings');
Route::get('/highlights', [SportsPageController::class, 'highlights'])->name('highlights');
Route::get('/search', [SportsPageController::class, 'search'])->middleware('throttle:search')->name('search');
Route::get('/about', [SportsPageController::class, 'staticPage'])->defaults('page', 'about')->name('about');
Route::get('/contact', [SportsPageController::class, 'staticPage'])->defaults('page', 'contact')->name('contact');
Route::get('/privacy-policy', [SportsPageController::class, 'staticPage'])->defaults('page', 'privacy-policy')->name('privacy');
Route::get('/terms', [SportsPageController::class, 'staticPage'])->defaults('page', 'terms')->name('terms');
Route::get('/copyright', [SportsPageController::class, 'staticPage'])->defaults('page', 'copyright')->name('copyright');
Route::get('/advertise', [SportsPageController::class, 'staticPage'])->defaults('page', 'advertise')->name('advertise');
Route::get('/editorial-policy', [SportsPageController::class, 'staticPage'])->defaults('page', 'editorial-policy')->name('editorial-policy');
Route::get('/sitemap.xml', [SportsPageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SportsPageController::class, 'robots'])->name('robots');
Route::permanentRedirect('/live', '/live-tv')->name('live');
Route::get('/live-tv', LiveTvController::class)->name('live-tv');
Route::get('/watch', [WatchController::class, 'index'])->name('watch.index');
Route::get('/watch/live', [WatchController::class, 'live'])->name('watch.live');
Route::get('/watch/movies', [WatchController::class, 'movies'])->name('watch.movies');
Route::get('/watch/series', [WatchController::class, 'series'])->name('watch.series');
Route::get('/watch/category/{category}', [WatchController::class, 'category'])->name('watch.category');
Route::get('/watch/item/{item}', [WatchController::class, 'item'])->name('watch.item');
Route::post('/watch/item/{item}/favorite', [WatchController::class, 'favorite'])->middleware('auth')->name('watch.favorite');
Route::post('/watch/item/{item}/history', [WatchController::class, 'history'])->name('watch.history');
Route::get('/watch/search', [WatchController::class, 'search'])->name('watch.search');
Route::get('/watch/{channel}', [ChannelController::class, 'show'])->name('channels.show');
Route::get('/stream/{encodedUrl}', StreamProxyController::class)
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('stream.proxy');
Route::get('/go/{channel}', [StreamProxyController::class, 'playChannel'])
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('stream.channel');
Route::get('/bridge/{encodedUrl}', StreamBridgeController::class)
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('stream.bridge');
Route::get('/bridge-channel/{channel}', [StreamBridgeController::class, 'playChannel'])
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('stream.bridge.channel');
Route::get('/play/iptv/{item}', [StreamBridgeController::class, 'playIptvItem'])
    ->middleware(['signed:relative', 'throttle:streams'])
    ->name('stream.bridge.iptv-item');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])
        ->middleware('throttle:auth')
        ->name('admin.login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('admin.dashboard');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');
    Route::get('/iptv-items', [AdminIptvItemController::class, 'index'])
        ->name('admin.iptv-items.index');
    Route::patch('/iptv-items/visibility', [AdminIptvItemController::class, 'updateAllVisibility'])
        ->name('admin.iptv-items.visibility.all');
    Route::patch('/iptv-items/{item}/visibility', [AdminIptvItemController::class, 'updateVisibility'])
        ->name('admin.iptv-items.visibility');
    Route::resource('categories', AdminCategoryController::class)
        ->except(['create', 'show'])
        ->names('admin.categories');
    Route::resource('channels', AdminChannelController::class)
        ->except(['create', 'show'])
        ->names('admin.channels');
    Route::resource('programs', AdminProgramController::class)
        ->except(['create', 'show'])
        ->names('admin.programs');
    Route::patch('/world-cup-matches/{world_cup_match}/quick-update', [AdminWorldCupMatchController::class, 'quickUpdate'])
        ->name('admin.world-cup-matches.quick-update');
    Route::get('/world-cup-matches/iptv-items/search', [AdminWorldCupMatchController::class, 'iptvItems'])
        ->name('admin.world-cup-matches.iptv-items');
    Route::patch('/world-cup-matches/{world_cup_match}/iptv-item', [AdminWorldCupMatchController::class, 'assignIptvItem'])
        ->name('admin.world-cup-matches.assign-iptv-item');
    Route::patch('/world-cup-matches/{world_cup_match}/iptv-items/{item}', [AdminWorldCupMatchController::class, 'updateIptvItem'])
        ->name('admin.world-cup-matches.update-iptv-item');
    Route::resource('world-cup-matches', AdminWorldCupMatchController::class)
        ->except('show')
        ->parameters(['world-cup-matches' => 'world_cup_match'])
        ->names('admin.world-cup-matches');
    Route::get('/playlists', [AdminPlaylistController::class, 'index'])
        ->name('admin.playlists.index');
    Route::get('/playlists/create', [AdminPlaylistController::class, 'create'])
        ->name('admin.playlists.create');
    Route::post('/playlists', [AdminPlaylistController::class, 'store'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.store');
    Route::get('/playlists/{playlist}/edit', [AdminPlaylistController::class, 'edit'])
        ->name('admin.playlists.edit');
    Route::post('/playlists/{playlist}/parse', [AdminPlaylistController::class, 'parse'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.parse');
    Route::post('/playlists/{playlist}/reimport', [AdminPlaylistController::class, 'reimport'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.reimport');
    Route::put('/playlists/{playlist}', [AdminPlaylistController::class, 'update'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.update');
    Route::delete('/playlists/{playlist}', [AdminPlaylistController::class, 'destroy'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.destroy');
});

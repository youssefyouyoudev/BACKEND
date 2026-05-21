<?php

use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Web\Admin\ChannelManagementController as AdminChannelController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\PlaylistController as AdminPlaylistController;
use App\Http\Controllers\Web\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Web\ChannelController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LiveTvController;
use App\Http\Controllers\Web\SportsPageController;
use App\Http\Controllers\StreamProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/news', [SportsPageController::class, 'news'])->name('news.index');
Route::get('/news/{slug}', [SportsPageController::class, 'article'])->name('news.show');
Route::get('/scores', [SportsPageController::class, 'scores'])->name('scores');
Route::get('/live-scores', [SportsPageController::class, 'scores'])->name('live-scores');
Route::get('/fixtures', [SportsPageController::class, 'fixtures'])->name('fixtures');
Route::get('/leagues', [SportsPageController::class, 'leagues'])->name('leagues.index');
Route::get('/leagues/{slug}', [SportsPageController::class, 'league'])->name('leagues.show');
Route::get('/teams', [SportsPageController::class, 'teams'])->name('teams.index');
Route::get('/teams/{slug}', [SportsPageController::class, 'team'])->name('teams.show');
Route::get('/matches/{slug}', [SportsPageController::class, 'match'])->name('matches.show');
Route::get('/highlights', [SportsPageController::class, 'highlights'])->name('highlights');
Route::get('/search', [SportsPageController::class, 'search'])->name('search');
Route::get('/about', [SportsPageController::class, 'staticPage'])->defaults('page', 'about')->name('about');
Route::get('/contact', [SportsPageController::class, 'staticPage'])->defaults('page', 'contact')->name('contact');
Route::get('/privacy-policy', [SportsPageController::class, 'staticPage'])->defaults('page', 'privacy-policy')->name('privacy');
Route::get('/terms', [SportsPageController::class, 'staticPage'])->defaults('page', 'terms')->name('terms');
Route::get('/copyright', [SportsPageController::class, 'staticPage'])->defaults('page', 'copyright')->name('copyright');
Route::get('/advertise', [SportsPageController::class, 'staticPage'])->defaults('page', 'advertise')->name('advertise');
Route::get('/editorial-policy', [SportsPageController::class, 'staticPage'])->defaults('page', 'editorial-policy')->name('editorial-policy');
Route::get('/sitemap.xml', [SportsPageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SportsPageController::class, 'robots'])->name('robots');
Route::get('/live', LiveTvController::class)->name('live');
Route::get('/watch/{channel}', [ChannelController::class, 'show'])->name('channels.show');
Route::get('/stream/{encodedUrl}', StreamProxyController::class)
    ->name('stream.proxy');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])
        ->middleware('throttle:auth')
        ->name('admin.login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('admin.dashboard');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');
    Route::resource('categories', AdminCategoryController::class)
        ->except(['create', 'show'])
        ->names('admin.categories');
    Route::resource('channels', AdminChannelController::class)
        ->except(['create', 'show'])
        ->names('admin.channels');
    Route::resource('programs', AdminProgramController::class)
        ->except(['create', 'show'])
        ->names('admin.programs');
    Route::post('/playlists', [AdminPlaylistController::class, 'store'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.store');
    Route::post('/playlists/{playlist}/parse', [AdminPlaylistController::class, 'parse'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.parse');
});

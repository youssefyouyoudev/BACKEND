<?php

use App\Http\Controllers\Api\Admin\LogsController;
use App\Http\Controllers\Api\Admin\PlaylistManagementController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EpgController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\Mobile\CategoryChannelController as MobileCategoryChannelController;
use App\Http\Controllers\Api\Mobile\ChannelController as MobileChannelController;
use App\Http\Controllers\Api\Mobile\LeagueController as MobileLeagueController;
use App\Http\Controllers\Api\Mobile\NewsController as MobileNewsController;
use App\Http\Controllers\Api\Mobile\SearchController as MobileSearchController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicTvController;
use App\Http\Controllers\Api\WatchController as ApiWatchController;
use App\Http\Controllers\FootballController as PublicFootballController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('throttle:auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('throttle:api')->group(function (): void {
    Route::get('/categories', CategoryController::class);
    Route::get('/channels/match', [PublicFootballController::class, 'matchChannelDebug']);
    Route::get('/football/event/{eventId}/tv', [PublicFootballController::class, 'eventTv'])->whereNumber('eventId');
    Route::get('/epg', EpgController::class);
    Route::get('/watch/categories', [ApiWatchController::class, 'categories']);
    Route::get('/watch/items', [ApiWatchController::class, 'items']);
    Route::get('/watch/items/{item}', [ApiWatchController::class, 'show']);
    Route::get('/watch/search', [ApiWatchController::class, 'search']);
    Route::post('/watch/items/{item}/favorite', [ApiWatchController::class, 'favorite'])->middleware('auth:sanctum');
    Route::post('/watch/items/{item}/history', [ApiWatchController::class, 'history'])->middleware('auth:sanctum');
});

Route::middleware('throttle:mobile-api')->name('api.mobile.')->group(function (): void {
    Route::get('/channels', [MobileChannelController::class, 'index'])->name('channels.index');
    Route::get('/channels/featured', [MobileChannelController::class, 'featured'])->name('channels.featured');
    Route::get('/channels/categories', [MobileChannelController::class, 'categories'])->name('channels.categories');
    Route::get('/channels/{id}', [MobileChannelController::class, 'show'])->whereNumber('id')->name('channels.show');
    Route::get('/categories/{slug}/channels', MobileCategoryChannelController::class)->name('categories.channels');
    Route::get('/news', [MobileNewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}', [MobileNewsController::class, 'show'])->name('news.show');
    Route::get('/leagues', MobileLeagueController::class)->name('leagues.index');
    Route::get('/search', MobileSearchController::class)->name('search');
});

Route::middleware(['auth:sanctum', 'active.user', 'throttle:api'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard', DashboardController::class);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/password', [ProfileController::class, 'updatePassword']);

    Route::get('/playlists', [PlaylistController::class, 'index']);
    Route::post('/playlists/url', [PlaylistController::class, 'storeFromUrl'])->middleware('throttle:playlists');
    Route::post('/playlists/upload', [PlaylistController::class, 'storeFromUpload'])->middleware('throttle:playlists');
    Route::get('/playlists/{playlist}', [PlaylistController::class, 'show']);
    Route::get('/playlists/{playlist}/channels', [PlaylistController::class, 'channels']);
    Route::post('/playlists/{playlist}/refresh', [PlaylistController::class, 'refresh'])->middleware('throttle:playlists');
    Route::delete('/playlists/{playlist}', [PlaylistController::class, 'destroy']);

    Route::get('/channels/favorites', [ChannelController::class, 'favorites']);
    Route::post('/channels/{channel}/favorite', [ChannelController::class, 'favorite']);
    Route::delete('/channels/{channel}/favorite', [ChannelController::class, 'unfavorite']);
    Route::get('/user/channels', [ChannelController::class, 'index']);
    Route::get('/user/channels/{channel}', [ChannelController::class, 'show']);

    Route::get('/history', [HistoryController::class, 'index']);
    Route::post('/history', [HistoryController::class, 'store']);

    Route::prefix('admin')->middleware('admin')->group(function (): void {
        Route::get('/stats', StatsController::class);
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::patch('/users/{user}/toggle', [UserManagementController::class, 'toggle']);
        Route::get('/playlists', [PlaylistManagementController::class, 'index']);
        Route::patch('/playlists/{playlist}/approve', [PlaylistManagementController::class, 'approve']);
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::put('/settings', [SettingsController::class, 'update']);
        Route::get('/logs', [LogsController::class, 'index']);
    });
});

/*
|--------------------------------------------------------------------------
| Public channel routes (no auth required — used by the TV player UI)
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:api')->group(function (): void {
    // Returns stream sources for the TV failover player when switching channels
    Route::get('/channels/{channel}/streams', [ChannelController::class, 'streams']);

    // Live TV split-screen: channels (paginated) + category counts
    Route::prefix('tv')->group(function (): void {
        Route::get('/channels', [PublicTvController::class, 'channels']);
        Route::get('/channels/{item}', [PublicTvController::class, 'show']);
        Route::get('/categories', [PublicTvController::class, 'categories']);
    });
});

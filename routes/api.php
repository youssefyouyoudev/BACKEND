<?php

use App\Http\Controllers\Api\Admin\LogsController;
use App\Http\Controllers\Api\Admin\PlaylistManagementController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\PublicChannelController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicTvController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('throttle:auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('throttle:api')->group(function (): void {
    Route::get('/channels', [PublicChannelController::class, 'index']);
    Route::get('/channels/{channel}', [PublicChannelController::class, 'show'])->whereNumber('channel');
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

    Route::get('/channels/featured', [ChannelController::class, 'featured']);
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
        Route::get('/channels',   [PublicTvController::class, 'channels']);
        Route::get('/channels/{channel}', [PublicTvController::class, 'show']);
        Route::get('/categories', [PublicTvController::class, 'categories']);
    });
});

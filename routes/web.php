<?php

use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\PlaylistController as AdminPlaylistController;
use App\Http\Controllers\Web\ChannelController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LiveTvController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/live', LiveTvController::class)->name('live');
Route::get('/watch/{channel}', [ChannelController::class, 'show'])->name('channels.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])
        ->middleware('throttle:auth')
        ->name('admin.login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('admin.dashboard');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');
    Route::post('/playlists', [AdminPlaylistController::class, 'store'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.store');
    Route::post('/playlists/{playlist}/parse', [AdminPlaylistController::class, 'parse'])
        ->middleware('throttle:playlists')
        ->name('admin.playlists.parse');
});

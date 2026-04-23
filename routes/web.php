<?php

use App\Http\Controllers\MarketController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedNewsController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ExchangeRateController;

Route::get('/', [NewsController::class, 'index'])->name('news');
Route::get('/markets', [MarketController::class, 'index'])->name('markets');
Route::get('/exchange', [ExchangeRateController::class, 'index'])->name('exchange');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\SavedMarketController;
use App\Http\Controllers\NotificationController;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('permission:save articles')->group(function () {
        Route::get('/saved-news', [SavedNewsController::class, 'index']);
        Route::post('/saved-news/{news}', [SavedNewsController::class, 'store']);
        Route::delete('/saved-news/{news}', [SavedNewsController::class, 'destroy']);
    });

    Route::middleware('permission:save markets')->group(function () {
        Route::get('/saved-markets', [SavedMarketController::class, 'index']);
        Route::get('/saved-markets/ids', [SavedMarketController::class, 'ids']);
        Route::post('/saved-markets/{market}', [SavedMarketController::class, 'store']);
        Route::delete('/saved-markets/{market}', [SavedMarketController::class, 'destroy']);
    });

    Route::middleware('permission:manage notifications')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::get('/notification-preferences', [NotificationController::class, 'preferences']);
        Route::post('/notification-preferences', [NotificationController::class, 'updatePreferences']);
    });
});

require __DIR__.'/auth.php';

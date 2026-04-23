<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketHistoryController;
use App\Http\Controllers\MarketStatusController;
use App\Http\Controllers\NewsController;

Route::get('/news', [NewsController::class, 'apiIndex']);

Route::get('/market-status', [MarketStatusController::class, 'index']);
Route::get('/market-status/all', [MarketStatusController::class, 'all']);

Route::get('/markets', [MarketController::class, 'apiIndex']);
Route::get('/markets/{symbol}/history', [MarketHistoryController::class, 'show']);

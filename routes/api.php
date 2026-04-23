<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketHistoryController;
use App\Http\Controllers\MarketRecommendationController;
use App\Http\Controllers\MarketStatusController;
use App\Http\Controllers\NewsController;

use App\Http\Controllers\ExchangeRateController;

Route::get('/news', [NewsController::class, 'apiIndex']);

Route::get('/market-status', [MarketStatusController::class, 'index']);
Route::get('/market-status/all', [MarketStatusController::class, 'all']);

Route::get('/markets', [MarketController::class, 'apiIndex']);
Route::get('/markets/{symbol}/history', [MarketHistoryController::class, 'show']);
Route::get('/markets/{symbol}/recommendation', [MarketRecommendationController::class, 'show']);

Route::get('/exchange-rates', [ExchangeRateController::class, 'pairs']);
Route::get('/exchange-rates/{base}/{target}/history', [ExchangeRateController::class, 'history']);
Route::post('/exchange-rates/convert', [ExchangeRateController::class, 'convert']);
Route::get('/exchange-rates/convert', [ExchangeRateController::class, 'convert']);

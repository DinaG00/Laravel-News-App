<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MarketRecommendationController extends Controller
{
    public function show(Request $request, string $symbol): JsonResponse
    {
        $symbol = strtoupper($symbol);
        $cacheKey = "recommendation_{$symbol}";
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return response()->json($cached);
        }

        $token = env('FINNHUB_API_KEY');

        if (! $token) {
            return response()->json(['error' => 'API key missing'], 500);
        }

        try {
            $response = Http::timeout(10)->get(
                'https://finnhub.io/api/v1/stock/recommendation',
                [
                    'symbol' => $symbol,
                    'token'  => $token,
                ]
            );

            if ($response->failed()) {
                return response()->json([
                    'error'  => 'API failed',
                    'status' => $response->status(),
                ], 502);
            }

            $body = $response->json();

            if (empty($body) || !is_array($body)) {
                return response()->json(['error' => 'No data available'], 404);
            }

            // Sort by period ascending and keep last 6 months
            $data = collect($body)
                ->sortBy('period')
                ->values()
                ->all();

            $data = array_slice($data, -6);

            $result = [
                'symbol' => $symbol,
                'labels' => array_map(fn ($d) => \Illuminate\Support\Carbon::parse($d['period'])->format('M Y'), $data),
                'strongBuy'  => array_map(fn ($d) => $d['strongBuy'] ?? 0, $data),
                'buy'        => array_map(fn ($d) => $d['buy'] ?? 0, $data),
                'hold'       => array_map(fn ($d) => $d['hold'] ?? 0, $data),
                'sell'       => array_map(fn ($d) => $d['sell'] ?? 0, $data),
                'strongSell' => array_map(fn ($d) => $d['strongSell'] ?? 0, $data),
            ];

            Cache::put($cacheKey, $result, now()->addMinutes(30));

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to fetch recommendations'], 500);
        }
    }
}

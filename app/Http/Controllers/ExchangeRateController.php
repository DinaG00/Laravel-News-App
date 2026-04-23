<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Services\ExchangerateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExchangeRateController extends Controller
{
    public function index(Request $request)
    {
        $fiat = ExchangeRate::where('type', 'fiat')
            ->where('rate_date', now()->toDateString())
            ->orderBy('base_currency')
            ->orderBy('target_currency')
            ->get();

        $crypto = ExchangeRate::where('type', 'crypto')
            ->where('rate_date', now()->toDateString())
            ->orderBy('base_currency')
            ->orderBy('target_currency')
            ->get();

        return view('exchange', [
            'fiat'   => $fiat,
            'crypto' => $crypto,
        ]);
    }

    public function pairs(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $minDate = now()->subDays(30)->toDateString();

        // Find pairs with at least 2 days of history (direct or inverse count)
        $pairs = ExchangeRate::query()
            ->selectRaw('base_currency, target_currency')
            ->whereBetween('rate_date', [$minDate, $today])
            ->groupBy('base_currency', 'target_currency')
            ->havingRaw('COUNT(DISTINCT rate_date) >= 2')
            ->get();

        // Fetch today's rate for each pair
        $result = collect($pairs)->map(function ($pair) use ($today) {
            $todayRate = ExchangeRate::query()
                ->where('base_currency', $pair->base_currency)
                ->where('target_currency', $pair->target_currency)
                ->where('rate_date', $today)
                ->first();

            if ($todayRate) {
                return [
                    'base_currency'   => $pair->base_currency,
                    'target_currency' => $pair->target_currency,
                    'rate'            => (float) $todayRate->rate,
                    'rate_date'       => $todayRate->rate_date,
                ];
            }

            // Try inverse and compute reciprocal
            $inverse = ExchangeRate::query()
                ->where('base_currency', $pair->target_currency)
                ->where('target_currency', $pair->base_currency)
                ->where('rate_date', $today)
                ->first();

            if ($inverse) {
                return [
                    'base_currency'   => $pair->base_currency,
                    'target_currency' => $pair->target_currency,
                    'rate'            => round(1 / (float) $inverse->rate, 10),
                    'rate_date'       => $inverse->rate_date,
                ];
            }

            return null;
        })
        ->filter()
        ->sortBy('base_currency')
        ->sortBy('target_currency')
        ->values()
        ->all();

        return response()->json($result);
    }

    public function history(Request $request, string $base, string $target): JsonResponse
    {
        $days = min((int) $request->input('days', 30), 90);
        $cacheKey = "exchange_history_{$base}_{$target}_{$days}";

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }

        $records = ExchangeRate::query()
            ->where('base_currency', strtoupper($base))
            ->where('target_currency', strtoupper($target))
            ->where('rate_date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('rate_date')
            ->get();

        if ($records->isEmpty()) {
            return response()->json(['labels' => [], 'rates' => []]);
        }

        $result = [
            'labels' => $records->pluck('rate_date')->map(fn ($d) => Carbon::parse($d)->format('M d'))->values()->all(),
            'rates'  => $records->pluck('rate')->map(fn ($r) => (float) $r)->values()->all(),
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(10));

        return response()->json($result);
    }

    public function convert(Request $request): JsonResponse
    {
        $from   = strtoupper($request->input('from'));
        $to     = strtoupper($request->input('to'));
        $amount = (float) $request->input('amount', 1);

        if (! $from || ! $to) {
            return response()->json(['error' => 'Missing from or to'], 422);
        }

        // Try direct pair
        $direct = ExchangeRate::where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('rate_date', now()->toDateString())
            ->first();

        if ($direct) {
            $rate = (float) $direct->rate;
            return response()->json([
                'from'     => $from,
                'to'       => $to,
                'amount'   => $amount,
                'rate'     => round($rate, 10),
                'result'   => round($amount * $rate, 6),
                'date'     => $direct->rate_date,
                'indirect' => false,
            ]);
        }

        // Try reverse pair
        $reverse = ExchangeRate::where('base_currency', $to)
            ->where('target_currency', $from)
            ->where('rate_date', now()->toDateString())
            ->first();

        if ($reverse) {
            $rate = 1 / ((float) $reverse->rate);
            return response()->json([
                'from'     => $from,
                'to'       => $to,
                'amount'   => $amount,
                'rate'     => round($rate, 10),
                'result'   => round($amount * $rate, 6),
                'date'     => $reverse->rate_date,
                'indirect' => true,
            ]);
        }

        // Try indirect via USD (using inverse if needed)
        $usdFrom = ExchangeRate::where('base_currency', $from)
            ->where('target_currency', 'USD')
            ->where('rate_date', now()->toDateString())
            ->first();

        // If no from→USD, try USD→from and use inverse
        $usdFromInv = ExchangeRate::where('base_currency', 'USD')
            ->where('target_currency', $from)
            ->where('rate_date', now()->toDateString())
            ->first();

        $usdTo = ExchangeRate::where('base_currency', 'USD')
            ->where('target_currency', $to)
            ->where('rate_date', now()->toDateString())
            ->first();

        if (($usdFrom || $usdFromInv) && $usdTo) {
            $fromRate = $usdFrom
                ? (float) $usdFrom->rate
                : 1 / ((float) $usdFromInv->rate);
            $toRate = (float) $usdTo->rate;
            $rate = $fromRate * $toRate;

            return response()->json([
                'from'     => $from,
                'to'       => $to,
                'amount'   => $amount,
                'rate'     => round($rate, 10),
                'result'   => round($amount * $rate, 6),
                'date'     => $usdFrom->rate_date ?? $usdFromInv->rate_date,
                'indirect' => true,
            ]);
        }

        return response()->json(['error' => 'Rate not available'], 404);
    }
}

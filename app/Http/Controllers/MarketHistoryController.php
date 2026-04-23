<?php

namespace App\Http\Controllers;

use App\Models\MarketHistory;
use Illuminate\Http\Request;

class MarketHistoryController extends Controller
{
    public function show(Request $request, string $symbol)
    {
        $symbol = strtoupper($symbol);

        $days = (int) $request->query('days', 30);
        if ($days < 1 || $days > 365) $days = 30;

        $history = MarketHistory::query()
            ->where('symbol', $symbol)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date')
            ->get(['symbol', 'date', 'open', 'high', 'low', 'close', 'volume']);

        return response()->json([
            'symbol' => $symbol,
            'labels' => $history->pluck('date')->map(fn ($d) => $d->format('M d')),
            'close'  => $history->pluck('close')->map(fn ($v) => (float) $v),
            'volume' => $history->pluck('volume'),
        ]);
    }
}

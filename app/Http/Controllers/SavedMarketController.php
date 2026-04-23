<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedMarketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $saved = $request->user()->savedMarkets()
            ->with('market')
            ->latest()
            ->get()
            ->pluck('market');

        return response()->json($saved);
    }

    public function ids(Request $request): JsonResponse
    {
        $ids = $request->user()->savedMarkets()
            ->pluck('market_id')
            ->values();

        return response()->json($ids);
    }

    public function store(Request $request, Market $market): JsonResponse
    {
        $request->user()->savedMarkets()->firstOrCreate([
            'market_id' => $market->id,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($market)
            ->withProperties(['market_id' => $market->id, 'symbol' => $market->symbol])
            ->log('Saved market');

        return response()->json(['message' => 'Saved']);
    }

    public function destroy(Request $request, Market $market): JsonResponse
    {
        $request->user()->savedMarkets()->where('market_id', $market->id)->delete();

        activity()
            ->causedBy($request->user())
            ->performedOn($market)
            ->withProperties(['market_id' => $market->id, 'symbol' => $market->symbol])
            ->log('Unsaved market');

        return response()->json(['message' => 'Unsaved']);
    }
}

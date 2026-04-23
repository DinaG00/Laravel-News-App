<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index()
    {
        return view('markets');
    }

    public function apiIndex(Request $request)
    {
        $search = $request->filled('search') ? $request->input('search') : null;

        $query = Market::query()
            ->where('date', now()->toDateString())
            ->orWhere(function ($q) {
                $q->whereNull('date')->orWhere('date', '>=', now()->subDays(7)->toDateString());
            })
            ->orderByDesc('date')
            ->orderBy('symbol');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        $markets = $query->paginate(12);

        return response()->json([
            'data' => $markets->items(),
            'pagination' => [
                'current_page' => $markets->currentPage(),
                'last_page'    => $markets->lastPage(),
                'per_page'     => $markets->perPage(),
                'total'        => $markets->total(),
            ],
        ]);
    }
}

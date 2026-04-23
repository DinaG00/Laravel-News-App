<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MarketStatusController extends Controller
{
    private const EXCHANGES = [
        'US'  => 'NYSE',
    ];

    public function index(Request $request)
    {
        $exchange = strtoupper($request->query('exchange', 'US'));
        return $this->fetchStatus($exchange);
    }

    public function all()
    {
        $results = [];
        foreach (self::EXCHANGES as $code => $name) {
            $results[$code] = $this->fetchStatus($code);
        }
        return response()->json($results);
    }

    private function fetchStatus(string $exchange): array
    {
        $cacheKey = "market_status_{$exchange}";
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        $token = env('FINNHUB_API_KEY');
        if (! $token) {
            return [
                'exchange' => $exchange,
                'label'    => 'N/A',
                'statusClass' => 'closed',
                'error'    => 'API key missing',
            ];
        }

        try {
            $response = Http::timeout(5)->get(
                'https://finnhub.io/api/v1/stock/market-status',
                ['exchange' => $exchange, 'token' => $token]
            );

            $body = $response->json();
            $session = $body['session'] ?? null;

            $result = [
                'exchange'    => $exchange,
                'isOpen'      => $body['isOpen'] ?? false,
                'session'     => $session,
                'label'       => $this->mapSessionLabel($session),
                'statusClass' => $this->mapSessionClass($session),
                'holiday'     => $body['holiday'] ?? null,
                'timezone'    => $body['timezone'] ?? null,
            ];

            Cache::put($cacheKey, $result, now()->addMinutes(5));
            return $result;
        } catch (\Throwable $e) {
            return [
                'exchange'    => $exchange,
                'label'       => 'Unknown',
                'statusClass' => 'closed',
                'error'       => 'Failed to fetch status',
            ];
        }
    }

    private function mapSessionLabel(?string $session): string
    {
        return match ($session) {
            'regular'     => 'Open',
            'pre-market'  => 'Pre-Market',
            'after-hours' => 'After-Hours',
            'closed'      => 'Closed',
            default       => ucfirst($session ?? 'Unknown'),
        };
    }

    private function mapSessionClass(?string $session): string
    {
        return match ($session) {
            'regular'     => 'open',
            'pre-market'  => 'premarket',
            'after-hours' => 'afterhours',
            'closed'      => 'closed',
            default       => 'closed',
        };
    }
}

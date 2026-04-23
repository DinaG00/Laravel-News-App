<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Market;

class FetchMarkets extends Command
{
    protected $signature = 'app:fetch-markets';
    protected $description = 'Fetch latest market tickers from Marketstack API';

    public function handle()
    {
        $apiKey = config('services.marketstack.key');

        if (! $apiKey) {
            $this->error('Marketstack API key is missing. Set MARKETSTACK_API_KEY in .env');
            return;
        }

        $symbols = 'AAPL,MSFT,GOOGL,AMZN,TSLA,NVDA,META,JPM,V,MA';

        // 1. Fetch latest EOD (single)
        $this->info('Fetching latest EOD snapshots...');
        $latestResponse = Http::get('https://api.marketstack.com/v1/eod/latest', [
            'access_key' => $apiKey,
            'symbols' => $symbols,
            'limit' => 100,
        ]);

        if ($latestResponse->failed()) {
            $this->error('Failed to fetch latest market data from API');
            $this->error('Status: ' . $latestResponse->status());
            return;
        }

        $latestData = $latestResponse->json()['data'] ?? [];
        $latestCount = 0;
        foreach ($latestData as $item) {
            Market::updateOrCreate(
                ['symbol' => $item['symbol']],
                [
                    'date'     => $item['date'] ?? now()->toDateString(),
                    'name'     => $this->mapName($item['symbol']),
                    'open'     => $item['open'] ?? null,
                    'high'     => $item['high'] ?? null,
                    'low'      => $item['low'] ?? null,
                    'close'    => $item['last'] ?? $item['close'] ?? null,
                    'volume'   => $item['volume'] ?? null,
                    'exchange' => $item['exchange'] ?? null,
                    'currency' => 'USD',
                ]
            );
            $latestCount++;
        }
        $this->info("Latest: {$latestCount} records.");

        // 2. Fetch history (last 30 days) for charting
        $this->info('Fetching 30-day history...');
        $historyResponse = Http::get('https://api.marketstack.com/v1/eod', [
            'access_key' => $apiKey,
            'symbols' => $symbols,
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to'   => now()->toDateString(),
            'limit' => 1000,
        ]);

        $historyCount = 0;
        if (! $historyResponse->failed()) {
            $historyData = $historyResponse->json()['data'] ?? [];
            foreach ($historyData as $item) {
                \App\Models\MarketHistory::updateOrCreate(
                    [
                        'symbol' => $item['symbol'],
                        'date'   => $item['date'] ?? now()->toDateString(),
                    ],
                    [
                        'open'   => $item['open'] ?? null,
                        'high'   => $item['high'] ?? null,
                        'low'    => $item['low'] ?? null,
                        'close'  => $item['last'] ?? $item['close'] ?? null,
                        'volume' => $item['volume'] ?? null,
                    ]
                );
                $historyCount++;
            }
            $this->info("History: {$historyCount} records.");
        } else {
            $this->warn('History fetch failed, skipping.');
        }

        $this->info('Markets imported successfully!');
    }

    private function mapName(string $symbol): string
    {
        return match ($symbol) {
            'AAPL'  => 'Apple Inc.',
            'MSFT'  => 'Microsoft Corp.',
            'GOOGL' => 'Alphabet Inc.',
            'AMZN'  => 'Amazon.com Inc.',
            'TSLA'  => 'Tesla Inc.',
            'NVDA'  => 'NVIDIA Corp.',
            'META'  => 'Meta Platforms Inc.',
            'JPM'   => 'JPMorgan Chase & Co.',
            'V'     => 'Visa Inc.',
            'MA'    => 'Mastercard Inc.',
            default => $symbol,
        };
    }
}

<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Seed the exchange_rates table with sample data.
     */
    public function run(): void
    {
        $today = Carbon::today();
        $days = 30;

        // Fiat pairs with realistic sample rates
        $fiatPairs = [
            ['base' => 'USD', 'target' => 'EUR', 'baseRate' => 0.9180],
            ['base' => 'USD', 'target' => 'GBP', 'baseRate' => 0.7945],
            ['base' => 'USD', 'target' => 'JPY', 'baseRate' => 148.75],
            ['base' => 'USD', 'target' => 'CHF', 'baseRate' => 0.8845],
            ['base' => 'USD', 'target' => 'CAD', 'baseRate' => 1.3520],
            ['base' => 'USD', 'target' => 'AUD', 'baseRate' => 1.5245],
            ['base' => 'USD', 'target' => 'CNY', 'baseRate' => 7.2150],
            ['base' => 'USD', 'target' => 'RON', 'baseRate' => 4.5825],
            ['base' => 'EUR', 'target' => 'USD', 'baseRate' => 1.0890],
            ['base' => 'EUR', 'target' => 'GBP', 'baseRate' => 0.8650],
            ['base' => 'EUR', 'target' => 'JPY', 'baseRate' => 162.05],
            ['base' => 'EUR', 'target' => 'CHF', 'baseRate' => 0.9630],
            ['base' => 'EUR', 'target' => 'RON', 'baseRate' => 4.9870],
            ['base' => 'GBP', 'target' => 'USD', 'baseRate' => 1.2590],
            ['base' => 'GBP', 'target' => 'EUR', 'baseRate' => 1.1560],
        ];

        // Crypto pairs
        $cryptoPairs = [
            ['base' => 'BTC', 'target' => 'USD', 'baseRate' => 67250.00],
            ['base' => 'BTC', 'target' => 'EUR', 'baseRate' => 61720.00],
        ];

        foreach ($fiatPairs as $pair) {
            for ($i = 0; $i < $days; $i++) {
                $date = $today->copy()->subDays($i);
                $rate = $this->generateDailyRate($pair['baseRate'], $i);

                ExchangeRate::updateOrCreate(
                    [
                        'type' => 'fiat',
                        'base_currency' => $pair['base'],
                        'target_currency' => $pair['target'],
                        'rate_date' => $date->toDateString(),
                    ],
                    ['rate' => $rate]
                );
            }
        }

        foreach ($cryptoPairs as $pair) {
            for ($i = 0; $i < $days; $i++) {
                $date = $today->copy()->subDays($i);
                $rate = $this->generateDailyRate($pair['baseRate'], $i, 0.025);

                ExchangeRate::updateOrCreate(
                    [
                        'type' => 'crypto',
                        'base_currency' => $pair['base'],
                        'target_currency' => $pair['target'],
                        'rate_date' => $date->toDateString(),
                    ],
                    ['rate' => $rate]
                );
            }
        }
    }

    /**
     * Generate a pseudo-random rate that varies slightly per day.
     */
    private function generateDailyRate(float $baseRate, int $daysAgo, float $volatility = 0.005): float
    {
        // Seed with date offset so the same day always gets the same randomness
        srand((int) ($baseRate * 1000) + $daysAgo);
        $change = (rand(-1000, 1000) / 1000) * $volatility;
        srand();
        return round($baseRate * (1 + $change), 10);
    }
}

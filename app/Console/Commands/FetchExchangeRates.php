<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use App\Services\ExchangerateService;
use Illuminate\Console\Command;

class FetchExchangeRates extends Command
{
    protected $signature = 'app:fetch-exchange-rates';
    protected $description = 'Fetch latest fiat/crypto exchange rates from exchangerate.host';

    private array $fiatPairs = [
        ['base' => 'USD', 'targets' => ['EUR', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'CNY', 'RON']],
        ['base' => 'EUR', 'targets' => ['USD', 'GBP', 'JPY', 'CHF', 'RON']],
        ['base' => 'GBP', 'targets' => ['USD', 'EUR']],
    ];

    // Free tier only supports BTC as a crypto target; ETH/XRP/LTC/ADA require paid tier.
    private array $cryptoPairs = [
        ['base' => 'BTC', 'targets' => ['USD', 'EUR']],
    ];

    public function handle(ExchangerateService $service): int
    {
        $today = now()->toDateString();

        foreach ($this->fiatPairs as $pair) {
            $this->fetchAndStore($service, 'fiat', $pair['base'], $pair['targets'], $today);
        }

        foreach ($this->cryptoPairs as $pair) {
            $this->fetchAndStore($service, 'crypto', $pair['base'], $pair['targets'], $today);
        }

        $this->info('Exchange rates updated.');
        return self::SUCCESS;
    }

    private function fetchAndStore(
        ExchangerateService $service,
        string $type,
        string $base,
        array $targets,
        string $date
    ): void {
        if ($type === 'crypto') {
            // Use convert endpoint for crypto (works for BTC on free tier)
            foreach ($targets as $target) {
                sleep(1);
                try {
                    $result = $service->convert($base, $target, 1);
                } catch (\Throwable $e) {
                    $this->error("Failed {$type} {$base}->{$target}: " . $e->getMessage());
                    continue;
                }

                if (empty($result['success'])) {
                    $this->error("Failed {$type} {$base}->{$target}: " . ($result['error'] ?? 'Unknown'));
                    continue;
                }

                $rate = $result['rate'];
                if ($rate === null) continue;

                ExchangeRate::updateOrCreate(
                    [
                        'type' => $type,
                        'base_currency' => $base,
                        'target_currency' => $target,
                        'rate_date' => $date,
                    ],
                    ['rate' => $rate]
                );
            }
            return;
        }

        // Fiat uses live endpoint
        $result = $service->live($base, $targets);

        if (empty($result['success'])) {
            $this->error("Failed {$type} {$base}: " . ($result['error'] ?? 'Unknown'));
            return;
        }

        foreach ($targets as $target) {
            $rate = $result['rates'][$target] ?? null;
            if ($rate === null) continue;

            ExchangeRate::updateOrCreate(
                [
                    'type' => $type,
                    'base_currency' => $base,
                    'target_currency' => $target,
                    'rate_date' => $date,
                ],
                ['rate' => $rate]
            );
        }
    }
}

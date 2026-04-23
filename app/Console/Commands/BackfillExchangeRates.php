<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use App\Services\ExchangerateService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillExchangeRates extends Command
{
    protected $signature = 'app:backfill-exchange-rates';
    protected $description = 'Backfill last 30 days of exchange rates from historical API';

    private array $pairs = [
        ['type' => 'fiat',  'base' => 'USD', 'target' => 'EUR'],
        ['type' => 'fiat',  'base' => 'USD', 'target' => 'GBP'],
        ['type' => 'fiat',  'base' => 'EUR', 'target' => 'USD'],
        ['type' => 'crypto', 'base' => 'BTC', 'target' => 'USD'],
    ];

    public function handle(ExchangerateService $service): int
    {
        $days = 30;
        for ($i = $days; $i > 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            foreach ($this->pairs as $pair) {
                $this->fetchHistorical($service, $pair['type'], $pair['base'], $pair['target'], $date);
            }
        }
        $this->info('Backfill complete.');
        return self::SUCCESS;
    }

    private function fetchHistorical(
        ExchangerateService $service,
        string $type,
        string $base,
        string $target,
        string $date
    ): void {
        $result = $service->historical($date, $base, [$target]);

        if (empty($result['success'])) {
            $this->error("Failed {$base}->{$target} {$date}: " . ($result['error'] ?? 'Unknown'));
            return;
        }

        $rate = $result['rates'][$target] ?? null;
        if ($rate === null) return;

        ExchangeRate::updateOrCreate(
            [
                'type' => $type,
                'base_currency' => $base,
                'target_currency' => $target,
                'rate_date' => $date,
            ],
            ['rate' => $rate]
        );

        $this->info("Stored {$type} {$base}->{$target} {$date}");
    }
}

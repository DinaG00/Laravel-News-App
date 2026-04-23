<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Market;
use App\Models\Notification;
use App\Models\SavedMarket;
use App\Models\UserPreference;

class CheckSavedMarketAlerts extends Command
{
    protected $signature = 'app:check-saved-market-alerts';
    protected $description = 'Check saved markets for >=2% moves and notify users';

    public function handle()
    {
        // Only today's data
        $today = now()->toDateString();
        $markets = Market::where('date', $today)->get();

        if ($markets->isEmpty()) {
            $this->info('No market data for today.');
            return;
        }

        $alertCount = 0;

        foreach ($markets as $market) {
            $open = (float) $market->open;
            $close = (float) $market->close;
            if (!$open || !$close) continue;

            $diff = $close - $open;
            $pct = abs(($diff / $open) * 100);

            if ($pct < 2.0) continue;

            // Find all users who saved this market
            $saved = SavedMarket::where('market_id', $market->id)->get();
            foreach ($saved as $s) {
                $pref = UserPreference::where('user_id', $s->user_id)->where('notify_price_alerts', true)->first();
                if (! $pref) continue;

                // Don't re-notify same user+market within 6 hours
                $already = Notification::where('user_id', $s->user_id)
                    ->where('type', 'price_alert')
                    ->where('created_at', '>=', now()->subHours(6))
                    ->whereJsonContains('metadata', [
                        'symbol' => $market->symbol,
                        'date'   => $today,
                    ])
                    ->first();

                if ($already) continue;

                $sign = $diff >= 0 ? '+' : '';
                Notification::create([
                    'user_id' => $s->user_id,
                    'type'    => 'price_alert',
                    'title'   => "{$market->symbol} moved {$sign}{$pct->toFixed(2)}%",
                    'body'    => "{$market->symbol} ({$market->name}) closed at {$close}. Open: {$open}",
                    'metadata' => [
                        'symbol' => $market->symbol,
                        'date'   => $today,
                        'change_pct' => (float) number_format($pct, 2),
                    ],
                ]);
                $alertCount++;
            }
        }

        $this->info("Sent {$alertCount} price alert notifications.");
    }
}

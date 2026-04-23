<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Notification;
use App\Models\UserPreference;

class CheckMarketStatus extends Command
{
    protected $signature = 'app:check-market-status';
    protected $description = 'Check US market status and notify users on status changes';

    public function handle()
    {
        $cacheKey = 'market_status_last_state';
        $lastState = Cache::get($cacheKey, null);

        $response = Http::timeout(10)->get(
            'https://finnhub.io/api/v1/stock/market-status',
            [
                'exchange' => 'US',
                'token'    => env('FINNHUB_API_KEY'),
            ]
        );

        if ($response->failed()) {
            $this->error('Failed to fetch market status');
            return;
        }

        $body = $response->json();
        $currentSession = $body['session'] ?? null;
        $isOpen = $body['isOpen'] ?? false;

        $this->info("Current session: {$currentSession}");

        // Skip if it's the very first run or state didn't change
        if ($lastState === null || $lastState !== $currentSession) {
            if ($lastState !== null) {
                // State changed — notify interested users
                $label = $this->mapSessionLabel($currentSession);
                $this->notifyUsers($label, $currentSession);
            }
            Cache::put($cacheKey, $currentSession, now()->addDay());
        }

        $this->info('Done.');
    }

    private function mapSessionLabel(?string $session): string
    {
        return match ($session) {
            'regular'     => 'Regular Trading',
            'pre-market'  => 'Pre-Market',
            'after-hours' => 'After-Hours',
            'closed'      => 'Closed',
            default       => 'Unknown',
        };
    }

    private function notifyUsers(string $label, string $session)
    {
        $title = match ($session) {
            'regular'     => 'US Market is now Open',
            'pre-market'  => 'US Pre-Market has started',
            'after-hours' => 'US Market is now in After-Hours',
            'closed'      => 'US Market has Closed',
            default       => 'US Market status updated',
        };

        $body = "The US market is now {$label}.";

        $prefs = UserPreference::where('notify_market_status', true)->get();
        foreach ($prefs as $pref) {
            Notification::create([
                'user_id' => $pref->user_id,
                'type'    => 'market_status',
                'title'   => $title,
                'body'    => $body,
                'metadata'  => ['session' => $session],
            ]);
        }

        $this->info("Notified {$prefs->count()} users about status: {$session}");
    }
}

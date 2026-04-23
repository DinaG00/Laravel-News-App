<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ExchangerateService
{
    private string $baseUrl = 'https://api.exchangerate.host';
    private string $key;

    public function __construct()
    {
        $this->key = config('services.exchangerate.key', '');
    }

    /**
     * Get latest exchange rates for a source currency.
     *
     * @param string $source
     * @param array<int, string> $currencies
     * @return array<string, mixed>
     */
    public function live(string $source = 'USD', array $currencies = []): array
    {
        $params = [
            'access_key' => $this->key,
            'source'     => $source,
        ];
        if (!empty($currencies)) {
            $params['currencies'] = implode(',', $currencies);
        }

        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->get("{$this->baseUrl}/live", $params);

        if ($response->failed()) {
            return ['success' => false, 'error' => 'API request failed'];
        }

        $data = $response->json();

        if (empty($data['success'])) {
            return ['success' => false, 'error' => $data['error']['info'] ?? 'API returned no success'];
        }

        $rates = [];
        foreach ($data['quotes'] ?? [] as $quoteKey => $value) {
            $target = substr($quoteKey, strlen($source));
            $rates[$target] = $value;
        }

        return [
            'success' => true,
            'source'  => $data['source'] ?? $source,
            'date'    => now()->toDateString(),
            'rates'   => $rates,
        ];
    }

    /**
     * Get historical exchange rate for a source currency and date.
     *
     * @param string $date   YYYY-MM-DD
     * @param string $source
     * @param array<int, string> $currencies
     * @return array<string, mixed>
     */
    public function historical(string $date, string $source = 'USD', array $currencies = []): array
    {
        $params = [
            'access_key' => $this->key,
            'date'       => $date,
            'source'     => $source,
        ];
        if (!empty($currencies)) {
            $params['currencies'] = implode(',', $currencies);
        }

        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->get("{$this->baseUrl}/historical", $params);

        if ($response->failed()) {
            return ['success' => false, 'error' => 'API request failed'];
        }

        $data = $response->json();

        if (empty($data['success'])) {
            return ['success' => false, 'error' => $data['error']['info'] ?? 'API returned no success'];
        }

        $rates = [];
        foreach ($data['quotes'] ?? [] as $quoteKey => $value) {
            $target = substr($quoteKey, strlen($source));
            $rates[$target] = $value;
        }

        return [
            'success' => true,
            'source'  => $data['source'] ?? $source,
            'date'    => $data['date'] ?? $date,
            'rates'   => $rates,
        ];
    }

    /**
     * Convert from one currency to another.
     *
     * @param string $from
     * @param string $to
     * @param float $amount
     * @return array<string, mixed>
     */
    public function convert(string $from, string $to, float $amount = 1): array
    {
        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->get("{$this->baseUrl}/convert", [
                'access_key' => $this->key,
                'from'       => $from,
                'to'         => $to,
                'amount'     => $amount,
            ]);

        if ($response->failed()) {
            return ['success' => false, 'error' => 'API request failed'];
        }

        $data = $response->json();

        if (empty($data['success'])) {
            return ['success' => false, 'error' => $data['error']['info'] ?? 'API returned no success'];
        }

        return [
            'success' => true,
            'rate'    => $data['info']['quote'] ?? null,
            'date'    => now()->toDateString(),
        ];
    }
}

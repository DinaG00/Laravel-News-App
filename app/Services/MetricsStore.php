<?php

namespace App\Services;

class MetricsStore
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/metrics.json');
    }

    public function incCounter(string $name, array $labels = []): void
    {
        $this->mutate(function (&$data) use ($name, $labels) {
            $key = $name . '|' . $this->encodeLabels($labels);
            $data['counters'][$key] = [
                'name' => $name,
                'labels' => $labels,
                'value' => (float) ($data['counters'][$key]['value'] ?? 0) + 1,
            ];
        });
    }

    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        $buckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];

        $this->mutate(function (&$data) use ($name, $value, $labels, $buckets) {
            $key = $name . '|' . $this->encodeLabels($labels);
            if (! isset($data['histograms'][$key])) {
                $data['histograms'][$key] = [
                    'name' => $name,
                    'labels' => $labels,
                    'buckets' => array_fill_keys(array_map('strval', $buckets), 0),
                    'sum' => 0,
                    'count' => 0,
                ];
            }

            $data['histograms'][$key]['sum'] += $value;
            $data['histograms'][$key]['count']++;
            foreach ($buckets as $b) {
                $bStr = (string) $b;
                if ($value <= $b) {
                    $data['histograms'][$key]['buckets'][$bStr]++;
                }
            }
        });
    }

    public function setGauge(string $name, float $value, array $labels = []): void
    {
        $this->mutate(function (&$data) use ($name, $value, $labels) {
            $key = $name . '|' . $this->encodeLabels($labels);
            $data['gauges'][$key] = [
                'name' => $name,
                'labels' => $labels,
                'value' => $value,
            ];
        });
    }

    public function getCounters(): array
    {
        return $this->read()['counters'] ?? [];
    }

    public function getHistograms(): array
    {
        return $this->read()['histograms'] ?? [];
    }

    public function getGauges(): array
    {
        return $this->read()['gauges'] ?? [];
    }

    private function read(): array
    {
        if (! file_exists($this->path)) {
            return ['counters' => [], 'histograms' => [], 'gauges' => []];
        }

        $fp = fopen($this->path, 'r');
        if (! $fp) {
            return ['counters' => [], 'histograms' => [], 'gauges' => []];
        }
        flock($fp, LOCK_SH);
        $raw = file_get_contents($this->path);
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($raw, true) ?: ['counters' => [], 'histograms' => [], 'gauges' => []];
    }

    private function mutate(callable $callback): void
    {
        $dir = dirname($this->path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fp = fopen($this->path, 'c+');
        if (! $fp) {
            return;
        }

        flock($fp, LOCK_EX);

        $raw = stream_get_contents($fp);
        $data = json_decode($raw, true) ?: ['counters' => [], 'histograms' => [], 'gauges' => []];

        $callback($data);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);

        flock($fp, LOCK_UN);
        fclose($fp);
    }

    private function encodeLabels(array $labels): string
    {
        ksort($labels);
        return http_build_query($labels);
    }
}

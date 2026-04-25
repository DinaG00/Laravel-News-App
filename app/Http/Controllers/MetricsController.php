<?php

namespace App\Http\Controllers;

use App\Services\MetricsStore;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function __invoke(): Response
    {
        $store = new MetricsStore();
        $lines = [];

        // --- HTTP Request Counter ---
        $lines[] = '# HELP http_requests_total Total HTTP requests processed.';
        $lines[] = '# TYPE http_requests_total counter';
        foreach ($store->getCounters() as $c) {
            $lines[] = $this->formatMetric($c['name'], $c['labels'], $c['value']);
        }

        // --- HTTP Request Duration Histogram ---
        $lines[] = '# HELP http_request_duration_seconds HTTP request latencies in seconds.';
        $lines[] = '# TYPE http_request_duration_seconds histogram';
        foreach ($store->getHistograms() as $h) {
            $name = $h['name'];
            $labels = $h['labels'];

            foreach ($h['buckets'] as $bucket => $count) {
                $leLabels = array_merge($labels, ['le' => (string) $bucket]);
                $lines[] = $this->formatMetric($name . '_bucket', $leLabels, $count);
            }
            $infLabels = array_merge($labels, ['le' => '+Inf']);
            $lines[] = $this->formatMetric($name . '_bucket', $infLabels, $h['count']);
            $lines[] = $this->formatMetric($name . '_sum', $labels, $h['sum']);
            $lines[] = $this->formatMetric($name . '_count', $labels, $h['count']);
        }

        // --- Custom gauges ---
        $lines[] = '# HELP php_memory_usage_bytes Current PHP memory usage in bytes.';
        $lines[] = '# TYPE php_memory_usage_bytes gauge';
        $lines[] = 'php_memory_usage_bytes ' . memory_get_usage(true);

        $lines[] = '# HELP php_peak_memory_usage_bytes Peak PHP memory usage in bytes.';
        $lines[] = '# TYPE php_peak_memory_usage_bytes gauge';
        $lines[] = 'php_peak_memory_usage_bytes ' . memory_get_peak_usage(true);

        $dbConnections = 0;
        try {
            $result = DB::select('SHOW STATUS WHERE Variable_name = "Threads_connected"');
            $dbConnections = (float) ($result[0]->Value ?? 0);
        } catch (\Throwable $e) {
            // ignore
        }

        $lines[] = '# HELP db_connections_active Number of active DB connections.';
        $lines[] = '# TYPE db_connections_active gauge';
        $lines[] = 'db_connections_active ' . $dbConnections;

        // queue depth - if you use database queue driver
        $queueDepth = 0;
        try {
            $result = DB::table('jobs')->count('id');
            $queueDepth = $result;
        } catch (\Throwable $e) {
            // ignore if jobs table doesn't exist
        }

        $lines[] = '# HELP queue_jobs_pending Number of pending queue jobs.';
        $lines[] = '# TYPE queue_jobs_pending gauge';
        $lines[] = 'queue_jobs_pending ' . $queueDepth;

        $body = implode("\n", $lines) . "\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function formatMetric(string $name, array $labels, float $value): string
    {
        $labelStr = '';
        if (! empty($labels)) {
            ksort($labels);
            $pairs = [];
            foreach ($labels as $k => $v) {
                $pairs[] = sprintf('%s="%s"', $k, addslashes((string) $v));
            }
            $labelStr = '{' . implode(',', $pairs) . '}';
        }
        return sprintf('%s%s %s', $name, $labelStr, $value);
    }
}

<?php

namespace App\Http\Middleware;

use App\Services\MetricsStore;
use Closure;
use Illuminate\Http\Request;

class CollectMetrics
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;

        $path = $request->path();
        if ($path === 'metrics') {
            return $response;
        }

        $status = (string) $response->getStatusCode();
        $route = $request->route()?->getName() ?? 'unnamed';

        $store = new MetricsStore();

        $store->incCounter('http_requests_total', ['method' => $request->method(), 'status' => $status]);
        $store->observeHistogram('http_request_duration_seconds', $duration, ['method' => $request->method(), 'route' => $route]);

        return $response;
    }
}

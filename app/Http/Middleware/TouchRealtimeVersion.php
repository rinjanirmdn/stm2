<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TouchRealtimeVersion
{
    private const VERSION_CACHE_KEY = 'st_realtime_version';
    private const THROTTLE_MS = 1000;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTouch($request, $response)) {
            $nowMs = (int) floor(microtime(true) * 1000);
            $last = (int) Cache::get(self::VERSION_CACHE_KEY, 0);

            if (($nowMs - $last) >= self::THROTTLE_MS) {
                Cache::forever(self::VERSION_CACHE_KEY, (string) $nowMs);
            }
        }

        return $response;
    }

    private function shouldTouch(Request $request, Response $response): bool
    {
        $method = strtoupper((string) $request->getMethod());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        return $response->getStatusCode() < 500;
    }
}

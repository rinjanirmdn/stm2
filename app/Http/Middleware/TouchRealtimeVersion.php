<?php

namespace App\Http\Middleware;

use App\Events\SlotDataChanged;
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

                // Broadcast data change via WebSocket (replaces client-side polling)
                try {
                    broadcast(new SlotDataChanged(
                        type: $this->guessType($request),
                        action: $this->guessAction($request),
                    ));
                } catch (\Throwable $e) {
                    // Don't let broadcast failures break the request
                }
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

    /**
     * Guess the entity type from the request URL.
     */
    private function guessType(Request $request): string
    {
        $path = strtolower($request->path());

        if (str_contains($path, 'booking')) {
            return 'booking';
        }
        if (str_contains($path, 'unplanned')) {
            return 'unplanned';
        }
        if (str_contains($path, 'slot')) {
            return 'slot';
        }
        if (str_contains($path, 'gate')) {
            return 'gate';
        }
        if (str_contains($path, 'user')) {
            return 'user';
        }

        return 'slot';
    }

    /**
     * Guess the action from the request URL.
     */
    private function guessAction(Request $request): string
    {
        $path = strtolower($request->path());

        if (str_contains($path, 'create') || str_contains($path, 'store')) {
            return 'created';
        }
        if (str_contains($path, 'delete')) {
            return 'deleted';
        }
        if (str_contains($path, 'approve')) {
            return 'approved';
        }
        if (str_contains($path, 'reject')) {
            return 'rejected';
        }
        if (str_contains($path, 'cancel')) {
            return 'cancelled';
        }

        return 'updated';
    }
}

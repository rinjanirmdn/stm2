<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class DynamicBaseUrlMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $scheme = $request->isSecure() ? 'https' : 'http';
        $host = $request->getHost();
        $port = $request->getPort();

        $defaultPort = $scheme === 'https' ? 443 : 80;
        $rootUrl = $scheme.'://'.$host.($port !== $defaultPort ? ':'.$port : '');

        config(['app.url' => $rootUrl]);
        URL::forceRootUrl($rootUrl);
        URL::forceScheme($scheme);

        $publicHotFile = public_path('hot');

        if (is_file($publicHotFile)) {
            $raw = trim((string) @file_get_contents($publicHotFile));

            $vitePort = 5173;
            $parsed = $raw !== '' ? parse_url($raw) : null;
            if (is_array($parsed) && isset($parsed['port'])) {
                $vitePort = (int) $parsed['port'];
            }

            $dynamicViteBase = $scheme.'://'.$host.':'.$vitePort;
            $dynamicHotFile = storage_path('framework/vite-hot-'.sha1($dynamicViteBase));

            @file_put_contents($dynamicHotFile, $dynamicViteBase);
            Vite::useHotFile($dynamicHotFile);
        }

        return $next($request);
    }
}

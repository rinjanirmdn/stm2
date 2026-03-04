<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorPortalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || empty($user->vendor_code)) {
            return redirect()->route('dashboard')->with('error', 'You are not authorized to access the vendor portal.');
        }

        return $next($request);
    }
}

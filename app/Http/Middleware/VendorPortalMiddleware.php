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

        if (! $user || (empty($user->vendor_code) && ! $user->is_internal_vendor)) {
            abort(403, 'You are not authorized to access the vendor portal (Vendor Code is required).');
        }

        return $next($request);
    }
}

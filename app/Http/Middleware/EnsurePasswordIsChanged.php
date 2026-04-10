<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            if (! $request->routeIs('password.force-change') && ! $request->routeIs('password.force-change.store') && ! $request->routeIs('logout')) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}

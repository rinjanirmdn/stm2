<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Parse allowed roles (support both comma and pipe)
        $roles = is_array($role)
            ? $role
            : explode('|', str_replace(',', '|', $role));

        $hasRole = false;

        // 1. Primary: Check using Spatie's built-in method (#25)
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole($roles)) {
                $hasRole = true;
            }
        }

        // 2. Fallback: Check via DB role_id (only if Spatie check didn't match)
        if (!$hasRole && $user->role_id) {
            $userRoleName = \Illuminate\Support\Facades\DB::table('md_roles')
                ->where('id', $user->role_id)
                ->value('roles_name');

            $allowedRoles = array_map('strtolower', $roles);
            if ($userRoleName && in_array(strtolower($userRoleName), $allowedRoles)) {
                $hasRole = true;
            }
        }

        if (! $hasRole) {
            // Log details for debugging, but do NOT expose to user (#41)
            Log::warning('Role authorization failed', [
                'user_id' => $user->id,
                'required_roles' => $roles,
                'path' => $request->path(),
            ]);

            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}

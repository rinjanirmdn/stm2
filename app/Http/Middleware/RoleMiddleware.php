<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            
        $allowedRoles = array_map('strtolower', $roles);

        // Get User Role via DB (Custom Implementation)
        $userRoleName = null;
        if ($user->role_id) {
            $userRoleName = DB::table('roles')->where('id', $user->role_id)->value('roles_name');
        }

        // Check if user has role (Custom or Spatie)
        $hasRole = false;

        // 1. Check DB Role Name (Case Insensitive)
        if ($userRoleName && in_array(strtolower($userRoleName), $allowedRoles)) {
            $hasRole = true;
        }
        
        // 2. Fallback: Check using Spatie's built-in method (if available)
        if (!$hasRole && method_exists($user, 'hasRole')) {
            // Spatie checks are usually strict, but passing array handles generic checks
            if ($user->hasRole($roles)) {
                $hasRole = true;
            }
        }

        if (! $hasRole) {
            abort(403, 'You do not have the required role (' . implode(', ', $roles) . '). Your role is: ' . ($userRoleName ?? 'None'));
        }

        return $next($request);
    }
}

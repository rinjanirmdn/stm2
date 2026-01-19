<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Handle multiple roles separated by comma
        $allowedRoles = explode(',', $role);
        $userRole = $user->role ?? null;

        // Also check role_id for users who have been migrated
        $userRoleId = $user->role_id;
        if ($userRoleId) {
            $userRoleName = DB::table('roles')->where('id', $userRoleId)->value('roles_name');
            if ($userRoleName) {
                $userRole = $userRoleName;
            }
        }

        if (!in_array($userRole, $allowedRoles)) {
            abort(403);
        }

        return $next($request);
    }
}

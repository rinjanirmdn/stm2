<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string) $credentials['login']);
        $password = $credentials['password'];
        $loginLower = strtolower($login);

        // Check if user is permanently locked in database
        $userRecord = DB::table('md_users')
            ->where(function ($q) use ($loginLower) {
                $q->whereRaw('LOWER(username) = ?', [$loginLower])
                    ->orWhereRaw('LOWER(nik) = ?', [$loginLower])
                    ->orWhereRaw('LOWER(email) = ?', [$loginLower]);
            })
            ->select(['id', 'is_locked'])
            ->first();

        if ($userRecord && ! empty($userRecord->is_locked)) {
            return redirect()
                ->route('forgot-password')
                ->with('error', 'Your account is locked due to too many failed login attempts. Please request a password reset to regain access.')
                ->withInput(['login' => $login]);
        }

        // Check cache-based attempt counter (for tracking attempts before lock)
        $attemptsKey = 'login_attempts_'.$loginLower;
        $attempts = Cache::get($attemptsKey, 0);

        $fields = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? ['email', 'username', 'nik']
            : ['username', 'nik', 'email'];

        $authed = false;
        foreach ($fields as $field) {
            if (Auth::attempt([$field => $login, 'password' => $password])) {
                $authed = true;
                break;
            }
        }

        if (! $authed) {
            // Increment failed attempts in cache
            $newAttempts = $attempts + 1;
            Cache::put($attemptsKey, $newAttempts, now()->addMinutes(30));

            $remainingAttempts = 3 - $newAttempts;
            $message = 'Invalid Email/NIK/username or password';

            if ($remainingAttempts > 0) {
                $message .= ". {$remainingAttempts} attempts remaining.";
            } else {
                // Lock permanently in database
                if ($userRecord) {
                    DB::table('md_users')
                        ->where('id', $userRecord->id)
                        ->update(['is_locked' => true]);
                }

                return redirect()
                    ->route('forgot-password')
                    ->with('error', 'Your account has been locked after too many failed login attempts. Please request a password reset to regain access.')
                    ->withInput(['login' => $login]);
            }

            return back()
                ->withErrors(['login' => $message])
                ->onlyInput('login');
        }

        // Clear failed attempts on successful login
        Cache::forget($attemptsKey);

        $request->session()->regenerate();

        // Enforce single device login: logout all other sessions for this user
        try {
            Auth::logoutOtherDevices($credentials['password']);
        } catch (\Throwable $e) {
            // Silently fail if password hash mismatch edge case
        }

        $user = $request->user();

        if ($user && $user->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['login' => 'Your account is inactive. Please contact the administrator.'])
                ->onlyInput('login');
        }

        if ($user) {
            try {
                $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
                $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');
                $roleId = (int) ($user->role_id ?? 0);

                if ($roleId > 0) {
                    DB::table($modelHasRolesTable)
                        ->where('model_type', 'App\\Models\\User')
                        ->where('model_id', (int) $user->id)
                        ->delete();

                    DB::table($modelHasRolesTable)->insert([
                        'role_id' => $roleId,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => (int) $user->id,
                    ]);

                    try {
                        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                    } catch (\Throwable $e) {
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        if ($user && $user->isVendor()) {
            return redirect()->intended(route('vendor.dashboard'));
        }

        // Security users → security dashboard (only if they don't have main dashboard access)
        if ($user && $user->can('security.dashboard') && ! $user->can('dashboard.view')) {
            return redirect()->intended(route('security.dashboard'));
        }

        if ($user && $user->can('dashboard.view')) {
            return redirect()->intended(route('dashboard'));
        }

        if ($user && $user->can('slots.index')) {
            return redirect()->route('slots.index');
        }

        return redirect()->route('profile');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

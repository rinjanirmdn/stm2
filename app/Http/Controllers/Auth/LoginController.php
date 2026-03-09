<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // Check for failed attempts
        $attemptsKey = 'login_attempts_' . strtolower($login);
        $attempts = Cache::get($attemptsKey, 0);

        // Lock user after 3 failed attempts
        if ($attempts >= 3) {
            return redirect()
                ->route('forgot-password')
                ->with('error', 'Your account is locked due to too many failed login attempts. Please request a password reset to regain access.')
                ->withInput(['login' => $login]);
        }

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
            // Increment failed attempts
            Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(30)); // Lock for 30 minutes

            $remainingAttempts = 3 - ($attempts + 1);
            $message = 'Invalid Email/NIK/username or password';

            if ($remainingAttempts > 0) {
                $message .= ". {$remainingAttempts} attempts remaining.";
            } else {
                // When the user just reached the lock threshold, redirect them straight
                // to the password reset request form instead of showing the login form.
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

                if (Schema::hasTable('md_users') && Schema::hasTable($rolesTable) && Schema::hasTable($modelHasRolesTable) && Schema::hasColumn('md_users', 'role_id')) {
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
                }
            } catch (\Throwable $e) {
            }
        }

        if ($user && $user->isVendor()) {
            return redirect()->intended(route('vendor.dashboard'));
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

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $login = $credentials['login'];
        $password = $credentials['password'];
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
            return back()
                ->withErrors(['login' => 'Invalid Email/NIK/username or password'])
                ->onlyInput('login');
        }

        $request->session()->regenerate();

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

                    if ($roleId <= 0) {
                        $roleStr = (string) ($user->role ?? '');
                        if ($roleStr !== '') {
                            $roleFilter = str_replace('_', ' ', strtolower($roleStr));
                            $roleId = (int) DB::table($rolesTable)
                                ->whereRaw('LOWER(roles_name) = ?', [$roleFilter])
                                ->value('id');

                            if ($roleId > 0) {
                                DB::table('md_users')->where('id', (int) $user->id)->update(['role_id' => $roleId]);
                                $user->role_id = $roleId;
                            }
                        }
                    }

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

        if ($user->hasRole('vendor')) {
            return redirect()->intended(route('vendor.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

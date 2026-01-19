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
            'nik' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['nik' => 'NIK atau password salah'])
                ->onlyInput('nik');
        }

        $request->session()->regenerate();

        $user = $request->user();
        if ($user && array_key_exists('is_active', $user->getAttributes()) && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['nik' => 'Akun Anda tidak aktif. Silakan hubungi administrator.'])
                ->onlyInput('nik');
        }

        if ($user) {
            try {
                $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
                $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');

                if (Schema::hasTable('users') && Schema::hasTable($rolesTable) && Schema::hasTable($modelHasRolesTable) && Schema::hasColumn('users', 'role_id')) {
                    $roleId = (int) ($user->role_id ?? 0);

                    if ($roleId <= 0) {
                        $roleStr = (string) ($user->role ?? '');
                        if ($roleStr !== '') {
                            $roleFilter = str_replace('_', ' ', strtolower($roleStr));
                            $roleId = (int) DB::table($rolesTable)
                                ->whereRaw('LOWER(roles_name) = ?', [$roleFilter])
                                ->value('id');

                            if ($roleId > 0) {
                                DB::table('users')->where('id', (int) $user->id)->update(['role_id' => $roleId]);
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

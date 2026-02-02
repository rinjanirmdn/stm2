<?php

namespace App\Http\Controllers;

use App\Services\UserRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRoleService $roleService
    ) {
    }
    public function index(Request $request)
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');
        // Get all filter parameters
        $q = trim((string) $request->query('q', ''));
        $nik = trim((string) $request->query('nik', ''));
        $full_name = trim((string) $request->query('full_name', ''));
        $role = trim((string) $request->query('role', ''));
        $is_active = trim((string) $request->query('is_active', ''));
        $status = trim((string) $request->query('status', ''));
        $active = trim((string) $request->query('active', ''));

        // Get sort parameters (supports multi-sort via sort[]/dir[])
        $rawSort = $request->query('sort', []);
        $rawDir = $request->query('dir', []);

        $sorts = is_array($rawSort) ? $rawSort : [trim((string) $rawSort)];
        $dirs = is_array($rawDir) ? $rawDir : [trim((string) $rawDir)];

        // Validate sort column
        $allowedSorts = ['id', 'email', 'name', 'role', 'created_at'];

        $sorts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $sorts), fn ($v) => $v !== ''));
        $dirs = array_values(array_map(function ($v) {
            $v = strtolower(trim((string) $v));
            return $v === 'desc' ? 'desc' : 'asc';
        }, $dirs));

        $validatedSorts = [];
        $validatedDirs = [];
        foreach ($sorts as $i => $s) {
            if (! in_array($s, $allowedSorts, true)) {
                continue;
            }
            $validatedSorts[] = $s;
            $validatedDirs[] = $dirs[$i] ?? 'asc';
        }
        $sorts = $validatedSorts;
        $dirs = $validatedDirs;

        // Backward-compatible single sort/dir values (used by view/JS)
        $sort = $sorts[0] ?? '';
        $dir = $dirs[0] ?? 'desc';

        $allowedRoles = ['admin', 'section_head', 'operator', 'vendor'];

        $usersQ = DB::table('md_users')
            ->leftJoin($modelHasRolesTable . ' as mhr', function ($join) {
                $join
                    ->on('mhr.model_id', '=', 'md_users.id')
                    ->where('mhr.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin($rolesTable . ' as r_spatie', 'r_spatie.id', '=', 'mhr.role_id')
            ->leftJoin($rolesTable . ' as r_user', 'r_user.id', '=', 'md_users.role_id')
            ->select([
                'md_users.id',
                'md_users.nik',
                'md_users.full_name',
                'md_users.role',
                'md_users.role_id',
                DB::raw('COALESCE(r_user.roles_name, r_spatie.roles_name) as role_name'),
                'md_users.is_active',
                'md_users.created_at',
                'md_users.updated_at',
            ]);

        // Apply individual column filters
        if ($nik !== '') {
            $usersQ->where('md_users.nik', 'like', '%' . $nik . '%');
        }

        if ($full_name !== '') {
            $usersQ->where('md_users.full_name', 'like', '%' . $full_name . '%');
        }

        if ($role !== '' && in_array($role, $allowedRoles, true)) {
            $roleFilter = str_replace('_', ' ', strtolower($role));
            $usersQ->where(function($q) use ($roleFilter) {
                $q->whereRaw('LOWER(r_user.roles_name) = ?', [$roleFilter])
                  ->orWhereRaw('LOWER(r_spatie.roles_name) = ?', [$roleFilter]);
            });
        } else {
            $role = '';
        }

        if ($is_active !== '') {
            $usersQ->where('md_users.is_active', $is_active === '1' ? 1 : 0);
        }

        // Legacy search filter (q parameter)
        if ($q !== '') {
            $like = '%' . $q . '%';
            $usersQ->where(function ($sub) use ($like) {
                $sub
                    ->where('md_users.nik', 'like', $like)
                    ->orWhere('md_users.full_name', 'like', $like);
            });
        }

        // Legacy query param: status=active|inactive.
        if ($status === 'active') {
            $usersQ->where('md_users.is_active', 1);
        } elseif ($status === 'inactive') {
            $usersQ->where('md_users.is_active', 0);
        } elseif ($active === '1') {
            // Backward compat if any existing link still uses active=1/0.
            $usersQ->where('md_users.is_active', 1);
            $status = 'active';
        } elseif ($active === '0') {
            $usersQ->where('md_users.is_active', 0);
            $status = 'inactive';
        } else {
            $status = '';
        }

        // Apply sorting
        if (count($sorts) > 0) {
            foreach ($sorts as $i => $s) {
                $d = $dirs[$i] ?? 'asc';
                if ($s === 'role') {
                    $usersQ->orderByRaw('COALESCE(r_user.roles_name, r_spatie.roles_name) ' . $d);
                } elseif ($s === 'name') {
                    $usersQ->orderBy('md_users.full_name', $d);
                } else {
                    $usersQ->orderBy('md_users.' . $s, $d);
                }
            }
            $usersQ->orderByDesc('md_users.created_at')->orderByDesc('md_users.id');
        } else {
            $usersQ
                ->orderByDesc('md_users.created_at')
                ->orderByDesc('md_users.id');
        }

        $users = $usersQ->get();

        return view('users.index', [
            'users' => $users,
            'q' => $q,
            'nik' => $nik,
            'full_name' => $full_name,
            'role' => $role,
            'is_active' => $is_active,
            'status' => $status,
            'sort' => $sort,
            'dir' => $dir,
            'sorts' => $sorts,
            'dirs' => $dirs,
        ]);
    }

    public function create(Request $request)
    {
        return view('users.create');
    }

    public function store(UserStoreRequest $request)
    {
        $validated = $request->validated();

        $nik = trim($validated['nik']);
        $email = trim($validated['email']);
        $name = trim($validated['name']);
        $role = $validated['role'];
        $vendorCode = isset($validated['vendor_code']) ? trim((string) $validated['vendor_code']) : '';
        $password = $validated['password'];

        // Convert role slug to proper name (admin -> Admin, section_head -> Section Head)
        $roleDisplayName = ucwords(str_replace('_', ' ', $role));

        // Get role ID
        $allRoles = $this->roleService->getAllRoles();
        $roleRecord = $allRoles->first(function ($r) use ($roleDisplayName) {
            return strtolower($r->roles_name) === strtolower($roleDisplayName);
        });
        $roleId = $roleRecord ? $roleRecord->id : null;

        if (!$roleId) {
            return back()->withInput()->with('error', 'Invalid role: ' . $roleDisplayName);
        }

        // Create user
        $userId = DB::table('md_users')->insertGetId([
            'nik' => $nik,
            'username' => $nik,
            'email' => $email,
            'full_name' => $name,
            'role' => $role,
            'role_id' => $roleId,
            'vendor_code' => $role === 'vendor' ? $vendorCode : null,
            'is_active' => true,
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign role using service with proper name
        if (!$this->roleService->assignRole($userId, $roleRecord->roles_name)) {
            return back()->withInput()->with('error', 'Failed to assign role');
        }

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function edit(Request $request, int $userId)
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');
        $user = DB::table('md_users')
            ->leftJoin($modelHasRolesTable . ' as mhr', function ($join) {
                $join
                    ->on('mhr.model_id', '=', 'md_users.id')
                    ->where('mhr.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin($rolesTable . ' as r_spatie', 'r_spatie.id', '=', 'mhr.role_id')
            ->leftJoin($rolesTable . ' as r_user', 'r_user.id', '=', 'md_users.role_id')
            ->where('md_users.id', $userId)
            ->selectRaw("
                md_users.id,
                md_users.nik,
                md_users.full_name,
                md_users.role,
                md_users.role_id,
                COALESCE(r_user.roles_name, r_spatie.roles_name) as role_name,
                LOWER(REPLACE(COALESCE(r_user.roles_name, r_spatie.roles_name), ' ', '_')) as role_slug,
                md_users.is_active
            ")
            ->first();

        if (! $user) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        return view('users.edit', [
            'editUser' => $user,
        ]);
    }

    public function update(UserUpdateRequest $request, int $userId)
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');

        $user = DB::table('md_users')
            ->where('id', $userId)
            ->select(['id'])
            ->first();

        if (! $user) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        $validated = $request->validated();

        $currentUserId = $request->user()->id ?? 0;
        
        $update = [
            'nik' => trim($validated['nik']),
            'username' => trim($validated['nik']),
            'email' => trim($validated['email']),
            'full_name' => trim($validated['name']),
            'role' => $validated['role'],
            'vendor_code' => $validated['role'] === 'vendor' ? trim((string) ($validated['vendor_code'] ?? '')) : null,
        ];

        $newRole = $validated['role'];
        $roleDisplayName = ucwords(str_replace('_', ' ', (string) $newRole));
        $allRoles = $this->roleService->getAllRoles();
        $roleRecord = $allRoles->first(function ($r) use ($roleDisplayName) {
            return strtolower((string) ($r->roles_name ?? '')) === strtolower($roleDisplayName);
        });
        $newRoleId = $roleRecord ? $roleRecord->id : null;
        $update['role_id'] = $newRoleId;

        $password = trim($request->input('password', ''));
        if ($password !== '') {
            $update['password'] = Hash::make($password);
        }

        DB::table('md_users')->where('id', $userId)->update($update);

        // Update role using service
        if ($newRoleId) {
            $this->roleService->removeRole($userId);
            $this->roleService->assignRole($userId, (string) $roleRecord->roles_name);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function toggle(Request $request, int $userId)
    {
        $user = DB::table('md_users')->where('id', $userId)->select(['id', 'is_active'])->first();
        if (!$user) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        $currentUserId = $request->user()->id ?? 0;
        $currentActive = !empty($user->is_active);

        // Check if user is trying to deactivate themselves
        if ($user->id === $currentUserId && $currentActive) {
            return redirect()->route('users.index')->with('error', 'You cannot deactivate your own account.');
        }

        // Check if user can be deactivated (not last admin)
        if ($currentActive && !$this->roleService->canDeactivateUser($userId)) {
            return redirect()->route('users.index')->with('error', 'Cannot deactivate the last admin user.');
        }

        $newActive = $currentActive ? 0 : 1;
        DB::table('md_users')->where('id', $userId)->update(['is_active' => $newActive]);

        return redirect()->route('users.index')->with('success', 'User status updated');
    }

    public function destroy(Request $request, int $userId)
    {
        $user = DB::table('md_users')->where('id', $userId)->select(['id', 'role'])->first();
        if (!$user) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        $currentUserId = $request->user()->id ?? 0;
        if ($user->id === $currentUserId) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        // Check if user is admin and if it's the last admin
        if (($user->role ?? '') === 'admin') {
            $remainingAdmins = DB::table('md_users')
                ->where('role', 'admin')
                ->where('id', '<>', $userId)
                ->count();
            if ($remainingAdmins === 0) {
                return redirect()->route('users.index')->with('error', 'You cannot delete the last admin user.');
            }
        }

        DB::table('md_users')->where('id', $userId)->delete();
        return redirect()->route('users.index')->with('success', 'User deleted permanently');
    }
}

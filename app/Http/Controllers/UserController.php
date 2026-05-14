<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Permission;
use App\Models\User;
use App\Services\SapPoService;
use App\Services\SlotService;
use App\Services\UserRoleService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRoleService $roleService,
        private readonly SapPoService $sapPoService,
        private readonly SlotService $slotService
    ) {}

    public function index(Request $request)
    {
        $rolesTable = (string) (config('permission.table_names.roles') ?? 'roles');
        $modelHasRolesTable = (string) (config('permission.table_names.model_has_roles') ?? 'model_has_roles');

        $q = trim((string) $request->query('q', ''));
        $nik = trim((string) $request->query('nik', ''));
        $full_name = trim((string) $request->query('full_name', ''));
        $role = trim((string) $request->query('role', ''));
        $is_active = trim((string) $request->query('is_active', ''));
        $status = trim((string) $request->query('status', ''));
        $active = trim((string) $request->query('active', ''));

        $rawSort = $request->query('sort', []);
        $rawDir = $request->query('dir', []);
        $sorts = is_array($rawSort) ? $rawSort : [trim((string) $rawSort)];
        $dirs = is_array($rawDir) ? $rawDir : [trim((string) $rawDir)];

        $allowedSorts = ['id_users', 'nik', 'email', 'name', 'role', 'created_at'];
        $sorts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $sorts), fn ($v) => $v !== ''));
        $dirs = array_values(array_map(fn ($v) => strtolower(trim((string) $v)) === 'desc' ? 'desc' : 'asc', $dirs));

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
        $sort = $sorts[0] ?? '';
        $dir = $dirs[0] ?? 'desc';

        $allowedRoles = ['admin', 'section_head', 'operator', 'admin_wh', 'vendor', 'security', 'super_account', 'display_account'];

        $usersQ = User::query()
            ->leftJoin($modelHasRolesTable.' as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'md_users.id_users')
                    ->where('mhr.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin($rolesTable.' as r_spatie', 'r_spatie.id_roles', '=', 'mhr.role_id')
            ->leftJoin($rolesTable.' as r_user', 'r_user.id_roles', '=', 'md_users.role_id')
            ->select([
                'md_users.*',
                DB::raw('COALESCE(r_user.roles_name, r_spatie.roles_name) as role_name'),
            ]);

        if ($nik !== '') {
            $usersQ->where('md_users.nik', 'like', '%'.$nik.'%');
        }
        if ($full_name !== '') {
            $usersQ->where('md_users.full_name', 'like', '%'.$full_name.'%');
        }

        if ($role !== '' && in_array($role, $allowedRoles, true)) {
            $roleFilter = str_replace('_', ' ', strtolower($role));
            $usersQ->where(function ($q) use ($roleFilter) {
                $q->whereRaw('LOWER(r_user.roles_name) = ?', [$roleFilter])
                    ->orWhereRaw('LOWER(r_spatie.roles_name) = ?', [$roleFilter]);
            });
        }

        if ($is_active !== '') {
            $usersQ->where('md_users.is_active', $is_active === '1' ? 1 : 0);
        }

        if ($q !== '') {
            $qEscaped = str_replace(['%', '_'], ['\%', '\_'], $q);
            $like = '%'.strtolower($qEscaped).'%';
            $usersQ->where(function ($sub) use ($like) {
                $sub->whereRaw('LOWER(md_users.nik) like ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(md_users.full_name, \'\')) like ?', [$like]);
            });
        }

        if ($status === 'active' || $active === '1') {
            $usersQ->where('md_users.is_active', 1);
        } elseif ($status === 'inactive' || $active === '0') {
            $usersQ->where('md_users.is_active', 0);
        }

        if (count($sorts) > 0) {
            foreach ($sorts as $i => $s) {
                $d = $dirs[$i] ?? 'asc';
                if ($s === 'role') {
                    $usersQ->orderByRaw('COALESCE(r_user.roles_name, r_spatie.roles_name) '.$d);
                } elseif ($s === 'name') {
                    $usersQ->orderBy('md_users.full_name', $d);
                } else {
                    $usersQ->orderBy('md_users.'.$s, $d);
                }
            }
        }
        $usersQ->orderByDesc('md_users.created_at')->orderByDesc('md_users.id_users');

        $usersCacheKey = 'users:index:data:'.sha1(json_encode(['uid' => Auth::id(), 'query' => $request->query(), 'version' => (string) Cache::get('st_realtime_version', '0')]));
        $users = Cache::remember($usersCacheKey, now()->addSeconds(10), fn () => $usersQ->limit(200)->get());

        return view('users.index', compact('users', 'q', 'nik', 'full_name', 'role', 'is_active', 'status', 'sort', 'dir', 'sorts', 'dirs'));
    }

    public function create(Request $request)
    {
        return view('users.create');
    }

    public function store(UserStoreRequest $request)
    {
        try {
            $validated = $request->validated();
            $nik = trim($validated['nik']);
            $email = trim($validated['email']);
            $name = trim($validated['name']);
            $role = $validated['role'];
            $vendorCode = isset($validated['vendor_code']) ? trim((string) $validated['vendor_code']) : '';
            $password = $validated['password'];

            $roleDisplayName = ucwords(str_replace('_', ' ', $role));
            $allRoles = $this->roleService->getAllRoles();

            $roleRecord = $allRoles->first(fn ($r) => strtolower($r->roles_name) === strtolower($roleDisplayName));
            if (! $roleRecord) {
                $roleRecord = $allRoles->first(function ($r) use ($role, $roleDisplayName) {
                    $rn = strtolower(str_replace([' ', '_', '-'], '', $r->roles_name));

                    return $rn === strtolower(str_replace([' ', '_', '-'], '', $role)) || $rn === strtolower(str_replace([' ', '_', '-'], '', $roleDisplayName));
                });
            }

            if (! $roleRecord) {
                return back()->withInput()->with('error', 'Invalid role: '.$roleDisplayName);
            }
            $roleId = $roleRecord->id_roles;

            $existing = User::withTrashed()
                ->where(function ($q) use ($nik, $email) {
                    $q->where('nik', $nik)->orWhere('email', $email);
                })
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                    $existing->update([
                        'full_name' => $name,
                        'role_id' => $roleId,
                        'vendor_code' => $role === 'vendor' ? $vendorCode : null,
                        'company_name' => $role === 'vendor' ? trim((string) ($request->input('company_name', ''))) ?: null : null,
                        'is_internal_vendor' => $role === 'vendor' ? (bool) ($validated['is_internal_vendor'] ?? false) : false,
                        'is_active' => true,
                        'must_change_password' => true,
                        'password' => Hash::make($password),
                        'password_changed_at' => now(),
                    ]);

                    $this->roleService->removeRole($existing->id_users);
                    $this->roleService->assignRole($existing->id_users, $roleRecord->roles_name);

                    $this->slotService->logActivity(
                        null,
                        'insert',
                        "Restored User: {$name} ({$nik})",
                        null,
                        $existing->toArray(),
                        feature: 'User Management'
                    );

                    return redirect()->route('users.index')->with('success', "User with NIK/Email '{$nik}/{$email}' was restored from deleted records.");
                }

                return back()->withInput()->with('error', 'User already exists.');
            }

            $user = User::create([
                'nik' => $nik,
                'username' => $nik,
                'email' => $email,
                'full_name' => $name,
                'role_id' => $roleId,
                'vendor_code' => $role === 'vendor' ? $vendorCode : null,
                'company_name' => $role === 'vendor' ? trim((string) ($request->input('company_name', ''))) ?: null : null,
                'is_internal_vendor' => $role === 'vendor' ? (bool) ($validated['is_internal_vendor'] ?? false) : false,
                'is_active' => true,
                'must_change_password' => true,
                'password' => Hash::make($password),
                'password_changed_at' => now(),
            ]);

            $this->roleService->assignRole($user->id_users, $roleRecord->roles_name);

            $this->slotService->logActivity(
                null,
                'insert',
                "Created User: {$name} ({$nik})",
                null,
                $user->toArray(),
                feature: 'User Management'
            );

            if ($role === 'vendor' && $vendorCode !== '' && ! ($validated['is_internal_vendor'] ?? false)) {
                try {
                    $this->sapPoService->getVendorNameByCode($vendorCode);
                } catch (\Throwable $e) {
                }
            }

            return redirect()->route('users.index')->with('success', 'User created successfully');
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->with('error', 'Gagal: NIK atau Email sudah terdaftar dalam sistem.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function edit(Request $request, int $userId)
    {
        $userModel = User::find($userId);
        if (! $userModel) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        $currentUser = $request->user();
        $canManagePermissions = $currentUser && $currentUser->hasRole('Admin');

        $allPermissions = [];
        if ($canManagePermissions) {
            $curated = [
                'dashboard.view', 'dashboard.range_filter', 'slots.index', 'slots.create', 'slots.store', 'slots.show', 'slots.edit', 'slots.update', 'slots.delete',
                'slots.arrival', 'slots.arrival.store', 'slots.start', 'slots.start.store', 'slots.complete', 'slots.complete.store', 'slots.cancel', 'slots.cancel.store',
                'gates.index', 'gates.toggle', 'gates.availability', 'bookings.index', 'bookings.show', 'bookings.approve', 'bookings.reject', 'bookings.reschedule',
                'unplanned.index', 'unplanned.create', 'unplanned.store', 'unplanned.edit', 'unplanned.update', 'unplanned.delete', 'unplanned.show', 'unplanned.start',
                'unplanned.start.store', 'unplanned.complete', 'unplanned.complete.store', 'reports.transactions', 'reports.export', 'trucks.index', 'trucks.create',
                'trucks.store', 'trucks.edit', 'trucks.update', 'trucks.delete', 'master.transporters.index', 'master.bp.index', 'users.index', 'users.create',
                'users.store', 'users.edit', 'users.update', 'users.delete', 'users.toggle', 'logs.index', 'logs.filter', 'vendor.dashboard', 'vendor.bookings.index',
                'vendor.bookings.create', 'vendor.bookings.store', 'vendor.bookings.show', 'vendor.bookings.cancel', 'vendor.bookings.ticket', 'vendor.availability',
            ];
            $allPermissions = Permission::whereIn('perm_name', $curated)->orderBy('perm_name')->pluck('perm_name')->map(fn ($v) => (string) $v)->values()->all();
        }

        $rolePermissions = $userModel->getPermissionsViaRoles()->pluck('perm_name')->all();
        $directPermissions = $userModel->getDirectPermissions()->pluck('perm_name')->all();

        return view('users.edit', [
            'editUser' => $userModel,
            'allPermissions' => $allPermissions,
            'rolePermissions' => $rolePermissions,
            'directPermissions' => $directPermissions,
            'canManagePermissions' => $canManagePermissions,
        ]);
    }

    public function update(UserUpdateRequest $request, int $userId)
    {
        $userModel = User::find($userId);
        if (! $userModel) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        $validated = $request->validated();
        try {
            $oldData = $userModel->toArray();
            $update = [
                'nik' => trim($validated['nik']),
                'username' => trim($validated['nik']),
                'email' => trim($validated['email']),
                'full_name' => trim($validated['name']),
                'vendor_code' => $validated['role'] === 'vendor' ? trim((string) ($validated['vendor_code'] ?? '')) : null,
                'company_name' => $validated['role'] === 'vendor' ? trim((string) ($request->input('company_name', ''))) ?: null : null,
                'is_internal_vendor' => $validated['role'] === 'vendor' ? (bool) ($validated['is_internal_vendor'] ?? false) : false,
            ];

            $newRole = $validated['role'];
            $roleDisplayName = ucwords(str_replace('_', ' ', (string) $newRole));
            $allRoles = $this->roleService->getAllRoles();
            $roleRecord = $allRoles->first(fn ($r) => strtolower($r->roles_name) === strtolower($roleDisplayName));

            if (! $roleRecord) {
                $roleRecord = $allRoles->first(function ($r) use ($newRole, $roleDisplayName) {
                    $rn = strtolower(str_replace([' ', '_', '-'], '', $r->roles_name));

                    return $rn === strtolower(str_replace([' ', '_', '-'], '', $newRole)) || $rn === strtolower(str_replace([' ', '_', '-'], '', $roleDisplayName));
                });
            }

            if ($roleRecord) {
                $update['role_id'] = $roleRecord->id_roles;
            }

            $password = trim($request->input('password', ''));
            if ($password !== '') {
                $update['password'] = Hash::make($password);
                $update['must_change_password'] = true;
                $update['is_locked'] = false;
                $update['password_changed_at'] = now();
            }

            $userModel->update($update);

            $this->slotService->logActivity(
                null,
                'update',
                "Updated User: {$userModel->full_name} ({$userModel->nik})",
                $oldData,
                $userModel->toArray(),
                feature: 'User Management'
            );

            if (($update['vendor_code'] ?? '') !== '' && ! ($update['is_internal_vendor'] ?? false)) {
                Cache::forget('vendor_company_'.$update['vendor_code']);
                try {
                    $this->sapPoService->getVendorNameByCode($update['vendor_code']);
                } catch (\Throwable $e) {
                }
            }

            $loginIdentifiers = array_unique([strtolower($userModel->nik), strtolower($userModel->email), strtolower($userModel->username)]);
            foreach ($loginIdentifiers as $identifier) {
                if ($identifier !== '') {
                    Cache::forget('login_attempts_'.$identifier);
                }
            }

            if ($roleRecord) {
                $this->roleService->removeRole($userId);
                $this->roleService->assignRole($userId, $roleRecord->roles_name);
            }

            $actor = $request->user();
            if ($actor && $actor->hasRole('Admin') && (int) $actor->id_users !== (int) $userId) {
                $permissions = $request->input('permissions', []);
                $userModel->syncPermissions($permissions);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            return redirect()->route('users.index')->with('success', 'User updated successfully');
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->with('error', 'Gagal: NIK atau Email sudah digunakan oleh user lain.');
        }
    }

    public function toggle(Request $request, int $userId)
    {
        $user = User::find($userId);
        if (! $user) {
            return $request->expectsJson() ? response()->json(['success' => false, 'message' => 'User not found'], 404) : redirect()->route('users.index')->with('error', 'User not found');
        }

        $currentUserId = (int) ($request->user()->id_users ?? 0);
        if ((int) $user->id_users === $currentUserId && $user->is_active) {
            return $request->expectsJson() ? response()->json(['success' => false, 'message' => 'You cannot deactivate your own account.'], 403) : redirect()->route('users.index')->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->is_active && ! $this->roleService->canDeactivateUser($userId)) {
            return $request->expectsJson() ? response()->json(['success' => false, 'message' => 'Cannot deactivate the last admin user.'], 403) : redirect()->route('users.index')->with('error', 'Cannot deactivate the last admin user.');
        }

        $oldValue = $user->is_active;
        $user->update(['is_active' => ! $user->is_active]);

        $this->slotService->logActivity(
            null,
            'update',
            ($user->is_active ? 'Activated' : 'Deactivated')." user {$user->full_name}",
            ['is_active' => $oldValue],
            ['is_active' => $user->is_active],
            feature: 'User Management'
        );

        return $request->expectsJson() ? response()->json(['success' => true, 'is_active' => (bool) $user->is_active, 'message' => ($user->is_active ? 'Activated' : 'Deactivated').' user '.$user->full_name]) : redirect()->route('users.index')->with('success', 'User status updated');
    }

    public function destroy(Request $request, int $userId)
    {
        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('users.index')->with('error', 'User not found');
        }

        if ((int) $user->id_users === (int) ($request->user()->id_users ?? 0)) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('Admin')) {
            $remainingAdmins = User::role('Admin')->where('id_users', '<>', $userId)->count();
            if ($remainingAdmins === 0) {
                return redirect()->route('users.index')->with('error', 'You cannot delete the last admin user.');
            }
        }

        $oldData = $user->toArray();
        $name = $user->full_name;
        $user->delete();

        $this->slotService->logActivity(
            null,
            'delete',
            "Deleted User: {$name}",
            $oldData,
            null,
            feature: 'User Management'
        );

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}

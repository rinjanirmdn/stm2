@extends('layouts.app')

@section('title', 'Users - e-Docking Control System')
@section('page_title', 'Users')

@section('content')
    <div class="st-card st-mb-12">
        <form method="GET" action="{{ route('users.index') }}">
            <div class="st-p-12">
                <div class="st-form-row st-gap-4 st-align-end">
                    <div class="st-form-field st-maxw-200">
                        <label class="st-label">Search</label>
                        <input type="text" name="q" class="st-input" placeholder="Email or name" value="{{ $q ?? '' }}">
                    </div>
                    <div class="st-form-field st-minw-80 st-flex st-flex-0 st-justify-end st-gap-8">
                        <a href="{{ route('users.index') }}" class="st-btn st-btn--outline-primary">Reset</a>
                        @can('users.create')
                            <button type="button" id="btnOpenAddUser" class="st-btn st-btn--primary">Add User</button>
                        @endcan
                    </div>
                </div>
            </div>
        </form>
    </div>

    <section class="st-row st-flex-1">
        <div class="st-col-12 st-flex st-flex-1 st-flex-col">
            <div class="st-card st-card--fill">
                <form method="GET" id="user-filter-form" data-multi-sort="1" action="{{ route('users.index') }}">
                    @php
                        $sortsArr = isset($sorts) && is_array($sorts) ? $sorts : [];
                        $dirsArr = isset($dirs) && is_array($dirs) ? $dirs : [];
                    @endphp
                    @foreach ($sortsArr as $i => $s)
                        @php $d = $dirsArr[$i] ?? 'asc'; @endphp
                        <input type="hidden" name="sort[]" value="{{ $s }}">
                        <input type="hidden" name="dir[]" value="{{ $d }}">
                    @endforeach
                    <div class="st-table-wrapper st-table-wrapper--minh-400">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th class="st-table-col-60">#</th>
                                    <th class="st-table-col-180">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">NIK</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger"
                                                    data-sort="nik" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger"
                                                    data-filter="nik" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220"
                                                data-filter-panel="nik">
                                                <div class="st-font-semibold st-mb-6">NIK Filter</div>
                                                <input type="text" name="nik" form="user-filter-form" class="st-input"
                                                    placeholder="Search NIK..." value="{{ $nik ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button"
                                                        class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear"
                                                        data-filter="nik">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="nik">
                                                <div class="st-panel__title">Sort NIK</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="nik" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="nik" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Full Name</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger"
                                                    data-sort="name" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger"
                                                    data-filter="full_name" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220"
                                                data-filter-panel="full_name">
                                                <div class="st-font-semibold st-mb-6">Full Name Filter</div>
                                                <input type="text" name="full_name" form="user-filter-form" class="st-input"
                                                    placeholder="Search name..." value="{{ $full_name ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button"
                                                        class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear"
                                                        data-filter="full_name">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="name">
                                                <div class="st-panel__title">Sort Name</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="name" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="name" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="st-table-col-180 st-th-center">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Email</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger"
                                                    data-sort="email" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger"
                                                    data-filter="email" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220"
                                                data-filter-panel="email">
                                                <div class="st-font-semibold st-mb-6">Email Filter</div>
                                                <input type="text" name="email" form="user-filter-form" class="st-input"
                                                    placeholder="Search Email..." value="{{ $email ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button"
                                                        class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear"
                                                        data-filter="email">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="email">
                                                <div class="st-panel__title">Sort Email</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="email" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="email" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="st-table-col-140 st-th-center">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Role</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger"
                                                    data-sort="role" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger"
                                                    data-filter="role" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220"
                                                data-filter-panel="role">
                                                <div class="st-font-semibold st-mb-6">Role Filter</div>
                                                <select name="role" form="user-filter-form" class="st-select">
                                                    <option value="">All Roles</option>
                                                    <option value="admin" {{ ($role ?? '') === 'admin' ? 'selected' : '' }}>
                                                        Admin</option>
                                                    <option value="super_account" {{ ($role ?? '') === 'super_account' ? 'selected' : '' }}>Super Account</option>
                                                    <option value="section_head" {{ ($role ?? '') === 'section_head' ? 'selected' : '' }}>Section Head</option>
                                                    <option value="operator" {{ ($role ?? '') === 'operator' ? 'selected' : '' }}>Operator</option>
                                                    <option value="admin_wh" {{ ($role ?? '') === 'admin_wh' ? 'selected' : '' }}>Admin WH</option>
                                                    <option value="security" {{ ($role ?? '') === 'security' ? 'selected' : '' }}>Security</option>
                                                    <option value="vendor" {{ ($role ?? '') === 'vendor' ? 'selected' : '' }}>
                                                        Vendor</option>
                                                    <option value="display_account" {{ ($role ?? '') === 'display_account' ? 'selected' : '' }}>Display Account</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button"
                                                        class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear"
                                                        data-filter="role">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="role">
                                                <div class="st-panel__title">Sort Role</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="role" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="role" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="st-table-col-120">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Status</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger"
                                                    data-sort="is_active" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger"
                                                    data-filter="is_active" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220"
                                                data-filter-panel="is_active">
                                                <div class="st-font-semibold st-mb-6">Status Filter</div>
                                                <select name="is_active" form="user-filter-form" class="st-select">
                                                    <option value="">All Status</option>
                                                    <option value="1" {{ ($is_active ?? '') === '1' ? 'selected' : '' }}>
                                                        Active</option>
                                                    <option value="0" {{ ($is_active ?? '') === '0' ? 'selected' : '' }}>
                                                        Inactive</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button"
                                                        class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear"
                                                        data-filter="is_active">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-panel st-panel--medium"
                                                data-sort-panel="is_active">
                                                <div class="st-panel__title">Sort Status</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="is_active" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="is_active" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="st-table-col-190 st-th-center">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Created</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger"
                                                    data-sort="created_at" data-type="date" title="Sort">â‡…</button>
                                            </span>
                                            <div class="st-sort-panel st-panel st-panel--medium"
                                                data-sort-panel="created_at">
                                                <div class="st-panel__title">Sort Created</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="created_at" data-dir="desc">
                                                    Newest
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact"
                                                    data-sort="created_at" data-dir="asc">
                                                    Oldest
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="st-table-col-260 st-th-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $u)
                                    @php
                                        $isCurrentUser = auth()->check() && (int) (auth()->user()->id ?? 0) === (int) $u->id;
                                        $roleTextRaw = (string) ($u->role_name ?? '');
                                        $roleVal = $roleTextRaw !== '' ? strtolower(str_replace(' ', '_', $roleTextRaw)) : 'operator';
                                        $roleText = ucwords(str_replace('_', ' ', $roleVal));
                                        $deleteConfirmMsg = 'Are you sure you want to delete this user?';
                                    @endphp
                                    <tr class="st-table-row st-row-clickable" data-href="{{ route('users.edit', ['userId' => $u->id]) }}">
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td class="st-font-semibold">{{ $u->nik ?? 'N/A' }}</td>
                                        <td>{{ $u->full_name ?? 'N/A' }}</td>
                                        <td class="st-td-center">{{ $u->email ?? 'N/A' }}</td>
                                        <td class="st-td-center">
                                            <span class="st-font-semibold">{{ $roleText }}</span>
                                        </td>
                                        <td class="st-td-center">
                                            @if (!$isCurrentUser)
                                                @can('users.toggle')
                                                    <label class="st-switch" title="{{ $u->is_active ? 'Click to Deactivate' : 'Click to Activate' }}">
                                                        <input type="checkbox" class="st-toggle-active"
                                                            data-toggle-url="{{ route('users.toggle', ['userId' => $u->id]) }}"
                                                            data-user-name="{{ $u->full_name ?? $u->nik ?? '' }}"
                                                            {{ $u->is_active ? 'checked' : '' }}>
                                                        <span class="st-switch__slider"></span>
                                                    </label>
                                                @else
                                                    @if($u->is_active)
                                                        <span class="st-badge st-badge--success">Active</span>
                                                    @else
                                                        <span class="st-badge st-badge--danger">Inactive</span>
                                                    @endif
                                                @endcan
                                            @else
                                                <span class="st-badge st-badge--success">Active</span>
                                                <span class="st-text--muted st-text--xs">(you)</span>
                                            @endif
                                        </td>
                                        <td class="st-td-center">
                                            @php
                                                $createdAt = $u->created_at ?? null;
                                                if ($createdAt) {
                                                    try {
                                                        $date = new \DateTime($createdAt);
                                                        echo $date->format('d-m-Y H:i');
                                                    } catch (\Exception $e) {
                                                        echo '-';
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                            @endphp
                                        </td>
                                        <td class="st-td-center">
                                            <div class="tw-actionbar">
                                                @can('users.edit')
                                                    <a href="{{ route('users.edit', ['userId' => $u->id]) }}" class="tw-action"
                                                        data-tooltip="Edit" aria-label="Edit">
                                                        <i class="fa-solid fa-pencil"></i>
                                                    </a>
                                                @endcan

                                                @if (!$isCurrentUser)
                                                    @can('users.delete')
                                                        <button type="button" class="tw-action tw-action--danger btn-delete-user"
                                                            data-tooltip="Delete" aria-label="Delete"
                                                            data-delete-url="{{ route('users.delete', ['userId' => $u->id]) }}"
                                                            data-user-name="{{ $u->full_name ?? $u->nik ?? 'N/A' }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    @endcan
                                                @endif
                                                @if ($isCurrentUser)
                                                    <span class="st-text--muted st-text--sm">(current)</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="st-table-empty st-text-center st-text--muted st-py-16">No users
                                            found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Delete User Confirmation Dialog -->
    <div id="deleteUserDialog" class="st-dialog st-dialog--overlay st-hidden">
        <div class="st-card st-dialog__card">
            <div class="st-card__header st-dialog__header">
                <h3 class="st-dialog__title">Delete User</h3>
            </div>
            <div class="st-card__body st-dialog__body">
                <form id="delete-user-form" method="POST" action="">
                    @csrf
                    <p class="st-dialog__text">
                        Are you sure you want to delete user <span id="deleteUserName" class="st-font-semibold"></span>?
                    </p>
                    <div class="st-dialog__actions">
                        <button id="confirmDeleteUserYes" type="submit" class="st-btn st-btn--danger st-dialog__btn">
                            DELETE
                        </button>
                        <button id="confirmDeleteUserNo" type="button"
                            class="st-btn st-btn--outline-primary st-dialog__btn">
                            CANCEL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    @can('users.create')
    <div id="addUserModal" class="st-dialog--overlay st-hidden">
        <div class="st-dialog__card" style="max-width: 520px;">
            <div class="st-dialog__header">
                <h3 class="st-dialog__title">Add User</h3>
            </div>
            <div class="st-dialog__body">
                <div id="addUserErrors" class="st-alert st-alert--error st-mb-12" style="display:none;">
                    <div class="st-alert__title">Please check the form</div>
                    <div class="st-alert__text">
                        <ul id="addUserErrorList" class="st-ml-16"></ul>
                    </div>
                </div>

                <form id="addUserForm" method="POST" action="{{ route('users.store') }}" class="st-form-block">
                    @csrf

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">NIK</label>
                        <input type="text" name="nik" class="st-input" maxlength="50" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Email</label>
                        <input type="email" name="email" class="st-input" maxlength="255" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Full Name</label>
                        <input type="text" name="name" class="st-input" maxlength="100" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Role</label>
                        <select name="role" class="st-select" required id="modal-role">
                            <option value="operator" selected>Operator</option>
                            <option value="admin_wh">Admin WH</option>
                            <option value="section_head">Section Head</option>
                            <option value="admin">Admin</option>
                            <option value="security">Security</option>
                            <option value="super_account">Super Account</option>
                            <option value="vendor">Vendor</option>
                            <option value="display_account">Display Account</option>
                        </select>
                    </div>

                    <div class="st-form-field st-form-field--mb st-form-field--hidden" id="modal-vendor-code-field">
                        <label class="st-label" id="modal-vendor-code-label">Vendor Code (SAP)</label>
                        <input type="text" name="vendor_code" id="modal-vendor-code-input" class="st-input" maxlength="50" placeholder="e.g. 1100000263">
                        <div class="st-form-note st-mb-8" id="modal-vendor-code-hint">Will be validated against SAP to get company name.</div>

                        <label class="st-flex st-align-center st-gap-6 st-cursor-pointer st-mt-2">
                            <input type="checkbox" name="is_internal_vendor" value="1" class="st-checkbox--plain" id="modal-internal-vendor-cb">
                            <span>Internal Vendor</span>
                        </label>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Password</label>
                        <div class="st-input-wrap">
                            <input type="password" name="password" id="modal-password" class="st-input st-input--pr-40" required>
                            <button type="button" class="btn-toggle-password st-btn-toggle-password" data-target="modal-password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Confirm Password</label>
                        <div class="st-input-wrap">
                            <input type="password" name="password_confirmation" id="modal-password-confirmation" class="st-input st-input--pr-40" required>
                            <button type="button" class="btn-toggle-password st-btn-toggle-password" data-target="modal-password-confirmation">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="st-dialog__actions">
                        <button type="submit" class="st-btn st-btn--primary st-dialog__btn">Save</button>
                        <button type="button" id="btnCloseAddUser" class="st-btn st-btn--outline-primary st-dialog__btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
@endsection

@push('scripts')
    @vite(['resources/js/pages/users-index.js'])

    @if (session('success') || session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var message = @json(session('success') ?: session('error'));
            var isSuccess = {{ session('success') ? 'true' : 'false' }};

            // Call the global showToast if available (from users-index.js)
            if (typeof window.showToast === 'function') {
                window.showToast(message, isSuccess);
            } else {
                // Fallback if showToast isn't exported globally yet
                var existing = document.querySelector('.st-toast');
                if (existing) existing.remove();

                var toast = document.createElement('div');
                toast.className = 'st-toast ' + (isSuccess ? 'st-toast--success' : 'st-toast--error');
                toast.textContent = message;
                document.body.appendChild(toast);

                requestAnimationFrame(function() {
                    toast.classList.add('st-toast--visible');
                });

                setTimeout(function() {
                    toast.classList.remove('st-toast--visible');
                    setTimeout(function() { toast.remove(); }, 300);
                }, 3500);
            }
        });
    </script>
    @endif

    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('addUserModal');
            var errorsDiv = document.getElementById('addUserErrors');
            var errorsList = document.getElementById('addUserErrorList');

            if (modal) {
                modal.classList.remove('st-hidden');
                modal.style.display = 'flex';
            }

            if (errorsDiv && errorsList) {
                errorsDiv.style.display = 'block';
                errorsList.innerHTML = @json($errors->all()).map(function(e) {
                    return '<li>' + e + '</li>';
                }).join('');
            }

            // Restore old input values
            var form = document.getElementById('addUserForm');
            if (form) {
                @if(old('nik'))
                    var nikInput = form.querySelector('input[name="nik"]');
                    if (nikInput) nikInput.value = @json(old('nik'));
                @endif
                @if(old('email'))
                    var emailInput = form.querySelector('input[name="email"]');
                    if (emailInput) emailInput.value = @json(old('email'));
                @endif
                @if(old('name'))
                    var nameInput = form.querySelector('input[name="name"]');
                    if (nameInput) nameInput.value = @json(old('name'));
                @endif
                @if(old('role'))
                    var roleSelect = document.getElementById('modal-role');
                    if (roleSelect) roleSelect.value = @json(old('role'));
                    // Trigger vendor field sync
                    var vendorField = document.getElementById('modal-vendor-code-field');
                    if (roleSelect && vendorField) {
                        vendorField.style.display = roleSelect.value === 'vendor' ? 'block' : 'none';
                    }
                @endif
                @if(old('vendor_code'))
                    var vcInput = form.querySelector('input[name="vendor_code"]');
                    if (vcInput) vcInput.value = @json(old('vendor_code'));
                @endif
            }
        });
    </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var cb = document.getElementById('modal-internal-vendor-cb');
            if (cb) {
                function syncVendorLabels() {
                    var label = document.getElementById('modal-vendor-code-label');
                    var input = document.getElementById('modal-vendor-code-input');
                    var hint = document.getElementById('modal-vendor-code-hint');
                    if (cb.checked) {
                        if (label) label.textContent = 'Division';
                        if (input) input.placeholder = 'e.g. PPIC, EXIM, Purchasing';
                        if (hint) hint.textContent = 'Division name will be shown alongside the user name.';
                    } else {
                        if (label) label.textContent = 'Vendor Code (SAP)';
                        if (input) input.placeholder = 'e.g. 1100000263';
                        if (hint) hint.textContent = 'Will be validated against SAP to get company name.';
                    }
                }
                cb.addEventListener('change', syncVendorLabels);
                syncVendorLabels();
            }
        });
    </script>
@endpush
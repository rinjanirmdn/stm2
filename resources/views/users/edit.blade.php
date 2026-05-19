@extends('layouts.app')

@section('title', 'Edit User - e-Docking Control System')
@section('page_title', 'Edit User')

@section('content')
    <section class="st-row">
        <div class="st-col-6 st-flex st-flex-col">
            <div class="st-card st-card--narrow st-flex st-flex-col st-flex-1">
                @php
                    $fromResetEmail = (string) request()->query('from_reset_email', '') === '1';
                @endphp

                @if ($errors->any())
                    <div class="st-alert st-alert--error st-mb-12">
                        <div class="st-alert__title">Please check the form</div>
                        <div class="st-alert__text">
                            <ul class="st-ml-16">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form id="user_edit_form" method="POST" action="{{ route('users.update', ['userId' => $editUser->id_users, 'from_reset_email' => $fromResetEmail ? 1 : null]) }}" class="st-form-block">
                    @csrf

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">NIK <span class="st-text--danger-dark">*</span></label>
                        <input type="text" name="nik" class="st-input{{ $errors->has('nik') ? ' st-input--invalid' : '' }}" maxlength="8" oninput="this.value = this.value.replace(/\D/g, '')" value="{{ old('nik', $editUser->nik) }}" required placeholder="e.g. 12345678">
                        @error('nik')
                            <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Email</label>
                        <input type="email" name="email" class="st-input" maxlength="255" value="{{ old('email', $editUser->email) }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Full Name</label>
                        <input type="text" name="name" class="st-input" maxlength="255" value="{{ old('name', $editUser->full_name) }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Role</label>
                        <select name="role" class="st-select" required id="role">
                            @php
                                $roleName = \Illuminate\Support\Facades\DB::table('md_roles')->where('id_roles', $editUser->role_id)->value('roles_name') ?? 'operator';
                                $currentRole = strtolower(str_replace(' ', '_', $roleName));
                            @endphp
                            <option value="operator" {{ old('role', $currentRole) === 'operator' ? 'selected' : '' }}>Operator</option>
                            <option value="admin_wh" {{ old('role', $currentRole) === 'admin_wh' ? 'selected' : '' }}>Admin WH</option>
                            <option value="section_head" {{ old('role', $currentRole) === 'section_head' ? 'selected' : '' }}>Section Head</option>
                            <option value="admin" {{ old('role', $currentRole) === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="security" {{ old('role', $currentRole) === 'security' ? 'selected' : '' }}>Security</option>
                            <option value="super_account" {{ old('role', $currentRole) === 'super_account' ? 'selected' : '' }}>Super Account</option>
                            <option value="vendor" {{ old('role', $currentRole) === 'vendor' ? 'selected' : '' }}>Vendor</option>
                            <option value="display_account" {{ old('role', $currentRole) === 'display_account' ? 'selected' : '' }}>Display Account</option>
                        </select>
                    </div>

                    <div class="st-form-field st-form-field--mb st-form-field--hidden" id="vendor_code_field">
                        <!-- Vendor Code Block -->
                        <div id="vendor-code-input-wrapper">
                            <label class="st-label">Vendor Code (SAP)</label>
                            <input type="text" name="vendor_code" id="edit-vendor-code-input" class="st-input" maxlength="50" value="{{ old('vendor_code', $editUser->is_internal_vendor ? '' : ($editUser->vendor_code ?? '')) }}" placeholder="e.g. 1100000263">
                            <div class="st-form-note st-mb-8">Will be validated against SAP to get company name.</div>
                        </div>

                        <!-- Division Dropdown Block -->
                        <div id="division-select-wrapper" style="display: none;">
                            <label class="st-label">Division</label>
                            <select name="vendor_code" id="edit-division-select" class="st-select">
                                @php $selectedDiv = old('vendor_code', $editUser->is_internal_vendor ? ($editUser->vendor_code ?? '') : ''); @endphp
                                <option value="" {{ $selectedDiv === '' ? 'selected' : '' }}>-- Select Division --</option>
                                <option value="PPIC" {{ $selectedDiv === 'PPIC' ? 'selected' : '' }}>PPIC</option>
                                <option value="Purchasing" {{ $selectedDiv === 'Purchasing' ? 'selected' : '' }}>Purchasing</option>
                                <option value="EXIM" {{ $selectedDiv === 'EXIM' ? 'selected' : '' }}>EXIM</option>
                            </select>
                            <div class="st-form-note st-mb-8">Division name will be shown alongside the user name.</div>
                        </div>
                        
                        <label class="st-flex st-align-center st-gap-6 st-cursor-pointer st-mt-2">
                            <input type="checkbox" name="is_internal_vendor" value="1" form="user_edit_form" id="edit-internal-vendor-cb" {{ old('is_internal_vendor', $editUser->is_internal_vendor) == '1' ? 'checked' : '' }} class="st-checkbox--plain">
                            <span>Requester</span>
                        </label>

                        <div id="edit_company_name_field" class="st-mt-8" style="display:none;">
                            <label class="st-label">Company Name (PT)</label>
                            <input type="text" name="company_name" id="edit-company-name-input" class="st-input" maxlength="255"
                                value="{{ old('company_name', $editUser->company_name ?? '') }}" placeholder="e.g. PT. Vendor Example">
                            <div class="st-form-note">Company name displayed alongside the user name.</div>
                        </div>
                    </div>

                    <div class="st-divider"></div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">New Password (optional)</label>
                        <div class="st-input-wrap">
                            <input type="password" name="password" id="password" class="st-input st-input--pr-40">
                            <button type="button" class="btn-toggle-password st-btn-toggle-password" data-target="password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="st-form-field st-form-field--mb-14">
                        <label class="st-label">Confirm New Password</label>
                        <div class="st-input-wrap">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="st-input st-input--pr-40">
                            <button type="button" class="btn-toggle-password st-btn-toggle-password" data-target="password_confirmation">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    @include('partials.password-validator', ['passwordId' => 'password', 'confirmId' => 'password_confirmation', 'submitBtnSelector' => '#user_edit_form .st-btn--primary', 'isOptional' => true])

                    <div class="st-form-actions">
                        <button type="submit" class="st-btn st-btn--primary">{{ $fromResetEmail ? 'Save and Send Email' : 'Save' }}</button>
                        <a href="{{ route('users.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="st-col-6 st-flex st-flex-col">
            <div class="st-card st-card--narrow st-flex st-flex-col st-flex-1">
                <div class="st-card-header-row">
                    <div>
                        <h2 class="st-card__title st-card-title-tight">Permission Settings</h2>
                    </div>
                </div>

                @if (!empty($canManagePermissions))
                    @php
                        $all = isset($allPermissions) && is_array($allPermissions) ? $allPermissions : [];
                        $rolePerms = isset($rolePermissions) && is_array($rolePermissions) ? $rolePermissions : [];
                        $directPerms = isset($directPermissions) && is_array($directPermissions) ? $directPermissions : [];
                        $oldPerms = old('permissions');
                        $selectedDirect = is_array($oldPerms) ? array_values(array_filter(array_map('strval', $oldPerms))) : $directPerms;

                        $grouped = [];
                        foreach ($all as $p) {
                            $p = (string) $p;
                            $group = 'other';
                            if (str_contains($p, '.')) {
                                $group = explode('.', $p, 2)[0] ?: 'other';
                            }
                            if (!isset($grouped[$group])) $grouped[$group] = [];
                            $grouped[$group][] = $p;
                        }
                        ksort($grouped);
                    @endphp

                    <div class="st-form-block st-flex st-flex-col st-flex-1">
                        <div class="st-text--sm st-text--muted st-mb-12">
                            Permissions can be adjusted here. Note: if a permission is granted by the user's role, removing it here may not remove access unless the role is adjusted.
                        </div>

                        <div class="st-flex-1" style="overflow-y: auto; max-height: 620px; padding-right: 6px;">
                            @foreach ($grouped as $group => $perms)
                                <div class="st-mb-12">
                                    <div class="st-font-semibold st-mb-6">{{ ucwords(str_replace('_', ' ', (string) $group)) }}</div>

                                    <div class="st-flex st-flex-col st-gap-6">
                                        @foreach ($perms as $perm)
                                            @php
                                                $inRole = in_array($perm, $rolePerms, true);
                                                $inDirect = in_array($perm, $selectedDirect, true);
                                                $checked = $inRole || $inDirect;
                                            @endphp
                                            <label class="st-flex st-align-center st-gap-8">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $perm }}"
                                                    form="user_edit_form"
                                                    {{ $checked ? 'checked' : '' }}
                                                >
                                                <span class="st-text--sm">{{ $perm }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="st-form-block">
                        <div class="st-text--sm st-text--muted">
                            Only <strong>Admin</strong> can view and manage permissions.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('scripts')
@vite(['resources/js/pages/users-edit.js'])
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var cb = document.getElementById('edit-internal-vendor-cb');
        if (cb) {
            function syncEditVendorLabels() {
                var codeWrapper = document.getElementById('vendor-code-input-wrapper');
                var codeInput = document.getElementById('edit-vendor-code-input');
                var divWrapper = document.getElementById('division-select-wrapper');
                var divSelect = document.getElementById('edit-division-select');
                var companyField = document.getElementById('edit_company_name_field');

                if (cb.checked) {
                    if (codeWrapper) codeWrapper.style.display = 'none';
                    if (codeInput) codeInput.disabled = true;
                    if (divWrapper) divWrapper.style.display = 'block';
                    if (divSelect) divSelect.disabled = false;
                    if (companyField) companyField.style.display = 'none';
                } else {
                    if (codeWrapper) codeWrapper.style.display = 'block';
                    if (codeInput) codeInput.disabled = false;
                    if (divWrapper) divWrapper.style.display = 'none';
                    if (divSelect) divSelect.disabled = true;
                    if (companyField) companyField.style.display = 'block';
                }
            }
            cb.addEventListener('change', syncEditVendorLabels);
            syncEditVendorLabels();
        }
    });
</script>
@endpush

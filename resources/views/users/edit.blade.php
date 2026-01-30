@extends('layouts.app')

@section('title', 'Edit User - Slot Time Management')
@section('page_title', 'Edit User')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card st-card--narrow">
                <div class="st-card-header-row">
                    <div>
                        <h2 class="st-card__title st-card-title-tight">Edit User</h2>
                    </div>
                </div>

                <form method="POST" action="{{ route('users.update', ['userId' => $editUser->id]) }}" class="st-form-block">
                    @csrf

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">NIK/Username</label>
                        <input type="text" name="nik" class="st-input" maxlength="50" value="{{ old('nik', $editUser->nik) }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Full Name</label>
                        <input type="text" name="full_name" class="st-input" maxlength="100" value="{{ old('full_name', $editUser->full_name) }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Role</label>
                        <select name="role" class="st-select" required id="role">
                            <option value="operator" {{ old('role', $editUser->role) === 'operator' ? 'selected' : '' }}>Operator</option>
                            <option value="section_head" {{ old('role', $editUser->role) === 'section_head' ? 'selected' : '' }}>Section Head</option>
                            <option value="admin" {{ old('role', $editUser->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="vendor" {{ old('role', $editUser->role) === 'vendor' ? 'selected' : '' }}>Vendor</option>
                        </select>
                    </div>

                    <div class="st-form-field st-form-field--mb st-form-field--hidden" id="vendor_code_field">
                        <label class="st-label">Vendor Code (SAP)</label>
                        <input type="text" name="vendor_code" class="st-input" maxlength="20" value="{{ old('vendor_code', $editUser->vendor_code ?? '') }}" placeholder="e.g. 1100000263">
                        <div class="st-form-note">Isi dengan SupplierCode/CustomerCode dari SAP untuk filter PO.</div>
                    </div>

                    <label class="st-checkbox-row">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', (int)$editUser->is_active === 1 ? '1' : '') ? 'checked' : '' }}>
                        <span class="st-checkbox-label">Active</span>
                    </label>

                    <div class="st-divider"></div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">New Password (optional)</label>
                        <input type="password" name="password" class="st-input">
                    </div>

                    <div class="st-form-field st-form-field--mb-14">
                        <label class="st-label">Confirm New Password</label>
                        <input type="password" name="password_confirm" class="st-input">
                    </div>

                    <div class="st-form-actions">
                        <button type="submit" class="st-btn st-btn--primary">Save</button>
                        <a href="{{ route('users.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var roleSelect = document.getElementById('role');
    var vendorField = document.getElementById('vendor_code_field');

    function syncVendorField() {
        if (!roleSelect || !vendorField) return;
        var role = String(roleSelect.value || '');
        vendorField.style.display = role === 'vendor' ? 'block' : 'none';
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', syncVendorField);
        syncVendorField();
    }
});
</script>
@endpush

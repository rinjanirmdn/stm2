@extends('layouts.app')

@section('title', 'Edit User - Slot Time Management')
@section('page_title', 'Edit User')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card" style="padding:16px;max-width:760px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                    <div>
                        <h2 class="st-card__title" style="margin:0 0 6px 0;">Edit User</h2>
                    </div>
                </div>

                <form method="POST" action="{{ route('users.update', ['userId' => $editUser->id]) }}" style="margin-top:14px;">
                    @csrf

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">NIK/Username</label>
                        <input type="text" name="nik" class="st-input" maxlength="50" value="{{ old('nik', $editUser->nik) }}" required>
                    </div>

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">Full Name</label>
                        <input type="text" name="full_name" class="st-input" maxlength="100" value="{{ old('full_name', $editUser->full_name) }}" required>
                    </div>

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">Role</label>
                        <select name="role" class="st-select" required id="role">
                            <option value="operator" {{ old('role', $editUser->role) === 'operator' ? 'selected' : '' }}>Operator</option>
                            <option value="section_head" {{ old('role', $editUser->role) === 'section_head' ? 'selected' : '' }}>Section Head</option>
                            <option value="admin" {{ old('role', $editUser->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="vendor" {{ old('role', $editUser->role) === 'vendor' ? 'selected' : '' }}>Vendor</option>
                        </select>
                    </div>

                    <div class="st-form-field" style="margin-bottom:10px; display:none;" id="vendor_code_field">
                        <label class="st-label">Vendor Code (SAP)</label>
                        <input type="text" name="vendor_code" class="st-input" maxlength="20" value="{{ old('vendor_code', $editUser->vendor_code ?? '') }}" placeholder="e.g. 1100000263">
                        <div style="font-size:12px;color:#6b7280;margin-top:4px;">Isi dengan SupplierCode/CustomerCode dari SAP untuk filter PO.</div>
                    </div>

                    <label style="display:flex;gap:8px;align-items:center;margin:12px 0 14px 0;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', (int)$editUser->is_active === 1 ? '1' : '') ? 'checked' : '' }}>
                        <span style="font-size:13px;color:#374151;">Active</span>
                    </label>

                    <div style="border-top:1px solid #e5e7eb;margin:14px 0;"></div>

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">New Password (optional)</label>
                        <input type="password" name="password" class="st-input">
                    </div>

                    <div class="st-form-field" style="margin-bottom:14px;">
                        <label class="st-label">Confirm New Password</label>
                        <input type="password" name="password_confirm" class="st-input">
                    </div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="submit" class="st-btn st-btn--primary">Save</button>
                        <a href="{{ route('users.index') }}" class="st-btn st-btn--secondary">Cancel</a>
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

@extends('layouts.app')

@section('title', 'Add User - Slot Time Management')
@section('page_title', 'Add User')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card" style="padding:16px;max-width:760px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                    <div>
                        <h2 class="st-card__title" style="margin:0 0 6px 0;">Add User</h2>
                    </div>
                </div>

                <form method="POST" action="{{ route('users.store') }}" style="margin-top:14px;">
                    @csrf

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">NIK/Username</label>
                        <input type="text" name="nik" class="st-input" maxlength="50" value="{{ old('nik') }}" required>
                    </div>

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">Full Name</label>
                        <input type="text" name="full_name" class="st-input" maxlength="100" value="{{ old('full_name') }}" required>
                    </div>

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">Role</label>
                        <select name="role" class="st-select" required id="role">
                            <option value="operator" {{ old('role', 'operator') === 'operator' ? 'selected' : '' }}>Operator</option>
                            <option value="section_head" {{ old('role', 'operator') === 'section_head' ? 'selected' : '' }}>Section Head</option>
                            <option value="admin" {{ old('role', 'operator') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="vendor" {{ old('role', 'operator') === 'vendor' ? 'selected' : '' }}>Vendor</option>
                        </select>
                    </div>

                    <div class="st-form-field" style="margin-bottom:10px; display:none;" id="vendor_code_field">
                        <label class="st-label">Vendor Code (SAP)</label>
                        <input type="text" name="vendor_code" class="st-input" maxlength="20" value="{{ old('vendor_code') }}" placeholder="e.g. 1100000263">
                        <div style="font-size:12px;color:#6b7280;margin-top:4px;">Isi dengan SupplierCode/CustomerCode dari SAP untuk filter PO.</div>
                    </div>

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">Password</label>
                        <div style="position:relative;">
                            <input type="password" name="password" id="password" class="st-input" style="padding-right:40px;" required>
                            <button type="button" class="btn-toggle-password" data-target="password" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6b7280;padding:4px;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">Confirm Password</label>
                        <div style="position:relative;">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="st-input" style="padding-right:40px;" required>
                            <button type="button" class="btn-toggle-password" data-target="password_confirmation" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6b7280;padding:4px;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <label style="display:flex;gap:8px;align-items:center;margin:12px 0 14px 0;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                        <span style="font-size:13px;color:#374151;">Active</span>
                    </label>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="submit" class="st-btn st-btn--primary">Save</button>
                        <a href="{{ route('users.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            var icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

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

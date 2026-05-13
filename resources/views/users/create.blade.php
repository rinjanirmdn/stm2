@extends('layouts.app')

@section('title', 'Add User - e-Docking Control System')
@section('page_title', 'Add User')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card st-card--narrow">
                <div class="st-card-header-row">
                    <div>
                        <h2 class="st-card__title st-card-title-tight">Add User</h2>
                    </div>
                </div>




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

                <form method="POST" action="{{ route('users.store') }}" class="st-form-block">
                    @csrf

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">NIK</label>
                        <input type="text" name="nik" class="st-input" maxlength="50" value="{{ old('nik') }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Email</label>
                        <input type="email" name="email" class="st-input" maxlength="255" value="{{ old('email') }}"
                            required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Full Name</label>
                        <input type="text" name="name" class="st-input" maxlength="100" value="{{ old('name') }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Role</label>
                        <select name="role" class="st-select" required id="role">
                            <option value="operator" {{ old('role', 'operator') === 'operator' ? 'selected' : '' }}>Operator
                            </option>
                            <option value="admin_wh" {{ old('role', 'operator') === 'admin_wh' ? 'selected' : '' }}>Admin WH
                            </option>
                            <option value="section_head" {{ old('role', 'operator') === 'section_head' ? 'selected' : '' }}>
                                Section Head</option>
                            <option value="admin" {{ old('role', 'operator') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="security" {{ old('role', 'operator') === 'security' ? 'selected' : '' }}>Security
                            </option>
                            <option value="super_account" {{ old('role', 'operator') === 'super_account' ? 'selected' : '' }}>
                                Super Account</option>
                            <option value="vendor" {{ old('role', 'operator') === 'vendor' ? 'selected' : '' }}>Vendor
                            </option>
                            <option value="display_account" {{ old('role', 'operator') === 'display_account' ? 'selected' : '' }}>Display Account</option>
                        </select>
                    </div>

                    <div class="st-form-field st-form-field--mb st-form-field--hidden" id="vendor_code_field">
                        <label class="st-label" id="create-vendor-code-label">Vendor Code (SAP)</label>
                        <input type="text" name="vendor_code" id="create-vendor-code-input" class="st-input" maxlength="50"
                            value="{{ old('vendor_code') }}" placeholder="e.g. 1100000263">
                        <div class="st-form-note st-mb-8" id="create-vendor-code-hint">Will be validated against SAP to get company name.</div>
                        
                        <label class="st-flex st-align-center st-gap-6 st-cursor-pointer st-mt-2">
                            <input type="checkbox" name="is_internal_vendor" value="1" id="create-internal-vendor-cb" {{ old('is_internal_vendor') == '1' ? 'checked' : '' }} class="st-checkbox--plain">
                            <span>Internal Vendor</span>
                        </label>

                        <div id="company_name_field" class="st-mt-8" style="display:none;">
                            <label class="st-label">Company Name (PT)</label>
                            <input type="text" name="company_name" id="create-company-name-input" class="st-input" maxlength="255"
                                value="{{ old('company_name') }}" placeholder="e.g. PT. Vendor Example">
                            <div class="st-form-note">Company name displayed alongside the user name.</div>
                        </div>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Password</label>
                        <div class="st-input-wrap">
                            <input type="password" name="password" id="password" class="st-input st-input--pr-40" required>
                            <button type="button" class="btn-toggle-password st-btn-toggle-password" data-target="password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Confirm Password</label>
                        <div class="st-input-wrap">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="st-input st-input--pr-40" required>
                            <button type="button" class="btn-toggle-password st-btn-toggle-password"
                                data-target="password_confirmation">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    @include('partials.password-validator', ['passwordId' => 'password', 'confirmId' => 'password_confirmation', 'submitBtnSelector' => '.st-form-actions .st-btn--primary'])

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
    @vite(['resources/js/pages/users-create.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var cb = document.getElementById('create-internal-vendor-cb');
            if (cb) {
                function syncCreateVendorLabels() {
                    var label = document.getElementById('create-vendor-code-label');
                    var input = document.getElementById('create-vendor-code-input');
                    var hint = document.getElementById('create-vendor-code-hint');
                    var companyField = document.getElementById('company_name_field');
                    if (cb.checked) {
                        if (label) label.textContent = 'Division';
                        if (input) input.placeholder = 'e.g. PPIC, EXIM, Purchasing';
                        if (hint) hint.textContent = 'Division name will be shown alongside the user name.';
                        if (companyField) companyField.style.display = 'none';
                    } else {
                        if (label) label.textContent = 'Vendor Code (SAP)';
                        if (input) input.placeholder = 'e.g. 1100000263';
                        if (hint) hint.textContent = 'Will be validated against SAP to get company name.';
                        if (companyField) companyField.style.display = 'block';
                    }
                }
                cb.addEventListener('change', syncCreateVendorLabels);
                syncCreateVendorLabels();
            }
        });
    </script>
@endpush

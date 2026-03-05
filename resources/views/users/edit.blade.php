@extends('layouts.app')

@section('title', 'Edit User - e-Docking Control System')
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

                <form method="POST" action="{{ route('users.update', ['userId' => $editUser->id, 'from_reset_email' => $fromResetEmail ? 1 : null]) }}" class="st-form-block">
                    @csrf

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">NIK</label>
                        <input type="text" name="nik" class="st-input" maxlength="50" value="{{ old('nik', $editUser->nik) }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Email</label>
                        <input type="email" name="email" class="st-input" maxlength="255" value="{{ old('email', $editUser->email) }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Name</label>
                        <input type="text" name="name" class="st-input" maxlength="255" value="{{ old('name', $editUser->full_name) }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Role</label>
                        <select name="role" class="st-select" required id="role">
                            @php $currentRole = $editUser->role_slug ?? 'operator'; @endphp
                            <option value="operator" {{ old('role', $currentRole) === 'operator' ? 'selected' : '' }}>Operator</option>
                            <option value="section_head" {{ old('role', $currentRole) === 'section_head' ? 'selected' : '' }}>Section Head</option>
                            <option value="admin" {{ old('role', $currentRole) === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="security" {{ old('role', $currentRole) === 'security' ? 'selected' : '' }}>Security</option>
                            <option value="super_account" {{ old('role', $currentRole) === 'super_account' ? 'selected' : '' }}>Super Account</option>
                            <option value="vendor" {{ old('role', $currentRole) === 'vendor' ? 'selected' : '' }}>Vendor</option>
                            <option value="display_account" {{ old('role', $currentRole) === 'display_account' ? 'selected' : '' }}>Display Account</option>
                        </select>
                    </div>

                    <div class="st-form-field st-form-field--mb st-form-field--hidden" id="vendor_code_field">
                        <label class="st-label">Vendor Code (SAP)</label>
                        <input type="text" name="vendor_code" class="st-input" maxlength="20" value="{{ old('vendor_code', $editUser->vendor_code ?? '') }}" placeholder="e.g. 1100000263">
                        <div class="st-form-note">Isi dengan SupplierCode/CustomerCode dari SAP untuk filter PO.</div>
                    </div>

                    <div class="st-divider"></div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">New Password (optional)</label>
                        <input type="password" name="password" class="st-input">
                    </div>

                    <div class="st-form-field st-form-field--mb-14">
                        <label class="st-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="st-input">
                    </div>

                    <div class="st-form-actions">
                        <button type="submit" class="st-btn st-btn--primary">{{ $fromResetEmail ? 'Save and Send Email' : 'Save' }}</button>
                        <a href="{{ route('users.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
@vite(['resources/js/pages/users-edit.js'])
@endpush


@extends('layouts.app')

@section('title', 'Add User - Slot Time Management')
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

                <form method="POST" action="{{ route('users.store') }}" class="st-form-block">
                    @csrf

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">NIK</label>
                        <input type="text" name="nik" class="st-input" maxlength="50" value="{{ old('nik') }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Email</label>
                        <input type="email" name="email" class="st-input" maxlength="255" value="{{ old('email') }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Name</label>
                        <input type="text" name="name" class="st-input" maxlength="100" value="{{ old('name') }}" required>
                    </div>

                    <div class="st-form-field st-form-field--mb">
                        <label class="st-label">Role</label>
                        <select name="role" class="st-select" required id="role">
                            <option value="operator" {{ old('role', 'operator') === 'operator' ? 'selected' : '' }}>Operator</option>
                            <option value="section_head" {{ old('role', 'operator') === 'section_head' ? 'selected' : '' }}>Section Head</option>
                            <option value="admin" {{ old('role', 'operator') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="vendor" {{ old('role', 'operator') === 'vendor' ? 'selected' : '' }}>Vendor</option>
                        </select>
                    </div>

                    <div class="st-form-field st-form-field--mb st-form-field--hidden" id="vendor_code_field">
                        <label class="st-label">Vendor Code (SAP)</label>
                        <input type="text" name="vendor_code" class="st-input" maxlength="20" value="{{ old('vendor_code') }}" placeholder="e.g. 1100000263">
                        <div class="st-form-note">Isi dengan SupplierCode/CustomerCode dari SAP untuk filter PO.</div>
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
                            <input type="password" name="password_confirmation" id="password_confirmation" class="st-input st-input--pr-40" required>
                            <button type="button" class="btn-toggle-password st-btn-toggle-password" data-target="password_confirmation">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
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
@vite(['resources/js/pages/users-create.js'])
@endpush


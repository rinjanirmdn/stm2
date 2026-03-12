@extends('layouts.app')

@section('title', 'Profile - e-Docking Control System')
@section('page_title', 'Profile')

@section('content')
@php
    $canSelfChangePassword = $user->hasAnyRole(['Admin', 'Super Admin', 'Super Account', 'admin']);
@endphp
<div class="st-profile-container">
    {{-- Profile Header --}}
    <div class="st-profile-header">
        <div class="st-profile-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="st-profile-header-info">
            <h2 class="st-profile-name">{{ $user->full_name ?? $user->username ?? 'User' }}</h2>
            <span class="st-profile-role-badge">
                <i class="fas fa-shield-halved"></i>
                {{ ucfirst($user->getRoleNames()->first() ?? 'User') }}
            </span>
            @if($user->vendor_code)
                <span class="st-profile-vendor-badge">
                    <i class="fas fa-building"></i>
                    Vendor: {{ $user->vendor_code }}
                </span>
            @endif
        </div>
    </div>

    {{-- Profile Form --}}
    <form action="{{ route('profile.update') }}" method="POST" class="st-profile-form">
        @csrf

        {{-- Account Information --}}
        <div class="st-profile-section">
            <div class="st-profile-section-header">
                <i class="fas fa-id-card"></i>
                <h3>Account Information</h3>
            </div>
            <div class="st-profile-section-body">
                <div class="st-profile-field">
                    <label class="st-profile-label">Username</label>
                    <input type="text" class="st-profile-input st-profile-input--disabled" value="{{ $user->username ?? '-' }}" disabled>
                    <span class="st-profile-hint">Username cannot be changed</span>
                </div>
                <div class="st-profile-field">
                    <label class="st-profile-label">NIK</label>
                    <input type="text" class="st-profile-input st-profile-input--disabled" value="{{ $user->nik ?? '-' }}" disabled>
                    <span class="st-profile-hint">NIK cannot be changed</span>
                </div>
                <div class="st-profile-field">
                    <label class="st-profile-label">Full Name</label>
                    <input type="text" name="full_name" class="st-profile-input" value="{{ $user->full_name ?? '' }}" placeholder="Enter your full name">
                </div>
            </div>
        </div>

        {{-- Email & Notifications --}}
        <div class="st-profile-section st-profile-section--highlight">
            <div class="st-profile-section-header">
                <i class="fas fa-envelope"></i>
                <h3>Email & Notifications</h3>
            </div>
            <div class="st-profile-section-body">
                <div class="st-profile-field">
                    <label class="st-profile-label">
                        Email Address
                        <span class="st-profile-label-badge">{{ $user->email ? 'Active' : 'Not Set' }}</span>
                    </label>
                    <div class="st-profile-input-group">
                        <span class="st-profile-input-icon">
                            <i class="fas fa-at"></i>
                        </span>
                        <input type="email" name="email" class="st-profile-input st-profile-input--with-icon"
                            value="{{ $user->email ?? '' }}"
                            placeholder="yourname@example.com">
                    </div>
                    <span class="st-profile-hint">
                        <i class="fas fa-info-circle"></i>
                        Email is used for booking notifications (submit, approve, reject). Leave blank to disable email notifications.
                    </span>
                </div>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="st-profile-section">
            <div class="st-profile-section-header">
                <i class="fas fa-lock"></i>
                <h3>Change Password</h3>
                <span class="st-profile-section-badge">Optional</span>
            </div>
            <div class="st-profile-section-body">
                @if ($user->can('profile.change_password'))
                    <div class="st-profile-field">
                        <label class="st-profile-label">Current Password</label>
                        <div class="st-password-wrap">
                            <input type="password" name="current_password" id="current_password" class="st-profile-input st-input" placeholder="Enter current password">
                            <button type="button" onclick="togglePasswordVisibility('current_password', this)" class="st-password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="st-profile-field-row">
                        <div class="st-profile-field">
                            <label class="st-profile-label">New Password</label>
                            <div class="st-password-wrap">
                                <input type="password" name="new_password" id="new_password" class="st-profile-input st-input" placeholder="Min. 6 characters">
                                <button type="button" onclick="togglePasswordVisibility('new_password', this)" class="st-password-toggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="st-profile-field">
                            <label class="st-profile-label">Confirm New Password</label>
                            <div class="st-password-wrap">
                                <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="st-profile-input st-input" placeholder="Re-enter new password">
                                <button type="button" onclick="togglePasswordVisibility('new_password_confirmation', this)" class="st-password-toggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="vendor-form__field-group">
                        <div class="vendor-form__info">
                            <div class="vendor-form__info-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="vendor-form__info-content">
                                <h4>Password Change Restricted</h4>
                                <p>For security reasons, only administrators can change passwords.
                                   Please contact your admin if you need to update your password.</p>
                                <button
                                    type="button"
                                    class="vendor-btn vendor-btn--outline vendor-btn--sm"
                                    onclick="event.preventDefault(); document.getElementById('vendor-password-request-form').submit();"
                                >
                                    Request Password Change
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="st-profile-actions">
            <a href="{{ route('dashboard') }}" class="st-profile-btn st-profile-btn--secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <button type="submit" class="st-profile-btn st-profile-btn--primary">
                <i class="fas fa-save"></i>
                Save Changes
            </button>
        </div>
    </form>

    @if (! $user->can('profile.change_password'))
        <form id="vendor-password-request-form" method="POST" action="{{ route('profile.password-request') }}" class="st-form--hidden">
            @csrf
        </form>
    @endif
</div>

@push('styles')
@endpush

<script>
    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endsection

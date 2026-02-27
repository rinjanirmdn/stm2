@extends('layouts.app')

@section('title', 'Profile - e-Docking Control System')
@section('page_title', 'Profile')

@section('content')
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
                <div class="st-profile-field">
                    <label class="st-profile-label">Current Password</label>
                    <input type="password" name="current_password" class="st-profile-input" placeholder="Enter current password">
                </div>
                <div class="st-profile-field-row">
                    <div class="st-profile-field">
                        <label class="st-profile-label">New Password</label>
                        <input type="password" name="new_password" class="st-profile-input" placeholder="Min. 6 characters">
                    </div>
                    <div class="st-profile-field">
                        <label class="st-profile-label">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" class="st-profile-input" placeholder="Re-enter new password">
                    </div>
                </div>
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
</div>

@push('styles')
<style>
.st-profile-container {
    max-width: 720px;
    margin: 0 auto;
    padding: 0 0 24px;
}

.st-profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    border-radius: 16px;
    margin-bottom: 20px;
    color: #fff;
    box-shadow: 0 4px 16px rgba(2, 132, 199, 0.25);
}

.st-profile-avatar {
    font-size: 56px;
    opacity: 0.9;
    line-height: 1;
}

.st-profile-header-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.st-profile-name {
    font-size: 22px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.st-profile-role-badge,
.st-profile-vendor-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
    background: rgba(255,255,255,0.18);
    padding: 3px 10px;
    border-radius: 999px;
    width: fit-content;
}

.st-profile-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.st-profile-section {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}

.st-profile-section--highlight {
    border-color: #bae6fd;
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.08);
}

.st-profile-section--highlight .st-profile-section-header {
    background: #f0f9ff;
    color: #0369a1;
}

.st-profile-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.st-profile-section-header h3 {
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    flex: 1;
}

.st-profile-section-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.st-profile-section-body {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.st-profile-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
    flex: 1;
}

.st-profile-field-row {
    display: flex;
    gap: 14px;
}

.st-profile-label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.st-profile-label-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 1px 7px;
    border-radius: 999px;
    background: #dcfce7;
    color: #15803d;
}

.st-profile-input {
    padding: 9px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #fff;
    color: #1f2937;
    width: 100%;
    box-sizing: border-box;
}

.st-profile-input:focus {
    outline: none;
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}

.st-profile-input--disabled {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.st-profile-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.st-profile-input-icon {
    position: absolute;
    left: 12px;
    color: #9ca3af;
    font-size: 14px;
    pointer-events: none;
    z-index: 1;
}

.st-profile-input--with-icon {
    padding-left: 36px;
}

.st-profile-hint {
    font-size: 11px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 4px;
}

.st-profile-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 4px;
}

.st-profile-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.st-profile-btn--primary {
    background: #0284c7;
    color: #fff;
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
}

.st-profile-btn--primary:hover {
    background: #0369a1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
}

.st-profile-btn--secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.st-profile-btn--secondary:hover {
    background: #e5e7eb;
}

@media (max-width: 640px) {
    .st-profile-header {
        flex-direction: column;
        text-align: center;
    }
    .st-profile-field-row {
        flex-direction: column;
    }
    .st-profile-actions {
        flex-direction: column-reverse;
    }
    .st-profile-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush
@endsection

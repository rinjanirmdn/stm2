@extends('vendor.layouts.vendor')

@section('title', 'Profile')

@section('content')
<div class="vendor-page vendor-page--profile">
    <div class="vendor-container">
        <div class="vendor-page-header">
            <div class="vendor-page-header__title">
                <h2 class="vendor-title">Profile Settings</h2>
                <p class="vendor-subtitle">Manage your account information</p>
            </div>
        </div>

        <div class="vendor-content">
            <div class="vendor-card">
                <div class="vendor-card__header">
                    <h2 class="vendor-card__title">Account Information</h2>
                </div>
                <div class="vendor-card__body">
                    <form method="POST" action="{{ route('profile.update') }}" class="vendor-form">
                        @csrf
                        <div class="vendor-form__grid vendor-form__grid--3">
                            <!-- Column 1: Account Information -->
                            <div class="vendor-form__section">
                                <h3 class="vendor-form__section-title">Account Information</h3>
                                <div class="vendor-form__field-group">
                                    <div class="vendor-form__field">
                                        <label class="vendor-form__label">Full Name</label>
                                        <input type="text" name="full_name" value="{{ $user->full_name ?? '' }}"
                                               class="vendor-form__input" placeholder="Enter your full name">
                                    </div>
                                    <div class="vendor-form__field">
                                        <label class="vendor-form__label">Username</label>
                                        <input type="text" value="{{ $user->username ?? '-' }}"
                                               class="vendor-form__input vendor-form__input--disabled" disabled>
                                        <div class="vendor-form__hint">Username cannot be changed</div>
                                    </div>
                                    <div class="vendor-form__field">
                                        <label class="vendor-form__label">NIK</label>
                                        <input type="text" value="{{ $user->nik ?? '-' }}"
                                               class="vendor-form__input vendor-form__input--disabled" disabled>
                                        <div class="vendor-form__hint">NIK cannot be changed</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Email & Notifications -->
                            <div class="vendor-form__section">
                                <h3 class="vendor-form__section-title">Email & Notifications</h3>
                                <div class="vendor-form__field-group">
                                    <div class="vendor-form__field">
                                        <label class="vendor-form__label">Email Address</label>
                                        <input type="email" name="email" value="{{ $user->email ?? '' }}"
                                               class="vendor-form__input" placeholder="Enter your email">
                                        <div class="vendor-form__help">
                                            Email is used for booking notifications (submit, approve, reject).
                                            Leave blank to disable email notifications.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 3: Password (read-only for vendor) -->
                            <div class="vendor-form__section">
                                <h3 class="vendor-form__section-title">Password</h3>
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
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="vendor-form__actions">
                            <button type="submit" class="vendor-btn vendor-btn--primary">
                                Save Changes
                            </button>
                            <a href="{{ route('vendor.dashboard') }}" class="vendor-btn vendor-btn--outline">
                                Cancel
                            </a>
                        </div>
                    </form>

                    {{-- Hidden form for password change request (separate from profile update) --}}
                    <form id="vendor-password-request-form" method="POST" action="{{ route('profile.password-request') }}" class="st-form--hidden">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

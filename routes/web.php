<?php

/*
 * Web Routes — Entry Point
 * Route definitions are split into modular files for maintainability.
 */

use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────
// Root redirect
// ──────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();

        if ($user && method_exists($user, 'isVendor') && $user->isVendor()) {
            return redirect()->route('vendor.dashboard');
        }

        // Security users → security dashboard (only if they don't have main dashboard access)
        if ($user && $user->can('security.dashboard') && ! $user->can('dashboard.view')) {
            return redirect()->route('security.dashboard');
        }

        if ($user && $user->can('dashboard.view')) {
            return redirect()->route('dashboard');
        }

        if ($user && $user->can('slots.index')) {
            return redirect()->route('slots.index');
        }

        return redirect()->route('profile');
    }

    return redirect()->route('login');
});

// ──────────────────────────────────────────
// Guest routes (login, forgot password, reset password)
// ──────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('forgot-password');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetEmail'])->name('forgot-password.send');
    Route::get('/reset-password', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.reset.store');
});

// ──────────────────────────────────────────
// Authenticated routes
// ──────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard')->middleware('permission:dashboard.view');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data')->middleware('permission:dashboard.view');
    Route::get('/dashboard/waiting-reasons', [DashboardController::class, 'waitingReasons'])->name('dashboard.waitingReasons')->middleware('permission:dashboard.range_filter');
    // CSRF token refresh endpoint (keeps session alive on mobile)
    Route::get('/csrf-refresh', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf.refresh');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile')->middleware('permission:profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('permission:profile.index');
    Route::post('/profile/password-request', [ForgotPasswordController::class, 'requestFromProfile'])->name('profile.password-request')->middleware('permission:profile.index');

    // Force password change
    Route::get('/force-change-password', [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::post('/force-change-password', [ForcePasswordChangeController::class, 'store'])->name('password.force-change.store');

    // ── Modular route files ──
    require __DIR__.'/slots.php';
    require __DIR__.'/unplanned.php';
    require __DIR__.'/vendor.php';
    require __DIR__.'/admin.php';
});

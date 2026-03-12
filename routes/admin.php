<?php

/* Admin Routes — Reports, Gates, Users, Logs, Trucks, Booking Approval, Notifications, SAP API */

use App\Http\Controllers\BookingApprovalController;
use App\Http\Controllers\GateStatusController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SapController;
use App\Http\Controllers\TruckTypeDurationController;
use App\Http\Controllers\UserController;

// Reports
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/transactions', [ReportController::class, 'transactions'])->name('transactions')->middleware('permission:reports.transactions');
    Route::get('/search-suggestions', [ReportController::class, 'searchSuggestions'])->name('search_suggestions')->middleware('permission:reports.search_suggestions');
    Route::get('/gate-status', [ReportController::class, 'gateStatus'])->name('gate_status')->middleware('permission:reports.gate_status');
});

// Real-time gate status streaming
Route::get('/api/gate-status', [GateStatusController::class, 'apiIndex'])->name('api.gate-status')->middleware('permission:gates.api_index');
Route::get('/api/gate-status/stream', [GateStatusController::class, 'stream'])->name('api.gate-status.stream')->middleware('permission:gates.stream');
Route::get('/api/realtime/version', function () {
    $cacheKey = 'st_realtime_version';
    $version = (string) \Illuminate\Support\Facades\Cache::get($cacheKey, '');
    if ($version === '') {
        $version = (string) floor(microtime(true) * 1000);
        \Illuminate\Support\Facades\Cache::forever($cacheKey, $version);
    }

    return response()->json([
        'success' => true,
        'version' => $version,
        'server_time' => now()->toIso8601String(),
    ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
})->name('api.realtime.version');

// Notifications
Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index')->middleware('permission:notifications.index');
Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.markAsRead')->middleware('permission:notifications.markAsRead');
Route::get('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.readAll')->middleware('permission:notifications.readAll');
Route::post('/notifications/clear', [\App\Http\Controllers\NotificationController::class, 'clearAll'])->name('notifications.clearAll')->middleware('permission:notifications.clearAll');
Route::get('/notifications/latest', [\App\Http\Controllers\NotificationController::class, 'latest'])->name('notifications.latest')->middleware('permission:notifications.latest');

// SAP API Integration
Route::prefix('api/sap')->name('api.sap.')->group(function () {
    // Legacy PO endpoints
    Route::post('/po/search', [SapController::class, 'searchPO'])->middleware('permission:sap.search_po');
    Route::get('/po/{poNumber}', [SapController::class, 'getPODetails'])->middleware('permission:sap.get_po_details');
    Route::post('/slot/sync', [SapController::class, 'syncSlot'])->middleware('permission:sap.sync_slot');

    // New OData V4 endpoints
    Route::get('/po/odata/search', [SapController::class, 'searchPOOdata'])->name('po.odata.search')->middleware('permission:sap.search_po');

    // Vendor endpoints
    Route::get('/vendor/search', [SapController::class, 'searchVendor'])->name('vendor.search')->middleware('permission:sap.search_po');
    Route::get('/vendor/{vendorCode}', [SapController::class, 'getVendor'])->name('vendor.show')->middleware('permission:sap.search_po');

    // Health check & testing
    Route::get('/health', [SapController::class, 'health'])->name('health')->middleware('permission:sap.health');
    Route::get('/metadata', [SapController::class, 'metadata']);
    Route::get('/test-po', [SapController::class, 'testPoConnection'])->name('test.po');
});

// Trucks
Route::prefix('trucks')->name('trucks.')->group(function () {
    Route::get('/', [TruckTypeDurationController::class, 'index'])->name('index')->middleware('permission:trucks.index');
    Route::get('/create', [TruckTypeDurationController::class, 'create'])->name('create')->middleware('permission:trucks.create');
    Route::post('/', [TruckTypeDurationController::class, 'store'])->name('store')->middleware('permission:trucks.store');
    Route::get('/{truckTypeDurationId}/edit', [TruckTypeDurationController::class, 'edit'])->whereNumber('truckTypeDurationId')->name('edit')->middleware('permission:trucks.edit');
    Route::post('/{truckTypeDurationId}/edit', [TruckTypeDurationController::class, 'update'])->whereNumber('truckTypeDurationId')->name('update')->middleware('permission:trucks.update');
    Route::post('/{truckTypeDurationId}/delete', [TruckTypeDurationController::class, 'destroy'])->whereNumber('truckTypeDurationId')->name('delete')->middleware('permission:trucks.delete');
});

// Gates
Route::prefix('gates')->name('gates.')->group(function () {
    Route::get('/', [ReportController::class, 'gatesIndex'])->name('index')
        ->middleware('permission:gates.index');
    Route::get('/monitor', [GateStatusController::class, 'index'])->name('monitor')
        ->middleware('permission:gates.index');

    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('/available-slots', [ReportController::class, 'ajaxAvailableSlots'])->name('available_slots')
            ->middleware('permission:gates.index');
        Route::post('/disabled-times', [ReportController::class, 'ajaxToggleDisabledTime'])->name('disabled_times')
            ->middleware('permission:gates.index');
    });

    Route::post('/{gateId}/toggle', [ReportController::class, 'toggleGate'])->whereNumber('gateId')->name('toggle')
        ->middleware(['permission:gates.toggle', 'role:admin|super account|section head|security|operator']);
});

// Logs
Route::middleware('permission:logs.index')->prefix('logs')->name('logs.')->group(function () {
    Route::get('/', [LogController::class, 'index'])->name('index');
});

// Users
Route::middleware(['permission:users.index'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');

    Route::middleware('permission:users.create')->group(function () {
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
    });

    Route::middleware('permission:users.edit')->group(function () {
        Route::get('/{userId}/edit', [UserController::class, 'edit'])->whereNumber('userId')->name('edit');
        Route::post('/{userId}/edit', [UserController::class, 'update'])->whereNumber('userId')->name('update');
    });

    Route::middleware('permission:users.delete')->group(function () {
        Route::post('/{userId}/delete', [UserController::class, 'destroy'])->whereNumber('userId')->name('delete');
    });

    Route::post('/{userId}/toggle', [UserController::class, 'toggle'])->whereNumber('userId')->name('toggle')->middleware('permission:users.toggle');
});

// Admin Booking Approval
Route::middleware(['permission:bookings.index'])->prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [BookingApprovalController::class, 'index'])->name('index');
    Route::get('/{id}', [BookingApprovalController::class, 'show'])->whereNumber('id')->name('show');

    // Approval actions
    Route::post('/{id}/approve', [BookingApprovalController::class, 'approve'])->whereNumber('id')->name('approve')->middleware('permission:bookings.approve');
    Route::post('/{id}/reject', [BookingApprovalController::class, 'reject'])->whereNumber('id')->name('reject')->middleware('permission:bookings.reject');

    // Reschedule
    Route::get('/{id}/reschedule', [BookingApprovalController::class, 'rescheduleForm'])->whereNumber('id')->name('reschedule')->middleware('permission:bookings.reschedule');
    Route::post('/{id}/reschedule', [BookingApprovalController::class, 'reschedule'])->whereNumber('id')->name('reschedule.store')->middleware('permission:bookings.reschedule');

    // AJAX
    Route::get('/ajax/calendar', [BookingApprovalController::class, 'calendarData'])->name('ajax.calendar')->middleware('permission:bookings.ajax.calendar');
    Route::get('/ajax/pending-count', [BookingApprovalController::class, 'pendingCount'])->name('ajax.pending_count')->middleware('permission:bookings.ajax.pending_count');
    Route::get('/ajax/reminders', [BookingApprovalController::class, 'reminderData'])->name('ajax.reminders')->middleware('permission:bookings.ajax.reminders');
    Route::get('/ajax/check-gate', [BookingApprovalController::class, 'ajaxCheckGateAvailability'])->name('ajax.check_gate')->middleware('permission:bookings.ajax.check_gate');
});

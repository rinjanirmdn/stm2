<?php

/* Admin Routes â€” Reports, Gates, Users, Logs, Trucks, Booking Approval, Notifications, SAP API, Security Dashboard */

use App\Http\Controllers\Admin\OfflineImportController;
use App\Http\Controllers\BookingApprovalController;
use App\Http\Controllers\GateStatusController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\Master\VendorTransporterController;
use App\Http\Controllers\MdBpController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SapController;
use App\Http\Controllers\SecurityDashboardController;
use App\Http\Controllers\TruckTypeDurationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Cache;

// Security Dashboard
Route::prefix('security')->name('security.')->group(function () {
    Route::get('/dashboard', [SecurityDashboardController::class, 'index'])->name('dashboard')->middleware('permission:security.dashboard');
    Route::post('/scan-ticket', [SecurityDashboardController::class, 'scanTicket'])->name('scan')->middleware(['permission:security.scan', 'throttle:30,1']);
    Route::post('/confirm-arrival/{slotId}', [SecurityDashboardController::class, 'confirmArrival'])->name('confirm_arrival')->middleware('permission:security.confirm_arrival');
    Route::get('/ajax/today-slots', [SecurityDashboardController::class, 'ajaxTodaySlots'])->name('ajax.today_slots')->middleware(['permission:security.dashboard', 'throttle:60,1']);
    Route::get('/ajax/slot/{slotId}', [SecurityDashboardController::class, 'slotDetail'])->whereNumber('slotId')->name('ajax.slot_detail')->middleware(['permission:security.dashboard', 'throttle:60,1']);
});

// Reports
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/transactions', [ReportController::class, 'transactions'])->name('transactions')->middleware('permission:reports.transactions');
    Route::get('/search-suggestions', [ReportController::class, 'searchSuggestions'])->name('search_suggestions')->middleware(['permission:reports.search_suggestions', 'throttle:30,1']);
    Route::get('/gate-status', [ReportController::class, 'gateStatus'])->name('gate_status')->middleware('permission:reports.gate_status');

    // Offline Import Routes
    Route::get('/offline-import/template', [OfflineImportController::class, 'downloadTemplate'])->name('offline_import.template')->middleware('permission:reports.offline_import');
    Route::post('/offline-import/upload', [OfflineImportController::class, 'import'])->name('offline_import.upload')->middleware('permission:reports.offline_import');
});

// Real-time gate status streaming
Route::get('/api/gate-status', [GateStatusController::class, 'apiIndex'])->name('api.gate-status')->middleware(['permission:gates.api_index', 'throttle:60,1']);
Route::get('/api/gate-status/stream', [GateStatusController::class, 'stream'])->name('api.gate-status.stream')->middleware(['permission:gates.stream', 'throttle:60,1']);
Route::get('/api/realtime/version', function () {
    $cacheKey = 'st_realtime_version';
    $version = (string) Cache::get($cacheKey, '');
    if ($version === '') {
        $version = (string) floor(microtime(true) * 1000);
        Cache::forever($cacheKey, $version);
    }

    return response()->json([
        'success' => true,
        'version' => $version,
        'server_time' => now()->toIso8601String(),
    ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
})->name('api.realtime.version');

// Notifications
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('permission:notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead')->middleware('permission:notifications.markAsRead');
Route::get('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll')->middleware('permission:notifications.readAll');
Route::post('/notifications/clear', [NotificationController::class, 'clearAll'])->name('notifications.clearAll')->middleware('permission:notifications.clearAll');
Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest')->middleware(['permission:notifications.latest', 'throttle:60,1']);

// SAP API Integration
Route::prefix('api/sap')->name('api.sap.')->group(function () {
    // Legacy PO endpoints
    Route::post('/po/search', [SapController::class, 'searchPO'])->middleware(['permission:sap.search_po', 'throttle:20,1']);
    Route::get('/po/{poNumber}', [SapController::class, 'getPODetails'])->middleware(['permission:sap.get_po_details', 'throttle:20,1']);
    Route::post('/slot/sync', [SapController::class, 'syncSlot'])->middleware(['permission:sap.sync_slot', 'throttle:10,1']);

    // New OData V4 endpoints
    Route::get('/po/odata/search', [SapController::class, 'searchPOOdata'])->name('po.odata.search')->middleware(['permission:sap.search_po', 'throttle:20,1']);

    // Vendor endpoints
    Route::get('/vendor/search', [SapController::class, 'searchVendor'])->name('vendor.search')->middleware(['permission:sap.search_po', 'throttle:20,1']);
    Route::get('/vendor/{vendorCode}', [SapController::class, 'getVendor'])->name('vendor.show')->middleware(['permission:sap.search_po', 'throttle:20,1']);

    // Health check & testing
    Route::get('/health', [SapController::class, 'health'])->name('health')->middleware('permission:sap.health');
    Route::get('/metadata', [SapController::class, 'metadata'])->middleware('permission:sap.health');
    Route::get('/test-po', [SapController::class, 'testPoConnection'])->name('test.po')->middleware('permission:sap.health');
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
            ->middleware(['permission:gates.index', 'throttle:30,1']);
        Route::post('/disabled-times', [ReportController::class, 'ajaxToggleDisabledTime'])->name('disabled_times')
            ->middleware(['permission:gates.index', 'throttle:60,1']);
    });

    Route::post('/{gateId}/toggle', [ReportController::class, 'toggleGate'])->whereNumber('gateId')->name('toggle')
        ->middleware(['permission:gates.toggle', 'role:admin|super account|section head|security|operator|admin wh']);
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
    Route::get('/ajax/calendar', [BookingApprovalController::class, 'calendarData'])->name('ajax.calendar')->middleware(['permission:bookings.ajax.calendar', 'throttle:30,1']);
    Route::get('/ajax/pending-count', [BookingApprovalController::class, 'pendingCount'])->name('ajax.pending_count')->middleware(['permission:bookings.ajax.pending_count', 'throttle:60,1']);
    Route::get('/ajax/reminders', [BookingApprovalController::class, 'reminderData'])->name('ajax.reminders')->middleware(['permission:bookings.ajax.reminders', 'throttle:30,1']);
    Route::get('/ajax/check-gate', [BookingApprovalController::class, 'ajaxCheckGateAvailability'])->name('ajax.check_gate')->middleware(['permission:bookings.ajax.check_gate', 'throttle:30,1']);
});

// -----------------------------------------------------------------------------
// Master Data: Business Partner (md_bp) — Vendor & Customer lokal
// Dibutuhkan untuk form 'Create Planned Uji Coba' (tanpa SAP)
// -----------------------------------------------------------------------------
Route::prefix('md-bp')->name('md_bp.')->middleware('permission:master.bp.index')->group(function () {
    Route::get('/', [MdBpController::class, 'index'])->name('index');
    Route::get('/create', [MdBpController::class, 'create'])->name('create');
    Route::post('/', [MdBpController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [MdBpController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::post('/{id}/edit', [MdBpController::class, 'update'])->whereNumber('id')->name('update');
    Route::post('/{id}/delete', [MdBpController::class, 'destroy'])->whereNumber('id')->name('destroy');
    Route::get('/ajax/search', [MdBpController::class, 'ajaxSearch'])->name('ajax.search');
});

// -----------------------------------------------------------------------------
// Master Data: Vendor Transporters
// -----------------------------------------------------------------------------
Route::prefix('master/transporters')->name('master.transporters.')->middleware('permission:master.transporters.index')->group(function () {
    Route::get('/', [VendorTransporterController::class, 'index'])->name('index');
    Route::get('/create', [VendorTransporterController::class, 'create'])->name('create');
    Route::post('/', [VendorTransporterController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [VendorTransporterController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::post('/{id}/edit', [VendorTransporterController::class, 'update'])->whereNumber('id')->name('update');
    Route::post('/{id}/delete', [VendorTransporterController::class, 'destroy'])->whereNumber('id')->name('destroy');
});

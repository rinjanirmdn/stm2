<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GateStatusController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SapController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\TruckTypeDurationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/waiting-reasons', [DashboardController::class, 'waitingReasons'])->name('dashboard.waitingReasons');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

    Route::prefix('slots')->name('slots.')->group(function () {
        Route::get('/', [SlotController::class, 'index'])->name('index');

        Route::get('/search-suggestions', [SlotController::class, 'searchSuggestions'])->name('search_suggestions');

        Route::middleware('permission:slots.create')->group(function () {
            Route::get('/create', [SlotController::class, 'create'])->name('create');
            Route::post('/', [SlotController::class, 'store'])->name('store');
        });

        Route::get('/{slotId}/edit', [SlotController::class, 'edit'])->whereNumber('slotId')->name('edit')
            ->middleware('permission:slots.edit');
        Route::post('/{slotId}/edit', [SlotController::class, 'update'])->whereNumber('slotId')->name('update')
            ->middleware('permission:slots.update');
        Route::post('/{slotId}/delete', [SlotController::class, 'destroy'])->whereNumber('slotId')->name('delete')
            ->middleware('permission:slots.delete');

        Route::prefix('ajax')->name('ajax.')->group(function () {
            Route::post('/check-risk', [SlotController::class, 'ajaxCheckRisk'])->name('check_risk');
            Route::post('/check-slot-time', [SlotController::class, 'ajaxCheckSlotTime'])->name('check_slot_time');
            Route::post('/recommend-gate', [SlotController::class, 'ajaxRecommendGate'])->name('recommend_gate');
            Route::post('/schedule-preview', [SlotController::class, 'ajaxSchedulePreview'])->name('schedule_preview');

            Route::get('/po-search', [SlotController::class, 'ajaxPoSearch'])->name('po_search');
            Route::get('/po/{poNumber}', [SlotController::class, 'ajaxPoDetail'])->where('poNumber', '[A-Za-z0-9\-]+')->name('po_detail');
        });

        Route::get('/{slotId}/ticket', [SlotController::class, 'ticket'])->whereNumber('slotId')->name('ticket')
            ->middleware('permission:slots.ticket');

        Route::get('/{slotId}', [SlotController::class, 'show'])->whereNumber('slotId')->name('show');

        Route::get('/{slotId}/arrival', [SlotController::class, 'arrival'])->whereNumber('slotId')->name('arrival');
        Route::post('/{slotId}/arrival', [SlotController::class, 'arrivalStore'])->whereNumber('slotId')->name('arrival.store');

        Route::get('/{slotId}/start', [SlotController::class, 'start'])->whereNumber('slotId')->name('start');
        Route::post('/{slotId}/start', [SlotController::class, 'startStore'])->whereNumber('slotId')->name('start.store');

        Route::get('/{slotId}/complete', [SlotController::class, 'complete'])->whereNumber('slotId')->name('complete');
        Route::post('/{slotId}/complete', [SlotController::class, 'completeStore'])->whereNumber('slotId')->name('complete.store');

        Route::get('/{slotId}/cancel', [SlotController::class, 'cancel'])->whereNumber('slotId')->name('cancel')
            ->middleware('permission:slots.cancel');
        Route::post('/{slotId}/cancel', [SlotController::class, 'cancelStore'])->whereNumber('slotId')->name('cancel.store')
            ->middleware('permission:slots.cancel.store');

        // Approval Actions (Mapped to BookingApprovalController)
        Route::post('/{id}/approve', [BookingApprovalController::class, 'approve'])->whereNumber('id')->name('approve')
            ->middleware('permission:bookings.approve');
        Route::post('/{id}/reject', [BookingApprovalController::class, 'reject'])->whereNumber('id')->name('reject')
            ->middleware('permission:bookings.reject');

        // Report routes
        Route::get('/report', [ReportController::class, 'index'])->name('report.index');
        Route::get('/export', [SlotController::class, 'export'])->name('export');
    });

    // Unplanned routes (separate from slots)
    Route::prefix('unplanned')->name('unplanned.')->group(function () {
        Route::get('/', [SlotController::class, 'unplannedIndex'])->name('index');

        Route::middleware('permission:unplanned.create')->group(function () {
            Route::get('/create', [SlotController::class, 'unplannedCreate'])->name('create');
            Route::post('/create', [SlotController::class, 'unplannedStore'])->name('store');
        });

        Route::get('/{slotId}', [SlotController::class, 'show'])->whereNumber('slotId')->name('show');
        Route::get('/{slotId}/edit', [SlotController::class, 'unplannedEdit'])->whereNumber('slotId')->name('edit')
            ->middleware('permission:unplanned.edit');
        Route::post('/{slotId}/edit', [SlotController::class, 'unplannedUpdate'])->whereNumber('slotId')->name('update')
            ->middleware('permission:unplanned.update');
        Route::post('/{slotId}/delete', [SlotController::class, 'unplannedDestroy'])->whereNumber('slotId')->name('delete')
            ->middleware('permission:unplanned.delete');

        // Unplanned specific actions
        Route::get('/{slotId}/start', [SlotController::class, 'unplannedStart'])->whereNumber('slotId')->name('start');
        Route::post('/{slotId}/start', [SlotController::class, 'unplannedStartStore'])->whereNumber('slotId')->name('start.store');
        Route::get('/{slotId}/complete', [SlotController::class, 'unplannedComplete'])->whereNumber('slotId')->name('complete');
        Route::post('/{slotId}/complete', [SlotController::class, 'unplannedCompleteStore'])->whereNumber('slotId')->name('complete.store');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/transactions', [ReportController::class, 'transactions'])->name('transactions');
        Route::get('/search-suggestions', [ReportController::class, 'searchSuggestions'])->name('search_suggestions');
        Route::get('/gate-status', [ReportController::class, 'gateStatus'])->name('gate_status');
    });

    // Real-time gate status streaming
    Route::get('/api/gate-status', [GateStatusController::class, 'apiIndex'])->name('api.gate-status');
    Route::get('/api/gate-status/stream', [GateStatusController::class, 'stream'])->name('api.gate-status.stream');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/clear', [\App\Http\Controllers\NotificationController::class, 'clearAll'])->name('notifications.clearAll');
    Route::get('/notifications/latest', [\App\Http\Controllers\NotificationController::class, 'latest'])->name('notifications.latest');

    // SAP API Integration
    Route::prefix('api/sap')->name('api.sap.')->group(function () {
        // Legacy PO endpoints
        Route::post('/po/search', [SapController::class, 'searchPO']);
        Route::get('/po/{poNumber}', [SapController::class, 'getPODetails']);
        Route::post('/slot/sync', [SapController::class, 'syncSlot']);
        
        // New OData V4 endpoints
        Route::get('/po/odata/search', [SapController::class, 'searchPOOdata'])->name('po.odata.search');
        
        // Vendor endpoints
        Route::get('/vendor/search', [SapController::class, 'searchVendor'])->name('vendor.search');
        Route::get('/vendor/{vendorCode}', [SapController::class, 'getVendor'])->name('vendor.show');
        
        // Health check & testing
        Route::get('/health', [SapController::class, 'health'])->name('health');
        Route::get('/metadata', [SapController::class, 'metadata']);
        Route::get('/test-po', [SapController::class, 'testPoConnection'])->name('test.po');
    });

    Route::prefix('trucks')->name('trucks.')->group(function () {
        Route::get('/', [TruckTypeDurationController::class, 'index'])->name('index');

        Route::get('/create', [TruckTypeDurationController::class, 'create'])->name('create');
        Route::post('/', [TruckTypeDurationController::class, 'store'])->name('store');

        Route::get('/{truckTypeDurationId}/edit', [TruckTypeDurationController::class, 'edit'])->whereNumber('truckTypeDurationId')->name('edit');
        Route::post('/{truckTypeDurationId}/edit', [TruckTypeDurationController::class, 'update'])->whereNumber('truckTypeDurationId')->name('update');

        Route::post('/{truckTypeDurationId}/delete', [TruckTypeDurationController::class, 'destroy'])->whereNumber('truckTypeDurationId')->name('delete');
    });

    Route::prefix('gates')->name('gates.')->group(function () {
        Route::get('/', [ReportController::class, 'gatesIndex'])->name('index');
        Route::get('/monitor', [GateStatusController::class, 'index'])->name('monitor');
        Route::middleware('permission:gates.toggle')->group(function () {
            Route::post('/{gateId}/toggle', [ReportController::class, 'toggleGate'])->whereNumber('gateId')->name('toggle');
        });
    });

    Route::middleware('permission:logs.index')->prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [LogController::class, 'index'])->name('index');
    });

    Route::middleware('permission:users.index')->prefix('users')->name('users.')->group(function () {
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

        Route::post('/{userId}/toggle', [UserController::class, 'toggle'])->whereNumber('userId')->name('toggle')
            ->middleware('permission:users.toggle');
    });

    // ========================================
    // VENDOR BOOKING ROUTES
    // ========================================
    Route::middleware(['role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
        // Vendor Dashboard
        Route::get('/dashboard', [VendorBookingController::class, 'dashboard'])->name('dashboard');
        
        // Bookings
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [VendorBookingController::class, 'index'])->name('index');
            Route::get('/create', [VendorBookingController::class, 'create'])->name('create');
            Route::post('/', [VendorBookingController::class, 'store'])->name('store');
            Route::get('/{id}', [VendorBookingController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{id}/ticket', [VendorBookingController::class, 'ticket'])->whereNumber('id')->name('ticket');
            Route::post('/{id}/cancel', [VendorBookingController::class, 'cancel'])->whereNumber('id')->name('cancel');
        });
        
        // Availability
        Route::get('/availability', [VendorBookingController::class, 'availability'])->name('availability');
        
        // AJAX endpoints
        Route::prefix('ajax')->name('ajax.')->group(function () {
            Route::get('/available-slots', [VendorBookingController::class, 'getAvailableSlots'])->name('available_slots');
            Route::get('/check-availability', [VendorBookingController::class, 'checkAvailability'])->name('check_availability');
            Route::get('/truck-type-duration', [VendorBookingController::class, 'getTruckTypeDuration'])->name('truck_type_duration');
            Route::get('/calendar-slots', [VendorBookingController::class, 'calendarSlots'])->name('calendar_slots');
            Route::get('/po-search', [VendorBookingController::class, 'ajaxPoSearch'])->name('po_search');
            Route::get('/po/{poNumber}', [VendorBookingController::class, 'ajaxPoDetail'])->where('poNumber', '[A-Za-z0-9\-]+')->name('po_detail');
        });
    });
    
    // Remove duplicate AJAX routes

    // ========================================
    // ADMIN BOOKING APPROVAL ROUTES
    // ========================================
    Route::middleware(['permission:bookings.index'])->prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingApprovalController::class, 'index'])->name('index');
        Route::get('/{id}', [BookingApprovalController::class, 'show'])->whereNumber('id')->name('show');
        
        // Approval actions
        Route::post('/{id}/approve', [BookingApprovalController::class, 'approve'])->whereNumber('id')->name('approve')
            ->middleware('permission:bookings.approve');
        Route::post('/{id}/reject', [BookingApprovalController::class, 'reject'])->whereNumber('id')->name('reject')
            ->middleware('permission:bookings.reject');
        
        // Reschedule
        Route::get('/{id}/reschedule', [BookingApprovalController::class, 'rescheduleForm'])->whereNumber('id')->name('reschedule')
            ->middleware('permission:bookings.reschedule');
        Route::post('/{id}/reschedule', [BookingApprovalController::class, 'reschedule'])->whereNumber('id')->name('reschedule.store')
            ->middleware('permission:bookings.reschedule');
        
        // AJAX
        Route::get('/ajax/calendar', [BookingApprovalController::class, 'calendarData'])->name('ajax.calendar');
        Route::get('/ajax/pending-count', [BookingApprovalController::class, 'pendingCount'])->name('ajax.pending_count');
        Route::get('/ajax/reminders', [BookingApprovalController::class, 'reminderData'])->name('ajax.reminders');
    });
});



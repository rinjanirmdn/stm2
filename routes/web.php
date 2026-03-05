<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GateStatusController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SapController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\SlotAjaxController;
use App\Http\Controllers\SlotLifecycleController;
use App\Http\Controllers\UnplannedSlotController;
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
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('forgot-password');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetEmail'])->name('forgot-password.send');
});


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard')->middleware('permission:dashboard.view');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data')->middleware('permission:dashboard.view');
    Route::get('/dashboard/waiting-reasons', [DashboardController::class, 'waitingReasons'])->name('dashboard.waitingReasons')->middleware('permission:dashboard.range_filter');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile')->middleware('permission:profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('permission:profile.index');
    Route::post('/profile/password-request', [ForgotPasswordController::class, 'requestFromProfile'])->name('profile.password-request')->middleware('permission:profile.index');

    Route::prefix('slots')->name('slots.')->group(function () {
        Route::get('/', [SlotController::class, 'index'])->name('index')
            ->middleware('permission:slots.index');

        Route::get('/search-suggestions', [SlotAjaxController::class, 'searchSuggestions'])->name('search_suggestions')->middleware('permission:slots.search_suggestions');

        // Block operator from create, edit, cancel, ticket
        Route::middleware(['permission:slots.create', 'role:admin|super account|section head|security'])->group(function () {
            Route::get('/create', [SlotController::class, 'create'])->name('create');
            Route::post('/', [SlotController::class, 'store'])->name('store');
        });

        Route::get('/{slotId}/edit', [SlotController::class, 'edit'])->whereNumber('slotId')->name('edit')
            ->middleware(['permission:slots.edit', 'role:admin|super account|section head|security']);
        Route::post('/{slotId}/edit', [SlotController::class, 'update'])->whereNumber('slotId')->name('update')
            ->middleware(['permission:slots.update', 'role:admin|super account|section head|security']);
        Route::post('/{slotId}/delete', [SlotController::class, 'destroy'])->whereNumber('slotId')->name('delete')
            ->middleware(['permission:slots.delete', 'role:admin|super account|section head|security']);

        Route::prefix('ajax')->name('ajax.')->group(function () {
            Route::post('/check-risk', [SlotAjaxController::class, 'ajaxCheckRisk'])->name('check_risk')->middleware('permission:slots.ajax.check_risk');
            Route::post('/check-slot-time', [SlotAjaxController::class, 'ajaxCheckSlotTime'])->name('check_slot_time')->middleware('permission:slots.ajax.check_slot_time');
            Route::post('/recommend-gate', [SlotAjaxController::class, 'ajaxRecommendGate'])->name('recommend_gate')->middleware('permission:slots.ajax.recommend_gate');
            Route::post('/schedule-preview', [SlotAjaxController::class, 'ajaxSchedulePreview'])->name('schedule_preview')->middleware('permission:slots.ajax.schedule_preview');

            Route::get('/po-search', [SlotAjaxController::class, 'ajaxPoSearch'])->name('po_search')->middleware('permission:slots.ajax.po_search');
            Route::get('/po/{poNumber}', [SlotAjaxController::class, 'ajaxPoDetail'])->where('poNumber', '[A-Za-z0-9\-]+')->name('po_detail')->middleware('permission:slots.ajax.po_detail');
        });

        Route::get('/{slotId}/ticket', [SlotLifecycleController::class, 'ticket'])->whereNumber('slotId')->name('ticket')
            ->middleware(['permission:slots.ticket', 'role:admin|super account|section head|security']);

        Route::get('/{slotId}', [SlotController::class, 'show'])->whereNumber('slotId')->name('show')
            ->middleware('permission:slots.show');

        Route::get('/{slotId}/arrival', [SlotLifecycleController::class, 'arrival'])->whereNumber('slotId')->name('arrival')
            ->middleware(['permission:slots.arrival', 'role:admin|super account|section head|security']);
        Route::post('/{slotId}/arrival', [SlotLifecycleController::class, 'arrivalStore'])->whereNumber('slotId')->name('arrival.store')
            ->middleware(['permission:slots.arrival.store', 'role:admin|super account|section head|security']);

        Route::get('/{slotId}/start', [SlotLifecycleController::class, 'start'])->whereNumber('slotId')->name('start')
            ->middleware('permission:slots.start');
        Route::post('/{slotId}/start', [SlotLifecycleController::class, 'startStore'])->whereNumber('slotId')->name('start.store')
            ->middleware('permission:slots.start.store');

        Route::get('/{slotId}/complete', [SlotLifecycleController::class, 'complete'])->whereNumber('slotId')->name('complete')
            ->middleware('permission:slots.complete');
        Route::post('/{slotId}/complete', [SlotLifecycleController::class, 'completeStore'])->whereNumber('slotId')->name('complete.store')
            ->middleware('permission:slots.complete.store');

        Route::get('/{slotId}/cancel', [SlotController::class, 'cancel'])->whereNumber('slotId')->name('cancel')
            ->middleware(['permission:slots.cancel', 'role:admin|super account|section head|security']);
        Route::post('/{slotId}/cancel', [SlotController::class, 'cancelStore'])->whereNumber('slotId')->name('cancel.store')
            ->middleware(['permission:slots.cancel.store', 'role:admin|super account|section head|security']);

        // Report routes
        Route::get('/report', [ReportController::class, 'index'])->name('report.index')->middleware('permission:reports.transactions');
        Route::get('/export', [SlotController::class, 'export'])->name('export')->middleware('permission:reports.export');
    });

    // Unplanned routes (separate from slots)
    Route::prefix('unplanned')->name('unplanned.')->group(function () {
        Route::get('/', [UnplannedSlotController::class, 'index'])->name('index')->middleware('permission:unplanned.index');

        // Block operator from create, edit, cancel, ticket
        Route::middleware(['permission:unplanned.create', 'role:admin|super account|section head|security'])->group(function () {
            Route::get('/create', [UnplannedSlotController::class, 'create'])->name('create');
            Route::post('/create', [UnplannedSlotController::class, 'store'])->name('store');
        });

        Route::get('/{slotId}/edit', [UnplannedSlotController::class, 'edit'])->whereNumber('slotId')->name('edit')
            ->middleware(['permission:unplanned.edit', 'role:admin|super account|section head|security']);
        Route::post('/{slotId}/edit', [UnplannedSlotController::class, 'update'])->whereNumber('slotId')->name('update')
            ->middleware(['permission:unplanned.update', 'role:admin|super account|section head|security']);
        Route::post('/{slotId}/delete', [UnplannedSlotController::class, 'destroy'])->whereNumber('slotId')->name('delete')
            ->middleware(['permission:unplanned.delete', 'role:admin|super account|section head|security']);

        // Allow operator to use ticket (but not create/edit/cancel)
        Route::get('/{slotId}/ticket', [SlotLifecycleController::class, 'ticket'])->whereNumber('slotId')->name('ticket')
            ->middleware(['permission:slots.ticket', 'role:admin|super account|section head|security']);

        // Unplanned specific actions (operator can use these)
        Route::get('/{slotId}/start', [SlotLifecycleController::class, 'unplannedStart'])->whereNumber('slotId')->name('start')
            ->middleware('permission:unplanned.start');
        Route::post('/{slotId}/start', [SlotLifecycleController::class, 'unplannedStartStore'])->whereNumber('slotId')->name('start.store')
            ->middleware('permission:unplanned.start.store');
        Route::get('/{slotId}/complete', [SlotLifecycleController::class, 'unplannedComplete'])->whereNumber('slotId')->name('complete')
            ->middleware('permission:unplanned.complete');
        Route::post('/{slotId}/complete', [SlotLifecycleController::class, 'unplannedCompleteStore'])->whereNumber('slotId')->name('complete.store')
            ->middleware('permission:unplanned.complete.store');

        Route::get('/{slotId}', [SlotController::class, 'show'])->whereNumber('slotId')->name('show');
    });

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

    });

    Route::prefix('trucks')->name('trucks.')->group(function () {
        Route::get('/', [TruckTypeDurationController::class, 'index'])->name('index')->middleware('permission:trucks.index');

        // Operator: only view, no create/edit/delete
        Route::middleware(['role:admin|super account|section head|security'])->group(function () {
            Route::get('/create', [TruckTypeDurationController::class, 'create'])->name('create');
            Route::post('/', [TruckTypeDurationController::class, 'store'])->name('store');
            Route::get('/{truckTypeDurationId}/edit', [TruckTypeDurationController::class, 'edit'])->whereNumber('truckTypeDurationId')->name('edit');
            Route::post('/{truckTypeDurationId}/edit', [TruckTypeDurationController::class, 'update'])->whereNumber('truckTypeDurationId')->name('update');
            Route::post('/{truckTypeDurationId}/delete', [TruckTypeDurationController::class, 'destroy'])->whereNumber('truckTypeDurationId')->name('delete');
        });
    });

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

        // Gate toggle: all authorized roles (operator limited to Gate C in controller)
        Route::post('/{gateId}/toggle', [ReportController::class, 'toggleGate'])->whereNumber('gateId')->name('toggle')
            ->middleware(['permission:gates.toggle', 'role:admin|super account|section head|security|operator']);
    });

    Route::middleware('permission:logs.index')->prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [LogController::class, 'index'])->name('index');
    });

    Route::middleware(['permission:users.index', 'role:admin|security'])->prefix('users')->name('users.')->group(function () {
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
    Route::middleware(['role:Vendor'])->prefix('vendor')->name('vendor.')->group(function () {
        // Vendor Dashboard
        Route::get('/dashboard', [VendorBookingController::class, 'dashboard'])->name('dashboard');

        // Bookings
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [VendorBookingController::class, 'index'])->name('index');
            Route::get('/create', [VendorBookingController::class, 'create'])->name('create');
            Route::post('/', [VendorBookingController::class, 'store'])->name('store');
            Route::get('/{id}', [VendorBookingController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{slotId}/ticket', [SlotController::class, 'ticket'])->whereNumber('slotId')->name('ticket');
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
    Route::middleware(['permission:bookings.index', 'role:admin|super account|section head|security'])->prefix('bookings')->name('bookings.')->group(function () {
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
        Route::get('/ajax/check-gate', [BookingApprovalController::class, 'ajaxCheckGateAvailability'])->name('ajax.check_gate');
    });
});



<?php
/* Vendor Portal Routes */

use App\Http\Controllers\SlotController;
use App\Http\Controllers\VendorBookingController;

Route::middleware('vendor.portal')->prefix('vendor')->name('vendor.')->group(function () {
    // Vendor Dashboard
    Route::get('/dashboard', [VendorBookingController::class, 'dashboard'])->name('dashboard')->middleware('permission:vendor.dashboard');

    // Bookings
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [VendorBookingController::class, 'index'])->name('index')->middleware('permission:vendor.bookings.index');
        Route::get('/create', [VendorBookingController::class, 'create'])->name('create')->middleware('permission:vendor.bookings.create');
        Route::post('/', [VendorBookingController::class, 'store'])->name('store')->middleware('permission:vendor.bookings.store');
        Route::get('/{id}', [VendorBookingController::class, 'show'])->whereNumber('id')->name('show')->middleware('permission:vendor.bookings.show');
        Route::get('/{slotId}/ticket', [VendorBookingController::class, 'ticket'])->whereNumber('slotId')->name('ticket')->middleware('permission:vendor.bookings.ticket');
        Route::post('/{id}/cancel', [VendorBookingController::class, 'cancel'])->whereNumber('id')->name('cancel')->middleware('permission:vendor.bookings.cancel');
    });

    // Availability
    Route::get('/availability', [VendorBookingController::class, 'availability'])->name('availability')->middleware('permission:vendor.availability');

    // AJAX endpoints
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('/available-slots', [VendorBookingController::class, 'getAvailableSlots'])->name('available_slots')->middleware('permission:vendor.ajax.available_slots');
        Route::get('/check-availability', [VendorBookingController::class, 'checkAvailability'])->name('check_availability')->middleware('permission:vendor.ajax.check_availability');
        Route::get('/truck-type-duration', [VendorBookingController::class, 'getTruckTypeDuration'])->name('truck_type_duration')->middleware('permission:vendor.ajax.truck_type_duration');
        Route::get('/calendar-slots', [VendorBookingController::class, 'calendarSlots'])->name('calendar_slots')->middleware('permission:vendor.ajax.calendar_slots');
        Route::get('/po-search', [VendorBookingController::class, 'ajaxPoSearch'])->name('po_search')->middleware('permission:vendor.ajax.po_search');
        Route::get('/po/{poNumber}', [VendorBookingController::class, 'ajaxPoDetail'])->where('poNumber', '[A-Za-z0-9\-]+')->name('po_detail')->middleware('permission:vendor.ajax.po_detail');
    });
});

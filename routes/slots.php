<?php
/* Slot (Planned) Routes */

use App\Http\Controllers\ReportController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\SlotAjaxController;
use App\Http\Controllers\SlotLifecycleController;

Route::prefix('slots')->name('slots.')->group(function () {
    Route::get('/', [SlotController::class, 'index'])->name('index')->middleware('permission:slots.index');

    Route::get('/search-suggestions', [SlotAjaxController::class, 'searchSuggestions'])->name('search_suggestions')->middleware('permission:slots.search_suggestions');

    Route::middleware(['permission:slots.create'])->group(function () {
        Route::get('/create', [SlotController::class, 'create'])->name('create');
        Route::post('/', [SlotController::class, 'store'])->name('store');
    });

    Route::get('/{slotId}/edit', [SlotController::class, 'edit'])->whereNumber('slotId')->name('edit')->middleware('permission:slots.edit');
    Route::post('/{slotId}/edit', [SlotController::class, 'update'])->whereNumber('slotId')->name('update')->middleware('permission:slots.update');
    Route::post('/{slotId}/delete', [SlotController::class, 'destroy'])->whereNumber('slotId')->name('delete')->middleware('permission:slots.delete');

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

    Route::get('/{slotId}/cancel', [SlotController::class, 'cancel'])->whereNumber('slotId')->name('cancel')->middleware('permission:slots.cancel');
    Route::post('/{slotId}/cancel', [SlotController::class, 'cancelStore'])->whereNumber('slotId')->name('cancel.store')->middleware('permission:slots.cancel.store');

    // Report routes
    Route::get('/report', [ReportController::class, 'index'])->name('report.index')->middleware('permission:reports.transactions');
    Route::get('/export', [SlotController::class, 'export'])->name('export')->middleware('permission:reports.export');
});

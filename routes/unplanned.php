<?php
/* Unplanned Slot Routes */

use App\Http\Controllers\SlotController;
use App\Http\Controllers\SlotLifecycleController;
use App\Http\Controllers\UnplannedSlotController;

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

    Route::get('/{slotId}', [SlotController::class, 'show'])->whereNumber('slotId')->name('show')->middleware('permission:unplanned.show');
});

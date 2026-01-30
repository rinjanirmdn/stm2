@extends('layouts.app')

@section('title', 'Cancel Slot - Slot Time Management')
@section('page_title', 'Cancel Slot')

@section('content')
    <div class="st-card st-mb-12">
        <div class="st-text--sm st-text--muted">Slot #{{ $slot->id }}</div>
        <div class="st-font-semibold">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.cancel.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-mb-10">Are You Sure You Want to Cancel This Slot?</div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Cancellation Reason <span class="st-text--danger-dark">*</span></label>
                    <textarea name="cancelled_reason" class="st-textarea" rows="3" required>{{ old('cancelled_reason') }}</textarea>
                </div>
            </div>

            <div class="st-form-actions">
                <button type="submit" class="st-btn st-btn--danger">Yes, Cancel Slot</button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--outline-primary">Back</a>
            </div>
        </form>
    </div>
@endsection

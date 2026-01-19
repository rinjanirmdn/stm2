@extends('layouts.app')

@section('title', 'Cancel Slot - Slot Time Management')
@section('page_title', 'Cancel Slot')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div style="font-size:12px;color:#6b7280;">Slot #{{ $slot->id }}</div>
        <div style="font-weight:600;">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.cancel.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div style="margin-bottom:10px;">Are you sure you want to cancel this slot?</div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Cancellation Reason <span style="color:#dc2626;">*</span></label>
                    <textarea name="cancelled_reason" class="st-textarea" rows="3" required>{{ old('cancelled_reason') }}</textarea>
                </div>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="st-btn" style="background-color:#dc2626;border-color:#b91c1c;color:#ffffff;">Yes, Cancel Slot</button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--secondary">Back</a>
            </div>
        </form>
    </div>
@endsection

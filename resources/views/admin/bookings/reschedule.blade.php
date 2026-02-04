@extends('layouts.app')

@section('title', 'Reschedule Booking')
@section('page_title', 'Reschedule Booking')

@section('content')
<div class="st-card">
    <div class="st-card__header">
        <h2 class="st-card__title">
            <i class="fas fa-calendar-alt"></i>
            Reschedule Request {{ $booking->request_number ?? ('REQ-' . $booking->id) }}
        </h2>
        <a href="{{ route('bookings.show', $booking->id) }}" class="st-btn st-btn--secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>

    <!-- Current Schedule Info -->
    <div class="st-alert st-alert--info">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Current Request:</strong>
            {{ $booking->planned_start?->format('d M Y H:i') ?? '-' }}
            ({{ $booking->planned_duration }} Min)
            - Requested by {{ $booking->requester?->full_name ?? 'Vendor' }}
        </div>
    </div>

    <form method="POST" action="{{ route('bookings.reschedule.store', $booking->id) }}">
        @csrf

        <div class="st-form-grid">
            <!-- Left Column: Current Info -->
            <div class="st-form-section">
                <h3 class="st-form-section__title">
                    <i class="fas fa-clock"></i>
                    Vendor's Request
                </h3>

                <table class="st-detail-table st-table st-table--sm">
                    <tr>
                        <td>Supplier</td>
                        <td><strong>{{ $booking->supplier_name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Direction</td>
                        <td>
                            <span class="st-badge st-badge--{{ $booking->direction === 'inbound' ? 'info' : 'warning' }}">
                                {{ ucfirst($booking->direction) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Requested Date</td>
                        <td>{{ $booking->planned_start?->format('d M Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Requested Time</td>
                        <td>{{ $booking->planned_start?->format('H:i') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Duration</td>
                        <td>{{ $booking->planned_duration }} Min</td>
                    </tr>
                    <tr>
                        <td>Truck Type</td>
                        <td>{{ $booking->truck_type ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            <!-- Right Column: New Schedule -->
            <div class="st-form-section">
                <h3 class="st-form-section__title">
                    <i class="fas fa-edit"></i>
                    New Schedule
                </h3>

                <div class="admin-form-group">
                    <label class="admin-form-label">Gate <span class="st-text-danger">*</span></label>
                    <select name="planned_gate_id" id="gate_select" class="admin-form-select" required>
                        <option value="">Select Gate...</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option value="{{ $gate->id }}" data-warehouse-id="{{ $gate->warehouse_id }}" {{ (string)old('planned_gate_id', $booking->planned_gate_id ?? '') === (string)$gate->id ? 'selected' : '' }}>
                                {{ $gateLabel }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="warehouse_id" id="warehouse_hidden" value="">
                </div>

                <div class="st-form-group">
                    <label class="st-label">Date <span class="st-required">*</span></label>
                    <input type="date" name="planned_date" class="st-input" required min="{{ now()->format('Y-m-d') }}"
                           value="{{ old('planned_date', $booking->planned_start?->format('Y-m-d')) }}"
                           id="planned_date" placeholder="Select Date">
                    @error('planned_date')
                        <span class="st-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="st-form-group">
                    <label class="st-label">Time <span class="st-required">*</span></label>
                    <input type="time" name="planned_time" class="st-input" required
                           min="07:00" max="22:00"
                           value="{{ old('planned_time', $booking->planned_start?->format('H:i')) }}"
                           id="planned_time">
                    <small class="st-hint">Operating hours: 07:00 - 23:00</small>
                    @error('planned_time')
                        <span class="st-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="st-form-group">
                    <label class="st-label">Duration (minutes) <span class="st-required">*</span></label>
                    <input type="number" name="planned_duration" class="st-input" required
                           min="30" max="480" step="1"
                           value="{{ old('planned_duration', $booking->planned_duration) }}"
                           id="planned_duration">
                    @error('planned_duration')
                        <span class="st-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="st-form-group st-mt-24">
                    <label class="st-label">Notes for Vendor</label>
                    <textarea name="notes" class="st-textarea" rows="3"
                              placeholder="Explain Why You're Rescheduling This Booking...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="st-form-actions">
            <a href="{{ route('bookings.show', $booking->id) }}" class="st-btn st-btn--secondary">
                Cancel
            </a>
            <button type="submit" class="st-btn st-btn--warning">
                <i class="fas fa-calendar-alt"></i>
                Reschedule & Approve
            </button>
        </div>
    </form>
</div>

@endsection

<script type="application/json" id="reschedule_gates_json">{!! $gates->map(function ($coll, $wid) {
    $arr = [];
    foreach ($coll as $g) {
        $arr[] = [
            'id' => (int) ($g->id ?? 0),
            'warehouse_id' => (int) ($g->warehouse_id ?? 0),
            'gate_number' => (string) ($g->gate_number ?? ''),
            'name' => (string) ($g->name ?? ''),
            'warehouse_code' => (string) ($g->warehouse?->wh_code ?? ''),
        ];
    }
    return $arr;
})->toJson() !!}</script>

@push('scripts')
<script type="application/json" id="admin_bookings_reschedule_config">{!! json_encode([
    'bookingId' => (int) ($booking->id ?? 0),
    'warehouseId' => (int) ($booking->warehouse_id ?? 0),
    'calendarBaseUrl' => url('/bookings/ajax/calendar'),
]) !!}</script>
@vite(['resources/js/pages/admin-bookings-reschedule.js'])
@endpush


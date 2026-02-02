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

                <table class="st-detail-table">
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

<style>
.st-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin: 1.5rem 0;
}

.st-form-section {
    background: var(--st-surface-alt, #f8fafc);
    padding: 1.5rem;
    border-radius: 12px;
}

.st-form-section__title {
    margin: 0 0 1.25rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--st-text-primary, #1e293b);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.st-detail-table {
    width: 100%;
}

.st-detail-table td {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--st-border, #e5e7eb);
}

.st-detail-table td:first-child {
    color: var(--st-text-muted, #64748b);
    width: 40%;
}

.st-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--st-border, #e5e7eb);
}
</style>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('planned_date');
    const timeInput = document.getElementById('planned_time');
    const durationInput = document.getElementById('planned_duration');
    const gateSelect = document.getElementById('gate_select');
    const availabilityCheck = document.getElementById('availability-check');
    const availabilityResult = document.getElementById('availability-result');
    const calendarPreview = document.getElementById('calendar-preview');
    const warehouseHidden = document.getElementById('warehouse_hidden');

    // Gate and warehouse sync
    function syncWarehouseFromGate() {
        const gateSelect = document.getElementById('gate_select');
        if (!gateSelect || !warehouseHidden) return;
        const selected = gateSelect.options[gateSelect.selectedIndex];
        if (!selected) return;
        warehouseHidden.value = selected.getAttribute('data-warehouse-id') || '';

        // Load availability after warehouse changes
        checkAvailability();
        loadCalendarPreview();
    }

    // Check availability when inputs change
    [dateInput, timeInput, durationInput, gateSelect].forEach(function(input) {
        input.addEventListener('change', checkAvailability);
    });

    function checkAvailability() {
        const gateId = gateSelect.value;
        const date = dateInput.value;
        const time = timeInput.value;
        const duration = durationInput.value;

        if (!date || !time || !duration) {
            availabilityCheck.style.display = 'none';
            return;
        }

        const plannedStart = date + ' ' + time + ':00';

        const warehouseId = (warehouseHidden && warehouseHidden.value) ? warehouseHidden.value : '{{ $booking->warehouse_id }}';

        fetch(`/bookings/ajax/calendar?warehouse_id=${warehouseId}&gate_id=${gateId}&planned_start=${encodeURIComponent(plannedStart)}&planned_duration=${duration}&exclude_slot_id={{ $booking->id }}`)
            .then(response => response.json())
            .then(data => {
                availabilityCheck.style.display = 'block';

                if (data.available !== false) {
                    availabilityResult.innerHTML = `
                        <div class="st-flex st-items-center st-gap-8 st-text-success">
                            <i class="fas fa-check-circle"></i>
                            <span>Time Slot Is Available</span>
                        </div>
                    `;
                } else {
                    availabilityResult.innerHTML = `
                        <div class="st-flex st-items-center st-gap-8 st-text-danger">
                            <i class="fas fa-times-circle"></i>
                            <span>${data.reason || 'Time Slot Is Not Available'}</span>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    function loadCalendarPreview() {
        const date = dateInput.value;

        const warehouseId = (warehouseHidden && warehouseHidden.value) ? warehouseHidden.value : '{{ $booking->warehouse_id }}';

        if (!date) {
            return;
        }

        calendarPreview.innerHTML = '<p class="st-text-center st-text--muted st-p-20"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

        fetch(`/bookings/ajax/calendar?warehouse_id=${warehouseId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.gates) {
                    renderCalendar(data.gates);
                }
            })
            .catch(error => {
                calendarPreview.innerHTML = '<p class="st-text-center st-text-danger st-p-20">Failed to load</p>';
            });
    }

    function renderCalendar(gates) {
        const hours = [];
        for (let h = 7; h < 23; h++) {
            hours.push(h.toString().padStart(2, '0') + ':00');
        }

        let html = '<div class="st-cal-preview__wrap"><table class="st-cal-preview__table">';
        html += '<thead><tr><th>Time</th>';

        gates.forEach(g => {
            html += `<th>${g.gate.name}</th>`;
        });
        html += '</tr></thead><tbody>';

        hours.forEach(hour => {
            html += `<tr><td class="st-font-semibold">${hour}</td>`;

            gates.forEach(g => {
                const slot = g.slots.find(s => s.start_time === hour);
                const occupiedSlot = g.slots.find(s => s.start_time < hour && s.end_time > hour);

                if (slot) {
                    const isPending = slot.status === 'pending_approval';
                    const isCurrentBooking = slot.id === {{ $booking->id }};
                    const bgColor = isCurrentBooking ? '#fef3c7' : (isPending ? '#fce7f3' : '#dcfce7');

                    const stateClass = isPending ? 'st-cal-preview__pending' : 'st-cal-preview__occupied';
                    const currentBadge = isCurrentBooking ? '<div class="st-cal-preview__current">Current</div>' : '';
                    html += `<td class="${stateClass}">
                        <div class="st-font-semibold">${slot.start_time} - ${slot.end_time}</div>
                        <div class="st-text--sm st-text--muted">${slot.vendor_name}</div>
                        ${currentBadge}
                    </td>`;
                } else if (!occupiedSlot) {
                    html += `<td class="st-cal-preview__slot">
                        <i class="fas fa-check st-cal-preview__slot-icon"></i>
                    </td>`;
                } else {
                    html += `<td></td>`;
                }
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        calendarPreview.innerHTML = html;
    }

    // Gate change listener
    const gateSelect = document.getElementById('gate_select');
    if (gateSelect) {
        gateSelect.addEventListener('change', function() {
            syncWarehouseFromGate();
        });
        // Initial sync if gate is pre-selected
        if (gateSelect.value) {
            syncWarehouseFromGate();
        }
    }

    // Initial load
    checkAvailability();
    loadCalendarPreview();
});
</script>
@endpush

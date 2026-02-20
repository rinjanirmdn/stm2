@extends('layouts.app')

@section('title', 'Reschedule Booking')
@section('page_title', 'Reschedule Booking')

@push('styles')
    @vite(['resources/css/bookings.css'])
@endpush

@section('content')
<div class="st-card">
    <div class="st-card__header">
        <h2 class="st-card__title">
            <i class="fas fa-calendar-alt"></i>
            Reschedule Request {{ $booking->request_number ?? ('REQ-' . $booking->id) }}
        </h2>
    </div>

    <!-- Top: Compact booking summary (3 cards) -->
    <div class="booking-detail-grid booking-detail-grid--compact st-mb-20">
        <!-- Request Information Card -->
        <div class="detail-card detail-card--compact">
            <div class="detail-card__header">
                <h3 class="detail-card__title">
                    <i class="fas fa-info-circle"></i>
                    Request Information
                </h3>
            </div>
            <div class="detail-card__body">
                <div class="detail-grid-compact">
                    <div class="detail-item">
                        <label class="detail-label">Request Number</label>
                        <div class="detail-value">{{ $booking->request_number ?? ('REQ-' . $booking->id) }}</div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Status</label>
                        <div class="detail-value">
                            @php
                                $badgeColor = match($booking->status) {
                                    'pending' => 'pending_approval',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'cancelled' => 'secondary',
                                    default => 'secondary',
                                };
                                $badgeLabel = match($booking->status) {
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                    'cancelled' => 'Cancelled',
                                    default => ucfirst(str_replace('_',' ', (string) $booking->status)),
                                };
                            @endphp
                            <span class="st-badge st-badge--{{ $badgeColor }}">{{ $badgeLabel }}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Supplier</label>
                        <div class="detail-value">{{ $booking->supplier_name ?? '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Requested By</label>
                        <div class="detail-value">{{ $booking->requester?->full_name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Information Card -->
        <div class="detail-card detail-card--compact">
            <div class="detail-card__header">
                <h3 class="detail-card__title">
                    <i class="fas fa-calendar-alt"></i>
                    Current Schedule
                </h3>
            </div>
            <div class="detail-card__body">
                <div class="detail-grid-compact">
                    <div class="detail-item">
                        <label class="detail-label">Warehouse</label>
                        <div class="detail-value">{{ $booking->warehouse_id ? ($booking->convertedSlot?->warehouse?->wh_code ?? '-') : '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Gate</label>
                        <div class="detail-value">
                            {{ app(\App\Services\SlotService::class)->getGateDisplayName(
                                $booking->convertedSlot?->plannedGate?->warehouse->wh_code ?? '',
                                $booking->convertedSlot?->plannedGate?->gate_number ?? ''
                            ) ?: 'To be assigned' }}
                        </div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Date</label>
                        <div class="detail-value">{{ $booking->planned_start?->format('d M Y') ?? '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Time</label>
                        <div class="detail-value">{{ $booking->planned_start?->format('H:i') ?? '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Duration</label>
                        <div class="detail-value">{{ $booking->planned_duration }} minutes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Information Card -->
        <div class="detail-card detail-card--compact">
            <div class="detail-card__header">
                <h3 class="detail-card__title">
                    <i class="fas fa-truck"></i>
                    Vehicle Information
                </h3>
            </div>
            <div class="detail-card__body">
                <div class="detail-grid-compact">
                    <div class="detail-item">
                        <label class="detail-label">Truck Type</label>
                        <div class="detail-value">{{ $booking->truck_type ?? '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Vehicle Number</label>
                        <div class="detail-value">{{ $booking->vehicle_number ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('bookings.reschedule.store', $booking->id) }}">
        @csrf

        <div class="st-form-grid">
            <!-- Left: New Schedule form -->
            <div class="st-form-section st-form-section--outlined-blue">
                <h3 class="st-form-section__title">
                    <i class="fas fa-edit"></i>
                    New Schedule
                </h3>

                <div class="reschedule-form-left">
                    <div class="st-form-group">
                        <label class="st-label">Gate <span class="st-required">*</span></label>
                        <select name="planned_gate_id" id="gate_select" class="st-select" required>
                            <option value="">Select Gate...</option>
                            @foreach ($gates as $gate)
                                @php
                                    $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse?->wh_code ?? '', $gate->gate_number ?? '');
                                @endphp
                                <option value="{{ $gate->id }}" data-warehouse-id="{{ $gate->warehouse_id }}" {{ (string)old('planned_gate_id', $booking->planned_gate_id ?? '') === (string)$gate->id ? 'selected' : '' }}>
                                    {{ $gateLabel }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="warehouse_id" id="warehouse_hidden" value="">
                        <div id="reschedule_gate_availability" class="st-text-12 st-mt-4"></div>
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

                    <div class="st-form-group st-mt-24 reschedule-notes">
                        <label class="st-label">Notes for Vendor</label>
                        <textarea name="notes" class="st-textarea" rows="3"
                                  placeholder="Explain Why You're Rescheduling This Booking...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Right: List Schedule (availability) -->
            <div class="st-form-section st-form-section--outlined-blue">
                <h3 class="st-form-section__title">
                    <i class="fas fa-list"></i>
                    List Schedule
                </h3>

                <div class="reschedule-availability-right">
                    <div class="reschedule-availability-header">
                        <h4 class="reschedule-availability-title">Gate Availability</h4>
                        <p class="reschedule-availability-subtitle">Inbound & Outbound slots for selected date and gate.</p>
                    </div>
                    <div id="reschedule_availability_list" class="reschedule-availability-list">
                        <p class="st-text-12 st-text--muted">Select a date and gate to see existing slots.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="st-form-actions">
            <a href="{{ route('bookings.index') }}" class="st-btn st-btn--secondary">
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

@push('scripts')
<script type="application/json" id="admin_bookings_reschedule_config">{!! json_encode([
    'bookingId' => (int) ($booking->id ?? 0),
    'checkGateUrl' => route('bookings.ajax.check_gate', [], false),
    'warehouseId' => (int) ($booking->warehouse_id ?? 0),
    'calendarBaseUrl' => route('bookings.ajax.calendar', [], false),
]) !!}</script>
@vite(['resources/js/pages/admin-bookings-reschedule.js'])
@endpush


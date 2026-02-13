@extends('layouts.app')

@section('title', 'Booking Detail')

@section('page_title', 'Booking Detail')

@push('styles')
    @vite(['resources/css/bookings.css'])
@endpush

@section('content')
<div class="st-card booking-detail">
    <div class="st-card__header booking-detail__header">
        <div>
            <h2 class="st-card__title">
                <i class="fas fa-ticket"></i>
                {{ $booking->request_number ?? ('REQ-' . $booking->id) }}
            </h2>
            <div class="booking-detail__subtitle">
                <span>{{ $booking->supplier_name ?? '-' }}</span>
                <span class="booking-detail__dot">â€¢</span>
                <span>{{ $booking->planned_start?->format('d M Y H:i') ?? '-' }}</span>
            </div>
        </div>
        <div class="st-card__actions">
            <a href="{{ route('bookings.index') }}" class="st-btn st-btn--secondary">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>
            @if($booking->status === 'pending')
                <button type="button" class="st-btn st-btn--success" onclick="openApproveModal({{ $booking->id }}, '{{ $booking->request_number ?? ('REQ-' . $booking->id) }}')">
                    <i class="fas fa-check"></i>
                    Approve
                </button>
                <button type="button" class="st-btn st-btn--danger" onclick="openRejectModal({{ $booking->id }}, '{{ $booking->request_number ?? ('REQ-' . $booking->id) }}')">
                    <i class="fas fa-times"></i>
                    Reject
                </button>
            @endif
        </div>
    </div>

    <!-- Status Alert (hidden when flash message already shown by layout) -->
    @if(!session('success') && !session('error'))
        @if($booking->status === 'pending')
        <div class="st-alert st-alert--pending_approval">
            <i class="fas fa-clock"></i>
            <div>
                <strong>Pending Approval</strong> - This booking request is waiting for your action.
            </div>
        </div>
        @elseif($booking->status === 'approved')
        <div class="st-alert st-alert--success">
            <i class="fas fa-check-circle"></i>
            <strong>Approved</strong> - This booking request has been approved.
        </div>
        @elseif($booking->status === 'cancelled')
        <div class="st-alert st-alert--danger">
            <i class="fas fa-times-circle"></i>
            <strong>Cancelled</strong> - {{ $booking->approval_notes }}
        </div>
        @endif
    @endif

    <!-- Booking Details -->
    <div class="booking-detail-grid">
        <!-- Request Information Card -->
        <div class="detail-card">
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
                        <label class="detail-label">Direction</label>
                        <div class="detail-value">
                            <span class="st-badge st-badge--{{ $booking->direction === 'inbound' ? 'info' : 'warning' }}">
                                {{ ucfirst($booking->direction) }}
                            </span>
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
        <div class="detail-card">
            <div class="detail-card__header">
                <h3 class="detail-card__title">
                    <i class="fas fa-calendar-alt"></i>
                    Schedule Information
                </h3>
            </div>
            <div class="detail-card__body">
                <div class="detail-grid-compact">
                    <div class="detail-item">
                        <label class="detail-label">Warehouse</label>
                        <div class="detail-value">{{ $booking->convertedSlot?->warehouse?->wh_code ?? '-' }}</div>
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
                    <div class="detail-item">
                        <label class="detail-label">Requested At</label>
                        <div class="detail-value">{{ $booking->created_at?->format('d M Y H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Information Card -->
        <div class="detail-card">
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
                    @if($booking->approved_at)
                    <div class="detail-item">
                        <label class="detail-label">Processed At</label>
                        <div class="detail-value">{{ $booking->approved_at->format('d M Y H:i') }}</div>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Processed By</label>
                        <div class="detail-value">{{ $booking->approver?->full_name ?? '-' }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Approve Modal -->
<div id="approveModal" class="st-custom-modal">
    <div class="st-custom-modal-overlay" onclick="closeApproveModal()"></div>
    <div class="st-custom-modal-container">
        <div class="st-custom-modal-header">
            <h3>Approve Booking</h3>
            <button type="button" class="st-custom-modal-close" onclick="closeApproveModal()">&times;</button>
        </div>
        <form method="POST" id="approveForm" action="">
            @csrf
            <div class="st-custom-modal-body">
                <p>Are you sure you want to approve booking <strong id="modalTicketNumber"></strong>?</p>
                <div class="st-form-group st-form-group--mt-15">
                    <label class="st-label st-label--strong">Gate <span class="st-required">*</span></label>
                    @php
                        $allGates = $gates->flatten(1);
                        $currentGateId = old('planned_gate_id', $booking->planned_gate_id ?? '');
                    @endphp
                    <select name="planned_gate_id" id="modal_planned_gate_id" class="st-select" required>
                        <option value="">Select Gate...</option>
                        @foreach($allGates as $g)
                            @php
                                $gid = $g->id ?? null;
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($g->warehouse?->wh_code ?? '', $g->gate_number ?? '');
                            @endphp
                            @if($gid)
                                <option value="{{ $gid }}" data-warehouse-id="{{ $g->warehouse_id }}" {{ (string)$currentGateId === (string)$gid ? 'selected' : '' }}>
                                    {{ $gateLabel }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" name="warehouse_id" id="modal_warehouse_id" value="">
                </div>
            </div>
            <div class="st-custom-modal-footer">
                <button type="submit" class="st-btn st-btn--success">Approve Booking</button>
                <button type="button" class="st-btn st-btn--secondary" onclick="closeApproveModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script type="application/json" id="approval_gates_json">{!! $gates->map(function ($coll, $wid) {
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
<script type="application/json" id="admin_bookings_show_config">{!! json_encode([
    'bookingsBaseUrl' => url('/bookings'),
]) !!}</script>
@vite(['resources/js/pages/admin-bookings-show.js'])
@endpush
@endsection


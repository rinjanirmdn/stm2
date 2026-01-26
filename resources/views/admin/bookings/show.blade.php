@extends('layouts.app')

@section('title', 'Booking Detail')
@section('page_title', 'Booking Detail')

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
                <span class="booking-detail__dot">•</span>
                <span>{{ $booking->planned_start?->format('d M Y H:i') ?? '-' }}</span>
            </div>
        </div>
        <div class="st-card__actions">
            <a href="{{ route('bookings.index') }}" class="st-button st-button--secondary">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>
        </div>
    </div>

    <!-- Status Alert -->
    @if($booking->status === 'pending')
    <div class="st-alert st-alert--warning">
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

    <!-- Booking Details -->
    <div class="booking-grid">
        <div class="st-detail-section">
            <h3 class="st-detail-title">
                <i class="fas fa-info-circle"></i>
                Request Information
            </h3>
            <table class="st-detail-table">
                <tr>
                    <td>Request</td>
                    <td><strong>{{ $booking->request_number ?? ('REQ-' . $booking->id) }}</strong></td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>
                        @php
                            $badgeColor = match($booking->status) {
                                'pending' => 'warning',
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
                    </td>
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
                    <td>COA</td>
                    <td>
                        @if(!empty($booking->coa_path))
                            <a href="{{ asset('storage/' . $booking->coa_path) }}" target="_blank" rel="noopener">View / Download</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Surat Jalan</td>
                    <td>
                        @if(!empty($booking->surat_jalan_path))
                            <a href="{{ asset('storage/' . $booking->surat_jalan_path) }}" target="_blank" rel="noopener">View / Download</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Supplier</td>
                    <td>{{ $booking->supplier_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Requested By</td>
                    <td>{{ $booking->requester?->full_name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="st-detail-section">
            <h3 class="st-detail-title">
                <i class="fas fa-file-invoice"></i>
                PO Items
            </h3>
            <table class="st-detail-table">
                <tr>
                    <td>PO Number</td>
                    <td><strong>{{ $booking->po_number ?? '-' }}</strong></td>
                </tr>
            </table>

            @if($booking->items && $booking->items->count() > 0)
                <div class="st-table-wrapper" style="margin-top: 10px;">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th style="width:80px;">Item</th>
                                <th>Material</th>
                                <th style="width:140px; text-align:right;">Qty PO</th>
                                <th style="width:140px; text-align:right;">Qty GR Total</th>
                                <th style="width:140px; text-align:right;">Qty Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->items as $it)
                                @php
                                    $itemNo = (string) ($it->item_no ?? '');
                                    $mat = trim((string) ($it->material_code ?? ''));
                                    $desc = trim((string) ($it->material_name ?? ''));
                                    $uom = trim((string) ($it->unit_po ?? ''));
                                    $qtyPo = $it->qty_po ?? null;
                                    $qtyGrTotal = $it->qty_gr_total ?? null;
                                    $qtyReq = $it->qty_requested ?? 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $itemNo }}</strong></td>
                                    <td>{{ $mat }}{{ $desc !== '' ? (' - ' . $desc) : '' }}</td>
                                    <td style="text-align:right;">{{ $qtyPo !== null ? $qtyPo : '-' }} {{ $uom }}</td>
                                    <td style="text-align:right;">{{ $qtyGrTotal !== null ? $qtyGrTotal : '-' }} {{ $uom }}</td>
                                    <td style="text-align:right;"><strong>{{ $qtyReq }}</strong> {{ $uom }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="margin-top:10px; color: var(--st-text-muted); font-size: 13px;">No PO Item Details Available for This Booking.</div>
            @endif
        </div>

        <div class="st-detail-section">
            <h3 class="st-detail-title">
                <i class="fas fa-calendar-alt"></i>
                Schedule
            </h3>
            <table class="st-detail-table">
                <tr>
                    <td>Warehouse</td>
                    <td>
                        {{ $booking->convertedSlot?->warehouse?->wh_code ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td style="color: #64748b;">Gate</td>
                    <td>
                        {{ app(\App\Services\SlotService::class)->getGateDisplayName(
                            $booking->convertedSlot?->plannedGate?->warehouse->wh_code ?? '', 
                            $booking->convertedSlot?->plannedGate?->gate_number ?? ''
                        ) ?: 'To be assigned' }}
                    </td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td><strong>{{ $booking->planned_start?->format('d M Y') ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td>Time</td>
                    <td><strong>{{ $booking->planned_start?->format('H:i') ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td>Duration</td>
                    <td>{{ $booking->planned_duration }} Min</td>
                </tr>
            </table>
        </div>

        <div class="st-detail-section">
            <h3 class="st-detail-title">
                <i class="fas fa-truck"></i>
                Vehicle Info
            </h3>
            <table class="st-detail-table">
                <tr>
                    <td>Truck Type</td>
                    <td>{{ $booking->truck_type ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Vehicle Number</td>
                    <td>{{ $booking->vehicle_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Requested At</td>
                    <td>{{ $booking->created_at?->format('d M Y H:i') ?? '-' }}</td>
                </tr>
                @if($booking->approved_at)
                <tr>
                    <td>Processed At</td>
                    <td>{{ $booking->approved_at->format('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td>Processed By</td>
                    <td>{{ $booking->approver?->full_name ?? '-' }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    @if(in_array((string) $booking->status, ['pending'], true))
    <div class="st-action-section booking-actions">
        <h3 class="st-detail-title">
            <i class="fas fa-gavel"></i>
            Actions
        </h3>

        <div class="booking-actions__layout">
            @can('bookings.approve')
            <form method="POST" action="{{ route('bookings.approve', $booking->id) }}" class="booking-actions__form">
                @csrf
                <div class="booking-actions__controls">
                    <div class="booking-actions__field">
                        <label class="st-label">Gate <span class="st-required">*</span></label>
                        @php
                            $allGates = $gates->flatten(1);
                            $currentGateId = old('planned_gate_id', $booking->planned_gate_id ?? '');
                        @endphp
                        <select name="planned_gate_id" id="approval_planned_gate_id" class="st-select" required>
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
                        <input type="hidden" name="warehouse_id" id="approval_warehouse_id" value="">
                    </div>
                    <button type="submit" class="st-button st-button--success st-button--lg booking-actions__submit" onclick="return confirm('Approve this booking with the current schedule?')">
                        <i class="fas fa-check"></i>
                        Approve Booking
                    </button>
                </div>
            </form>
            @endcan

            <div class="booking-actions__buttons">
                @can('bookings.reschedule')
                <a href="{{ route('bookings.reschedule', $booking->id) }}" class="st-button st-button--warning st-button--lg">
                    <i class="fas fa-calendar-alt"></i>
                    Reschedule
                </a>
                @endcan

                @can('bookings.reject')
                <button type="button" class="st-button st-button--danger st-button--lg" onclick="openRejectModal({{ $booking->id }}, '{{ $booking->request_number ?? ('REQ-' . $booking->id) }}')">
                    <i class="fas fa-times"></i>
                    Reject Booking
                </button>
                @endcan
            </div>
        </div>
    </div>
    @endif
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    var gateSel = document.getElementById('approval_planned_gate_id');
    var warehouseHidden = document.getElementById('approval_warehouse_id');
    
    // Gate and warehouse sync
    function syncWarehouseFromGate() {
        if (!gateSel || !warehouseHidden) return;
        var selected = gateSel.options[gateSel.selectedIndex];
        if (!selected) return;
        warehouseHidden.value = selected.getAttribute('data-warehouse-id') || '';
    }
    
    // Gate change listener
    if (gateSel) {
        gateSel.addEventListener('change', function() {
            syncWarehouseFromGate();
        });
        // Initial sync if gate is pre-selected
        if (gateSel.value) {
            syncWarehouseFromGate();
        }
    }
});
</script>
@endpush



<style>
.booking-detail {
    border: 1px solid var(--st-border, #e5e7eb);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.booking-detail__header {
    align-items: flex-start;
    gap: 1rem;
}

.booking-detail__subtitle {
    margin-top: 0.35rem;
    font-size: 0.875rem;
    color: var(--st-text-muted, #64748b);
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}

.booking-detail__dot {
    opacity: 0.6;
}

.booking-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
    margin: 1.5rem 0;
}

.st-detail-section {
    background: var(--st-surface-alt, #f8fafc);
    padding: 1.25rem;
    border-radius: 14px;
    border: 1px solid rgba(148, 163, 184, 0.25);
}

.st-detail-title {
    margin: 0 0 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--st-text-secondary, #64748b);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.st-detail-table {
    width: 100%;
    border-collapse: collapse;
}

.st-detail-table td {
    padding: 0.55rem 0;
    border-bottom: 1px solid var(--st-border, #e5e7eb);
    vertical-align: top;
}

.st-detail-table tr:last-child td {
    border-bottom: none;
}

.st-detail-table td:first-child {
    color: var(--st-text-muted, #64748b);
    width: 42%;
    font-weight: 500;
}

.st-detail-table td:last-child {
    color: var(--st-text, #0f172a);
}

.st-action-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--st-border, #e5e7eb);
}

.st-action-buttons-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.booking-actions__layout {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.booking-actions__form {
    width: 100%;
}

.booking-actions__controls {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.booking-actions__field {
    min-width: 220px;
    flex: 1 1 240px;
}

.booking-actions__submit {
    min-width: 200px;
}

.booking-actions__buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.st-button--lg {
    padding: 1rem 2rem;
    font-size: 1rem;
}

.st-timeline {
    padding-left: 2rem;
    position: relative;
}

.st-timeline__item {
    position: relative;
    padding: 0 0 1.5rem 1.5rem;
    border-left: 2px solid var(--st-border, #e5e7eb);
}

.st-timeline__item:last-child {
    padding-bottom: 0;
    border-left-color: transparent;
}

.st-timeline__marker {
    position: absolute;
    left: -0.5rem;
    top: 0;
    width: 1rem;
    height: 1rem;
    background: var(--st-surface, #fff);
    border: 2px solid var(--st-primary, #3b82f6);
    border-radius: 50%;
}

.st-timeline__header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.st-timeline__user {
    color: var(--st-text-muted, #64748b);
    font-size: 0.875rem;
}

.st-timeline__time {
    color: var(--st-text-muted, #94a3b8);
    font-size: 0.75rem;
    margin-left: auto;
}

.st-timeline__notes {
    margin: 0.5rem 0;
    color: var(--st-text-secondary, #475569);
    font-size: 0.875rem;
}

.st-timeline__changes {
    font-size: 0.875rem;
    color: var(--st-text-muted, #64748b);
}

.st-text-strikethrough {
    text-decoration: line-through;
}

@media (max-width: 768px) {
    .booking-detail__header {
        flex-direction: column;
        align-items: flex-start;
    }

    .booking-actions__controls {
        align-items: stretch;
    }

    .booking-actions__submit {
        width: 100%;
    }
}
</style>
@push('scripts')
<script>
function openApproveModal(id, ticket) {
    const modal = document.getElementById('approveModal');
    const ticketSpan = document.getElementById('modalTicketNumber');
    const form = document.getElementById('approveForm');

    ticketSpan.innerText = ticket;
    form.action = "{{ url('/bookings') }}/" + id + "/approve";
    modal.classList.add('active');
}

function closeApproveModal() {
    document.getElementById('approveModal').classList.remove('active');
}

function openRejectModal(id, ticket) {
    const modal = document.getElementById('reject-modal');
    document.getElementById('reject-ticket').innerText = ticket;
    document.getElementById('reject-form').action = "{{ url('/bookings') }}/" + id + "/reject";
    modal.classList.add('active');
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.remove('active');
}
</script>
@endpush
@endsection

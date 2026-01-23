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
        <a href="{{ route('bookings.show', $booking->id) }}" class="st-button st-button--secondary">
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
                
                <div class="st-form-group">
                    <label class="st-label">Warehouse <span class="st-required">*</span></label>
                    <select name="warehouse_id" class="st-select" required id="warehouse_id">
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->wh_code }} - {{ $wh->wh_name ?? ($wh->name ?? '') }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')
                        <span class="st-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="st-form-group">
                    <label class="st-label">Date <span class="st-required">*</span></label>
                    <input type="date" name="planned_date" class="st-input" required
                           min="{{ date('Y-m-d') }}" 
                           value="{{ old('planned_date', $booking->planned_start?->format('Y-m-d')) }}"
                           id="planned_date">
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
                           min="30" max="480" step="30"
                           value="{{ old('planned_duration', $booking->planned_duration) }}"
                           id="planned_duration">
                    @error('planned_duration')
                        <span class="st-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="st-form-group">
                    <label class="st-label">Gate (optional)</label>
                    <select name="planned_gate_id" class="st-select" id="gate_select">
                        <option value="">Auto-assign</option>
                    </select>
                </div>

                <div class="st-form-group" style="margin-top: 1.5rem;">
                    <label class="st-label">Notes for Vendor</label>
                    <textarea name="notes" class="st-textarea" rows="3" 
                              placeholder="Explain Why You're Rescheduling This Booking...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="st-form-actions">
            <a href="{{ route('bookings.show', $booking->id) }}" class="st-button st-button--secondary">
                Cancel
            </a>
            <button type="submit" class="st-button st-button--warning">
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
document.addEventListener('DOMContentLoaded', function () {
    var whSel = document.getElementById('warehouse_id');
    var gateSel = document.getElementById('gate_select');
    var gatesEl = document.getElementById('reschedule_gates_json');
    if (!whSel || !gateSel || !gatesEl) return;

    var gatesData = {};
    try {
        gatesData = JSON.parse(gatesEl.textContent || '{}') || {};
    } catch (e) {
        gatesData = {};
    }

    function buildGateLabel(g) {
        var base = (g.gate_number || g.name || ('Gate #' + (g.id || '')));
        var wc = (g.warehouse_code || '').trim();
        return wc ? (wc + '-' + base) : base;
    }

    function refreshGateOptions() {
        var wid = String(whSel.value || '');
        var current = String(gateSel.value || '');
        var list = gatesData[wid] || [];

        gateSel.innerHTML = '<option value="">Auto-assign</option>';
        list.forEach(function (g) {
            if (!g || !g.id) return;
            var opt = document.createElement('option');
            opt.value = String(g.id);
            opt.textContent = buildGateLabel(g);
            gateSel.appendChild(opt);
        });

        if (current) {
            gateSel.value = current;
        }
    }

    whSel.addEventListener('change', function () {
        gateSel.value = '';
        refreshGateOptions();
    });

    refreshGateOptions();
});
</script>
@endpush


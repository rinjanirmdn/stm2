@extends('layouts.app')

@section('title', 'Gate Status - Slot Time Management')
@section('page_title', 'Gate Status Monitor')

@section('content')
<div class="st-card">
    <div class="st-card__header">
        <h3 class="st-card__title">Real-time Gate Status</h3>
        <div class="st-card__actions">
            <button class="st-btn st-btn--sm st-btn--outline" onclick="toggleAutoRefresh()">
                <i class="fas fa-sync-alt"></i>
                <span id="refresh-status">Auto Refresh ON</span>
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="st-grid st-grid--4" style="margin-bottom: 24px;">
        <div class="st-stat-card">
            <div class="st-stat-card__value" id="total-gates">0</div>
            <div class="st-stat-card__label">Total Gates</div>
        </div>
        <div class="st-stat-card st-stat-card--success">
            <div class="st-stat-card__value" id="available-gates">0</div>
            <div class="st-stat-card__label">Available</div>
        </div>
        <div class="st-stat-card st-stat-card--danger">
            <div class="st-stat-card__value" id="busy-gates">0</div>
            <div class="st-stat-card__label">Busy</div>
        </div>
        <div class="st-stat-card st-stat-card--warning">
            <div class="st-stat-card__value" id="occupied-gates">0</div>
            <div class="st-stat-card__label">Occupied</div>
        </div>
    </div>

    <!-- Gates Grid -->
    <div id="gate-status-container" class="st-grid st-grid--3">
        @foreach($gates as $gate)
        <div class="st-card gate-card" data-gate-id="{{ $gate->id }}">
            <div class="st-card__header">
                <div class="d-flex align-items-center">
                    <span class="gate-status-indicator status-{{ $gate->gate_status ?? 'available' }}"></span>
                    <div>
                        <h4 class="st-card__title mb-0">Gate {{ $gate->gate_number }}</h4>
                        <small class="text-muted">{{ $gate->warehouse_code }}</small>
                    </div>
                </div>
            </div>
            <div class="st-card__body">
                <div class="gate-status-text mb-2">
                    {{ $gate->gate_status == 'available' ? 'Available' :
                       ($gate->gate_status == 'busy' ? 'Busy' :
                       ($gate->gate_status == 'occupied' ? 'Occupied' : 'Reserved')) }}
                </div>
                <div class="current-slot-info">
                    @if($gate->po_number)
                        <div class="slot-po">{{ $gate->po_number }}</div>
                        <div class="slot-time">
                            {{ \Carbon\Carbon::parse($gate->planned_start)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($gate->planned_finish)->format('H:i') }}
                        </div>
                    @else
                        <div class="no-slot">No Slot</div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/gate-status.js'])
<script>
let autoRefresh = true;

function toggleAutoRefresh() {
    autoRefresh = !autoRefresh;
    const statusEl = document.getElementById('refresh-status');
    const btn = event.target.closest('button');

    if (autoRefresh) {
        statusEl.textContent = 'Auto Refresh ON';
        btn.classList.remove('st-btn--danger');
        btn.classList.add('st-btn--outline');
        window.gateStatusMonitor.connect();
    } else {
        statusEl.textContent = 'Auto Refresh OFF';
        btn.classList.remove('st-btn--outline');
        btn.classList.add('st-btn--danger');
        window.gateStatusMonitor.disconnect();
    }
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    // Load initial data
    fetch('/api/gate-status')
        .then(response => response.json())
        .then(data => {
            data.gates.forEach(gate => {
                window.gateStatusMonitor.gateStatuses.set(gate.id, gate);
                window.gateStatusMonitor.updateGateUI(gate);
            });
            window.gateStatusMonitor.updateSummaryStats();
        });
});
</script>
@endpush

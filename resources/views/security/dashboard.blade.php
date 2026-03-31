@extends('layouts.app')

@section('title', 'Security Dashboard - e-Docking Control System')
@section('page_title', 'Security Dashboard')
@section('body_class', 'st-page--security-dashboard')

@section('content')

    {{-- Scan Ticket Hero Section --}}
    <div class="st-security-scan-hero">
        <div class="st-security-scan-hero__inner">
            <div class="st-security-scan-hero__icon">
                <i class="fas fa-barcode"></i>
            </div>
            <div class="st-security-scan-hero__title">Scan Tiket Kedatangan</div>
            <div class="st-security-scan-hero__subtitle">Scan barcode tiket atau ketik nomor tiket secara manual</div>
            <form id="security-scan-form" class="st-security-scan-hero__form" autocomplete="off">
                @csrf
                <div class="st-security-scan-hero__input-wrap">
                    <input
                        type="text"
                        id="security-scan-input"
                        name="ticket_number"
                        class="st-security-scan-hero__input"
                        placeholder="Scan atau ketik nomor tiket..."
                        autofocus
                        autocomplete="off"
                    >
                    <button type="submit" class="st-security-scan-hero__btn" id="security-scan-btn">
                        <i class="fas fa-search"></i>
                        <span>PROSES</span>
                    </button>
                </div>
                <div class="st-security-scan-hero__hint">
                    <i class="fas fa-bolt"></i> Otomatis proses saat barcode scanner scan
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="st-security-summary">
        <div class="st-security-summary__card st-security-summary__card--total">
            <div class="st-security-summary__number" id="summary-total">{{ $summary['total'] }}</div>
            <div class="st-security-summary__label">Total Hari Ini</div>
            <i class="fas fa-calendar-day st-security-summary__icon"></i>
        </div>
        <div class="st-security-summary__card st-security-summary__card--scheduled">
            <div class="st-security-summary__number" id="summary-scheduled">{{ $summary['scheduled'] }}</div>
            <div class="st-security-summary__label">Menunggu Datang</div>
            <i class="fas fa-clock st-security-summary__icon"></i>
        </div>
        <div class="st-security-summary__card st-security-summary__card--waiting">
            <div class="st-security-summary__number" id="summary-waiting">{{ $summary['waiting'] }}</div>
            <div class="st-security-summary__label">Sudah Tiba</div>
            <i class="fas fa-truck-ramp-box st-security-summary__icon"></i>
        </div>
        <div class="st-security-summary__card st-security-summary__card--active">
            <div class="st-security-summary__number" id="summary-active">{{ $summary['in_progress'] }}</div>
            <div class="st-security-summary__label">Sedang Proses</div>
            <i class="fas fa-spinner st-security-summary__icon"></i>
        </div>
        <div class="st-security-summary__card st-security-summary__card--completed">
            <div class="st-security-summary__number" id="summary-completed">{{ $summary['completed'] }}</div>
            <div class="st-security-summary__label">Selesai</div>
            <i class="fas fa-check-circle st-security-summary__icon"></i>
        </div>
    </div>

    {{-- Today's Schedule --}}
    <div class="st-card st-security-schedule">
        <div class="st-card__header st-security-schedule__header">
            <h3 class="st-card__title">
                <i class="fas fa-list-check"></i>
                Jadwal Kedatangan Hari Ini
            </h3>
            <span class="st-security-schedule__shift-badge">{{ $shiftLabel }}</span>
        </div>
        <div class="st-security-schedule__body" id="security-schedule-list">
            @forelse ($schedule as $slot)
                @php
                    $status = (string) ($slot->status ?? '');
                    $eta = date('H:i', strtotime($slot->planned_start));
                    $isLate = false;
                    if ($status === 'scheduled' && !empty($slot->planned_start)) {
                        $etaPlus15 = strtotime($slot->planned_start) + (15 * 60);
                        $isLate = time() > $etaPlus15;
                    }

                    $gateDisplay = '-';
                    $whCode = trim((string) ($slot->planned_gate_warehouse_code ?? ''));
                    $gateNo = trim((string) ($slot->planned_gate_number ?? ''));
                    if ($whCode !== '' && $gateNo !== '') {
                        $gateDisplay = $whCode . ' - Gate ' . $gateNo;
                    } elseif (trim((string) ($slot->warehouse_name ?? '')) !== '') {
                        $gateDisplay = $slot->warehouse_name;
                    }

                    $statusClass = match($status) {
                        'scheduled' => 'st-security-slot--scheduled',
                        'waiting' => 'st-security-slot--waiting',
                        'in_progress' => 'st-security-slot--active',
                        'completed' => 'st-security-slot--completed',
                        default => '',
                    };
                    $statusLabel = match($status) {
                        'scheduled' => 'Menunggu Datang',
                        'waiting' => 'Sudah Tiba',
                        'in_progress' => 'Sedang Proses',
                        'completed' => 'Selesai',
                        default => ucwords(str_replace('_', ' ', $status)),
                    };
                @endphp
                <div class="st-security-slot {{ $statusClass }}{{ $isLate ? ' st-security-slot--late' : '' }}">
                    <div class="st-security-slot__time">
                        <span class="st-security-slot__eta">{{ $eta }}</span>
                        @if (!empty($slot->arrival_time))
                            <span class="st-security-slot__arrival">Tiba {{ date('H:i', strtotime($slot->arrival_time)) }}</span>
                        @endif
                    </div>
                    <div class="st-security-slot__info">
                        <div class="st-security-slot__ticket">{{ $slot->ticket_number ?? '-' }}</div>
                        <div class="st-security-slot__detail">
                            <span><i class="fas fa-file-invoice"></i> {{ $slot->po_number ?? '-' }}</span>
                            <span><i class="fas fa-building"></i> {{ $slot->vendor_name ?? '-' }}</span>
                        </div>
                        <div class="st-security-slot__detail">
                            <span><i class="fas fa-door-open"></i> {{ $gateDisplay }}</span>
                            <span><i class="fas fa-truck"></i> {{ $slot->vehicle_number_snap ?? '-' }}</span>
                            <span class="st-security-slot__direction st-security-slot__direction--{{ strtolower($slot->direction ?? '') }}">
                                {{ strtoupper($slot->direction ?? '') }}
                            </span>
                        </div>
                    </div>
                    <div class="st-security-slot__status">
                        <span class="st-security-slot__badge">{{ $statusLabel }}</span>
                        @if ($isLate)
                            <span class="st-security-slot__late-badge">TERLAMBAT</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="st-security-schedule__empty">
                    <i class="fas fa-calendar-xmark"></i>
                    <p>Tidak ada jadwal kedatangan hari ini</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Scan Result Modal --}}
    <div class="st-dialog st-dialog--overlay" id="securityScanModal" style="display:none;">
        <div class="st-card st-dialog__card st-security-modal">
            <div class="st-security-modal__header" id="scanModalHeader">
                <i class="fas fa-check-circle"></i>
                <span>Tiket Ditemukan</span>
            </div>
            <div class="st-security-modal__body">
                {{-- Warnings --}}
                <div id="scanModalWarnings"></div>

                {{-- Slot Details --}}
                <div class="st-security-modal__details">
                    <div class="st-security-modal__row">
                        <span class="st-security-modal__label">Nomor Tiket</span>
                        <span class="st-security-modal__value st-font-bold" id="scanTicketNumber">-</span>
                    </div>
                    <div class="st-security-modal__row">
                        <span class="st-security-modal__label">Nomor PO</span>
                        <span class="st-security-modal__value" id="scanPoNumber">-</span>
                    </div>
                    <div class="st-security-modal__row">
                        <span class="st-security-modal__label">Vendor</span>
                        <span class="st-security-modal__value" id="scanVendor">-</span>
                    </div>
                    <div class="st-security-modal__row">
                        <span class="st-security-modal__label">Kendaraan</span>
                        <span class="st-security-modal__value" id="scanVehicle">-</span>
                    </div>
                    <div class="st-security-modal__row">
                        <span class="st-security-modal__label">Pengemudi</span>
                        <span class="st-security-modal__value" id="scanDriver">-</span>
                    </div>
                    <div class="st-security-modal__row">
                        <span class="st-security-modal__label">Aktivitas</span>
                        <span class="st-security-modal__value" id="scanDirection">-</span>
                    </div>
                    <div class="st-security-modal__row">
                        <span class="st-security-modal__label">Gate Tujuan</span>
                        <span class="st-security-modal__value" id="scanGate">-</span>
                    </div>
                    <div class="st-security-modal__row">
                        <span class="st-security-modal__label">ETA</span>
                        <span class="st-security-modal__value" id="scanEta">-</span>
                    </div>
                </div>
            </div>
            <div class="st-security-modal__actions">
                <button type="button" class="st-btn st-btn--primary st-btn--lg st-w-full st-justify-center" id="scanConfirmBtn" style="display:none;">
                    <i class="fas fa-check"></i> KONFIRMASI KEDATANGAN
                </button>
                <button type="button" class="st-btn st-btn--outline-primary st-w-full st-justify-center" id="scanCloseBtn">
                    Tutup
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script type="application/json" id="security_dashboard_config">{!! json_encode([
    'scanUrl' => route('security.scan'),
    'confirmUrl' => rtrim(route('security.confirm_arrival', ['slotId' => '__SLOT_ID__']), '/'),
    'refreshUrl' => route('security.ajax.today_slots'),
    'csrfToken' => csrf_token(),
]) !!}</script>
@vite(['resources/js/pages/security-dashboard.js'])
@endpush

@push('styles')
<style>
/* ── Security Dashboard Styles ── */
.st-security-scan-hero {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border-radius: 16px;
    padding: 32px 24px;
    margin-bottom: 20px;
    text-align: center;
    color: #fff;
    box-shadow: 0 8px 32px rgba(13, 110, 253, 0.25);
}
.st-security-scan-hero__icon {
    font-size: 48px;
    margin-bottom: 8px;
    opacity: 0.9;
}
.st-security-scan-hero__title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 4px;
}
.st-security-scan-hero__subtitle {
    font-size: 13px;
    opacity: 0.85;
    margin-bottom: 20px;
}
.st-security-scan-hero__form {
    max-width: 560px;
    margin: 0 auto;
}
.st-security-scan-hero__input-wrap {
    display: flex;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 6px;
    backdrop-filter: blur(4px);
}
.st-security-scan-hero__input {
    flex: 1;
    border: none;
    background: #fff;
    border-radius: 8px;
    padding: 14px 18px;
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    outline: none;
    letter-spacing: 1px;
}
.st-security-scan-hero__input::placeholder {
    font-weight: 400;
    color: #999;
    letter-spacing: 0;
}
.st-security-scan-hero__btn {
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
    background: #198754;
    color: #fff;
    border-radius: 8px;
    padding: 14px 24px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}
.st-security-scan-hero__btn:hover {
    background: #157347;
}
.st-security-scan-hero__hint {
    font-size: 12px;
    opacity: 0.7;
    margin-top: 12px;
}

/* Summary Cards */
.st-security-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.st-security-summary__card {
    position: relative;
    background: #fff;
    border-radius: 12px;
    padding: 18px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}
.st-security-summary__number {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
}
.st-security-summary__label {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}
.st-security-summary__icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 28px;
    opacity: 0.12;
}
.st-security-summary__card--total { border-left: 4px solid #0d6efd; }
.st-security-summary__card--total .st-security-summary__number { color: #0d6efd; }
.st-security-summary__card--scheduled { border-left: 4px solid #ffc107; }
.st-security-summary__card--scheduled .st-security-summary__number { color: #d4a106; }
.st-security-summary__card--waiting { border-left: 4px solid #0dcaf0; }
.st-security-summary__card--waiting .st-security-summary__number { color: #0aa2c0; }
.st-security-summary__card--active { border-left: 4px solid #fd7e14; }
.st-security-summary__card--active .st-security-summary__number { color: #e8690b; }
.st-security-summary__card--completed { border-left: 4px solid #198754; }
.st-security-summary__card--completed .st-security-summary__number { color: #198754; }

/* Schedule */
.st-security-schedule__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
}
.st-security-schedule__shift-badge {
    background: #e8f0fe;
    color: #1967d2;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
}
.st-security-schedule__body {
    padding: 12px 16px;
    max-height: 520px;
    overflow-y: auto;
}
.st-security-schedule__empty {
    text-align: center;
    padding: 48px 20px;
    color: #adb5bd;
}
.st-security-schedule__empty i {
    font-size: 48px;
    margin-bottom: 12px;
}

/* Slot Cards */
.st-security-slot {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border-left: 4px solid #dee2e6;
    transition: all 0.2s;
}
.st-security-slot:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.st-security-slot--scheduled { border-left-color: #ffc107; background: #fffbeb; }
.st-security-slot--waiting { border-left-color: #0dcaf0; background: #edfafc; }
.st-security-slot--active { border-left-color: #fd7e14; background: #fff8f0; }
.st-security-slot--completed { border-left-color: #198754; background: #f0faf4; opacity: 0.7; }
.st-security-slot--late { border-left-color: #dc3545 !important; }

.st-security-slot__time {
    min-width: 60px;
    text-align: center;
    flex-shrink: 0;
}
.st-security-slot__eta {
    display: block;
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
}
.st-security-slot__arrival {
    display: block;
    font-size: 10px;
    color: #198754;
    font-weight: 600;
    margin-top: 2px;
}
.st-security-slot__info {
    flex: 1;
    min-width: 0;
}
.st-security-slot__ticket {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 4px;
}
.st-security-slot__detail {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 2px;
}
.st-security-slot__detail i {
    margin-right: 4px;
    width: 14px;
    text-align: center;
    opacity: 0.6;
}
.st-security-slot__direction {
    font-weight: 700;
    font-size: 11px;
    padding: 1px 8px;
    border-radius: 4px;
}
.st-security-slot__direction--inbound { background: #d1ecf1; color: #0c5460; }
.st-security-slot__direction--outbound { background: #d4edda; color: #155724; }

.st-security-slot__status {
    flex-shrink: 0;
    text-align: right;
}
.st-security-slot__badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
    background: #e9ecef;
    color: #495057;
}
.st-security-slot--scheduled .st-security-slot__badge { background: #fff3cd; color: #856404; }
.st-security-slot--waiting .st-security-slot__badge { background: #cff4fc; color: #055160; }
.st-security-slot--active .st-security-slot__badge { background: #ffe5d0; color: #984c0c; }
.st-security-slot--completed .st-security-slot__badge { background: #d1e7dd; color: #0f5132; }

.st-security-slot__late-badge {
    display: block;
    font-size: 10px;
    font-weight: 700;
    color: #dc3545;
    margin-top: 4px;
    animation: st-pulse 2s infinite;
}
@keyframes st-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Modal */
.st-security-modal {
    max-width: 440px;
    width: 100%;
    border-radius: 16px;
    overflow: hidden;
}
.st-security-modal__header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 24px;
    font-size: 17px;
    font-weight: 700;
    background: #198754;
    color: #fff;
}
.st-security-modal__header--error {
    background: #dc3545;
}
.st-security-modal__header--warning {
    background: #fd7e14;
}
.st-security-modal__body {
    padding: 20px 24px;
}
.st-security-modal__details {
    margin-top: 12px;
}
.st-security-modal__row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}
.st-security-modal__row:last-child { border-bottom: none; }
.st-security-modal__label {
    font-size: 13px;
    color: #6c757d;
}
.st-security-modal__value {
    font-size: 13px;
    color: #1a1a1a;
    text-align: right;
}
.st-security-modal__actions {
    padding: 16px 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-top: 1px solid #f0f0f0;
}
.st-security-modal__warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
}
.st-security-modal__warning--error {
    background: #f8d7da;
    color: #842029;
}
.st-security-modal__warning--warning {
    background: #fff3cd;
    color: #664d03;
}
.st-security-modal__warning--late {
    background: #f8d7da;
    color: #dc3545;
    font-weight: 700;
}

/* Responsive */
@media (max-width: 640px) {
    .st-security-scan-hero { padding: 24px 16px; }
    .st-security-scan-hero__input-wrap { flex-direction: column; }
    .st-security-scan-hero__btn { justify-content: center; }
    .st-security-summary { grid-template-columns: repeat(2, 1fr); }
    .st-security-slot { flex-direction: column; gap: 8px; }
    .st-security-slot__status { text-align: left; }
}
</style>
@endpush

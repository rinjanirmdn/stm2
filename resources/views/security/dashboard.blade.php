@extends('layouts.app')

@section('title', 'Security Dashboard - e-Docking Control System')
@section('page_title', 'Security Dashboard')
@section('body_class', 'st-page--security-dashboard')

@section('content')

    {{-- ─── Header: Date Picker + Summary Stats ─── --}}
    <div class="sec-header">
        <div class="sec-header__top">
            <div class="sec-header__date-group">
                <button class="sec-date-nav" id="datePrev" title="Hari Sebelumnya"><i class="fas fa-chevron-left"></i></button>
                <div class="sec-date-display" id="dateDisplay">
                    <input type="date" id="secDatePicker" value="{{ $selectedDate }}" class="sec-date-input">
                    <span class="sec-date-label" id="secDateLabel">
                        @if ($selectedDate === $today)
                            Hari Ini
                        @else
                            {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M Y') }}
                        @endif
                    </span>
                    <i class="fas fa-calendar-alt sec-date-icon"></i>
                </div>
                <button class="sec-date-nav" id="dateNext" title="Hari Selanjutnya"><i class="fas fa-chevron-right"></i></button>
                @if ($selectedDate !== $today)
                    <button class="sec-date-today" id="dateToday" title="Kembali ke hari ini">Hari Ini</button>
                @endif
            </div>
            <select class="sec-header__shift-select" id="secShiftFilter">
                <option value="all" selected>24 Jam</option>
                <option value="1">Shift 1 (07:00 - 15:00)</option>
                <option value="2">Shift 2 (15:00 - 23:00)</option>
                <option value="3">Shift 3 (23:00 - 07:00)</option>
            </select>
        </div>

        <div class="sec-stats" id="secStatsBar">
            <div class="sec-stat sec-stat--total sec-stat--active-filter" data-filter="all" role="button" tabindex="0">
                <div class="sec-stat__val" id="summary-total">{{ $summary['total'] }}</div>
                <div class="sec-stat__lbl">Total</div>
            </div>
            <div class="sec-stat sec-stat--scheduled" data-filter="scheduled" role="button" tabindex="0">
                <div class="sec-stat__val" id="summary-scheduled">{{ $summary['scheduled'] }}</div>
                <div class="sec-stat__lbl">Dijadwalkan</div>
            </div>
            <div class="sec-stat sec-stat--waiting" data-filter="waiting" role="button" tabindex="0">
                <div class="sec-stat__val" id="summary-waiting">{{ $summary['waiting'] }}</div>
                <div class="sec-stat__lbl">Sudah Tiba</div>
            </div>
            <div class="sec-stat sec-stat--active" data-filter="in_progress" role="button" tabindex="0">
                <div class="sec-stat__val" id="summary-active">{{ $summary['in_progress'] }}</div>
                <div class="sec-stat__lbl">Proses</div>
            </div>
            <div class="sec-stat sec-stat--done" data-filter="completed" role="button" tabindex="0">
                <div class="sec-stat__val" id="summary-completed">{{ $summary['completed'] }}</div>
                <div class="sec-stat__lbl">Selesai</div>
            </div>
        </div>
    </div>

    {{-- ─── Schedule List ─── --}}
    <div class="sec-schedule" id="security-schedule-list">
        @forelse ($schedule as $slot)
            @php
                $status = (string) ($slot->status ?? '');
                $eta = date('H:i', strtotime($slot->planned_start));
                $isLate = false;
                if ($status === 'scheduled' && !empty($slot->planned_start)) {
                    $isLate = time() > strtotime($slot->planned_start) + 900;
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
                    'scheduled' => 'sec-slot--scheduled',
                    'waiting' => 'sec-slot--waiting',
                    'in_progress' => 'sec-slot--active',
                    'completed' => 'sec-slot--done',
                    default => '',
                };
                $statusEmoji = match($status) {
                    'scheduled' => '🕐',
                    'waiting' => '✅',
                    'in_progress' => '🔄',
                    'completed' => '✔️',
                    default => '•',
                };
                $statusLabel = match($status) {
                    'scheduled' => 'Dijadwalkan',
                    'waiting' => 'Sudah Tiba',
                    'in_progress' => 'Sedang Proses',
                    'completed' => 'Selesai',
                    default => ucwords(str_replace('_', ' ', $status)),
                };
            @endphp
            <div class="sec-slot {{ $statusClass }}{{ $isLate ? ' sec-slot--late' : '' }}">
                <div class="sec-slot__left">
                    <div class="sec-slot__eta">{{ $eta }}</div>
                    @if (!empty($slot->arrival_time))
                        <div class="sec-slot__arrived">Tiba {{ date('H:i', strtotime($slot->arrival_time)) }}</div>
                    @endif
                </div>
                <div class="sec-slot__body">
                    <div class="sec-slot__row-top">
                        <span class="sec-slot__ticket">{{ $slot->ticket_number ?? '-' }}</span>
                        <span class="sec-slot__badge">{{ $statusEmoji }} {{ $statusLabel }}</span>
                    </div>
                    <div class="sec-slot__vendor">{{ $slot->vendor_name ?? '-' }}</div>
                    <div class="sec-slot__meta">
                        <span title="PO Number"><i class="fas fa-file-invoice"></i> {{ $slot->po_number ?? '-' }}</span>
                        <span title="Gate"><i class="fas fa-door-open"></i> {{ $gateDisplay }}</span>
                        <span title="Kendaraan"><i class="fas fa-truck"></i> {{ $slot->vehicle_number_snap ?? '-' }}</span>
                        <span class="sec-slot__dir sec-slot__dir--{{ strtolower($slot->direction ?? '') }}">{{ strtoupper($slot->direction ?? '') }}</span>
                    </div>
                    @if ($isLate)
                        <div class="sec-slot__late-tag"><i class="fas fa-exclamation-triangle"></i> TERLAMBAT</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="sec-schedule__empty">
                <i class="fas fa-inbox"></i>
                <p>Tidak ada jadwal untuk tanggal ini</p>
            </div>
        @endforelse
    </div>

    {{-- ─── Bottom: Scan Section ─── --}}
    <div class="sec-scan-bar" id="secScanBar">
        <form id="security-scan-form" class="sec-scan-bar__form" autocomplete="off">
            @csrf
            <div class="sec-scan-bar__input-group">
                <i class="fas fa-barcode sec-scan-bar__icon-left"></i>
                <input type="text" id="security-scan-input" name="ticket_number" class="sec-scan-bar__input" placeholder="Scan / ketik nomor tiket..." autofocus autocomplete="off">
                <button type="button" class="sec-scan-bar__cam-btn" id="secCameraBtn" title="Scan dengan Kamera">
                    <i class="fas fa-camera"></i>
                </button>
                <button type="submit" class="sec-scan-bar__submit" id="security-scan-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    {{-- ─── Camera Scanner Modal ─── --}}
    <div class="sec-camera-overlay" id="secCameraOverlay" style="display:none;">
        <div class="sec-camera-panel">
            <div class="sec-camera-panel__header">
                <span><i class="fas fa-camera"></i> Scan Barcode</span>
                <button class="sec-camera-panel__close" id="secCameraClose"><i class="fas fa-times"></i></button>
            </div>
            <div id="secCameraPreview" class="sec-camera-panel__preview"></div>
            <div class="sec-camera-panel__hint">Arahkan kamera ke barcode pada tiket</div>
        </div>
    </div>

    {{-- ─── Result Modal ─── --}}
    <div class="sec-result-overlay" id="securityScanModal" style="display:none;">
        <div class="sec-result-card">
            <div class="sec-result-card__header" id="scanModalHeader">
                <i class="fas fa-check-circle"></i>
                <span>Tiket Ditemukan</span>
            </div>
            <div class="sec-result-card__body">
                <div id="scanModalWarnings"></div>
                <div class="sec-result-card__details">
                    <div class="sec-result-row">
                        <span class="sec-result-row__k">Nomor Tiket</span>
                        <span class="sec-result-row__v sec-result-row__v--bold" id="scanTicketNumber">-</span>
                    </div>
                    <div class="sec-result-row">
                        <span class="sec-result-row__k">Nomor PO</span>
                        <span class="sec-result-row__v" id="scanPoNumber">-</span>
                    </div>
                    <div class="sec-result-row">
                        <span class="sec-result-row__k">Vendor</span>
                        <span class="sec-result-row__v" id="scanVendor">-</span>
                    </div>
                    <div class="sec-result-row">
                        <span class="sec-result-row__k">Kendaraan</span>
                        <span class="sec-result-row__v" id="scanVehicle">-</span>
                    </div>
                    <div class="sec-result-row">
                        <span class="sec-result-row__k">Pengemudi</span>
                        <span class="sec-result-row__v" id="scanDriver">-</span>
                    </div>
                    <div class="sec-result-row">
                        <span class="sec-result-row__k">Aktivitas</span>
                        <span class="sec-result-row__v" id="scanDirection">-</span>
                    </div>
                    <div class="sec-result-row">
                        <span class="sec-result-row__k">Gate Tujuan</span>
                        <span class="sec-result-row__v" id="scanGate">-</span>
                    </div>
                    <div class="sec-result-row">
                        <span class="sec-result-row__k">ETA</span>
                        <span class="sec-result-row__v" id="scanEta">-</span>
                    </div>
                </div>
            </div>
            <div class="sec-result-card__actions">
                <button type="button" class="sec-btn sec-btn--confirm" id="scanConfirmBtn" style="display:none;">
                    <i class="fas fa-check-circle"></i> KONFIRMASI KEDATANGAN
                </button>
                <button type="button" class="sec-btn sec-btn--close" id="scanCloseBtn">Tutup</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" defer></script>
<script type="application/json" id="security_dashboard_config">{!! json_encode([
    'scanUrl' => route('security.scan'),
    'confirmUrl' => rtrim(route('security.confirm_arrival', ['slotId' => '__SLOT_ID__']), '/'),
    'refreshUrl' => route('security.ajax.today_slots'),
    'csrfToken' => csrf_token(),
    'selectedDate' => $selectedDate,
    'today' => $today,
]) !!}</script>
@vite(['resources/js/pages/security-dashboard.js'])
@endpush

@push('styles')
<style>
/* ═══════════════════════════════════════════════════
   Security Dashboard — Premium Redesign
   ═══════════════════════════════════════════════════ */

/* ── Header ── */
.sec-header {
    margin-bottom: 16px;
}
.sec-header__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.sec-header__shift-select {
    background: linear-gradient(135deg, #e8eaf6 0%, #c5cae9 100%);
    color: #283593;
    font-size: 12px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 10px;
    border: 1px solid #c5cae9;
    cursor: pointer;
    outline: none;
    appearance: auto;
    -webkit-appearance: auto;
}

/* Date navigation */
.sec-header__date-group {
    display: flex;
    align-items: center;
    gap: 6px;
}
.sec-date-nav {
    width: 34px;
    height: 34px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #555;
    transition: all 0.15s;
    font-size: 13px;
}
.sec-date-nav:hover { background: #f0f0f0; border-color: #bbb; }
.sec-date-display {
    position: relative;
    cursor: pointer;
}
.sec-date-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
    z-index: 2;
}
.sec-date-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 14px;
    font-weight: 600;
    color: #1a1a2e;
    position: relative;
    z-index: 1;
    user-select: none;
    pointer-events: none;
}
.sec-date-icon { font-size: 13px; color: #7c8db5; }
.sec-date-today {
    background: #0d6efd;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
    white-space: nowrap;
}
.sec-date-today:hover { background: #0b5ed7; }

/* Summary stats row */
.sec-stats {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 2px;
}
.sec-stats::-webkit-scrollbar { display: none; }
.sec-stat {
    flex: 1;
    min-width: 90px;
    text-align: center;
    padding: 12px 8px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border-bottom: 3px solid transparent;
    transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
    cursor: pointer;
    user-select: none;
    opacity: 0.65;
}
.sec-stat:hover { transform: translateY(-2px); opacity: 0.85; }
.sec-stat--active-filter {
    opacity: 1;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.sec-stat__val { font-size: 22px; font-weight: 800; line-height: 1; margin-bottom: 2px; }
.sec-stat__lbl { font-size: 10px; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.sec-stat--total { border-bottom-color: #5c6bc0; }
.sec-stat--total .sec-stat__val { color: #3949ab; }
.sec-stat--scheduled { border-bottom-color: #ffb300; }
.sec-stat--scheduled .sec-stat__val { color: #f9a825; }
.sec-stat--waiting { border-bottom-color: #26c6da; }
.sec-stat--waiting .sec-stat__val { color: #00acc1; }
.sec-stat--active { border-bottom-color: #ff7043; }
.sec-stat--active .sec-stat__val { color: #f4511e; }
.sec-stat--done { border-bottom-color: #66bb6a; }
.sec-stat--done .sec-stat__val { color: #43a047; }

/* ── Schedule ── */
.sec-schedule {
    padding-bottom: 90px; /* space for sticky scan bar */
}
.sec-schedule__empty {
    text-align: center;
    padding: 60px 20px;
    color: #bbb;
}
.sec-schedule__empty i { font-size: 52px; margin-bottom: 14px; display: block; }
.sec-schedule__empty p { font-size: 14px; }

/* Slot card */
.sec-slot {
    display: flex;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 8px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border-left: 4px solid #dee2e6;
    transition: box-shadow 0.15s, transform 0.15s;
    animation: sec-fadeIn 0.25s ease;
}
@keyframes sec-fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
.sec-slot:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.08); transform: translateY(-1px); }
.sec-slot--scheduled { border-left-color: #ffb300; }
.sec-slot--waiting { border-left-color: #26c6da; background: #f4fdff; }
.sec-slot--active { border-left-color: #ff7043; background: #fff9f5; }
.sec-slot--done { border-left-color: #66bb6a; background: #f6fdf7; opacity: 0.65; }
.sec-slot--late { border-left-color: #ef5350 !important; }

.sec-slot__left { min-width: 52px; text-align: center; flex-shrink: 0; }
.sec-slot__eta { font-size: 18px; font-weight: 800; color: #1a1a2e; line-height: 1.1; }
.sec-slot__arrived { font-size: 9px; color: #00897b; font-weight: 700; margin-top: 3px; }

.sec-slot__body { flex: 1; min-width: 0; }
.sec-slot__row-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 3px; }
.sec-slot__ticket { font-size: 13px; font-weight: 700; color: #1a1a2e; }
.sec-slot__badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    background: #f5f5f5;
    color: #666;
    white-space: nowrap;
}
.sec-slot--scheduled .sec-slot__badge { background: #fff8e1; color: #e65100; }
.sec-slot--waiting .sec-slot__badge { background: #e0f7fa; color: #00695c; }
.sec-slot--active .sec-slot__badge { background: #fbe9e7; color: #bf360c; }
.sec-slot--done .sec-slot__badge { background: #e8f5e9; color: #2e7d32; }

.sec-slot__vendor { font-size: 12px; font-weight: 600; color: #37474f; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.sec-slot__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 11px;
    color: #78909c;
}
.sec-slot__meta i { margin-right: 3px; opacity: 0.5; width: 12px; text-align: center; }
.sec-slot__dir {
    font-weight: 700;
    font-size: 10px;
    padding: 1px 7px;
    border-radius: 4px;
    letter-spacing: 0.5px;
}
.sec-slot__dir--inbound { background: #e0f2f1; color: #00695c; }
.sec-slot__dir--outbound { background: #e8eaf6; color: #283593; }

.sec-slot__late-tag {
    margin-top: 5px;
    font-size: 10px;
    font-weight: 700;
    color: #d32f2f;
    animation: sec-blink 1.5s infinite;
}
@keyframes sec-blink { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

/* ── Sticky Scan Bar ── */
.sec-scan-bar {
    position: fixed;
    bottom: 0;
    left: var(--st-sidebar-width, 64px);
    right: 0;
    z-index: 100;
    background: linear-gradient(180deg, rgba(255,255,255,0) 0%, #fff 14%);
    padding: 20px 20px 16px 20px;
}
.sec-scan-bar__form { max-width: 640px; margin: 0 auto; }
.sec-scan-bar__input-group {
    display: flex;
    align-items: center;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.sec-scan-bar__input-group:focus-within {
    border-color: #5c6bc0;
    box-shadow: 0 8px 30px rgba(92,107,192,0.18);
}
.sec-scan-bar__icon-left { padding: 0 0 0 16px; color: #9e9e9e; font-size: 18px; }
.sec-scan-bar__input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 14px 12px;
    font-size: 15px;
    font-weight: 600;
    color: #1a1a2e;
    outline: none;
    letter-spacing: 0.5px;
    min-width: 0;
}
.sec-scan-bar__input::placeholder { font-weight: 400; color: #aaa; letter-spacing: 0; }
.sec-scan-bar__cam-btn {
    flex-shrink: 0;
    border: none;
    background: none;
    padding: 12px;
    cursor: pointer;
    color: #5c6bc0;
    font-size: 20px;
    transition: color 0.15s;
}
.sec-scan-bar__cam-btn:hover { color: #3949ab; }
.sec-scan-bar__submit {
    flex-shrink: 0;
    border: none;
    background: linear-gradient(135deg, #5c6bc0, #3949ab);
    color: #fff;
    padding: 14px 20px;
    cursor: pointer;
    font-size: 18px;
    transition: opacity 0.15s;
}
.sec-scan-bar__submit:hover { opacity: 0.9; }

/* ── Camera Overlay ── */
.sec-camera-overlay {
    position: fixed;
    inset: 0;
    z-index: 200;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.sec-camera-panel {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    width: 360px;
    max-width: 92vw;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.sec-camera-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    font-weight: 700;
    color: #1a1a2e;
    border-bottom: 1px solid #eee;
}
.sec-camera-panel__close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #999;
    padding: 4px;
}
.sec-camera-panel__close:hover { color: #333; }
.sec-camera-panel__preview {
    width: 100%;
    min-height: 280px;
    background: #111;
}
.sec-camera-panel__hint {
    text-align: center;
    padding: 12px;
    font-size: 12px;
    color: #888;
}

/* ── Result Modal ── */
.sec-result-overlay {
    position: fixed;
    inset: 0;
    z-index: 200;
    background: rgba(15,23,42,0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(3px);
}
.sec-result-card {
    background: #fff;
    border-radius: 20px;
    width: 420px;
    max-width: 92vw;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: sec-modalIn 0.2s ease;
}
@keyframes sec-modalIn { from { opacity: 0; transform: scale(0.95); } }
.sec-result-card__header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 22px;
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #43a047, #2e7d32);
}
.sec-result-card__header--error { background: linear-gradient(135deg, #e53935, #c62828); }
.sec-result-card__header--warning { background: linear-gradient(135deg, #fb8c00, #ef6c00); }
.sec-result-card__body { padding: 18px 22px; }
.sec-result-card__details { margin-top: 8px; }
.sec-result-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid #f5f5f5;
}
.sec-result-row:last-child { border-bottom: none; }
.sec-result-row__k { font-size: 12px; color: #78909c; }
.sec-result-row__v { font-size: 13px; color: #1a1a2e; text-align: right; }
.sec-result-row__v--bold { font-weight: 700; font-size: 14px; }
.sec-result-card__actions {
    padding: 14px 22px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-top: 1px solid #f0f0f0;
}

/* Warning badges */
.sec-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 6px;
}
.sec-warning--error { background: #ffebee; color: #c62828; }
.sec-warning--warning { background: #fff8e1; color: #e65100; }
.sec-warning--late { background: #ffebee; color: #d32f2f; font-weight: 700; }

/* Buttons */
.sec-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 12px;
    border: none;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
    width: 100%;
}
.sec-btn--confirm {
    background: linear-gradient(135deg, #43a047, #2e7d32);
    color: #fff;
    box-shadow: 0 4px 12px rgba(67,160,71,0.3);
}
.sec-btn--confirm:hover { box-shadow: 0 6px 18px rgba(67,160,71,0.4); transform: translateY(-1px); }
.sec-btn--close {
    background: #f5f5f5;
    color: #555;
}
.sec-btn--close:hover { background: #eee; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .sec-scan-bar { left: 0; padding: 12px 12px 10px; }
    .sec-stats { gap: 6px; }
    .sec-stat { min-width: 70px; padding: 10px 4px; }
    .sec-stat__val { font-size: 18px; }
    .sec-slot { padding: 12px; gap: 10px; }
    .sec-slot__meta { gap: 6px; }
    .sec-header__top { flex-direction: column; align-items: stretch; gap: 8px; }
    .sec-header__date-group { justify-content: center; }
    .sec-header__shift { text-align: center; }
}
</style>
@endpush

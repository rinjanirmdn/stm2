@extends('layouts.app')

@section('title', 'Security Dashboard - e-Docking Control System')
@section('page_title', 'Security Dashboard')
@section('body_class', 'st-page--security-dashboard')

@section('content')

    {{-- ─── Top Bar: Date + Scan + Shift (single row) ─── --}}
    <div class="sec-topbar">
        <div class="sec-topbar__date-group">
            <button class="sec-date-nav" id="datePrev" title="Hari Sebelumnya"><i class="fas fa-chevron-left"></i></button>
            <div class="sec-date-display" id="dateDisplay">
                <input type="date" id="secDatePicker" value="{{ $selectedDate }}" class="sec-date-input">
                <span class="sec-date-label" id="secDateLabel">
                    @if ($selectedDate === $today)
                        Hari Ini, {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M Y') }}
                    @endif
                </span>
            </div>
            <button class="sec-date-nav" id="dateNext" title="Hari Selanjutnya"><i class="fas fa-chevron-right"></i></button>
            @if ($selectedDate !== $today)
                <button class="sec-date-today" id="dateToday" title="Kembali ke hari ini">Hari Ini</button>
            @endif
        </div>

        <form id="security-scan-form" class="sec-topbar__scan" autocomplete="off">
            @csrf
            <div class="sec-scan-group">
                <i class="fas fa-barcode sec-scan-group__icon"></i>
                <input type="text" id="security-scan-input" name="ticket_number" class="sec-scan-group__input" placeholder="Scan / ketik nomor tiket..." autofocus autocomplete="off">
                <button type="button" class="sec-scan-group__cam" id="secCameraBtn" title="Scan dengan Kamera"><i class="fas fa-camera"></i></button>
                <button type="submit" class="sec-scan-group__btn" id="security-scan-btn"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <select class="sec-shift-select" id="secShiftFilter">
            <option value="all" selected>Semua Shift</option>
            <option value="1">Shift 1 (07-15)</option>
            <option value="2">Shift 2 (15-23)</option>
            <option value="3">Shift 3 (23-07)</option>
        </select>
    </div>

    {{-- ─── Stats Row ─── --}}
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
            <a href="{{ route('slots.show', $slot->id) }}" class="sec-slot {{ $statusClass }}{{ $isLate ? ' sec-slot--late' : '' }}" data-slot-id="{{ $slot->id }}">
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
                        <span><i class="fas fa-file-invoice"></i> {{ $slot->po_number ?? '-' }}</span>
                        <span><i class="fas fa-door-open"></i> {{ $gateDisplay }}</span>
                        <span><i class="fas fa-truck"></i> {{ $slot->vehicle_number_snap ?? '-' }}</span>
                        <span class="sec-slot__dir sec-slot__dir--{{ strtolower($slot->direction ?? '') }}">{{ strtoupper($slot->direction ?? '') }}</span>
                    </div>
                    @if ($isLate)
                        <div class="sec-slot__late-tag"><i class="fas fa-exclamation-triangle"></i> TERLAMBAT</div>
                    @endif
                </div>
                @if ($status === 'scheduled')
                    <button type="button" class="sec-slot__arrival-btn" data-arrival-id="{{ $slot->id }}" title="Catat Kedatangan" onclick="event.preventDefault();event.stopPropagation();">
                        <i class="fas fa-right-to-bracket"></i>
                        <span>Arrival</span>
                    </button>
                @endif
            </a>
        @empty
            <div class="sec-schedule__empty">
                <i class="fas fa-inbox"></i>
                <p>Tidak ada jadwal untuk tanggal ini</p>
            </div>
        @endforelse
    </div>

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
                    <div class="sec-result-row"><span class="sec-result-row__k">Nomor Tiket</span><span class="sec-result-row__v sec-result-row__v--bold" id="scanTicketNumber">-</span></div>
                    <div class="sec-result-row"><span class="sec-result-row__k">Nomor PO</span><span class="sec-result-row__v" id="scanPoNumber">-</span></div>
                    <div class="sec-result-row"><span class="sec-result-row__k">Vendor</span><span class="sec-result-row__v" id="scanVendor">-</span></div>
                    <div class="sec-result-row"><span class="sec-result-row__k">Kendaraan</span><span class="sec-result-row__v" id="scanVehicle">-</span></div>
                    <div class="sec-result-row"><span class="sec-result-row__k">Pengemudi</span><span class="sec-result-row__v" id="scanDriver">-</span></div>
                    <div class="sec-result-row"><span class="sec-result-row__k">Aktivitas</span><span class="sec-result-row__v" id="scanDirection">-</span></div>
                    <div class="sec-result-row"><span class="sec-result-row__k">Gate Tujuan</span><span class="sec-result-row__v" id="scanGate">-</span></div>
                    <div class="sec-result-row"><span class="sec-result-row__k">ETA</span><span class="sec-result-row__v" id="scanEta">-</span></div>
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
    'slotShowUrl' => rtrim(route('slots.show', ['slotId' => '__SLOT_ID__']), '/'),
    'csrfToken' => csrf_token(),
    'selectedDate' => $selectedDate,
    'today' => $today,
]) !!}</script>
@vite(['resources/js/pages/security-dashboard.js'])
@endpush

@push('styles')
<style>
/* ═══════ Security Dashboard v5 ═══════ */
:root {
    --sec-indigo: #5c6bc0;
    --sec-indigo-dark: #3949ab;
    --sec-amber: #ffb300;
    --sec-teal: #26c6da;
    --sec-orange: #ff7043;
    --sec-green: #66bb6a;
    --sec-green-dark: #2e7d32;
    --sec-red: #ef5350;
}

/* ═══════ Top Bar: [Date] [Scan] [Shift] ═══════ */
.sec-topbar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.sec-topbar__date-group {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}
.sec-date-nav {
    width: 32px; height: 32px;
    border: 1px solid #e0e0e0; border-radius: 8px;
    background: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #555; font-size: 12px;
    transition: all .15s;
}
.sec-date-nav:hover { background: #f0f0f0; border-color: #bbb; }
.sec-date-display { position: relative; cursor: pointer; }
.sec-date-input {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer; width: 100%; height: 100%; z-index: 2;
}
.sec-date-label {
    display: inline-flex; align-items: center;
    background: #fff; border: 1px solid #e0e0e0; border-radius: 8px;
    padding: 6px 12px; font-size: 12px; font-weight: 600; color: #1a1a2e;
    user-select: none; pointer-events: none; white-space: nowrap;
}
.sec-date-today {
    background: var(--sec-indigo); color: #fff;
    border: none; border-radius: 8px;
    padding: 6px 12px; font-size: 11px; font-weight: 600;
    cursor: pointer; transition: background .15s; white-space: nowrap;
}
.sec-date-today:hover { background: var(--sec-indigo-dark); }

/* Inline scan bar — fills remaining space */
.sec-topbar__scan { flex: 1; min-width: 180px; }
.sec-scan-group {
    display: flex; align-items: center;
    background: #fff;
    border: 1px solid #e0e0e0; border-radius: 8px;
    overflow: hidden; height: 32px;
    transition: border-color .2s, box-shadow .2s;
}
.sec-scan-group:focus-within {
    border-color: var(--sec-indigo);
    box-shadow: 0 0 0 2px rgba(92,107,192,.15);
}
.sec-scan-group__icon { padding: 0 0 0 10px; color: #bbb; font-size: 13px; }
.sec-scan-group__input {
    flex: 1; border: none; background: transparent;
    padding: 0 8px; font-size: 12px; font-weight: 500;
    color: #1a1a2e; outline: none; min-width: 0; height: 100%;
}
.sec-scan-group__input::placeholder { color: #aaa; font-weight: 400; }
.sec-scan-group__cam {
    border: none; background: none;
    padding: 0 6px; cursor: pointer;
    color: var(--sec-indigo); font-size: 14px;
    display: flex; align-items: center; height: 100%;
    transition: color .15s;
}
.sec-scan-group__cam:hover { color: var(--sec-indigo-dark); }
.sec-scan-group__btn {
    border: none;
    background: var(--sec-indigo);
    color: #fff; padding: 0 12px; cursor: pointer;
    font-size: 13px; height: 100%;
    display: flex; align-items: center;
    transition: background .15s;
}
.sec-scan-group__btn:hover { background: var(--sec-indigo-dark); }

/* Shift select */
.sec-shift-select {
    background: #fff; color: #37474f;
    font-size: 12px; font-weight: 600;
    padding: 6px 10px; border-radius: 8px;
    border: 1px solid #e0e0e0; cursor: pointer; outline: none;
    flex-shrink: 0; height: 32px;
}

/* ═══════ Stats Row ═══════ */
.sec-stats {
    display: flex; gap: 6px;
    overflow-x: auto; scrollbar-width: none;
    -ms-overflow-style: none; margin-bottom: 12px;
}
.sec-stats::-webkit-scrollbar { display: none; }
.sec-stat {
    flex: 1; min-width: 80px;
    text-align: center; padding: 10px 6px;
    border-radius: 10px; background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    border-bottom: 3px solid transparent;
    transition: transform .15s, box-shadow .15s, opacity .15s;
    cursor: pointer; user-select: none; opacity: .55;
}
.sec-stat:hover { transform: translateY(-2px); opacity: .8; }
.sec-stat--active-filter { opacity: 1; box-shadow: 0 3px 10px rgba(0,0,0,.1); transform: translateY(-1px); }
.sec-stat__val { font-size: 20px; font-weight: 800; line-height: 1; margin-bottom: 2px; }
.sec-stat__lbl { font-size: 9px; color: #888; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.sec-stat--total { border-bottom-color: var(--sec-indigo); }
.sec-stat--total .sec-stat__val { color: var(--sec-indigo-dark); }
.sec-stat--scheduled { border-bottom-color: var(--sec-amber); }
.sec-stat--scheduled .sec-stat__val { color: #f9a825; }
.sec-stat--waiting { border-bottom-color: var(--sec-teal); }
.sec-stat--waiting .sec-stat__val { color: #00acc1; }
.sec-stat--active { border-bottom-color: var(--sec-orange); }
.sec-stat--active .sec-stat__val { color: #f4511e; }
.sec-stat--done { border-bottom-color: var(--sec-green); }
.sec-stat--done .sec-stat__val { color: #43a047; }

/* ═══════ Schedule ═══════ */
.sec-schedule { padding-bottom: 8px; }
.sec-schedule__empty { text-align: center; padding: 48px 16px; color: #bbb; }
.sec-schedule__empty i { font-size: 44px; margin-bottom: 10px; display: block; }
.sec-schedule__empty p { font-size: 13px; }
.sec-slot {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 12px;
    margin-bottom: 6px; background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
    border-left: 4px solid #dee2e6;
    transition: box-shadow .15s, transform .15s;
    animation: sec-fadeIn .25s ease;
    text-decoration: none; color: inherit; cursor: pointer;
}
@keyframes sec-fadeIn { from { opacity:0; transform: translateY(4px); } to { opacity:1; transform: none; } }
.sec-slot:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); transform: translateY(-1px); color: inherit; text-decoration: none; }
.sec-slot--scheduled { border-left-color: var(--sec-amber); }
.sec-slot--waiting { border-left-color: var(--sec-teal); background: #f8fdff; }
.sec-slot--active { border-left-color: var(--sec-orange); background: #fffaf6; }
.sec-slot--done { border-left-color: var(--sec-green); background: #f7fdf8; opacity: .6; }
.sec-slot--late { border-left-color: var(--sec-red) !important; }
.sec-slot__left { min-width: 48px; text-align: center; flex-shrink: 0; }
.sec-slot__eta { font-size: 17px; font-weight: 800; color: #1a1a2e; line-height: 1.1; }
.sec-slot__arrived { font-size: 9px; color: #00897b; font-weight: 700; margin-top: 2px; }
.sec-slot__body { flex: 1; min-width: 0; }
.sec-slot__row-top { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 2px; }
.sec-slot__ticket { font-size: 13px; font-weight: 700; color: #1a1a2e; }
.sec-slot__badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px; background: #f5f5f5; color: #666; white-space: nowrap; }
.sec-slot--scheduled .sec-slot__badge { background: #fff8e1; color: #e65100; }
.sec-slot--waiting .sec-slot__badge { background: #e0f7fa; color: #00695c; }
.sec-slot--active .sec-slot__badge { background: #fbe9e7; color: #bf360c; }
.sec-slot--done .sec-slot__badge { background: #e8f5e9; color: #2e7d32; }
.sec-slot__vendor { font-size: 12px; font-weight: 600; color: #37474f; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sec-slot__meta { display: flex; flex-wrap: wrap; gap: 8px; font-size: 11px; color: #78909c; }
.sec-slot__meta i { margin-right: 2px; opacity: .5; width: 12px; text-align: center; }
.sec-slot__dir { font-weight: 700; font-size: 10px; padding: 1px 6px; border-radius: 4px; letter-spacing: .4px; }
.sec-slot__dir--inbound { background: #e0f2f1; color: #00695c; }
.sec-slot__dir--outbound { background: #e8eaf6; color: #283593; }
.sec-slot__late-tag { margin-top: 3px; font-size: 10px; font-weight: 700; color: #d32f2f; animation: sec-blink 1.5s infinite; }
@keyframes sec-blink { 0%,100% { opacity:1; } 50% { opacity:.4; } }
.sec-slot__arrival-btn {
    flex-shrink: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 2px; padding: 8px 12px; border-radius: 10px;
    background: linear-gradient(135deg, var(--sec-teal), #00acc1);
    color: #fff; border: none; cursor: pointer;
    font-size: 16px; font-weight: 700;
    transition: all .15s; box-shadow: 0 2px 8px rgba(0,172,193,.3); min-width: 56px;
}
.sec-slot__arrival-btn span { font-size: 9px; font-weight: 700; letter-spacing: .3px; }
.sec-slot__arrival-btn:hover { box-shadow: 0 4px 14px rgba(0,172,193,.4); transform: scale(1.05); }

/* ═══════ Camera Overlay ═══════ */
.sec-camera-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,.7); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.sec-camera-panel { background: #fff; border-radius: 16px; overflow: hidden; width: 340px; max-width: 92vw; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
.sec-camera-panel__header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; font-weight: 700; color: #1a1a2e; border-bottom: 1px solid #eee; }
.sec-camera-panel__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #999; padding: 4px; }
.sec-camera-panel__close:hover { color: #333; }
.sec-camera-panel__preview { width: 100%; min-height: 260px; background: #111; }
.sec-camera-panel__hint { text-align: center; padding: 10px; font-size: 11px; color: #888; }

/* ═══════ Result Modal ═══════ */
.sec-result-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(15,23,42,.55); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
.sec-result-card { background: #fff; border-radius: 16px; width: 400px; max-width: 92vw; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.2); animation: sec-modalIn .2s ease; }
@keyframes sec-modalIn { from { opacity:0; transform: scale(.95); } }
.sec-result-card__header { display: flex; align-items: center; gap: 10px; padding: 14px 20px; font-size: 15px; font-weight: 700; color: #fff; background: linear-gradient(135deg, #43a047, var(--sec-green-dark)); }
.sec-result-card__header--error { background: linear-gradient(135deg, #e53935, #c62828); }
.sec-result-card__header--warning { background: linear-gradient(135deg, #fb8c00, #ef6c00); }
.sec-result-card__body { padding: 16px 20px; }
.sec-result-card__details { margin-top: 6px; }
.sec-result-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #f5f5f5; }
.sec-result-row:last-child { border-bottom: none; }
.sec-result-row__k { font-size: 12px; color: #78909c; }
.sec-result-row__v { font-size: 13px; color: #1a1a2e; text-align: right; }
.sec-result-row__v--bold { font-weight: 700; font-size: 14px; }
.sec-result-card__actions { padding: 12px 20px; display: flex; flex-direction: column; gap: 6px; border-top: 1px solid #f0f0f0; }
.sec-warning { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; margin-bottom: 6px; }
.sec-warning--error { background: #ffebee; color: #c62828; }
.sec-warning--warning { background: #fff8e1; color: #e65100; }
.sec-warning--late { background: #ffebee; color: #d32f2f; font-weight: 700; }
.sec-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 11px; border-radius: 10px; border: none; font-size: 14px; font-weight: 700; cursor: pointer; transition: all .15s; width: 100%; }
.sec-btn--confirm { background: linear-gradient(135deg, #43a047, var(--sec-green-dark)); color: #fff; box-shadow: 0 4px 12px rgba(67,160,71,.3); }
.sec-btn--confirm:hover { box-shadow: 0 6px 18px rgba(67,160,71,.4); transform: translateY(-1px); }
.sec-btn--close { background: #f5f5f5; color: #555; }
.sec-btn--close:hover { background: #eee; }

/* ═══════ RESPONSIVE ═══════ */
@media (max-width: 768px) {
    .sec-topbar { gap: 6px; }
    .sec-topbar__scan { order: 3; flex-basis: 100%; }
    .sec-stats { gap: 4px; }
    .sec-stat { min-width: 60px; padding: 8px 4px; border-radius: 8px; }
    .sec-stat__val { font-size: 17px; }
    .sec-stat__lbl { font-size: 8px; }
    .sec-slot { padding: 10px; gap: 8px; border-radius: 10px; }
    .sec-slot__eta { font-size: 15px; }
    .sec-slot__ticket { font-size: 12px; }
    .sec-slot__vendor { font-size: 11px; }
    .sec-slot__meta { gap: 6px; font-size: 10px; }
    .sec-slot__arrival-btn { padding: 6px 8px; min-width: 46px; font-size: 14px; }
    .sec-slot__arrival-btn span { font-size: 8px; }
}
@media (max-width: 480px) {
    .sec-topbar { flex-direction: column; align-items: stretch; }
    .sec-topbar__date-group { justify-content: center; }
    .sec-topbar__scan { order: unset; }
    .sec-shift-select { width: 100%; text-align: center; }
    .sec-stats { gap: 3px; }
    .sec-stat { min-width: 50px; padding: 7px 2px; }
    .sec-stat__val { font-size: 15px; }
    .sec-stat__lbl { font-size: 7px; letter-spacing: 0; }
    .sec-date-label { font-size: 11px; padding: 5px 8px; }
    .sec-date-nav { width: 28px; height: 28px; font-size: 11px; }
    .sec-slot { flex-wrap: wrap; }
    .sec-slot__left { min-width: 42px; }
    .sec-slot__eta { font-size: 14px; }
    .sec-slot__body { width: calc(100% - 120px); }
    .sec-slot__row-top { flex-direction: column; align-items: flex-start; gap: 2px; }
    .sec-slot__meta { gap: 4px; font-size: 9px; }
    .sec-slot__arrival-btn { padding: 5px 6px; min-width: 40px; font-size: 13px; border-radius: 8px; }
    .sec-scan-group { height: 36px; }
    .sec-scan-group__input { font-size: 13px; }
    .sec-camera-panel { width: 95vw; }
    .sec-result-card { width: 95vw; }
}
@media (max-width: 360px) {
    .sec-stat { min-width: 44px; padding: 6px 2px; }
    .sec-stat__val { font-size: 13px; }
    .sec-slot__meta span { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
}
</style>
@endpush

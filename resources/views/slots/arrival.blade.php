@extends('layouts.app')

@section('title', 'Arrival - Slot Time Management')
@section('page_title', 'Arrival')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div style="font-size:12px;color:#6b7280;">Slot #{{ $slot->id }}</div>
        <div style="font-weight:600;">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.arrival.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Scan Ticket / Manual Input <span style="color:#dc2626;">*</span></label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input
                            type="text"
                            name="ticket_number"
                            class="st-input"
                            required
                            value="{{ old('ticket_number') }}"
                            placeholder="Scan barcode or enter ticket number..."
                        >
                        <button type="button" id="btn_scan_ticket" class="st-btn st-btn--secondary" style="white-space:nowrap; padding:8px 12px;" title="Scan via Camera">
                            <i class="fas fa-camera"></i>
                        </button>
                        @if (!empty($slot->ticket_number) && in_array((string) ($slot->status ?? ''), ['scheduled', 'waiting', 'in_progress'], true))
                            @unless(optional(auth()->user())->hasRole('Operator'))
                            @can('slots.ticket')
                            <a href="{{ route('slots.ticket', ['slotId' => $slot->id]) }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary); padding:8px 12px;" title="Print Ticket" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">
                                <i class="fas fa-print"></i>
                            </a>
                            @endcan
                            @endunless
                        @endif
                    </div>
                    <div id="ticket_match_hint" class="st-text--small" style="margin-top:6px;color:#dc2626;display:none;"></div>
                    <div id="scan_camera_wrap" style="display:none;margin-top:8px;border:1px solid #e5e7eb;border-radius:8px;padding:8px;background:#f8fafc;">
                        <div style="display:flex;align-items:center;gap:8px;justify-content:space-between;">
                            <div style="font-size:12px;color:#475569;">Point camera at ticket barcode/QR.</div>
                            <button type="button" id="btn_scan_stop" class="st-btn st-btn--secondary" style="padding:6px 10px;font-size:11px;" title="Stop Camera">
                                <i class="fas fa-stop"></i>
                            </button>
                        </div>
                        <video id="scan_camera" style="width:100%;max-width:360px;margin-top:8px;border-radius:6px;transform:scaleX(-1);" autoplay muted playsinline></video>
                        <div id="scan_qr_reader" style="width:100%;max-width:360px;margin-top:8px;border-radius:6px;display:none;transform:scaleX(-1);"></div>
                        <div id="scan_camera_status" class="st-text--small st-text--muted" style="margin-top:6px;"></div>
                    </div>
                    <div class="st-text--small st-text--muted" style="margin-top:4px;">After ticket is filled, detail form will appear.</div>
                </div>
            </div>

            <div id="arrival_details" style="display:none;">

                <div style="border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#f8fafc;margin-bottom:12px;">
                    <div style="font-weight:600;margin-bottom:8px;">Slot Details</div>
                    <div style="font-size:12px;color:#475569;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;">
                        <div><strong>PO/DO:</strong> {{ $slot->po_number ?? $slot->truck_number ?? '-' }}</div>
                        <div><strong>Supplier:</strong> {{ $slot->vendor_name ?? '-' }}</div>
                        <div><strong>Warehouse:</strong> {{ $slot->warehouse_name ?? '-' }}</div>
                        <div><strong>Direction:</strong> {{ ucfirst($slot->direction ?? '-') }}</div>
                        <div><strong>Planned Start:</strong> {{ $slot->planned_start ?? '-' }}</div>
                        <div><strong>Planned Gate:</strong> {{ app(\App\Services\SlotService::class)->getGateDisplayName($slot->planned_gate_warehouse_code ?? '', $slot->planned_gate_number ?? '') }}</div>
                    </div>
                    <div style="margin-top:10px;">
                        <div style="font-weight:600;margin-bottom:6px;">Item & Qty (Slot)</div>
                        @if (!empty($slotItems) && $slotItems->count() > 0)
                            <div class="st-table-wrapper" style="margin-top:6px;">
                                <table class="st-table" style="font-size:12px;">
                                    <thead>
                                        <tr>
                                            <th style="width:70px;">Item</th>
                                            <th>Material</th>
                                            <th style="width:120px;text-align:right;">Qty</th>
                                            <th style="width:90px;">UOM</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($slotItems as $item)
                                            <tr>
                                                <td><strong>{{ $item->item_no }}</strong></td>
                                                <td>{{ $item->material_code ?? '-' }}{{ $item->material_name ? ' - ' . $item->material_name : '' }}</td>
                                                <td style="text-align:right;">{{ number_format((float) ($item->qty_booked ?? 0), 3) }}</td>
                                                <td>{{ $item->uom ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="st-text--small st-text--muted">No item details available for this slot.</div>
                        @endif
                    </div>
                </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="st-btn" style="padding:8px 16px;" title="Save Arrival">
                    <i class="fas fa-save"></i>
                    <span style="margin-left:6px;">Save</span>
                </button>
                <a href="{{ route('slots.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary); padding:8px 16px;" title="Cancel">
                    <i class="fas fa-times"></i>
                    <span style="margin-left:6px;">Cancel</span>
                </a>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ticketInput = document.querySelector('input[name="ticket_number"]');
    var arrivalDetails = document.getElementById('arrival_details');
    var ticketHint = document.getElementById('ticket_match_hint');
    var scanBtn = document.getElementById('btn_scan_ticket');
    var scanWrap = document.getElementById('scan_camera_wrap');
    var scanVideo = document.getElementById('scan_camera');
    var scanReader = document.getElementById('scan_qr_reader');
    var scanStopBtn = document.getElementById('btn_scan_stop');
    var scanStatus = document.getElementById('scan_camera_status');
    var scanStream = null;
    var scanActive = false;
    var html5Qr = null;

    var expectedTicket = "{{ (string) ($slot->ticket_number ?? '') }}";

    function toggleArrivalDetails() {
        if (!ticketInput || !arrivalDetails) return;
        var value = (ticketInput.value || '').trim();
        var hasTicket = value !== '';
        var matches = expectedTicket === '' || value === expectedTicket;
        arrivalDetails.style.display = hasTicket && matches ? 'block' : 'none';
        if (ticketHint) {
            if (!hasTicket || matches) {
                ticketHint.style.display = 'none';
                ticketHint.textContent = '';
            } else {
                ticketHint.style.display = 'block';
                ticketHint.textContent = 'Ticket number tidak sesuai dengan slot ini.';
            }
        }
    }

    function updateScanStatus(message) {
        if (scanStatus) {
            scanStatus.textContent = message || '';
        }
    }

    function stopCameraScan() {
        scanActive = false;
        if (scanStream) {
            scanStream.getTracks().forEach(function (track) { track.stop(); });
            scanStream = null;
        }
        if (html5Qr) {
            html5Qr.stop().catch(function () {}).finally(function () {
                html5Qr = null;
            });
        }
        if (scanVideo) scanVideo.style.display = 'none';
        if (scanReader) scanReader.style.display = 'none';
        if (scanWrap) scanWrap.style.display = 'none';
    }

    function handleScanResult(code) {
        if (!code) return;
        if (ticketInput) {
            ticketInput.value = code;
            ticketInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        updateScanStatus('Ticket terdeteksi: ' + code);
        stopCameraScan();
    }

    function startHtml5Scanner() {
        if (!scanReader) return;
        if (!window.Html5Qrcode) {
            updateScanStatus('Scanner tidak siap. Silakan input manual.');
            scanWrap.style.display = 'block';
            return;
        }

        scanActive = true;
        scanWrap.style.display = 'block';
        if (scanVideo) scanVideo.style.display = 'none';
        scanReader.style.display = 'block';
        updateScanStatus('Scanning (HTML5)...');

        var supportedFormats = null;
        if (window.Html5QrcodeSupportedFormats) {
            supportedFormats = [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E
            ];
        }

        html5Qr = new Html5Qrcode('scan_qr_reader');
        html5Qr
            .start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 240, height: 240 },
                    formatsToSupport: supportedFormats || undefined
                },
                function (decodedText) { handleScanResult(decodedText); },
                function () {}
            )
            .catch(function () {
                updateScanStatus('Akses kamera ditolak. Silakan input manual.');
            });
    }

    function startCameraScan() {
        if (!scanWrap || !scanVideo) return;
        if (!('BarcodeDetector' in window)) {
            startHtml5Scanner();
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function (stream) {
                scanStream = stream;
                scanVideo.srcObject = stream;
                scanWrap.style.display = 'block';
                scanVideo.style.display = 'block';
                if (scanReader) scanReader.style.display = 'none';
                updateScanStatus('Scanning...');
                scanActive = true;

                var detector = new BarcodeDetector({
                    formats: ['qr_code', 'code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e']
                });

                var scanLoop = function () {
                    if (!scanActive || !scanVideo) return;
                    detector.detect(scanVideo)
                        .then(function (barcodes) {
                            if (!scanActive) return;
                            if (barcodes && barcodes.length) {
                                handleScanResult(barcodes[0].rawValue || '');
                                return;
                            }
                            requestAnimationFrame(scanLoop);
                        })
                        .catch(function () {
                            updateScanStatus('Gagal membaca barcode. Coba ulangi.');
                            requestAnimationFrame(scanLoop);
                        });
                };

                requestAnimationFrame(scanLoop);
            })
            .catch(function () {
                updateScanStatus('Akses kamera ditolak. Silakan input manual.');
                scanWrap.style.display = 'block';
            });
    }

    if (ticketInput) {
        ticketInput.addEventListener('input', toggleArrivalDetails);
        ticketInput.addEventListener('blur', toggleArrivalDetails);
    }
    toggleArrivalDetails();

    if (scanBtn) {
        scanBtn.addEventListener('click', function () {
            if (scanActive) return;
            startCameraScan();
        });
    }
    if (scanStopBtn) {
        scanStopBtn.addEventListener('click', function () {
            stopCameraScan();
        });
    }
});
</script>
@endpush

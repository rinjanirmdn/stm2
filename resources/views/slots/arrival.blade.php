    <div class="st-card st-mb-16 st-border-l-4 st-card--primary-accent">
        <div class="st-flex st-justify-between st-align-center st-mb-12">
            <h3 class="st-m-0 st-text-16">Arrival Registration</h3>
            <div class="st-flex st-align-center st-gap-8">
                <span class="st-badge st-badge--primary st-text--sm">Ref #{{ $slot->id }}</span>
            </div>
        </div>
        <div class="st-form-row--grid-3 st-text--sm">
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-truck"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">PO</div>
                    <div class="st-font-semibold">{{ $slot->truck_number ?? '-' }}</div>
                </div>
            </div>
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-id-card"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">Vehicle Number</div>
                    <div class="st-font-semibold">{{ $slot->vehicle_number_snap ?? '-' }}</div>
                </div>
            </div>
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-warehouse"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">Warehouse</div>
                    <div class="st-font-semibold">{{ $slot->warehouse_name ?? '-' }}</div>
                </div>
            </div>
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">Planned ETA</div>
                    <div class="st-flex st-flex-col st-gap-2 st-mt-2">
                        @if(isset($slot->planned_start))
                            @php $eta = \Carbon\Carbon::parse($slot->planned_start); @endphp
                            <div class="st-font-semibold st-flex st-align-center st-gap-6"><i class="far fa-calendar-alt st-text--slate st-text-12"></i> {{ $eta->format('d-m-Y') }}</div>
                            <div class="st-font-semibold st-flex st-align-center st-gap-6"><i class="far fa-clock st-text--slate st-text-12"></i> {{ $eta->format('H:i') }}</div>
                        @else
                            <div class="st-font-semibold">-</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <form method="POST" action="{{ route('slots.arrival.store', ['slotId' => $slot->id, 'popup' => request()->boolean('popup') ? 1 : null]) }}">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Scan Ticket / Manual Input <span class="st-text--danger-dark">*</span></label>
                    <div class="st-flex st-gap-8 st-align-center">
                        <input
                            type="text"
                            name="ticket_number"
                            class="st-input"
                            required
                            value="{{ old('ticket_number') }}"
                            placeholder="Scan barcode or enter ticket number..."
                        >
                        <button type="button" id="btn_scan_ticket" class="st-btn st-btn--secondary st-btn--pad-md st-nowrap" title="Scan via Camera">
                            <i class="fas fa-camera"></i>
                        </button>
                        @if (!empty($slot->ticket_number) && in_array((string) ($slot->status ?? ''), ['scheduled', 'waiting', 'in_progress'], true))
                            @can('slots.ticket')
                            <a href="{{ route('slots.ticket', ['slotId' => $slot->id]) }}" class="st-btn st-btn--outline-primary st-btn--pad-md" title="Print Ticket" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">
                                <i class="fas fa-print"></i>
                            </a>
                            @endcan
                        @endif
                    </div>
                    <div id="ticket_match_hint" class="st-text--small st-text--danger st-mt-6 st-hidden-soft"></div>
                    <div id="scan_camera_wrap" class="st-hidden-soft st-mt-8 st-border st-rounded-8 st-p-8 st-bg-slate-50">
                        <div class="st-flex st-align-center st-gap-8 st-justify-between">
                            <div class="st-text--sm st-text--slate">Point camera at ticket barcode/QR.</div>
                            <button type="button" id="btn_scan_stop" class="st-btn st-btn--secondary st-btn--xs st-btn--pad-sm" title="Stop Camera">
                                <i class="fas fa-stop"></i>
                            </button>
                        </div>
                        <video id="scan_camera" class="st-w-full st-maxw-360 st-mt-8 st-rounded-6 st-scale-x-neg" autoplay muted playsinline></video>
                        <div id="scan_qr_reader" class="st-w-full st-maxw-360 st-mt-8 st-rounded-6 st-hidden-soft st-scale-x-neg"></div>
                        <div id="scan_camera_status" class="st-text--small st-text--muted st-mt-6"></div>
                    </div>
                    <div class="st-flex st-justify-between st-align-center st-mt-4 st-gap-10">
                        <div class="st-text--small st-text--muted st-flex-1">After ticket is filled, detail form will appear.</div>
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('slots.index') }}" class="st-btn st-btn--outline-primary st-btn--xs st-flex-0 st-nowrap">Back</a>
                    </div>
                </div>
            </div>

            <div id="arrival_details" class="st-hidden-soft">

                <div class="st-border st-border-slate-200 st-rounded-10 st-p-20 st-mb-16 st-bg-white st-shadow-sm">
                    <div class="st-font-semibold st-mb-16 st-text-16 st-border-b st-border-slate-100 st-pb-10">
                        <i class="fas fa-list-alt st-text--muted st-mr-8"></i> Booking Information
                    </div>
                    <div class="st-form-row--grid-2 st-gap-24">
                        <div class="st-flex st-flex-col st-gap-16">
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Customer/Vendor</div>
                                <div class="st-font-semibold st-text-15">{{ $slot->vendor_name ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">PO Number</div>
                                <div class="st-font-semibold st-text-15">{{ $slot->po_number ?? $slot->truck_number ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Vehicle Number</div>
                                <div class="st-font-semibold st-text-15">{{ $slot->vehicle_number_snap ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Activity Direction</div>
                                <div class="st-font-semibold st-text-15 st-text--primary">{{ ucfirst($slot->direction ?? '-') }}</div>
                            </div>
                        </div>
                        <div class="st-flex st-flex-col st-gap-16">
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Warehouse</div>
                                <div class="st-font-semibold st-text-15">{{ $slot->warehouse_name ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Assigned Gate</div>
                                <div class="st-font-semibold st-text-15">{{ app(\App\Services\SlotService::class)->getGateDisplayName($slot->planned_gate_warehouse_code ?? '', $slot->planned_gate_number ?? '') }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Planned ETA</div>
                                <div class="st-flex st-flex-col st-gap-4">
                                    @if(isset($slot->planned_start))
                                        @php $etaDetail = \Carbon\Carbon::parse($slot->planned_start); @endphp
                                        <div class="st-font-semibold st-text-15 st-flex st-align-center st-gap-8"><i class="far fa-calendar-alt st-text--muted st-text-14"></i> {{ $etaDetail->format('d-m-Y') }}</div>
                                        <div class="st-font-semibold st-text-15 st-flex st-align-center st-gap-8"><i class="far fa-clock st-text--muted st-text-14"></i> {{ $etaDetail->format('H:i') }}</div>
                                    @else
                                        <div class="st-font-semibold st-text-15">-</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @include('partials.backdate-section')

            <div class="st-form-actions">
                <button type="submit" class="st-btn st-btn--pad-lg" title="Save Arrival">
                    <i class="fas fa-save"></i>
                    <span class="st-ml-6">Save</span>
                </button>
                <button type="button" class="st-btn st-btn--outline-primary st-btn--pad-lg" title="Cancel" onclick="closeGlobalAjaxModal()">
                    <i class="fas fa-times"></i>
                    <span class="st-ml-6">Cancel</span>
                </button>
            </div>
        </form>
    </div>

<script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
<script type="application/json" id="slots_arrival_config">{!! json_encode([
    'expectedTicket' => (string) ($slot->ticket_number ?? ''),
]) !!}</script>
<script>
function stReadJson(id, fallback) {
    try {
        var el = document.getElementById(id);
        if (!el) return fallback;
        return JSON.parse(el.textContent || '{}') || fallback;
    } catch (e) {
        return fallback;
    }
}

(function () {
    var config = stReadJson('slots_arrival_config', {});
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

    var expectedTicket = String(config.expectedTicket || '');

    function toggleArrivalDetails() {
        if (!ticketInput || !arrivalDetails) return;
        var value = (ticketInput.value || '').trim();
        var hasTicket = value !== '';
        var matches = expectedTicket === '' || value === expectedTicket;
        var submitBtn = document.querySelector('#arrival_details').closest('form').querySelector('button[type="submit"]');
        
        arrivalDetails.style.display = hasTicket && matches ? 'block' : 'none';
        if (submitBtn) submitBtn.disabled = !(hasTicket && matches);
        
        if (ticketHint) {
            if (!hasTicket || matches) {
                ticketHint.style.display = 'none';
                ticketHint.textContent = '';
            } else {
                ticketHint.style.display = 'block';
                ticketHint.textContent = 'Ticket number does not match this slot.';
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
        updateScanStatus('Ticket detected: ' + code);
        stopCameraScan();
    }

    function startHtml5Scanner() {
        if (!scanReader) return;
        if (!window.Html5Qrcode) {
            updateScanStatus('Scanner is not ready. Please enter manually.');
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
                    qrbox: { width: 260, height: 120 },
                    formatsToSupport: supportedFormats || undefined
                },
                function (decodedText) { handleScanResult(decodedText); },
                function () {}
            )
            .catch(function () {
                updateScanStatus('Camera access denied. Please enter manually.');
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

                // Detect front/back camera and mirror accordingly
                var track = stream.getVideoTracks()[0];
                var settings = track.getSettings ? track.getSettings() : {};
                var isFrontCamera = (settings.facingMode === 'user' || !settings.facingMode);
                scanVideo.style.transform = isFrontCamera ? 'scaleX(-1)' : 'none';

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
                            updateScanStatus('Failed to read barcode. Please try again.');
                            requestAnimationFrame(scanLoop);
                        });
                };

                requestAnimationFrame(scanLoop);
            })
            .catch(function () {
                updateScanStatus('Camera access denied. Please enter manually.');
                scanWrap.style.display = 'block';
            });
    }

    if (ticketInput) {
        ticketInput.addEventListener('input', toggleArrivalDetails);
        ticketInput.addEventListener('blur', toggleArrivalDetails);

        // Prevent barcode scanner's auto-Enter from submitting the form
        ticketInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                e.stopPropagation();
                toggleArrivalDetails();
                ticketInput.blur(); // remove focus so user sees the validated result
            }
        });
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
})();
</script>

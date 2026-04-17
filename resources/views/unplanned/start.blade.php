
    @php
        $gateStatuses = $gateStatuses ?? [];
        $conflictDetails = $conflictDetails ?? [];
        $selectedGateId = old('actual_gate_id') !== null && (string) old('actual_gate_id') !== ''
            ? (int) old('actual_gate_id')
            : ($selectedGateId ?? null);
        $conflictLines = session('conflict_lines');
    @endphp

    <div class="st-card st-mb-12">
        <div class="st-text--sm st-text--muted">Unplanned #{{ $slot->id }}</div>
        <div class="st-font-semibold">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
        <div class="st-text--sm st-text--muted st-mt-4">
            Estimated Process Duration: {{ (int) $plannedDurationMinutes }} Minutes
        </div>
    </div>

    @if (is_array($conflictLines) && count($conflictLines) > 0)
        <div class="st-alert st-alert--error st-mb-12">
            <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="st-alert__text">
                <div class="st-font-semibold st-mb-2">Lane Conflict</div>
                <div class="st-text--sm st-text--dark">
                    <div class="st-mb-6">Conflicting Active Bookings:</div>
                    <ul class="st-list">
                        @foreach ($conflictLines as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div>
        <form method="POST" action="{{ route('unplanned.start.store', ['slotId' => $slot->id, 'popup' => request()->boolean('popup') ? 1 : null]) }}">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Scan Ticket <span class="st-text--danger-dark">*</span></label>
                    <div class="st-flex st-gap-8 st-align-center">
                        <input
                            type="text"
                            name="ticket_number"
                            class="st-input"
                            required
                            value="{{ old('ticket_number') }}"
                            placeholder="Scan barcode or enter ticket number..."
                            autocomplete="off"
                        >
                        <button type="button" id="btn_scan_ticket" class="st-btn st-btn--secondary st-btn--pad-md st-nowrap" title="Scan via Camera">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
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
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Actual Gate <span class="st-text--danger-dark">*</span></label>
                    <select name="actual_gate_id" class="st-select" required>
                        <option value="">Choose Gate...</option>
                        @php
                            $slotWhId = (int) ($slot->warehouse_id ?? 0);
                            $sameWh = [];
                            $otherWh = [];
                            foreach ($gates as $g) {
                                if ((int)($g->warehouse_id ?? 0) === $slotWhId) {
                                    $sameWh[] = $g;
                                } else {
                                    $otherWh[] = $g;
                                }
                            }
                        @endphp
                        @if (!empty($sameWh))
                            <optgroup label="Same Warehouse">
                                @foreach ($sameWh as $gate)
                                    @php
                                        $gid = (int) ($gate->id ?? 0);
                                        $st = $gateStatuses[$gid] ?? ['is_conflict' => false, 'overlapping_slots' => []];
                                        $isConflict = !empty($st['is_conflict']);
                                        $label = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                                        $text = $label;
                                        if ($isConflict) {
                                            $firstId = !empty($st['overlapping_slots']) ? (int) $st['overlapping_slots'][0] : 0;
                                            $row = $firstId ? ($conflictDetails[$firstId] ?? null) : null;
                                            $short = $row ? ('Booking #' . (int)$row->id . ' ' . (string)($row->ticket_number ?? '')) : ($firstId ? ('Booking #' . $firstId) : 'Occupied');
                                            $text .= ' (In Use: ' . $short . ')';
                                        } else {
                                            $text .= ' (Available)';
                                        }
                                    @endphp
                                    <option value="{{ $gid }}" {{ (int)$selectedGateId === $gid ? 'selected' : '' }}>{{ $text }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if (!empty($otherWh))
                            <optgroup label="Other Warehouses">
                                @foreach ($otherWh as $gate)
                                    @php
                                        $gid = (int) ($gate->id ?? 0);
                                        $st = $gateStatuses[$gid] ?? ['is_conflict' => false, 'overlapping_slots' => []];
                                        $isConflict = !empty($st['is_conflict']);
                                        $label = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                                        $text = $label;
                                        if ($isConflict) {
                                            $firstId = !empty($st['overlapping_slots']) ? (int) $st['overlapping_slots'][0] : 0;
                                            $row = $firstId ? ($conflictDetails[$firstId] ?? null) : null;
                                            $short = $row ? ('Booking #' . (int)$row->id . ' ' . (string)($row->ticket_number ?? '')) : ($firstId ? ('Booking #' . $firstId) : 'Occupied');
                                            $text .= ' (In Use: ' . $short . ')';
                                        } else {
                                            $text .= ' (Available)';
                                        }
                                    @endphp
                                    <option value="{{ $gid }}" {{ (int)$selectedGateId === $gid ? 'selected' : '' }}>{{ $text }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>

                    @if ($recommendedGateId)
                        <div class="st-text--sm st-text--muted st-mt-6">
                            Rekomendasi: {{ $recommendedGateId }}
                        </div>
                    @endif
                </div>
            </div>

            @php
                $waitingMinutes = $waitingMinutes ?? 0;
                $requireReason = $waitingMinutes > 60;
            @endphp

            @if ($requireReason)
                <div class="st-alert st-alert--warning st-mb-12">
                    <span class="st-alert__icon"><i class="fa-solid fa-clock"></i></span>
                    <span class="st-alert__text">
                        The truck has been waiting for <strong>{{ $waitingMinutes }} minutes</strong> (more than 60 minutes).
                        Please provide the reason for the long wait before starting the process.
                    </span>
                </div>

                <div class="st-form-row st-form-field--mb-12">
                    <div class="st-form-field">
                        <label class="st-label">Long Waiting Reason <span class="st-text--danger-dark">*</span></label>
                        <textarea name="waiting_reason" class="st-input" rows="3" required
                                  placeholder="Explain why the truck waited more than 60 minutes...">{{ old('waiting_reason') }}</textarea>
                        <div class="st-text--sm st-text--muted st-mt-4">
                            Example: Gate occupied, previous unloading delay, document issue, etc.
                        </div>
                    </div>
                </div>
            @endif

            @include('partials.backdate-section')

            <div class="st-form-actions">
                <button type="submit" class="st-btn">Start</button>
                <button type="button" class="st-btn st-btn--outline-primary" onclick="closeGlobalAjaxModal()">Cancel</button>
            </div>
        </form>
    </div>

<script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    var ticketInput = document.querySelector('input[name="ticket_number"]');
    var scanBtn = document.getElementById('btn_scan_ticket');
    var scanWrap = document.getElementById('scan_camera_wrap');
    var scanVideo = document.getElementById('scan_camera');
    var scanReader = document.getElementById('scan_qr_reader');
    var scanStopBtn = document.getElementById('btn_scan_stop');
    var scanStatus = document.getElementById('scan_camera_status');
    var scanStream = null;
    var scanActive = false;
    var html5Qr = null;

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
        ticketInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                e.stopPropagation();
                ticketInput.blur();
            }
        });
    }

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

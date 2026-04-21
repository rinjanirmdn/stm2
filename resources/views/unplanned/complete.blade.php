
    <div class="st-card st-mb-12">
        <div class="st-text--sm st-text--muted">Unplanned #{{ $slot->id }}</div>
        <div class="st-font-semibold">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
    </div>

    <div>
        <form method="POST" action="{{ route('unplanned.complete.store', ['slotId' => $slot->id, 'popup' => request()->boolean('popup') ? 1 : null]) }}">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">SJ / Resi Number <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="mat_doc" class="st-input" required value="{{ old('mat_doc') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Mat Doc <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="mat_doc_number" class="st-input" value="{{ old('mat_doc_number') }}">
                </div>
            </div>

            @if(strtolower($slot->direction ?? '') === 'outbound')
            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">NO Seal <span class="st-text--danger-dark">*</span></label>
                    <div id="seal_entries_container">
                        <div class="seal-entry st-flex st-gap-8 st-align-center st-mb-8" data-seal-index="0">
                            <input
                                type="text"
                                name="seal_number[]"
                                class="st-input seal-input"
                                required
                                value="{{ old('seal_number.0') }}"
                                placeholder="Enter or scan seal number..."
                            >
                            <button type="button" class="st-btn st-btn--secondary st-btn--pad-md st-nowrap btn-scan-seal" title="Scan via Camera">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>
                    <div class="st-flex st-gap-8 st-mt-4">
                        <button type="button" id="btn_add_seal" class="st-btn st-btn--outline-primary st-btn--xs st-btn--pad-sm">
                            <i class="fas fa-plus st-mr-4"></i> Tambah Seal
                        </button>
                    </div>
                    <div id="seal_scan_camera_wrap" class="st-hidden-soft st-mt-8 st-border st-rounded-8 st-p-8 st-bg-slate-50">
                        <div class="st-flex st-align-center st-gap-8 st-justify-between">
                            <div class="st-text--sm st-text--slate">Point camera at seal barcode/QR.</div>
                            <button type="button" id="btn_seal_scan_stop" class="st-btn st-btn--secondary st-btn--xs st-btn--pad-sm" title="Stop Camera">
                                <i class="fas fa-stop"></i>
                            </button>
                        </div>
                        <video id="seal_scan_camera" class="st-w-full st-maxw-360 st-mt-8 st-rounded-6 st-scale-x-neg" autoplay muted playsinline></video>
                        <div id="seal_scan_qr_reader" class="st-w-full st-maxw-360 st-mt-8 st-rounded-6 st-hidden-soft st-scale-x-neg"></div>
                        <div id="seal_scan_camera_status" class="st-text--small st-text--muted st-mt-6"></div>
                    </div>
                </div>
            </div>
            @endif

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field st-form-field--relative">
                    <label class="st-label">Truck Type <span class="st-text--danger-dark">*</span></label>
                    <select name="truck_type" class="st-select" required>
                        <option value="">Select Truck Type</option>
                        @foreach($truckTypes as $type)
                            <option value="{{ $type }}" {{ (old('truck_type', $slot->truck_type ?? '') === $type) ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="vehicle_number" class="st-input" required value="{{ old('vehicle_number', $slot->vehicle_number_snap ?? '') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_name" class="st-input" value="{{ old('driver_name') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="driver_number" class="st-input" required value="{{ old('driver_number') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Notes <span class="st-text--optional">(Optional)</span></label>
                    <textarea name="notes" class="st-textarea" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>

            @include('partials.backdate-section')

            <div class="st-form-actions">
                <button type="submit" class="st-btn">Complete Unplanned</button>
                <button type="button" class="st-btn st-btn--outline-primary" onclick="closeGlobalAjaxModal()">Cancel</button>
            </div>
        </form>
    </div>

    <script type="application/json" id="truck_types_json">{{ json_encode(array_values($truckTypes ?? [])) }}</script>

@if(strtolower($slot->direction ?? '') === 'outbound')
<script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    var container = document.getElementById('seal_entries_container');
    var addBtn = document.getElementById('btn_add_seal');
    var scanWrap = document.getElementById('seal_scan_camera_wrap');
    var scanVideo = document.getElementById('seal_scan_camera');
    var scanReader = document.getElementById('seal_scan_qr_reader');
    var scanStopBtn = document.getElementById('btn_seal_scan_stop');
    var scanStatus = document.getElementById('seal_scan_camera_status');
    var scanStream = null;
    var scanActive = false;
    var html5Qr = null;
    var activeScanTarget = null;
    var sealIndex = 0;

    function updateScanStatus(message) {
        if (scanStatus) scanStatus.textContent = message || '';
    }

    function stopCameraScan() {
        scanActive = false;
        activeScanTarget = null;
        if (scanStream) {
            scanStream.getTracks().forEach(function (track) { track.stop(); });
            scanStream = null;
        }
        if (html5Qr) {
            html5Qr.stop().catch(function () {}).finally(function () { html5Qr = null; });
        }
        if (scanVideo) scanVideo.style.display = 'none';
        if (scanReader) scanReader.style.display = 'none';
        if (scanWrap) scanWrap.style.display = 'none';
    }

    function handleScanResult(code) {
        if (!code) return;
        if (activeScanTarget) {
            activeScanTarget.value = code;
            activeScanTarget.dispatchEvent(new Event('input', { bubbles: true }));
        }
        updateScanStatus('Seal detected: ' + code);
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

        html5Qr = new Html5Qrcode('seal_scan_qr_reader');
        html5Qr
            .start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    formatsToSupport: supportedFormats || undefined
                },
                function (decodedText) { handleScanResult(decodedText); },
                function () {}
            )
            .catch(function () {
                updateScanStatus('Camera access denied. Please enter manually.');
            });
    }

    function startCameraScan(targetInput) {
        if (!scanWrap || !scanVideo) return;
        activeScanTarget = targetInput;

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

    function createSealEntry(idx) {
        var div = document.createElement('div');
        div.className = 'seal-entry st-flex st-gap-8 st-align-center st-mb-8';
        div.setAttribute('data-seal-index', idx);
        div.innerHTML =
            '<input type="text" name="seal_number[]" class="st-input seal-input" required placeholder="Enter or scan seal number...">' +
            '<button type="button" class="st-btn st-btn--secondary st-btn--pad-md st-nowrap btn-scan-seal" title="Scan via Camera"><i class="fas fa-camera"></i></button>' +
            '<button type="button" class="st-btn st-btn--danger st-btn--pad-md st-nowrap btn-remove-seal" title="Hapus"><i class="fas fa-trash-alt"></i></button>';
        return div;
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            sealIndex++;
            var entry = createSealEntry(sealIndex);
            container.appendChild(entry);
        });
    }

    if (container) {
        container.addEventListener('click', function (e) {
            var scanBtn = e.target.closest('.btn-scan-seal');
            var removeBtn = e.target.closest('.btn-remove-seal');

            if (scanBtn) {
                if (scanActive) return;
                var entry = scanBtn.closest('.seal-entry');
                var input = entry ? entry.querySelector('.seal-input') : null;
                if (input) startCameraScan(input);
            }

            if (removeBtn) {
                var entry = removeBtn.closest('.seal-entry');
                if (entry && container.querySelectorAll('.seal-entry').length > 1) {
                    entry.remove();
                }
            }
        });

        container.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.keyCode === 13) && e.target.classList.contains('seal-input')) {
                e.preventDefault();
                e.stopPropagation();
                e.target.blur();
            }
        });
    }

    if (scanStopBtn) {
        scanStopBtn.addEventListener('click', function () {
            stopCameraScan();
        });
    }
})();
</script>
@endif

@vite(['resources/js/pages/unplanned-complete.js'])


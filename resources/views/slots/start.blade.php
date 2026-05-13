@extends(request()->boolean('popup') ? 'layouts.popup' : 'layouts.app')

@section('title', 'Start Process - e-Docking Control System')
@section('page_title', 'Start Process')

@section('content')
    @php
        $gateStatuses = $gateStatuses ?? [];
        $conflictDetails = $conflictDetails ?? [];
        $selectedGateId = old('actual_gate_id') !== null && (string) old('actual_gate_id') !== ''
            ? (int) old('actual_gate_id')
            : ($selectedGateId ?? null);
        $conflictLines = session('conflict_lines');
    @endphp

    <div class="st-card st-mb-16 st-border-l-4 st-card--primary-accent">
        <div class="st-flex st-justify-between st-align-center st-mb-12">
            <h3 class="st-m-0 st-text-16">Start Process</h3>
            <span class="st-badge st-badge--primary st-text--sm">Ref #{{ $slot->id_slots }}</span>
        </div>
        <div class="st-form-row--grid-3 st-text--sm">
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-truck"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">PO / SO</div>
                    <div class="st-font-semibold">{{ $slot->truck_number ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-warehouse"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">Warehouse</div>
                    <div class="st-font-semibold">{{ $slot->warehouse_name ?? 'N/A' }}</div>
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
                        <div class="st-text--xs st-text--muted st-mt-4">
                            Duration: {{ (int) $plannedDurationMinutes }} Min
                        </div>
                    </div>
                </div>
            </div>
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
        <form method="POST" action="{{ route('slots.start.store', ['slotId' => $slot->id_slots, 'popup' => request()->boolean('popup') ? 1 : null]) }}" enctype="multipart/form-data">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Scan Ticket <span class="st-text--danger-dark">*</span></label>
                    <div class="st-flex st-gap-8 st-align-center">
                        <input
                            type="text"
                            name="ticket_number"
                            id="ticket_number_input"
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
                    <div id="ticket_validation_msg" class="st-text--sm st-mt-4" style="color: #dc2626; display: none;">
                        <i class="fas fa-times-circle st-mr-4"></i> <span>Ticket number does not match this booking.</span>
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
                    {{-- Hidden select for form submission --}}
                    <select name="actual_gate_id" id="gate_select_hidden" required style="display:none;">
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
                        @foreach ($gates as $gate)
                            @php $gid = (int)($gate->id_gates ?? 0); @endphp
                            <option value="{{ $gid }}" {{ (int)$selectedGateId === $gid ? 'selected' : '' }}>{{ $gid }}</option>
                        @endforeach
                    </select>

                    {{-- Custom gate dropdown with color indicators --}}
                    <div class="gate-dropdown" id="gate_custom_dropdown" style="position:relative;">
                        <div class="gate-dropdown__trigger st-input" id="gate_dropdown_trigger" tabindex="0"
                             style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;user-select:none;">
                            <span id="gate_dropdown_label" style="display:flex;align-items:center;gap:8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <span style="color:#94a3b8;">Choose Gate...</span>
                            </span>
                            <i class="fa-solid fa-chevron-down" style="font-size:0.7rem;color:#94a3b8;flex-shrink:0;"></i>
                        </div>
                        <div class="gate-dropdown__menu" id="gate_dropdown_menu"
                             style="display:none;position:absolute;top:100%;left:0;right:0;z-index:999;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-height:240px;overflow-y:auto;margin-top:4px;">
                            @if (!empty($sameWh))
                                <div style="padding:6px 12px;font-size:0.7rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;background:#f8fafc;">Same Warehouse</div>
                                @foreach ($sameWh as $gate)
                                    @php
                                        $gid = (int) ($gate->id_gates ?? 0);
                                        $st = $gateStatuses[$gid] ?? ['is_conflict' => false, 'overlapping_slots' => []];
                                        $isConflict = !empty($st['is_conflict']);
                                        $label = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                                        $text = trim(($gate->warehouse_name ?? '') . ' - ' . $label);
                                        if ($isConflict) {
                                            $firstId = !empty($st['overlapping_slots']) ? (int) $st['overlapping_slots'][0] : 0;
                                            $row = $firstId ? ($conflictDetails[$firstId] ?? null) : null;
                                            $short = $row ? ('Planned #' . (int)$row->id_slots . ' ' . (string)($row->ticket_number ?? '')) : ($firstId ? ('Planned #' . $firstId) : 'Occupied');
                                            $statusLabel = 'In Use';
                                        } else {
                                            $statusLabel = 'Available';
                                        }
                                    @endphp
                                    <div class="gate-dropdown__item" data-value="{{ $gid }}" data-available="{{ $isConflict ? '0' : '1' }}" data-label="{{ $text }}" data-status="{{ $statusLabel }}"
                                         style="padding:8px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:0.85rem;"
                                         onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background=''">
                                        <span style="width:10px;height:10px;border-radius:50%;flex-shrink:0;background:{{ $isConflict ? '#ef4444' : '#22c55e' }};"></span>
                                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $text }}</span>
                                        <span style="font-size:0.7rem;color:{{ $isConflict ? '#ef4444' : '#16a34a' }};font-weight:500;flex-shrink:0;">{{ $statusLabel }}</span>
                                    </div>
                                @endforeach
                            @endif
                            @if (!empty($otherWh))
                                <div style="padding:6px 12px;font-size:0.7rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;background:#f8fafc;{{ !empty($sameWh) ? 'border-top:1px solid #e2e8f0;' : '' }}">Other Warehouses</div>
                                @foreach ($otherWh as $gate)
                                    @php
                                        $gid = (int) ($gate->id_gates ?? 0);
                                        $st = $gateStatuses[$gid] ?? ['is_conflict' => false, 'overlapping_slots' => []];
                                        $isConflict = !empty($st['is_conflict']);
                                        $label = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                                        $text = trim(($gate->warehouse_name ?? '') . ' - ' . $label);
                                        if ($isConflict) {
                                            $firstId = !empty($st['overlapping_slots']) ? (int) $st['overlapping_slots'][0] : 0;
                                            $row = $firstId ? ($conflictDetails[$firstId] ?? null) : null;
                                            $short = $row ? ('Planned #' . (int)$row->id_slots . ' ' . (string)($row->ticket_number ?? '')) : ($firstId ? ('Planned #' . $firstId) : 'Occupied');
                                            $statusLabel = 'In Use';
                                        } else {
                                            $statusLabel = 'Available';
                                        }
                                    @endphp
                                    <div class="gate-dropdown__item" data-value="{{ $gid }}" data-available="{{ $isConflict ? '0' : '1' }}" data-label="{{ $text }}" data-status="{{ $statusLabel }}"
                                         style="padding:8px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:0.85rem;"
                                         onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background=''">
                                        <span style="width:10px;height:10px;border-radius:50%;flex-shrink:0;background:{{ $isConflict ? '#ef4444' : '#22c55e' }};"></span>
                                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $text }}</span>
                                        <span style="font-size:0.7rem;color:{{ $isConflict ? '#ef4444' : '#16a34a' }};font-weight:500;flex-shrink:0;">{{ $statusLabel }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    @if ($recommendedGateId)
                        <div class="st-text--sm st-text--muted st-mt-6">
                            Rekomendasi: {{ $recommendedGateId }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Photo Documentation <span class="st-text--optional">(Optional)</span></label>
                    <input type="file" name="photos[]" id="photo_input" class="st-hidden-soft" accept="image/*" multiple>
                    <div id="photo_capture_container">
                        <div id="photo_initial_state" class="st-border st-border-dashed st-rounded-6 st-p-16 st-bg-slate-50 st-text-center">
                            <i class="fas fa-camera st-text--xl st-text--slate-light st-mb-8"></i>
                            <div class="st-text--sm st-text--muted st-mb-12">No photo attached</div>
                            <div class="st-flex st-justify-center st-gap-8">
                                <button type="button" id="btn_open_camera" class="st-btn st-btn--secondary st-btn--sm">
                                    <i class="fas fa-camera st-mr-4"></i> Open Camera
                                </button>
                                <button type="button" id="btn_upload_photo" class="st-btn st-btn--secondary st-btn--sm" onclick="document.getElementById('photo_input').click()">
                                    <i class="fas fa-upload st-mr-4"></i> Upload File
                                </button>
                            </div>
                        </div>

                        <div id="photo_camera_view" class="st-hidden-soft st-mt-8 st-border st-rounded-8 st-p-8 st-bg-slate-50">
                            <div class="st-flex st-align-center st-gap-8 st-justify-between st-mb-8">
                                <div class="st-text--sm st-text--slate">Point camera to capture photo.</div>
                                <button type="button" id="btn_close_camera" class="st-btn st-btn--secondary st-btn--xs st-btn--pad-sm" title="Close Camera">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <video id="photo_video_stream" class="st-w-full st-rounded-6 st-bg-black" autoplay muted playsinline style="max-height: 300px; object-fit: contain; background: #000;"></video>
                            <div class="st-mt-8 st-text-center">
                                <button type="button" id="btn_capture_photo" class="st-btn st-btn--primary">
                                    <i class="fas fa-circle st-mr-4"></i> Capture Photo
                                </button>
                            </div>
                        </div>

                        <div id="photo_preview_state" class="st-hidden-soft st-mt-8 st-border st-border-dashed st-rounded-6 st-p-16 st-bg-slate-50 st-text-center">
                            <div id="photo_preview_grid" class="st-flex st-flex-wrap st-gap-8 st-mb-12 st-justify-center"></div>
                            <div class="st-text--sm st-text--muted st-mb-8"><span id="photo_count">0</span>/5 photos selected</div>
                            <div class="st-flex st-justify-center st-gap-8">
                                <button type="button" id="btn_add_more_photo" class="st-btn st-btn--secondary st-btn--sm">
                                    <i class="fas fa-plus st-mr-4"></i> Add More
                                </button>
                                <button type="button" id="btn_remove_photo" class="st-btn st-btn--danger-soft st-btn--sm">
                                    <i class="fas fa-trash st-mr-4"></i> Remove All
                                </button>
                            </div>
                        </div>
                    </div>
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
                <button type="submit" class="st-btn st-btn--pad-lg">
                    <i class="fas fa-play"></i>
                    <span class="st-ml-6">Start Process</span>
                </button>
                <button type="button" class="st-btn st-btn--outline-primary st-btn--pad-lg" onclick="closeGlobalAjaxModal()">
                    <i class="fas fa-times"></i>
                    <span class="st-ml-6">Cancel</span>
                </button>
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

    // Real-time Ticket Validation
    var expectedTicket = "{{ strtoupper($slot->ticket_number ?? '') }}";
    var slotType = "{{ $slot->slot_type ?? 'planned' }}";
    var submitBtn = document.querySelector('form button[type="submit"]');
    var ticketValidationMsg = document.getElementById('ticket_validation_msg');

    if (ticketInput && slotType !== 'unplanned') {
        function validateTicket() {
            var val = (ticketInput.value || '').trim().toUpperCase();
            if (expectedTicket !== '' && val !== '' && val !== expectedTicket) {
                ticketInput.style.borderColor = '#dc2626';
                if (ticketValidationMsg) ticketValidationMsg.style.display = 'block';
                if (submitBtn) submitBtn.disabled = true;
            } else {
                ticketInput.style.borderColor = '';
                if (ticketValidationMsg) ticketValidationMsg.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        }
        ticketInput.addEventListener('input', validateTicket);
        ticketInput.addEventListener('change', validateTicket);
        
        // Initial check if there's already value
        if (ticketInput.value) {
            validateTicket();
        }
    }

    // Photo Capture Logic
    var photoInputHidden = document.getElementById('photo_input');
    var btnOpenCamera = document.getElementById('btn_open_camera');
    var btnCloseCamera = document.getElementById('btn_close_camera');
    var btnCapturePhoto = document.getElementById('btn_capture_photo');
    var btnAddMorePhoto = document.getElementById('btn_add_more_photo');
    var btnRemovePhoto = document.getElementById('btn_remove_photo');
    
    var viewInitial = document.getElementById('photo_initial_state');
    var viewCamera = document.getElementById('photo_camera_view');
    var viewPreview = document.getElementById('photo_preview_state');
    
    var videoStream = document.getElementById('photo_video_stream');
    var previewGrid = document.getElementById('photo_preview_grid');
    var photoCountSpan = document.getElementById('photo_count');
    
    var photoStream = null;
    var shouldMirrorPhoto = false;
    var selectedPhotos = new DataTransfer();

    function updatePreviewView() {
        previewGrid.innerHTML = '';
        var files = selectedPhotos.files;
        photoCountSpan.innerText = files.length;
        
        if (files.length === 0) {
            viewPreview.style.display = 'none';
            viewInitial.style.display = 'block';
            return;
        }
        
        viewInitial.style.display = 'none';
        viewPreview.style.display = 'block';
        
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var reader = new FileReader();
            reader.onload = (function(f, idx) {
                return function(e) {
                    var container = document.createElement('div');
                    container.className = 'st-relative';
                    
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'st-rounded-6 st-border';
                    img.style.width = '100px';
                    img.style.height = '100px';
                    img.style.objectFit = 'cover';
                    
                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'st-btn st-btn--danger st-btn--xs st-absolute';
                    removeBtn.style.top = '-5px';
                    removeBtn.style.right = '-5px';
                    removeBtn.style.padding = '2px 6px';
                    removeBtn.style.borderRadius = '50%';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.onclick = function() {
                        var dt = new DataTransfer();
                        var currentFiles = selectedPhotos.files;
                        for (var j = 0; j < currentFiles.length; j++) {
                            if (j !== idx) dt.items.add(currentFiles[j]);
                        }
                        selectedPhotos = dt;
                        photoInputHidden.files = selectedPhotos.files;
                        updatePreviewView();
                    };
                    
                    container.appendChild(img);
                    container.appendChild(removeBtn);
                    previewGrid.appendChild(container);
                };
            })(file, i);
            reader.readAsDataURL(file);
        }
    }

    function stopPhotoCamera() {
        if (photoStream) {
            photoStream.getTracks().forEach(function(track) { track.stop(); });
            photoStream = null;
        }
        if (viewCamera) viewCamera.style.display = 'none';
        if (selectedPhotos.files.length === 0) {
            if (viewInitial) viewInitial.style.display = 'block';
        } else {
            if (viewPreview) viewPreview.style.display = 'block';
        }
    }

    function openCameraView() {
        if (selectedPhotos.files.length >= 5) {
            alert('Maximum 5 photos selected.');
            return;
        }
        viewInitial.style.display = 'none';
        viewPreview.style.display = 'none';
        viewCamera.style.display = 'block';
        
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                photoStream = stream;
                videoStream.srcObject = stream;
                
                var track = stream.getVideoTracks()[0];
                var settings = track.getSettings();
                shouldMirrorPhoto = (settings.facingMode === 'user' || !settings.facingMode);
                
                if (shouldMirrorPhoto) {
                    videoStream.style.transform = 'scaleX(-1)';
                } else {
                    videoStream.style.transform = 'none';
                }
            })
            .catch(function(err) {
                alert('Tidak dapat mengakses kamera: ' + err.message);
                stopPhotoCamera();
                updatePreviewView();
            });
    }

    if (btnOpenCamera) btnOpenCamera.addEventListener('click', openCameraView);
    if (btnAddMorePhoto) btnAddMorePhoto.addEventListener('click', openCameraView);
    
    if (btnCloseCamera) {
        btnCloseCamera.addEventListener('click', stopPhotoCamera);
    }

    function processImageFile(file, callback) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                var maxSize = 1024;
                var width = img.width;
                var height = img.height;

                if (width > height) {
                    if (width > maxSize) { height *= maxSize / width; width = maxSize; }
                } else {
                    if (height > maxSize) { width *= maxSize / height; height = maxSize; }
                }

                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                callback(canvas.toDataURL('image/jpeg', 0.7), canvas, file);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    if (btnCapturePhoto) {
        btnCapturePhoto.addEventListener('click', function() {
            var canvas = document.createElement('canvas');
            var width = videoStream.videoWidth || 640;
            var height = videoStream.videoHeight || 480;
            
            var maxSize = 1024;
            if (width > height) {
                if (width > maxSize) { height *= maxSize / width; width = maxSize; }
            } else {
                if (height > maxSize) { width *= maxSize / height; height = maxSize; }
            }
            
            canvas.width = width;
            canvas.height = height;
            var ctx = canvas.getContext('2d');
            
            if (shouldMirrorPhoto) {
                ctx.translate(width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(videoStream, 0, 0, width, height);
            if (shouldMirrorPhoto) {
                ctx.setTransform(1, 0, 0, 1, 0, 0);
            }
            
            canvas.toBlob(function(blob) {
                var compressedFile = new File([blob], "capture_" + Date.now() + ".jpg", { type: 'image/jpeg', lastModified: Date.now() });
                selectedPhotos.items.add(compressedFile);
                photoInputHidden.files = selectedPhotos.files;
                
                stopPhotoCamera();
                updatePreviewView();
            }, 'image/jpeg', 0.7);
        });
    }

    if (photoInputHidden) {
        photoInputHidden.addEventListener('change', function(e) {
            var files = e.target.files;
            if (!files || files.length === 0) return;
            
            var filesProcessed = 0;
            var totalFilesToProcess = Math.min(files.length, 5 - selectedPhotos.items.length);
            
            if (totalFilesToProcess <= 0) {
                alert('Maksimal 5 foto.');
                return;
            }

            for (var i = 0; i < totalFilesToProcess; i++) {
                processImageFile(files[i], function(dataUrl, canvas, originalFile) {
                    canvas.toBlob(function(blob) {
                        var compressedFile = new File([blob], originalFile.name, { type: 'image/jpeg', lastModified: Date.now() });
                        selectedPhotos.items.add(compressedFile);
                        filesProcessed++;
                        
                        if (filesProcessed === totalFilesToProcess) {
                            photoInputHidden.files = selectedPhotos.files;
                            updatePreviewView();
                            if (viewCamera) viewCamera.style.display = 'none';
                        }
                    }, 'image/jpeg', 0.7);
                });
            }
        });
    }

    if (btnRemovePhoto) {
        btnRemovePhoto.addEventListener('click', function() {
            selectedPhotos = new DataTransfer();
            photoInputHidden.files = selectedPhotos.files;
            updatePreviewView();
        });
    }
})();
</script>

<script>
// Custom Gate Dropdown Controller
(function() {
    var trigger = document.getElementById('gate_dropdown_trigger');
    var menu = document.getElementById('gate_dropdown_menu');
    var label = document.getElementById('gate_dropdown_label');
    var hiddenSelect = document.getElementById('gate_select_hidden');
    if (!trigger || !menu || !label || !hiddenSelect) return;

    var isOpen = false;

    function toggleMenu() {
        isOpen = !isOpen;
        menu.style.display = isOpen ? 'block' : 'none';
    }

    function closeMenu() {
        isOpen = false;
        menu.style.display = 'none';
    }

    function selectItem(item) {
        var value = item.getAttribute('data-value');
        var itemLabel = item.getAttribute('data-label');
        var available = item.getAttribute('data-available') === '1';
        var status = item.getAttribute('data-status');
        var dotColor = available ? '#22c55e' : '#ef4444';
        var textColor = available ? '#16a34a' : '#dc2626';

        // Update hidden select
        hiddenSelect.value = value;

        // Update trigger label
        label.innerHTML = '<span style="width:10px;height:10px;border-radius:50%;flex-shrink:0;background:' + dotColor + ';display:inline-block;"></span>' +
            '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + itemLabel + '</span>' +
            '<span style="font-size:0.7rem;color:' + textColor + ';font-weight:500;flex-shrink:0;margin-left:auto;">' + status + '</span>';

        // Update trigger border
        trigger.style.borderColor = dotColor;
        trigger.style.boxShadow = '0 0 0 2px ' + (available ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.15)');

        // Highlight selected item
        menu.querySelectorAll('.gate-dropdown__item').forEach(function(el) {
            el.style.background = el === item ? '#f0f9ff' : '';
            el.style.fontWeight = el === item ? '600' : '';
        });

        closeMenu();
    }

    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu();
    });

    menu.querySelectorAll('.gate-dropdown__item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            selectItem(this);
        });
    });

    document.addEventListener('click', function() {
        closeMenu();
    });

    // Pre-select if there's a value
    var currentVal = hiddenSelect.value;
    if (currentVal) {
        var preSelected = menu.querySelector('.gate-dropdown__item[data-value="' + currentVal + '"]');
        if (preSelected) selectItem(preSelected);
    }
})();
</script>
@endsection

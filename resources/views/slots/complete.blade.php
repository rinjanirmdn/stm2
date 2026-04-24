
    <div class="st-card st-mb-16 st-border-l-4 st-card--primary-accent">
        <div class="st-flex st-justify-between st-align-center st-mb-12">
            <h3 class="st-m-0 st-text-16">Complete Registration</h3>
            <span class="st-badge st-badge--primary st-text--sm">Ref #{{ $slot->id }}</span>
        </div>
        <div class="st-form-row--grid-3 st-text--sm">
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-truck"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">PO / SO</div>
                    <div class="st-font-semibold">{{ $slot->truck_number ?? '-' }}</div>
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
        <form method="POST" action="{{ route('slots.complete.store', ['slotId' => $slot->id, 'popup' => request()->boolean('popup') ? 1 : null]) }}" enctype="multipart/form-data">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Nomor Surat Jalan <span class="st-text--danger-dark">*</span></label>
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
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="vehicle_number" class="st-input" required value="{{ old('vehicle_number', $slot->vehicle_number_snap ?? '') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="driver_name" class="st-input" required value="{{ old('driver_name', $slot->driver_name ?? '') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="driver_number" class="st-input" value="{{ old('driver_number', $slot->driver_number ?? '') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Notes <span class="st-text--optional">(Optional)</span></label>
                    <textarea name="notes" class="st-textarea" rows="3">{{ old('notes') }}</textarea>
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

            @include('partials.backdate-section')

            <div class="st-form-actions">
                <button type="submit" class="st-btn st-btn--pad-lg">
                    <i class="fas fa-check-circle"></i>
                    <span class="st-ml-6">Complete Registration</span>
                </button>
                <button type="button" class="st-btn st-btn--outline-primary st-btn--pad-lg" onclick="closeGlobalAjaxModal()">
                    <i class="fas fa-times"></i>
                    <span class="st-ml-6">Cancel</span>
                </button>
            </div>
        </form>
    </div>

    <script type="application/json" id="truck_types_json">{{ json_encode(array_values($truckTypes)) }}</script>
    <script>
    (function() {
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
            (function(idx) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var container = document.createElement('div');
                    container.style.position = 'relative';
                    container.style.display = 'inline-block';
                    
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '100px';
                    img.style.height = '100px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '6px';
                    img.style.border = '1px solid #e2e8f0';
                    
                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.style.position = 'absolute';
                    removeBtn.style.top = '-6px';
                    removeBtn.style.right = '-6px';
                    removeBtn.style.background = '#ef4444';
                    removeBtn.style.color = '#fff';
                    removeBtn.style.border = 'none';
                    removeBtn.style.borderRadius = '50%';
                    removeBtn.style.width = '22px';
                    removeBtn.style.height = '22px';
                    removeBtn.style.cursor = 'pointer';
                    removeBtn.style.fontSize = '12px';
                    removeBtn.style.lineHeight = '22px';
                    removeBtn.style.textAlign = 'center';
                    removeBtn.style.padding = '0';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.onclick = function() {
                        var dt = new DataTransfer();
                        for (var j = 0; j < selectedPhotos.files.length; j++) {
                            if (j !== idx) dt.items.add(selectedPhotos.files[j]);
                        }
                        selectedPhotos = dt;
                        photoInputHidden.files = selectedPhotos.files;
                        updatePreviewView();
                    };
                    
                    container.appendChild(img);
                    container.appendChild(removeBtn);
                    previewGrid.appendChild(container);
                };
                reader.readAsDataURL(selectedPhotos.files[idx]);
            })(i);
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
            alert('Maksimal 5 foto telah dipilih.');
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
                callback(canvas, file);
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
            var totalFilesToProcess = Math.min(files.length, 5 - selectedPhotos.files.length);
            
            if (totalFilesToProcess <= 0) {
                alert('Maksimal 5 foto.');
                return;
            }

            for (var i = 0; i < totalFilesToProcess; i++) {
                processImageFile(files[i], function(canvas, originalFile) {
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
    var activeScanTarget = null; // which input to fill on scan result
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

    // Add seal entry
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            sealIndex++;
            var entry = createSealEntry(sealIndex);
            container.appendChild(entry);
        });
    }

    // Delegate click events for scan and remove buttons
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

        // Prevent barcode scanner's auto-Enter from submitting the form
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


@vite(['resources/js/pages/slots-complete.js'])


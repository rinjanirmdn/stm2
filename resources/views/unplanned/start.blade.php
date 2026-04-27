
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
        <form method="POST" action="{{ route('unplanned.start.store', ['slotId' => $slot->id, 'popup' => request()->boolean('popup') ? 1 : null]) }}" enctype="multipart/form-data">
            @csrf



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
                <button type="submit" class="st-btn">Start</button>
                <button type="button" class="st-btn st-btn--outline-primary" onclick="closeGlobalAjaxModal()">Cancel</button>
            </div>
        </form>
    </div>

<script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
<script>
(function () {


    // Photo Compression Logic
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

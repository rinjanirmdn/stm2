/**
 * Security Dashboard v5 — shift/status filter, native arrival modal, slot links, camera
 */
(function () {
    'use strict';

    var configEl = document.getElementById('security_dashboard_config');
    if (!configEl) return;

    var config = JSON.parse(configEl.textContent);
    var scanInput = document.getElementById('security-scan-input');
    var scanForm = document.getElementById('security-scan-form');
    var scanBtn = document.getElementById('security-scan-btn');
    var modal = document.getElementById('securityScanModal');
    var confirmBtn = document.getElementById('scanConfirmBtn');
    var closeBtn = document.getElementById('scanCloseBtn');

    var arrivalModal = document.getElementById('securityArrivalModal');
    var arrivalCloseBtn = document.getElementById('arrivalCloseBtn');
    var arrivalConfirmBtn = document.getElementById('arrivalConfirmBtn');

    var currentSlotId = null;
    var arrivalSlotId = null;
    var arrivalExpectedTicket = '';
    var arrivalTicketVerified = false;
    var selectedDate = config.selectedDate || config.today;
    var html5QrCode = null;
    var activeStatusFilter = 'all';
    var activeShiftFilter = 'all';
    var allSlots = [];
    var cameraScanTarget = 'main'; // 'main' = scan input, 'arrival' = arrival ticket input

    // ═══════════════════════════════════════
    //  DATE NAVIGATION
    // ═══════════════════════════════════════
    var datePicker = document.getElementById('secDatePicker');
    var dateLabel = document.getElementById('secDateLabel');
    var datePrev = document.getElementById('datePrev');
    var dateNext = document.getElementById('dateNext');
    var dateToday = document.getElementById('dateToday');

    function normalizeDate(input) {
        if (input instanceof Date && !isNaN(input.getTime())) {
            return input.getUTCFullYear() + '-' + String(input.getUTCMonth()+1).padStart(2,'0') + '-' + String(input.getUTCDate()).padStart(2,'0');
        }
        var s = String(input || '').trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
        var m = s.match(/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/);
        if (m) return m[3] + '-' + m[2] + '-' + m[1];
        var d = new Date(s);
        if (!isNaN(d.getTime())) {
            return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
        }
        return config.today;
    }

    function formatDateLabel(dateStr) {
        var safe = normalizeDate(dateStr);
        var d = new Date(safe + 'T00:00:00');
        var today = new Date(config.today + 'T00:00:00');
        var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        if (isNaN(d.getTime())) return safe;
        var base = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        if (d.getTime() === today.getTime()) return 'Hari Ini, ' + base;
        return base;
    }

    function shiftDate(days) {
        var safe = normalizeDate(selectedDate);
        var d = new Date(safe + 'T00:00:00');
        d.setDate(d.getDate() + days);
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }

    function navigateToDate(dateStr) {
        selectedDate = normalizeDate(dateStr);
        if (datePicker) datePicker.value = selectedDate;
        if (dateLabel) dateLabel.textContent = formatDateLabel(selectedDate);
        if (dateToday) dateToday.style.display = selectedDate === config.today ? 'none' : '';
        refreshSchedule();
    }

    if (datePicker) datePicker.addEventListener('change', function () {
        var picked = this.valueAsDate ? normalizeDate(this.valueAsDate) : normalizeDate(this.value);
        navigateToDate(picked);
    });
    if (datePrev) datePrev.addEventListener('click', function () { navigateToDate(shiftDate(-1)); });
    if (dateNext) dateNext.addEventListener('click', function () { navigateToDate(shiftDate(1)); });
    if (dateToday) {
        dateToday.addEventListener('click', function () { navigateToDate(config.today); });
        dateToday.style.display = selectedDate === config.today ? 'none' : '';
    }

    // ═══════════════════════════════════════
    //  SHIFT FILTER
    // ═══════════════════════════════════════
    var shiftSelect = document.getElementById('secShiftFilter');
    if (shiftSelect) {
        shiftSelect.addEventListener('change', function () {
            activeShiftFilter = this.value;
            renderFilteredSlots();
        });
    }

    function getShiftForTime(etaStr) {
        var h = parseInt(etaStr.split(':')[0], 10);
        if (h >= 7 && h < 15) return '1';
        if (h >= 15 && h < 23) return '2';
        return '3';
    }

    // ═══════════════════════════════════════
    //  STATUS FILTER (clickable stat cards)
    // ═══════════════════════════════════════
    var statsBar = document.getElementById('secStatsBar');
    if (statsBar) {
        statsBar.addEventListener('click', function (e) {
            var card = e.target.closest('.sec-stat');
            if (!card || !card.dataset.filter) return;
            activeStatusFilter = card.dataset.filter;
            updateActiveFilterUI();
            renderFilteredSlots();
        });
    }

    function updateActiveFilterUI() {
        document.querySelectorAll('.sec-stat[data-filter]').forEach(function (c) {
            c.classList.toggle('sec-stat--active-filter', c.dataset.filter === activeStatusFilter);
        });
    }

    // ═══════════════════════════════════════
    //  RENDER FILTERED SCHEDULE
    // ═══════════════════════════════════════
    function renderFilteredSlots() {
        var filtered = allSlots;
        if (activeStatusFilter !== 'all') {
            filtered = filtered.filter(function (s) { return s.status === activeStatusFilter; });
        }
        if (activeShiftFilter !== 'all') {
            filtered = filtered.filter(function (s) { return getShiftForTime(s.eta) === activeShiftFilter; });
        }

        var list = document.getElementById('security-schedule-list');
        if (!list) return;

        if (filtered.length === 0) {
            list.innerHTML = '<div class="sec-schedule__empty"><i class="fas fa-inbox"></i><p>Tidak ada jadwal yang cocok</p></div>';
            return;
        }

        var html = '';
        filtered.forEach(function (s) {
            var sc = { scheduled:'sec-slot--scheduled', waiting:'sec-slot--waiting', in_progress:'sec-slot--active', completed:'sec-slot--done' }[s.status] || '';
            var emoji = { scheduled:'🕐', waiting:'✅', in_progress:'🔄', completed:'✔️' }[s.status] || '•';
            var label = { scheduled:'Dijadwalkan', waiting:'Sudah Tiba', in_progress:'Sedang Proses', completed:'Selesai' }[s.status] || s.status;
            var dir = (s.direction || '').toLowerCase();
            var dirCls = dir === 'inbound' ? 'sec-slot__dir--inbound' : dir === 'outbound' ? 'sec-slot__dir--outbound' : '';
            var detailUrl = config.slotShowUrl.replace('__SLOT_ID__', s.id_slots);

            html += '<a href="' + detailUrl + '" class="sec-slot ' + sc + '" data-slot-id="' + s.id_slots + '">';
            html += '<div class="sec-slot__left"><div class="sec-slot__eta">' + esc(s.eta) + '</div>';
            if (s.arrival_time) html += '<div class="sec-slot__arrived">Tiba ' + esc(s.arrival_time) + '</div>';
            html += '</div><div class="sec-slot__body">';
            html += '<div class="sec-slot__row-top"><span class="sec-slot__ticket">' + esc(s.ticket_number) + '</span>';
            html += '<span class="sec-slot__badge">' + emoji + ' ' + esc(label) + '</span></div>';
            html += '<div class="sec-slot__vendor">' + esc(s.vendor_name) + '</div>';
            html += '<div class="sec-slot__meta">';
            html += '<span><i class="fas fa-file-invoice"></i> ' + esc(!s.po_number || s.po_number === '-' ? 'Tanpa PO' : s.po_number) + '</span>';
            html += '<span><i class="fas fa-door-open"></i> ' + esc(s.gate) + '</span>';
            if (s.vehicle_number && s.vehicle_number !== '-') {
                html += '<span><i class="fas fa-truck"></i> ' + esc(s.vehicle_number) + '</span>';
            }
            html += '<span class="sec-slot__dir ' + dirCls + '">' + esc(s.direction || '') + '</span>';
            html += '</div></div>';

            // Arrival button for scheduled slots
            if (s.status === 'scheduled') {
                html += '<button type="button" class="sec-slot__arrival-btn" data-arrival-id="' + s.id_slots + '" title="Catat Kedatangan" onclick="event.preventDefault();event.stopPropagation();">';
                html += '<i class="fas fa-right-to-bracket"></i><span>Arrival</span></button>';
            }

            html += '</a>';
        });
        list.innerHTML = html;
    }

    // ═══════════════════════════════════════
    //  ARRIVAL BUTTON ON SLOT CARDS
    // ═══════════════════════════════════════
    document.addEventListener('click', function (e) {
        var arrBtn = e.target.closest('.sec-slot__arrival-btn');
        if (!arrBtn) return;
        e.preventDefault();
        e.stopPropagation();
        var slotId = arrBtn.dataset.arrivalId;
        if (!slotId) return;

        openArrivalModal(slotId);
    }, true);

    function openArrivalModal(slotId) {
        if (!arrivalModal) return;

        arrivalSlotId = slotId;
        arrivalExpectedTicket = '';
        arrivalTicketVerified = false;

        // Show modal with loading state
        var loadingEl = document.getElementById('arrivalModalLoading');
        var contentEl = document.getElementById('arrivalModalContent');
        var confirmBtnEl = document.getElementById('arrivalConfirmBtn');
        var headerEl = document.getElementById('arrivalModalHeader');

        if (loadingEl) loadingEl.style.display = 'flex';
        if (contentEl) contentEl.style.display = 'none';
        if (confirmBtnEl) { confirmBtnEl.style.display = 'none'; setConfirmEnabled(false); }

        // Reset header
        if (headerEl) {
            headerEl.style.background = 'linear-gradient(135deg, var(--sec-teal), #00acc1)';
            headerEl.innerHTML = '<i class="fas fa-right-to-bracket"></i> <span>Arrival</span>';
        }

        // Reset ticket input
        var ticketInput = document.getElementById('arrivalTicketInput');
        if (ticketInput) { ticketInput.value = ''; ticketInput.className = 'sec-arrival-ticket__input'; }
        var hintEl = document.getElementById('arrivalTicketHint');
        var errorEl = document.getElementById('arrivalTicketError');
        var successEl = document.getElementById('arrivalTicketSuccess');
        if (hintEl) hintEl.style.display = '';
        if (errorEl) errorEl.style.display = 'none';
        if (successEl) successEl.style.display = 'none';

        arrivalModal.style.display = 'flex';

        // Fetch slot details via AJAX
        var url = config.slotDetailUrl.replace('__SLOT_ID__', slotId);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                if (loadingEl) loadingEl.style.display = 'none';

                if (!data.success) {
                    if (contentEl) {
                        contentEl.innerHTML = '<div class="sec-warning sec-warning--error"><i class="fas fa-exclamation-triangle"></i> ' + esc(data.message) + '</div>';
                        contentEl.style.display = '';
                    }
                    return;
                }

                populateArrivalModal(data);
            })
            .catch(function () {
                if (loadingEl) loadingEl.style.display = 'none';
                if (contentEl) {
                    contentEl.innerHTML = '<div class="sec-warning sec-warning--error"><i class="fas fa-exclamation-triangle"></i> Gagal memuat data. Coba lagi.</div>';
                    contentEl.style.display = '';
                }
            });
    }

    function populateArrivalModal(data) {
        var contentEl = document.getElementById('arrivalModalContent');
        var warningsEl = document.getElementById('arrivalModalWarnings');
        var confirmBtnEl = document.getElementById('arrivalConfirmBtn');
        var headerEl = document.getElementById('arrivalModalHeader');
        var ticketSection = document.getElementById('arrivalTicketSection');
        var slot = data.slot;

        if (!contentEl) return;

        // Store expected ticket for verification
        arrivalExpectedTicket = (slot.ticket_number || '').trim();

        // Update header based on status
        if (headerEl) {
            if (data.is_late) {
                headerEl.style.background = 'linear-gradient(135deg, #fb8c00, #ef6c00)';
                headerEl.innerHTML = '<i class="fas fa-clock"></i> <span>Arrival — Terlambat</span>';
            } else if (!data.can_proceed) {
                headerEl.style.background = 'linear-gradient(135deg, #fb8c00, #ef6c00)';
                headerEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span>Perhatian</span>';
            } else {
                headerEl.style.background = 'linear-gradient(135deg, var(--sec-teal), #00acc1)';
                headerEl.innerHTML = '<i class="fas fa-right-to-bracket"></i> <span>Arrival</span>';
            }
        }

        // Show warnings
        if (warningsEl) {
            warningsEl.innerHTML = '';
            if (data.warnings && data.warnings.length) {
                data.warnings.forEach(function (w) {
                    var cls = 'sec-warning sec-warning--' + w.type;
                    var icon = w.type === 'error' ? 'fas fa-times-circle' : w.type === 'late' ? 'fas fa-clock' : 'fas fa-exclamation-triangle';
                    warningsEl.innerHTML += '<div class="' + cls + '"><i class="' + icon + '"></i> ' + esc(w.message) + '</div>';
                });
            }
        }

        // Populate fields
        setText('arrivalPoNumber', slot.po_number);
        setText('arrivalVendor', slot.vendor_name);
        setText('arrivalVehicle', slot.vehicle_number);
        setText('arrivalDriver', slot.driver_name);
        setText('arrivalDirection', slot.direction);
        setText('arrivalWarehouse', slot.warehouse);
        setText('arrivalGate', slot.gate);
        setText('arrivalEta', slot.eta);

        // Show/hide ticket section and confirm button based on can_proceed
        if (ticketSection) ticketSection.style.display = data.can_proceed ? '' : 'none';
        if (confirmBtnEl) {
            confirmBtnEl.style.display = data.can_proceed ? '' : 'none';
            setConfirmEnabled(false); // Start disabled, enable after ticket verified
        }

        contentEl.style.display = '';

        // Auto-focus ticket input
        if (data.can_proceed) {
            setTimeout(function () {
                var ticketInput = document.getElementById('arrivalTicketInput');
                if (ticketInput) ticketInput.focus();
            }, 150);
        }
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    }

    // ═══════════════════════════════════════
    //  TICKET VERIFICATION IN ARRIVAL MODAL
    // ═══════════════════════════════════════
    var arrivalTicketInputEl = document.getElementById('arrivalTicketInput');
    if (arrivalTicketInputEl) {
        // Verify on Enter key
        arrivalTicketInputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                verifyArrivalTicket();
            }
        });
        // Also verify on blur (when user tabs away)
        arrivalTicketInputEl.addEventListener('blur', function () {
            if (this.value.trim()) verifyArrivalTicket();
        });
    }

    function verifyArrivalTicket() {
        var ticketInput = document.getElementById('arrivalTicketInput');
        var hintEl = document.getElementById('arrivalTicketHint');
        var errorEl = document.getElementById('arrivalTicketError');
        var successEl = document.getElementById('arrivalTicketSuccess');

        if (!ticketInput) return;
        var entered = ticketInput.value.trim();

        // Hide previous states
        if (hintEl) hintEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        if (successEl) successEl.style.display = 'none';

        if (!entered) {
            ticketInput.className = 'sec-arrival-ticket__input';
            if (hintEl) hintEl.style.display = '';
            setConfirmEnabled(false);
            arrivalTicketVerified = false;
            return;
        }

        if (arrivalExpectedTicket && entered !== arrivalExpectedTicket) {
            // Ticket doesn't match
            ticketInput.className = 'sec-arrival-ticket__input sec-arrival-ticket__input--error';
            if (errorEl) {
                errorEl.innerHTML = '<i class="fas fa-times-circle"></i> Nomor tiket tidak cocok dengan booking ini.';
                errorEl.style.display = '';
            }
            setConfirmEnabled(false);
            arrivalTicketVerified = false;
        } else {
            // Ticket matches (or no expected ticket to compare)
            ticketInput.className = 'sec-arrival-ticket__input sec-arrival-ticket__input--success';
            if (successEl) {
                successEl.innerHTML = '<i class="fas fa-check-circle"></i> Tiket terverifikasi — siap konfirmasi.';
                successEl.style.display = '';
            }
            setConfirmEnabled(true);
            arrivalTicketVerified = true;
        }
    }

    function setConfirmEnabled(enabled) {
        var btn = document.getElementById('arrivalConfirmBtn');
        if (!btn) return;
        if (enabled) {
            btn.disabled = false;
            btn.classList.remove('sec-btn--disabled');
        } else {
            btn.disabled = true;
            btn.classList.add('sec-btn--disabled');
        }
    }

    function closeArrivalModal() {
        if (!arrivalModal) return;
        arrivalModal.style.display = 'none';
        arrivalSlotId = null;
        arrivalExpectedTicket = '';
        arrivalTicketVerified = false;
        // Stop inline camera if running
        if (typeof closeArrivalCamera === 'function') closeArrivalCamera();
        refreshSchedule();
        focusScanInput();
    }

    // Arrival confirm button
    if (arrivalConfirmBtn) {
        arrivalConfirmBtn.addEventListener('click', async function () {
            if (!arrivalSlotId || !arrivalTicketVerified) return;
            arrivalConfirmBtn.disabled = true;
            arrivalConfirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            try {
                var resp = await fetch(config.confirmUrl.replace('__SLOT_ID__', arrivalSlotId), {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':config.csrfToken, 'Accept':'application/json' },
                });
                var data = await resp.json();
                closeArrivalModal();
                if (data.success) { showToast('success', data.message); refreshSchedule(); }
                else { showToast('error', data.message); }
            } catch (err) { closeArrivalModal(); showToast('error', 'Gagal menghubungi server.'); }
            finally { arrivalConfirmBtn.disabled = false; arrivalConfirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> KONFIRMASI KEDATANGAN'; }
        });
    }

    // ═══════════════════════════════════════
    //  SCAN (text input + barcode scanner)
    // ═══════════════════════════════════════
    function focusScanInput() {
        if (scanInput) { scanInput.value = ''; scanInput.focus(); }
    }

    if (scanForm) {
        scanForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var ticket = (scanInput.value || '').trim();
            if (!ticket) { focusScanInput(); return; }
            doScan(ticket);
        });
    }

    async function doScan(ticketNumber) {
        if (document.activeElement && document.activeElement.blur) {
            document.activeElement.blur();
        }
        scanBtn.disabled = true;
        scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            var resp = await fetch(config.scanUrl, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':config.csrfToken, 'Accept':'application/json' },
                body: JSON.stringify({ ticket_number: ticketNumber }),
            });
            var data = await resp.json();
            showScanResult(data);
        } catch (err) {
            showScanResult({ success: false, message: 'Gagal menghubungi server. Coba lagi.' });
        } finally {
            scanBtn.disabled = false;
            scanBtn.innerHTML = '<i class="fas fa-search"></i>';
        }
    }

    // ═══════════════════════════════════════
    //  CAMERA SCAN (html5-qrcode)
    // ═══════════════════════════════════════
    var camBtn = document.getElementById('secCameraBtn');
    var camOverlay = document.getElementById('secCameraOverlay');
    var camClose = document.getElementById('secCameraClose');
    var camPreview = document.getElementById('secCameraPreview');
    var arrivalCameraBtn = document.getElementById('arrivalCameraBtn');
    var arrivalCameraWrap = document.getElementById('arrivalCameraWrap');
    var arrivalCameraPreview = document.getElementById('arrivalCameraPreview');
    var arrivalCameraStop = document.getElementById('arrivalCameraStop');
    var arrivalHtml5QrCode = null;

    if (camBtn) camBtn.addEventListener('click', function () { openMainCamera(); });
    if (camClose) camClose.addEventListener('click', function () { closeMainCamera(); });
    if (camOverlay) camOverlay.addEventListener('click', function (e) { if (e.target === camOverlay) closeMainCamera(); });

    // Main camera (full overlay for top scan bar)
    function openMainCamera() {
        camOverlay.style.display = 'flex';
        if (typeof Html5Qrcode === 'undefined') {
            camPreview.innerHTML = '<div style="color:#fff;padding:40px;text-align:center;">Memuat kamera...</div>';
            setTimeout(openMainCamera, 500);
            return;
        }
        if (html5QrCode) { try { html5QrCode.stop(); } catch(e){} }
        html5QrCode = new Html5Qrcode('secCameraPreview');
        html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 260, height: 120 }, aspectRatio: 1.33 },
            function onSuccess(decodedText) {
                closeMainCamera();
                if (scanInput) scanInput.value = decodedText;
                doScan(decodedText.trim());
            },
            function onFail() {}
        ).catch(function () {
            camPreview.innerHTML = '<div style="color:#ef5350;padding:30px;text-align:center;font-size:13px;">' +
                '<i class="fas fa-exclamation-triangle" style="font-size:28px;margin-bottom:10px;display:block;"></i>' +
                'Kamera tidak tersedia.<br>Pastikan izin kamera diaktifkan.</div>';
        });
    }

    function closeMainCamera() {
        if (html5QrCode) { try { html5QrCode.stop(); } catch(e){} html5QrCode = null; }
        if (camPreview) camPreview.innerHTML = '';
        camOverlay.style.display = 'none';
        focusScanInput();
    }

    // Arrival inline camera (inside the modal)
    if (arrivalCameraBtn) arrivalCameraBtn.addEventListener('click', function () { openArrivalCamera(); });
    if (arrivalCameraStop) arrivalCameraStop.addEventListener('click', function () { closeArrivalCamera(); });

    function openArrivalCamera() {
        if (!arrivalCameraWrap || !arrivalCameraPreview) return;
        arrivalCameraWrap.style.display = '';
        if (typeof Html5Qrcode === 'undefined') {
            arrivalCameraPreview.innerHTML = '<div style="color:#b2ebf2;padding:20px;text-align:center;font-size:12px;">Memuat kamera...</div>';
            setTimeout(openArrivalCamera, 500);
            return;
        }
        if (arrivalHtml5QrCode) { try { arrivalHtml5QrCode.stop(); } catch(e){} }
        arrivalHtml5QrCode = new Html5Qrcode('arrivalCameraPreview');
        arrivalHtml5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 280, height: 80 }, aspectRatio: 3.0 },
            function onSuccess(decodedText) {
                closeArrivalCamera();
                var text = decodedText.trim();
                var arrTicketInput = document.getElementById('arrivalTicketInput');
                if (arrTicketInput) {
                    arrTicketInput.value = text;
                    verifyArrivalTicket();
                }
            },
            function onFail() {}
        ).catch(function () {
            arrivalCameraPreview.innerHTML = '<div style="color:#ef5350;padding:16px;text-align:center;font-size:12px;">' +
                '<i class="fas fa-exclamation-triangle" style="font-size:20px;margin-bottom:6px;display:block;"></i>' +
                'Kamera tidak tersedia.<br>Pastikan izin kamera diaktifkan.</div>';
        });
    }

    function closeArrivalCamera() {
        if (arrivalHtml5QrCode) { try { arrivalHtml5QrCode.stop(); } catch(e){} arrivalHtml5QrCode = null; }
        if (arrivalCameraPreview) arrivalCameraPreview.innerHTML = '';
        if (arrivalCameraWrap) arrivalCameraWrap.style.display = 'none';
    }

    // ═══════════════════════════════════════
    //  RESULT MODAL (scan result)
    // ═══════════════════════════════════════
    function showScanResult(data) {
        var header = document.getElementById('scanModalHeader');
        var warningsEl = document.getElementById('scanModalWarnings');
        warningsEl.innerHTML = '';

        if (!data.success) {
            header.className = 'sec-result-card__header sec-result-card__header--error';
            header.innerHTML = '<i class="fas fa-times-circle"></i> <span>Tiket Tidak Ditemukan</span>';
            warningsEl.innerHTML = '<div class="sec-warning sec-warning--error"><i class="fas fa-exclamation-triangle"></i> ' + esc(data.message) + '</div>';
            confirmBtn.style.display = 'none';
            currentSlotId = null;
            ['scanTicketNumber','scanPoNumber','scanVendor','scanVehicle','scanDriver','scanDirection','scanGate','scanEta']
                .forEach(function (id) { var el = document.getElementById(id); if (el) el.textContent = '-'; });
        } else {
            var slot = data.slot;
            currentSlotId = slot.id_slots;
            if (!data.can_proceed) {
                header.className = 'sec-result-card__header sec-result-card__header--warning';
                header.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span>Perhatian</span>';
            } else if (data.is_late) {
                header.className = 'sec-result-card__header sec-result-card__header--warning';
                header.innerHTML = '<i class="fas fa-clock"></i> <span>Tiket Ditemukan — Terlambat</span>';
            } else {
                header.className = 'sec-result-card__header';
                header.innerHTML = '<i class="fas fa-check-circle"></i> <span>Tiket Ditemukan</span>';
            }
            if (data.warnings && data.warnings.length) {
                data.warnings.forEach(function (w) {
                    var cls = 'sec-warning sec-warning--' + w.type;
                    var icon = w.type === 'error' ? 'fas fa-times-circle' : w.type === 'late' ? 'fas fa-clock' : 'fas fa-exclamation-triangle';
                    warningsEl.innerHTML += '<div class="' + cls + '"><i class="' + icon + '"></i> ' + esc(w.message) + '</div>';
                });
            }
            document.getElementById('scanTicketNumber').textContent = slot.ticket_number || '-';
            document.getElementById('scanPoNumber').textContent = slot.po_number || '-';
            document.getElementById('scanVendor').textContent = slot.vendor_name || '-';
            document.getElementById('scanVehicle').textContent = slot.vehicle_number || '-';
            document.getElementById('scanDriver').textContent = slot.driver_name || '-';
            document.getElementById('scanDirection').textContent = slot.direction || '-';
            document.getElementById('scanGate').textContent = slot.gate || '-';
            document.getElementById('scanEta').textContent = slot.eta || '-';
            confirmBtn.style.display = data.can_proceed ? '' : 'none';
        }
        modal.style.display = 'flex';
    }

    // ═══════════════════════════════════════
    //  CONFIRM ARRIVAL (from scan modal)
    // ═══════════════════════════════════════
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function () {
            if (!currentSlotId) return;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            try {
                var resp = await fetch(config.confirmUrl.replace('__SLOT_ID__', currentSlotId), {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':config.csrfToken, 'Accept':'application/json' },
                });
                var data = await resp.json();
                closeModal();
                if (data.success) { showToast('success', data.message); refreshSchedule(); }
                else { showToast('error', data.message); }
            } catch (err) { closeModal(); showToast('error', 'Gagal menghubungi server.'); }
            finally { confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> KONFIRMASI KEDATANGAN'; }
        });
    }

    // ═══════════════════════════════════════
    //  MODAL CONTROLS
    // ═══════════════════════════════════════
    function closeModal() { modal.style.display = 'none'; currentSlotId = null; focusScanInput(); }
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    if (arrivalCloseBtn) arrivalCloseBtn.addEventListener('click', closeArrivalModal);
    if (arrivalModal) arrivalModal.addEventListener('click', function (e) { if (e.target === arrivalModal) closeArrivalModal(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (modal.style.display !== 'none') closeModal();
            if (camOverlay && camOverlay.style.display !== 'none') closeCamera();
            if (arrivalModal && arrivalModal.style.display !== 'none') closeArrivalModal();
        }
    });

    // ═══════════════════════════════════════
    //  TOAST
    // ═══════════════════════════════════════
    function showToast(type, msg) {
        document.querySelectorAll('.sec-toast').forEach(function (el) { el.remove(); });
        var toast = document.createElement('div');
        toast.className = 'st-alert st-alert--' + (type === 'success' ? 'success' : 'error') + ' st-alert--autodismiss st-mb-12';
        var icon = type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation';
        toast.innerHTML = '<span class="st-alert__icon"><i class="fa-solid ' + icon + '"></i></span><span class="st-alert__text">' + esc(msg) + '</span><button type="button" class="st-alert__close" onclick="this.parentElement.remove()">&times;</button>';
        var main = document.querySelector('.st-content--layout');
        if (main) main.insertBefore(toast, main.firstChild);
        setTimeout(function () { toast.remove(); }, 5000);
    }

    // ═══════════════════════════════════════
    //  AUTO-REFRESH + INITIAL LOAD
    // ═══════════════════════════════════════
    async function refreshSchedule() {
        try {
            var url = config.refreshUrl + '?date=' + encodeURIComponent(selectedDate);
            var resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            var data = await resp.json();
            if (!data.success) return;

            var s = data.summary;
            var ids = { 'summary-total': s.total, 'summary-scheduled': s.scheduled, 'summary-waiting': s.waiting, 'summary-active': s.in_progress, 'summary-completed': s.completed };
            for (var id in ids) { var el = document.getElementById(id); if (el) el.textContent = ids[id]; }

            allSlots = data.slots || [];
            renderFilteredSlots();
        } catch (e) { /* silent */ }
    }

    setInterval(refreshSchedule, 60000);
    refreshSchedule();

    function esc(t) { if (!t) return ''; var el = document.createElement('span'); el.textContent = t; return el.innerHTML; }

    focusScanInput();
})();

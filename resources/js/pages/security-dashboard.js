/**
 * Security Dashboard v4 — shift/status filter, arrival buttons, slot links, camera
 */
(function () {
    'use strict';

    const configEl = document.getElementById('security_dashboard_config');
    if (!configEl) return;

    const config = JSON.parse(configEl.textContent);
    const scanInput = document.getElementById('security-scan-input');
    const scanForm = document.getElementById('security-scan-form');
    const scanBtn = document.getElementById('security-scan-btn');
    const modal = document.getElementById('securityScanModal');
    const confirmBtn = document.getElementById('scanConfirmBtn');
    const closeBtn = document.getElementById('scanCloseBtn');

    let currentSlotId = null;
    let selectedDate = config.selectedDate || config.today;
    let html5QrCode = null;
    let activeStatusFilter = 'all';
    let activeShiftFilter = 'all';
    let allSlots = [];

    // ═══════════════════════════════════════
    //  DATE NAVIGATION
    // ═══════════════════════════════════════
    const datePicker = document.getElementById('secDatePicker');
    const dateLabel = document.getElementById('secDateLabel');
    const datePrev = document.getElementById('datePrev');
    const dateNext = document.getElementById('dateNext');
    const dateToday = document.getElementById('dateToday');

    function formatDateLabel(dateStr) {
        var d = new Date(dateStr + 'T00:00:00');
        var today = new Date(config.today + 'T00:00:00');
        var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        var base = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        if (d.getTime() === today.getTime()) return 'Hari Ini, ' + base;
        return base;
    }

    function shiftDate(days) {
        var d = new Date(selectedDate + 'T00:00:00');
        d.setDate(d.getDate() + days);
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }

    function navigateToDate(dateStr) {
        selectedDate = dateStr;
        if (datePicker) datePicker.value = dateStr;
        if (dateLabel) dateLabel.textContent = formatDateLabel(dateStr);
        if (dateToday) dateToday.style.display = dateStr === config.today ? 'none' : '';
        refreshSchedule();
    }

    if (datePicker) datePicker.addEventListener('change', function () { navigateToDate(this.value); });
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
            var detailUrl = config.slotShowUrl.replace('__SLOT_ID__', s.id);

            html += '<a href="' + detailUrl + '" class="sec-slot ' + sc + '" data-slot-id="' + s.id + '">';
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
                html += '<button type="button" class="sec-slot__arrival-btn" data-arrival-id="' + s.id + '" title="Catat Kedatangan" onclick="event.preventDefault();event.stopPropagation();">';
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

        // Quick confirm arrival via the same endpoint
        arrBtn.disabled = true;
        arrBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(config.confirmUrl.replace('__SLOT_ID__', slotId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                showToast('success', data.message);
                refreshSchedule();
            } else {
                showToast('error', data.message);
                arrBtn.disabled = false;
                arrBtn.innerHTML = '<i class="fas fa-right-to-bracket"></i><span>Arrival</span>';
            }
        })
        .catch(function () {
            showToast('error', 'Gagal menghubungi server.');
            arrBtn.disabled = false;
            arrBtn.innerHTML = '<i class="fas fa-right-to-bracket"></i><span>Arrival</span>';
        });
    });

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

    if (camBtn) camBtn.addEventListener('click', function () { openCamera(); });
    if (camClose) camClose.addEventListener('click', function () { closeCamera(); });
    if (camOverlay) camOverlay.addEventListener('click', function (e) { if (e.target === camOverlay) closeCamera(); });

    function openCamera() {
        camOverlay.style.display = 'flex';
        if (typeof Html5Qrcode === 'undefined') {
            camPreview.innerHTML = '<div style="color:#fff;padding:40px;text-align:center;">Memuat kamera...</div>';
            setTimeout(openCamera, 500);
            return;
        }
        if (html5QrCode) { try { html5QrCode.stop(); } catch(e){} }
        html5QrCode = new Html5Qrcode('secCameraPreview');
        html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 260, height: 120 }, aspectRatio: 1.33 },
            function onSuccess(decodedText) {
                closeCamera();
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

    function closeCamera() {
        if (html5QrCode) { try { html5QrCode.stop(); } catch(e){} html5QrCode = null; }
        if (camPreview) camPreview.innerHTML = '';
        camOverlay.style.display = 'none';
        focusScanInput();
    }

    // ═══════════════════════════════════════
    //  RESULT MODAL
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
            currentSlotId = slot.id;
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
    //  CONFIRM ARRIVAL (from modal)
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
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (modal.style.display !== 'none') closeModal();
            if (camOverlay && camOverlay.style.display !== 'none') closeCamera();
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
    refreshSchedule(); // initial AJAX to populate allSlots for filtering

    function esc(t) { if (!t) return ''; var el = document.createElement('span'); el.textContent = t; return el.innerHTML; }

    focusScanInput();
})();

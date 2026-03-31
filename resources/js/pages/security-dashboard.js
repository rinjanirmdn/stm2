/**
 * Security Dashboard — Scan-to-Arrival
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

    // ── Auto-focus scan input ──
    function focusScanInput() {
        if (scanInput) {
            scanInput.value = '';
            scanInput.focus();
        }
    }

    // ── Scan form submit ──
    if (scanForm) {
        scanForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const ticket = (scanInput.value || '').trim();
            if (!ticket) {
                focusScanInput();
                return;
            }
            doScan(ticket);
        });
    }

    async function doScan(ticketNumber) {
        scanBtn.disabled = true;
        scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mencari...</span>';

        try {
            const resp = await fetch(config.scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ticket_number: ticketNumber }),
            });

            const data = await resp.json();
            showScanResult(data);
        } catch (err) {
            showScanResult({
                success: false,
                message: 'Gagal menghubungi server. Coba lagi.',
            });
        } finally {
            scanBtn.disabled = false;
            scanBtn.innerHTML = '<i class="fas fa-search"></i> <span>PROSES</span>';
        }
    }

    function showScanResult(data) {
        const header = document.getElementById('scanModalHeader');
        const warningsEl = document.getElementById('scanModalWarnings');
        const confirmBtnEl = document.getElementById('scanConfirmBtn');

        // Clear warnings
        warningsEl.innerHTML = '';

        if (!data.success) {
            // Error — ticket not found
            header.className = 'st-security-modal__header st-security-modal__header--error';
            header.innerHTML = '<i class="fas fa-times-circle"></i> <span>Tiket Tidak Ditemukan</span>';
            warningsEl.innerHTML =
                '<div class="st-security-modal__warning st-security-modal__warning--error">' +
                '<i class="fas fa-exclamation-triangle"></i> ' + escapeHtml(data.message) +
                '</div>';
            confirmBtnEl.style.display = 'none';
            currentSlotId = null;

            // Clear detail values
            ['scanTicketNumber','scanPoNumber','scanVendor','scanVehicle','scanDriver','scanDirection','scanGate','scanEta']
                .forEach(id => { const el = document.getElementById(id); if (el) el.textContent = '-'; });
        } else {
            const slot = data.slot;
            currentSlotId = slot.id;

            // Header color based on state
            if (!data.can_proceed) {
                header.className = 'st-security-modal__header st-security-modal__header--warning';
                header.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span>Perhatian</span>';
            } else if (data.is_late) {
                header.className = 'st-security-modal__header st-security-modal__header--warning';
                header.innerHTML = '<i class="fas fa-clock"></i> <span>Tiket Ditemukan — Terlambat</span>';
            } else {
                header.className = 'st-security-modal__header';
                header.innerHTML = '<i class="fas fa-check-circle"></i> <span>Tiket Ditemukan</span>';
            }

            // Show warnings
            if (data.warnings && data.warnings.length > 0) {
                data.warnings.forEach(function (w) {
                    const cls = 'st-security-modal__warning st-security-modal__warning--' + w.type;
                    const icon = w.type === 'error' ? 'fas fa-times-circle'
                        : w.type === 'late' ? 'fas fa-clock'
                        : 'fas fa-exclamation-triangle';
                    warningsEl.innerHTML +=
                        '<div class="' + cls + '">' +
                        '<i class="' + icon + '"></i> ' + escapeHtml(w.message) +
                        '</div>';
                });
            }

            // Fill details
            document.getElementById('scanTicketNumber').textContent = slot.ticket_number || '-';
            document.getElementById('scanPoNumber').textContent = slot.po_number || '-';
            document.getElementById('scanVendor').textContent = slot.vendor_name || '-';
            document.getElementById('scanVehicle').textContent = slot.vehicle_number || '-';
            document.getElementById('scanDriver').textContent = slot.driver_name || '-';
            document.getElementById('scanDirection').textContent = slot.direction || '-';
            document.getElementById('scanGate').textContent = slot.gate || '-';
            document.getElementById('scanEta').textContent = slot.eta || '-';

            // Show/hide confirm button
            confirmBtnEl.style.display = data.can_proceed ? '' : 'none';
        }

        // Show modal
        modal.style.display = 'flex';
    }

    // ── Confirm Arrival ──
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function () {
            if (!currentSlotId) return;

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            try {
                const resp = await fetch(config.confirmUrl.replace('__SLOT_ID__', currentSlotId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await resp.json();
                closeModal();

                if (data.success) {
                    showToast('success', data.message || 'Kedatangan berhasil dicatat.');
                    refreshSchedule();
                } else {
                    showToast('error', data.message || 'Gagal mencatat kedatangan.');
                }
            } catch (err) {
                closeModal();
                showToast('error', 'Gagal menghubungi server.');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> KONFIRMASI KEDATANGAN';
            }
        });
    }

    // ── Close Modal ──
    function closeModal() {
        modal.style.display = 'none';
        currentSlotId = null;
        focusScanInput();
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    // Close on overlay click
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            closeModal();
        }
    });

    // ── Toast Notification ──
    function showToast(type, message) {
        const existing = document.querySelectorAll('.st-security-toast');
        existing.forEach(el => el.remove());

        const toast = document.createElement('div');
        toast.className = 'st-alert st-alert--' + (type === 'success' ? 'success' : 'error') + ' st-alert--autodismiss st-mb-12';
        const icon = type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation';
        toast.innerHTML =
            '<span class="st-alert__icon"><i class="fa-solid ' + icon + '"></i></span>' +
            '<span class="st-alert__text">' + escapeHtml(message) + '</span>' +
            '<button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>';

        const main = document.querySelector('.st-content--layout');
        if (main) main.insertBefore(toast, main.firstChild);

        setTimeout(() => toast.remove(), 5000);
    }

    // ── Auto-refresh schedule every 60s ──
    async function refreshSchedule() {
        try {
            const resp = await fetch(config.refreshUrl, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await resp.json();
            if (!data.success) return;

            // Update summary numbers
            const s = data.summary;
            const ids = { 'summary-total': s.total, 'summary-scheduled': s.scheduled, 'summary-waiting': s.waiting, 'summary-active': s.in_progress, 'summary-completed': s.completed };
            for (const [id, val] of Object.entries(ids)) {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            }

            // Rebuild schedule list
            const list = document.getElementById('security-schedule-list');
            if (!list || !data.slots) return;

            if (data.slots.length === 0) {
                list.innerHTML = '<div class="st-security-schedule__empty"><i class="fas fa-calendar-xmark"></i><p>Tidak ada jadwal kedatangan hari ini</p></div>';
                return;
            }

            let html = '';
            data.slots.forEach(function (s) {
                const statusClass = {
                    scheduled: 'st-security-slot--scheduled',
                    waiting: 'st-security-slot--waiting',
                    in_progress: 'st-security-slot--active',
                    completed: 'st-security-slot--completed',
                }[s.status] || '';
                const statusLabel = {
                    scheduled: 'Menunggu Datang',
                    waiting: 'Sudah Tiba',
                    in_progress: 'Sedang Proses',
                    completed: 'Selesai',
                }[s.status] || s.status;

                const dir = (s.direction || '').toLowerCase();
                const dirClass = dir === 'inbound' ? 'st-security-slot__direction--inbound' : dir === 'outbound' ? 'st-security-slot__direction--outbound' : '';

                html += '<div class="st-security-slot ' + statusClass + '">';
                html += '<div class="st-security-slot__time"><span class="st-security-slot__eta">' + escapeHtml(s.eta) + '</span>';
                if (s.arrival_time) html += '<span class="st-security-slot__arrival">Tiba ' + escapeHtml(s.arrival_time) + '</span>';
                html += '</div>';
                html += '<div class="st-security-slot__info">';
                html += '<div class="st-security-slot__ticket">' + escapeHtml(s.ticket_number) + '</div>';
                html += '<div class="st-security-slot__detail"><span><i class="fas fa-file-invoice"></i> ' + escapeHtml(s.po_number) + '</span>';
                html += '<span><i class="fas fa-building"></i> ' + escapeHtml(s.vendor_name) + '</span></div>';
                html += '<div class="st-security-slot__detail"><span><i class="fas fa-door-open"></i> ' + escapeHtml(s.gate) + '</span>';
                html += '<span><i class="fas fa-truck"></i> ' + escapeHtml(s.vehicle_number) + '</span>';
                html += '<span class="st-security-slot__direction ' + dirClass + '">' + escapeHtml(s.direction || '') + '</span>';
                html += '</div></div>';
                html += '<div class="st-security-slot__status"><span class="st-security-slot__badge">' + escapeHtml(statusLabel) + '</span></div>';
                html += '</div>';
            });
            list.innerHTML = html;
        } catch (e) {
            // silently fail
        }
    }

    setInterval(refreshSchedule, 60000);

    function escapeHtml(text) {
        if (!text) return '';
        const el = document.createElement('span');
        el.textContent = text;
        return el.innerHTML;
    }

    // Initial focus
    focusScanInput();
})();

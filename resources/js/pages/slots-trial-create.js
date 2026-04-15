/**
 * slots-trial-create.js
 *
 * JavaScript for "Create Planned Uji Coba" form.
 * - Does NOT call SAP PO search / vendor search APIs.
 * - Vendor list is loaded from the embedded JSON (md_bp master data).
 * - Reuses the same gate/risk/time-overlap AJAX helpers as slots-create.js.
 */
document.addEventListener('DOMContentLoaded', function () {
    // ─── Element refs ──────────────────────────────────────────────────────────
    var truckTypeSelect      = document.getElementById('trial_truck_type');
    var bpSelect             = document.getElementById('trial_bp_id');
    var directionSelect      = document.getElementById('trial_direction');
    var warehouseSelect      = document.getElementById('trial_warehouse_id');
    var gateSelect           = document.getElementById('trial_planned_gate_id');
    var plannedStartInput    = document.getElementById('trial_planned_start_input');
    var plannedStartDateInput= document.getElementById('trial_planned_start_date_input');
    var plannedStartTimeInput= document.getElementById('trial_planned_start_time_input');
    var plannedDurationInput = document.getElementById('trial_planned_duration_input');
    var riskPreview          = document.getElementById('trial_risk_preview');
    var timeWarning          = document.getElementById('trial_time_warning');
    var saveButton           = document.getElementById('trial_save_button');
    var scheduleModal        = document.getElementById('trial_schedule_modal');
    var scheduleModalBody    = document.getElementById('trial_schedule_modal_body');
    var scheduleModalInfo    = document.getElementById('trial_schedule_modal_info');
    var scheduleModalClose   = document.getElementById('trial_schedule_modal_close');
    var schedulePreviewBtn   = document.getElementById('trial_btn_schedule_preview');

    // ─── Load truck type durations ─────────────────────────────────────────────
    var truckTypeDurations = {};
    try {
        var ttEl = document.getElementById('truck_type_durations_json');
        truckTypeDurations = ttEl ? JSON.parse(ttEl.textContent || '{}') : {};
    } catch (e) { truckTypeDurations = {}; }

    // ─── AJAX routes ───────────────────────────────────────────────────────────
    var slotRoutes = {};
    try {
        var routesEl = document.getElementById('slot_routes_json');
        slotRoutes = routesEl ? JSON.parse(routesEl.textContent || '{}') : {};
    } catch (e) { slotRoutes = {}; }

    var urlCheckRisk      = slotRoutes.check_risk      || '';
    var urlCheckSlotTime  = slotRoutes.check_slot_time || '';
    var urlSchedulePreview= slotRoutes.schedule_preview|| '';

    // ─── UI state ──────────────────────────────────────────────────────────────
    var uiHasOverlap    = false;
    var uiOverlapPending= false;
    var uiRiskHigh      = false;
    var uiRiskPending   = false;

    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function postJson(url, fd) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: fd
        }).then(function (r) { return r.json(); });
    }

    // ─── Save button state ─────────────────────────────────────────────────────
    function applySaveState() {
        if (!saveButton) return;
        saveButton.disabled = !!(uiOverlapPending || uiHasOverlap || uiRiskPending || uiRiskHigh);
    }

    // ─── Truck type → duration auto-fill ──────────────────────────────────────
    function updateDurationFromTruckType() {
        if (!truckTypeSelect || !plannedDurationInput) return;
        var tt = (truckTypeSelect.value || '').trim();
        var minutes = tt && truckTypeDurations[tt] ? parseInt(truckTypeDurations[tt], 10) : NaN;
        if (!tt || !isFinite(minutes) || minutes <= 0) {
            plannedDurationInput.removeAttribute('readonly');
        } else {
            plannedDurationInput.setAttribute('readonly', 'readonly');
            plannedDurationInput.value = String(minutes);
        }
        updateRiskPreview();
        checkTimeOverlap();
        applySaveState();
    }

    // ─── Vendor/BP change listener (Autofill Direction) ───────────────────────
    function updateDirectionFromBp() {
        if (!bpSelect || !directionSelect) return;
        var selected = bpSelect.options[bpSelect.selectedIndex];
        if (!selected || !selected.value) return;

        var type = selected.getAttribute('data-type'); // vendor or customer
        if (type === 'vendor') {
            directionSelect.value = 'inbound';
        } else if (type === 'customer') {
            directionSelect.value = 'outbound';
        }
    }

    if (bpSelect) {
        bpSelect.addEventListener('change', function () {
            updateDirectionFromBp();
            updateRiskPreview();
            checkTimeOverlap();
        });
    }

    // ─── Gate → warehouse sync ─────────────────────────────────────────────────
    function syncWarehouseFromGate() {
        if (!warehouseSelect || !gateSelect) return;
        var sel = gateSelect.options[gateSelect.selectedIndex];
        if (!sel) return;
        warehouseSelect.value = sel.getAttribute('data-warehouse-id') || '';
    }

    // ─── ETA date input ────────────────────────────────────────────────────────
    function syncPlannedStart() {
        if (!plannedStartInput || !plannedStartDateInput || !plannedStartTimeInput) return;
        var d = (plannedStartDateInput.value || '').trim();
        var t = (plannedStartTimeInput.value || '').trim();
        plannedStartInput.value = (d && t) ? d + ' ' + t : d || '';
    }

    function applyWarehouseLockState() {
        var hasGate = !!(gateSelect && gateSelect.value);

        if (plannedStartInput) plannedStartInput.disabled = !hasGate;
        if (plannedStartDateInput) plannedStartDateInput.disabled = !hasGate;
        if (plannedStartTimeInput) plannedStartTimeInput.disabled = !hasGate;

        if (!hasGate) {
            if (plannedStartInput) plannedStartInput.value = '';
            if (plannedStartDateInput) plannedStartDateInput.value = '';
            if (plannedStartTimeInput) plannedStartTimeInput.value = '';
        } else {
            // Init timepicker when gate is selected
            initEtaTimepicker();
        }

        if (schedulePreviewBtn) schedulePreviewBtn.disabled = !hasGate;
    }

    function initEtaTimepicker() {
        if (!plannedStartTimeInput) return;
        if (plannedStartTimeInput.getAttribute('data-st-timepicker') === '1') return;
        if (typeof window.mdtimepicker !== 'function') return;
        plannedStartTimeInput.setAttribute('data-st-timepicker', '1');

        plannedStartTimeInput.addEventListener('keydown', function (e) { e.preventDefault(); });
        plannedStartTimeInput.addEventListener('paste',   function (e) { e.preventDefault(); });

        window.mdtimepicker('#trial_planned_start_time_input', {
            format: 'hh:mm', is24hour: true, theme: 'cyan', hourPadding: true
        });

        plannedStartTimeInput.addEventListener('change', function () {
            syncPlannedStart();
            updateRiskPreview();
            checkTimeOverlap();
        });
    }

    if (plannedStartDateInput) {
        plannedStartDateInput.addEventListener('change', function () {
            syncPlannedStart();
            updateRiskPreview();
            checkTimeOverlap();
        });
    }

    // ─── Risk preview ──────────────────────────────────────────────────────────
    function updateRiskPreview() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !riskPreview) return;

        var whId     = warehouseSelect.value;
        var gateId   = gateSelect ? gateSelect.value : '';
        var start    = plannedStartInput.value;
        var duration = plannedDurationInput.value;

        if (!whId || !start || !duration) {
            riskPreview.textContent = 'Risk Not Calculated.';
            uiRiskHigh = false;
            uiRiskPending = false;
            applySaveState();
            return;
        }

        uiRiskPending = true;
        applySaveState();

        var fd = new FormData();
        fd.append('warehouse_id',    whId);
        fd.append('planned_gate_id', gateId);
        fd.append('planned_start',   start.replace('T', ' ') + ':00');
        fd.append('planned_duration',duration);
        fd.append('duration_unit',   'minutes');

        postJson(urlCheckRisk, fd)
            .then(function (data) {
                if (!data || !data.success) {
                    riskPreview.textContent = 'Risk Cannot Be Calculated.';
                    uiRiskHigh = false;
                } else {
                    var cls = 'small';
                    if (data.badge === 'success') cls += ' text-success';
                    if (data.badge === 'warning') cls += ' text-warning';
                    if (data.badge === 'danger')  cls += ' text-danger';
                    riskPreview.className   = cls;
                    riskPreview.textContent = data.label + ' — ' + data.message;
                    uiRiskHigh = !!(data.risk_level >= 2);
                }
                uiRiskPending = false;
                applySaveState();
            })
            .catch(function () {
                riskPreview.textContent = 'Risk could not be calculated.';
                uiRiskHigh = false;
                uiRiskPending = false;
                applySaveState();
            });
    }

    // ─── Time-overlap check ────────────────────────────────────────────────────
    function checkTimeOverlap() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput) return;

        var whId     = warehouseSelect.value;
        var gateId   = gateSelect ? gateSelect.value : '';
        var start    = plannedStartInput.value;
        var duration = plannedDurationInput.value;

        if (!whId || !start || !duration) {
            if (timeWarning) timeWarning.textContent = '';
            uiHasOverlap = false;
            uiOverlapPending = false;
            applySaveState();
            return;
        }

        uiOverlapPending = true;
        applySaveState();

        var fd = new FormData();
        fd.append('warehouse_id',    whId);
        fd.append('planned_gate_id', gateId);
        fd.append('planned_start',   start.replace('T', ' ') + ':00');
        fd.append('planned_duration',duration);
        fd.append('duration_unit',   'minutes');

        postJson(urlCheckSlotTime, fd)
            .then(function (data) {
                if (!data || !data.success) {
                    if (timeWarning) timeWarning.textContent = '';
                    uiHasOverlap = false;
                } else if (data.overlap) {
                    var msg = data.message || 'Waktu bentrok dengan booking lain di gate ini.';
                    if (timeWarning) timeWarning.textContent = msg;
                    uiHasOverlap = true;
                } else {
                    if (timeWarning) timeWarning.textContent = '';
                    uiHasOverlap = false;
                }
                uiOverlapPending = false;
                applySaveState();
            })
            .catch(function () {
                if (timeWarning) timeWarning.textContent = '';
                uiHasOverlap = false;
                uiOverlapPending = false;
                applySaveState();
            });
    }

    // ─── Schedule modal ────────────────────────────────────────────────────────
    function openScheduleModal() {
        if (!scheduleModal || !scheduleModalBody || !warehouseSelect) return;

        var whId   = warehouseSelect.value;
        var gateId = gateSelect ? gateSelect.value : '';
        var dateStr = '';
        if (plannedStartInput && plannedStartInput.value) {
            dateStr = plannedStartInput.value.split(/\s|T/)[0];
        } else {
            dateStr = new Date().toISOString().slice(0, 10);
        }

        scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--muted st-modal__message">Loading...</td></tr>';
        if (scheduleModalInfo) {
            scheduleModalInfo.textContent = 'WH: ' + whId + (gateId ? ' | Gate: ' + gateId : '') + ' | Date: ' + dateStr;
        }

        var fd = new FormData();
        fd.append('warehouse_id',    whId);
        fd.append('planned_gate_id', gateId);
        fd.append('date',            dateStr);

        postJson(urlSchedulePreview, fd)
            .then(function (data) {
                if (!data || !data.success || !data.items || data.items.length === 0) {
                    scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--muted st-modal__message">Tidak ada jadwal pada tanggal ini.</td></tr>';
                    return;
                }
                var html = '';
                data.items.forEach(function (it, idx) {
                    var startObj = new Date(it.planned_start.replace(/-/g, '/'));
                    var finishObj = it.planned_finish ? new Date(it.planned_finish.replace(/-/g, '/')) : null;
                    
                    var startTime = !isNaN(startObj) ? ('0' + startObj.getHours()).slice(-2) + ':' + ('0' + startObj.getMinutes()).slice(-2) : '-';
                    var finishTime = finishObj && !isNaN(finishObj) ? ('0' + finishObj.getHours()).slice(-2) + ':' + ('0' + finishObj.getMinutes()).slice(-2) : '-';
                    var timeStr = startTime + ' - ' + finishTime;
                    
                    var po = it.po_number || '-';
                    var truck = it.truck || '-';
                    var vendor = it.vendor_name || '-';
                    var status = (it.status || '').replace('_', ' ');
                    var safeStatus = status.charAt(0).toUpperCase() + status.slice(1);

                    html += '<tr><td>'  + timeStr + '</td>'
                          + '<td>'  + po + '</td>'
                          + '<td>'  + truck + '</td>'
                          + '<td>'  + vendor + '</td>'
                          + '<td>'  + safeStatus + '</td></tr>';
                });
                scheduleModalBody.innerHTML = html;
            })
            .catch(function () {
                scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--danger st-modal__message">Gagal memuat jadwal.</td></tr>';
            });

        scheduleModal.style.display = 'flex';
    }

    if (schedulePreviewBtn) {
        schedulePreviewBtn.addEventListener('click', openScheduleModal);
    }
    if (scheduleModalClose) {
        scheduleModalClose.addEventListener('click', function () {
            if (scheduleModal) scheduleModal.style.display = 'none';
        });
    }
    if (scheduleModal) {
        scheduleModal.addEventListener('click', function (e) {
            if (e.target === scheduleModal) scheduleModal.style.display = 'none';
        });
    }

    // ─── Event listeners ───────────────────────────────────────────────────────
    if (truckTypeSelect) {
        truckTypeSelect.addEventListener('change', updateDurationFromTruckType);
    }
    if (plannedDurationInput) {
        plannedDurationInput.addEventListener('input', function () {
            updateRiskPreview();
            checkTimeOverlap();
        });
    }
    if (gateSelect) {
        gateSelect.addEventListener('change', function () {
            syncWarehouseFromGate();
            applyWarehouseLockState();
            updateRiskPreview();
            checkTimeOverlap();
        });
    }

    // ─── Init ──────────────────────────────────────────────────────────────────
    applyWarehouseLockState();
    updateDurationFromTruckType();
    updateDirectionFromBp();
});

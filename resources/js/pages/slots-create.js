document.addEventListener('DOMContentLoaded', function () {
    var directionSelect = document.getElementById('direction');
    var truckTypeSelect = document.getElementById('truck_type');
    var poInput = document.getElementById('po_number');
    var poSuggestions = document.getElementById('po_suggestions');
    var poPreview = document.getElementById('po_preview');
    var poItemsGroup = document.getElementById('po_items_group');

    var vendorSearch = document.getElementById('vendor_search');
    var vendorSelect = document.getElementById('vendor_id');
    var vendorSuggestions = document.getElementById('vendor_suggestions');

    var warehouseSelect = document.getElementById('warehouse_id');
    var gateSelect = document.getElementById('planned_gate_id');
    var plannedStartInput = document.getElementById('planned_start_input');
    var plannedStartDateInput = document.getElementById('planned_start_date_input');
    var plannedStartTimeInput = document.getElementById('planned_start_time_input');
    var plannedDurationInput = document.querySelector('input[name="planned_duration"]');
    var durationUnitSelect = document.querySelector('select[name="duration_unit"]');

    var truckTypeDurationsEl = document.getElementById('truck_type_durations_json');
    var truckTypeDurations = {};
    try {
        truckTypeDurations = truckTypeDurationsEl ? JSON.parse(truckTypeDurationsEl.textContent || '{}') : {};
    } catch (e) {
        truckTypeDurations = {};
    }

    var riskPreview = document.getElementById('risk_preview');
    var gateRecommendation = document.getElementById('gate_recommendation');
    var timeWarning = document.getElementById('time_warning');
    var saveButton = document.getElementById('save_button');

    var scheduleModal = document.getElementById('schedule_modal');
    var scheduleModalBody = document.getElementById('schedule_modal_body');
    var scheduleModalInfo = document.getElementById('schedule_modal_info');
    var scheduleModalClose = document.getElementById('schedule_modal_close');
    var schedulePreviewBtn = document.getElementById('btn_schedule_preview');

    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    var uiHasOverlap = false;
    var uiOverlapPending = false;

    function setPlannedStartValue(val) {
        if (!plannedStartInput) return;
        var safe = String(val || '').trim();
        safe = safe.replace('T', ' ');
        plannedStartInput.value = safe;

        if (plannedStartDateInput && plannedStartTimeInput) {
            if (safe) {
                var parts = safe.split(' ');
                plannedStartDateInput.value = parts[0] || '';
                plannedStartTimeInput.value = (parts[1] || '').slice(0, 5);
            } else {
                plannedStartDateInput.value = '';
                plannedStartTimeInput.value = '';
            }
        }
    }

    var uiRiskHigh = false;
    var uiRiskPending = false;

    var routesEl = document.getElementById('slot_routes_json');
    var slotRoutes = {};
    try {
        slotRoutes = routesEl ? JSON.parse(routesEl.textContent || '{}') : {};
    } catch (e) {
        slotRoutes = {};
    }

    var urlCheckRisk = slotRoutes.check_risk || '';
    var urlCheckSlotTime = slotRoutes.check_slot_time || '';
    var urlRecommendGate = slotRoutes.recommend_gate || '';
    var urlSchedulePreview = slotRoutes.schedule_preview || '';
    var urlPoSearch = slotRoutes.po_search || '';
    var urlPoDetailTemplate = slotRoutes.po_detail_template || '';
    var urlVendorSearch = slotRoutes.vendor_search || '';

    var oldPoItems = {};
    try {
        var oldPoItemsEl = document.getElementById('old_po_items_json');
        oldPoItems = oldPoItemsEl ? JSON.parse(oldPoItemsEl.textContent || '{}') : {};
    } catch (e) {
        oldPoItems = {};
    }

    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function postJson(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        }).then(function (res) { return res.json(); });
    }

    function getJson(url) {
        return fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            }
        }).then(function (res) { return res.json(); });
    }

    function applySaveState() {
        if (!saveButton) return;
        saveButton.disabled = !!(uiOverlapPending || uiHasOverlap || uiRiskPending || uiRiskHigh);
    }

    function updateDurationFromTruckType() {
        if (!truckTypeSelect || !plannedDurationInput) return;
        var tt = (truckTypeSelect.value || '').trim();
        var minutes = tt && truckTypeDurations && truckTypeDurations[tt] ? parseInt(truckTypeDurations[tt], 10) : NaN;

        if (!tt || !isFinite(minutes) || minutes <= 0) {
            // If truck type is not in the database, allow manual input
            plannedDurationInput.removeAttribute('readonly');
            plannedDurationInput.value = plannedDurationInput.value || '';
        } else {
            // If truck type is in the database, autofill and set to readonly
            plannedDurationInput.setAttribute('readonly', 'readonly');
            plannedDurationInput.value = String(minutes);
        }

        if (durationUnitSelect) {
            durationUnitSelect.value = 'minutes';
        }
        updateRiskPreview();
        updateGateRecommendation();
        checkTimeOverlap();
        applySaveState();
    }

    function closePoSuggestions() {
        if (!poSuggestions) return;
        poSuggestions.style.display = 'none';
        poSuggestions.innerHTML = '';
    }

    function renderPoSuggestions(items) {
        if (!poSuggestions) return;
        if (!items || !items.length) {
            closePoSuggestions();
            return;
        }

        poSuggestions.innerHTML = '';
        // Limit to 5 items
        items.slice(0, 5).forEach(function (it) {
            var div = document.createElement('div');
            div.className = 'po-item';
            div.setAttribute('data-po', it.po_number || '');
            div.innerHTML = '<div class="po-item__title">' + (it.po_number || '') + '</div>'
                + '<div class="po-item__sub">' + (it.vendor_name || '') + (it.plant ? (' â€¢ ' + it.plant) : '') + '</div>';
            div.style.cssText = 'padding:6px 8px;cursor:pointer;border-bottom:1px solid #f3f4f6;';
            poSuggestions.appendChild(div);
        });
        poSuggestions.style.display = 'block';
    }

    function formatQty(value) {
        var num = Number(value);
        if (!isFinite(num)) {
            return '0';
        }
        return String(num).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
    }

    function setPoPreview(po) {
        if (!poPreview) return;
        if (poItemsGroup) {
            poItemsGroup.style.display = 'none';
            poItemsGroup.innerHTML = '';
        }
        if (!po) {
            poPreview.textContent = 'No PO/DO Data Yet.';
            return;
        }

        var items = Array.isArray(po.items) ? po.items : [];
        poPreview.innerHTML = ''
            + '<div class="po-preview__box">'
            + '<div class="po-preview__title">' + (po.po_number || '') + '</div>'
            + '<div class="po-preview__info">Vendor: ' + (po.vendor_name || '-') + '</div>'
            + '<div class="po-preview__info">Plant: ' + (po.plant || '-') + '</div>'
            + '<div class="po-preview__info">Doc Date: ' + (po.doc_date || '-') + '</div>'
            + '<div class="po-preview__items">Items: ' + items.length + '</div>'
            + '</div>';

        if (!poItemsGroup) return;
        if (!items.length) {
            poItemsGroup.style.display = 'none';
            poItemsGroup.innerHTML = '';
            return;
        }

        var html = '';
        html += '<div class="st-font-semibold st-mb-2">PO Items & Quantity for This Slot <span class="st-text--danger-dark">*</span></div>';
        html += '<div class="st-table-wrapper st-table-wrapper--mt-6">';
        html += '<table class="st-table st-table--compact">';
        html += '<thead><tr>'
            + '<th class="st-table-col-70" title="PO line item number.">Item</th>'
            + '<th title="Material code and description.">Material</th>'
            + '<th class="st-table-col-110 st-text-right" title="Total quantity ordered in the PO.">Qty PO</th>'
            + '<th class="st-table-col-110 st-text-right" title="Total quantity already received in SAP (GR)."><span>GR Total</span></th>'
            + '<th class="st-table-col-110 st-text-right" title="Total quantity already booked in previous slots.">Booked</th>'
            + '<th class="st-table-col-110 st-text-right" title="Remaining quantity available to book.">Remaining</th>'
            + '<th class="st-table-col-160" title="Quantity to book for this slot.">Qty This Slot</th>'
            + '</tr></thead><tbody>';

        items.forEach(function (it) {
            if (!it) return;
            var itemNo = String(it.item_no || '').trim();
            if (!itemNo) return;
            var mat = String(it.material || '').trim();
            var desc = String(it.description || '').trim();
            var uom = String(it.uom || '').trim();
            var qtyPo = (it.qty !== undefined && it.qty !== null) ? formatQty(it.qty) : '-';
            var qtyGr = (it.qty_gr_total !== undefined && it.qty_gr_total !== null) ? formatQty(it.qty_gr_total) : '0';
            var qtyBooked = (it.qty_booked !== undefined && it.qty_booked !== null) ? formatQty(it.qty_booked) : '0';
            var remainingValue = (it.remaining_qty !== undefined && it.remaining_qty !== null) ? Number(it.remaining_qty) : NaN;
            if (!isFinite(remainingValue)) {
                remainingValue = NaN;
            }
            var remaining = isFinite(remainingValue) ? formatQty(Math.max(remainingValue, 0)) : '-';

            var oldQty = '';
            try {
                if (oldPoItems && oldPoItems[itemNo] && oldPoItems[itemNo].qty !== undefined && oldPoItems[itemNo].qty !== null) {
                    oldQty = String(oldPoItems[itemNo].qty);
                }
            } catch (e) {}

            html += '<tr>';
            html += '<td><strong>' + itemNo + '</strong></td>';
            html += '<td>' + mat + (desc ? (' - ' + desc) : '') + '</td>';
            html += '<td class="st-text-right">' + qtyPo + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td class="st-text-right">' + qtyGr + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td class="st-text-right">' + qtyBooked + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td class="st-text-right"><strong>' + remaining + '</strong>' + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td>';
            html += '<input type="number" step="1" min="0" name="po_items[' + itemNo + '][qty]" class="st-input st-input--w-140" value="' + (oldQty || '') + '" placeholder="0">';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        html += '<div class="st-text--small st-text--muted st-note">Input quantity for this slot delivery. Remaining qty remains available for the next slot.</div>';

        poItemsGroup.innerHTML = html;
        poItemsGroup.style.display = 'block';
    }

    function fetchPoDetail(poNumber, callback) {
        if (!poNumber) {
            callback({ success: false });
            return;
        }

        var url = String(urlPoDetailTemplate || '').replace('__PO__', encodeURIComponent(poNumber));
        getJson(url)
            .then(function (data) {
                if (data && data.success && data.data) {
                    callback({ success: true, data: data.data });
                } else {
                    callback({ success: false });
                }
            })
            .catch(function () {
                callback({ success: false });
            });
    }

    function normalizeVendorText(v) {
        return (v || '').replace(/\s+/g, ' ').trim();
    }

    function filterVendors() {
        if (!vendorSelect || !vendorSearch || !vendorSuggestions) return;

        var dir = directionSelect ? directionSelect.value : '';

        if (!dir) {
            vendorSearch.value = '';
            vendorSearch.disabled = true;
            vendorSearch.placeholder = 'Choose Direction First...';
            vendorSelect.value = '';
            clearVendorSuggestions();
            return;
        }

        vendorSearch.disabled = false;
        vendorSearch.placeholder = dir === 'outbound' ? 'Search Customer (SAP)...' : 'Search Supplier (SAP)...';

        var q = (vendorSearch.value || '').trim();
        // Don't search if query is empty, unless we want to show recent/all (maybe too heavy)
        if (q.length < 2) {
            clearVendorSuggestions();
            return;
        }

        var requiredType = dir === 'outbound' ? 'customer' : 'supplier';

        // Debounce AJAX request
        if (vendorDebounceTimer) clearTimeout(vendorDebounceTimer);

        vendorDebounceTimer = setTimeout(function () {
            var finalUrl = String(urlVendorSearch || '') + '?q=' + encodeURIComponent(q) + '&type=' + encodeURIComponent(requiredType);

            // Show loading indicator?
            vendorSuggestions.innerHTML = '<div class="st-suggestion-empty">Searching SAP...</div>';
            vendorSuggestions.style.display = 'block';

            getJson(finalUrl)
                .then(function (data) {
                    if (!data || !data.success || !data.data || data.data.length === 0) {
                        vendorSuggestions.innerHTML = '<div class="st-suggestion-empty">No Vendors Found in SAP/Local</div>';
                        return;
                    }

                    var html = '';
                    data.data.forEach(function (item) {
                        var safeName = (item.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var safeCode = (item.code || '');
                        var sourceBadge = (item.source === 'sap') ? '<span class="st-badge st-badge--info st-badge--xs">SAP</span>' : '';

                        html += '<div class="vendor-suggestion-item" data-id="' + item.id + '" data-name="' + safeName + '" data-code="' + safeCode + '">'
                             + '<div>' + safeName + sourceBadge + '</div>'
                             + '<div class="st-suggestion-code">' + safeCode + '</div>'
                             + '</div>';
                    });

                    vendorSuggestions.innerHTML = html;
                    vendorSuggestions.style.display = 'block';
                })
                .catch(function () {
                    vendorSuggestions.innerHTML = '<div class="st-suggestion-empty">Error Searching Vendor</div>';
                });
        }, 300);
    }

    function onDirectionChanged() {
        var dir = directionSelect ? (directionSelect.value || '') : '';
        if (vendorSelect) vendorSelect.value = '';
        if (vendorSearch) vendorSearch.value = '';
        clearVendorSuggestions();

        if (!vendorSearch) return;
        if (!dir) {
            vendorSearch.disabled = true;
            vendorSearch.placeholder = 'Choose Direction First...';
            return;
        }

        vendorSearch.disabled = false;
        vendorSearch.placeholder = 'Search Vendor...';
    }

    function syncWarehouseFromGate() {
        if (!warehouseSelect || !gateSelect) return;
        var selected = gateSelect.options[gateSelect.selectedIndex];
        if (!selected) return;
        var wh = selected.getAttribute('data-warehouse-id') || '';
        warehouseSelect.value = wh;
    }

    function applyDatepickerTooltips(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);

        dp.find('td.is-holiday').each(function() {
            const cell = window.jQuery(this);
            const dayText = cell.find('a, span').first().text();
            if (!dayText) return;
            const fallbackYear = inst.drawYear ?? inst.selectedYear;
            const fallbackMonth = inst.drawMonth ?? inst.selectedMonth;
            const year = cell.data('year') ?? fallbackYear;
            const month = cell.data('month') ?? fallbackMonth;
            if (year === undefined || month === undefined) return;
            const ds = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayText).padStart(2, '0')}`;
            const title = holidayData[ds] || '';
            if (title) {
                cell.attr('data-st-tooltip', title);
                cell.find('a, span').attr('data-st-tooltip', title);
            }
            cell.removeAttr('title');
            cell.find('a, span').removeAttr('title');
        });
    }

    function bindDatepickerHover(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);
        let hideTimer = null;
        let tooltip = document.getElementById('st-datepicker-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'st-datepicker-tooltip';
            tooltip.className = 'st-datepicker-tooltip';
            document.body.appendChild(tooltip);
        }

        dp.off('mouseenter.st-tooltip mousemove.st-tooltip mouseleave.st-tooltip', 'td.is-holiday');
        dp.on('mouseenter.st-tooltip', 'td.is-holiday', function(event) {
            const text = window.jQuery(this).attr('data-st-tooltip') || '';
            if (!text) return;
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
            tooltip.textContent = text;
            tooltip.classList.add('st-datepicker-tooltip--visible');
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mousemove.st-tooltip', 'td.is-holiday', function(event) {
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mouseleave.st-tooltip', 'td.is-holiday', function() {
            hideTimer = setTimeout(function() {
                tooltip.classList.remove('st-datepicker-tooltip--visible');
            }, 300);
        });
    }

    function initEtaDatepicker() {
        return;
    }

    function initEtaTimepicker() {
        if (!plannedStartTimeInput) return;
        if (plannedStartTimeInput.getAttribute('data-st-timepicker') === '1') return;
        if (typeof window.mdtimepicker !== 'function') return;
        plannedStartTimeInput.setAttribute('data-st-timepicker', '1');

        plannedStartTimeInput.addEventListener('keydown', function (event) { event.preventDefault(); });
        plannedStartTimeInput.addEventListener('paste', function (event) { event.preventDefault(); });

        window.mdtimepicker('#planned_start_time_input', {
            format: 'hh:mm',
            is24hour: true,
            theme: 'cyan',
            hourPadding: true
        });

        plannedStartTimeInput.addEventListener('change', function () {
            syncPlannedStart();
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    function syncPlannedStart() {
        if (!plannedStartInput || !plannedStartDateInput || !plannedStartTimeInput) return;
        var dateVal = (plannedStartDateInput.value || '').trim();
        var timeVal = (plannedStartTimeInput.value || '').trim();
        if (dateVal && timeVal) {
            plannedStartInput.value = dateVal + ' ' + timeVal;
        } else if (dateVal) {
            plannedStartInput.value = dateVal;
        } else {
            plannedStartInput.value = '';
        }
    }

    function applyWarehouseLockState() {
        var hasGate = !!(gateSelect && gateSelect.value);

        if (plannedStartInput) {
            plannedStartInput.disabled = !hasGate;
            if (plannedStartDateInput) plannedStartDateInput.disabled = !hasGate;
            if (plannedStartTimeInput) plannedStartTimeInput.disabled = !hasGate;
            if (hasGate) {
                initEtaDatepicker();
                initEtaTimepicker();
            } else {
                plannedStartInput.value = '';
                if (plannedStartDateInput) plannedStartDateInput.value = '';
                if (plannedStartTimeInput) plannedStartTimeInput.value = '';
            }
        }

        if (schedulePreviewBtn) {
            schedulePreviewBtn.disabled = !hasGate;
        }
    }

    function onGateChanged() {
        syncWarehouseFromGate();
        applyWarehouseLockState();
    }

    function updateRiskPreview() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !durationUnitSelect || !riskPreview) return;

        var whId = warehouseSelect.value;
        var gateId = gateSelect ? gateSelect.value : '';
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect.value;

        if (!whId || !start || !duration) {
            riskPreview.textContent = 'Risk Not Calculated.';
            uiRiskHigh = false;
            uiRiskPending = false;
            applySaveState();
            return;
        }

        uiRiskPending = true;
        applySaveState();

        var formData = new FormData();
        formData.append('warehouse_id', whId);
        formData.append('planned_gate_id', gateId);
        formData.append('planned_start', start.replace('T', ' ') + ':00');
        formData.append('planned_duration', duration);
        formData.append('duration_unit', unit);

        postJson(urlCheckRisk, formData)
            .then(function (data) {
                if (!data || !data.success) {
                    riskPreview.textContent = 'Risk Cannot Be Calculated.';
                    uiRiskHigh = false;
                    uiRiskPending = false;
                    applySaveState();
                    return;
                }

                var cls = 'small';
                if (data.badge === 'success') cls += ' text-success';
                if (data.badge === 'warning') cls += ' text-warning';
                if (data.badge === 'danger') cls += ' text-danger';

                riskPreview.className = cls;
                riskPreview.textContent = data.label + ' - ' + data.message;

                uiRiskHigh = !!(data.risk_level >= 2);
                uiRiskPending = false;
                applySaveState();
            })
            .catch(function () {
                riskPreview.textContent = 'Risk tidak dapat dihitung.';
                uiRiskHigh = false;
                uiRiskPending = false;
                applySaveState();
            });
    }

    function checkTimeOverlap() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !durationUnitSelect) return;

        var whId = warehouseSelect.value;
        var gateId = gateSelect ? gateSelect.value : '';
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect.value;

        if (!whId || !start || !duration) {
            if (timeWarning) timeWarning.textContent = '';
            uiHasOverlap = false;
            uiOverlapPending = false;
            applySaveState();
            return;
        }

        uiOverlapPending = true;
        applySaveState();

        var formData = new FormData();
        formData.append('warehouse_id', whId);
        formData.append('planned_gate_id', gateId);
        formData.append('planned_start', start.replace('T', ' ') + ':00');
        formData.append('planned_duration', duration);
        formData.append('duration_unit', unit);

        postJson(urlCheckSlotTime, formData)
            .then(function (data) {
                if (!data || !data.success) {
                    if (timeWarning) timeWarning.textContent = '';
                    uiHasOverlap = false;
                    uiOverlapPending = false;
                    applySaveState();
                    return;
                }

                if (data.overlap) {
                    var msg = data.message ? String(data.message) : 'Waktu Ini Bentrok dengan Slot Lain pada Gate Ini.';
                    if (data.suggested_start) {
                        msg += ' Waktu Otomatis Disesuaikan ke Setelah ' + data.suggested_start + '.';
                        setPlannedStartValue(String(data.suggested_start));
                        if (timeWarning) timeWarning.textContent = msg;
                        updateRiskPreview();
                        updateGateRecommendation();

                        uiHasOverlap = true;
                        uiOverlapPending = false;
                        applySaveState();

                        setTimeout(function () { checkTimeOverlap(); }, 0);
                        return;
                    }

                    if (timeWarning) timeWarning.textContent = msg;
                    uiHasOverlap = true;
                    uiOverlapPending = false;
                    applySaveState();
                } else {
                    if (timeWarning) timeWarning.textContent = '';
                    uiHasOverlap = false;
                    uiOverlapPending = false;
                    applySaveState();
                }
            })
            .catch(function () {
                if (timeWarning) timeWarning.textContent = '';
                uiHasOverlap = false;
                uiOverlapPending = false;
                applySaveState();
            });
    }

    function updateGateRecommendation() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !durationUnitSelect || !gateRecommendation) return;

        var whId = warehouseSelect.value;
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect.value;

        if (!whId || !start || !duration) {
            gateRecommendation.textContent = '';
            return;
        }

        var formData = new FormData();
        formData.append('warehouse_id', whId);
        formData.append('planned_start', start.replace('T', ' ') + ':00');
        formData.append('planned_duration', duration);
        formData.append('duration_unit', unit);

        postJson(urlRecommendGate, formData)
            .then(function (data) {
                if (!data || !data.success) {
                    gateRecommendation.textContent = '';
                    return;
                }

                var recId = (data.gate_id !== undefined && data.gate_id !== null) ? String(data.gate_id) : '';
                var selectedId = gateSelect && gateSelect.value ? String(gateSelect.value) : '';
                if (selectedId !== '' && recId !== '' && selectedId === recId) {
                    gateRecommendation.textContent = '';
                    return;
                }

                var recText = 'Recommended: ' + data.gate_label + ' (' + data.risk_label + ' risk)';
                if (data.note) {
                    recText += ' - ' + String(data.note);
                }
                gateRecommendation.textContent = recText;
            })
            .catch(function () {
                gateRecommendation.textContent = '';
            });
    }

    function formatDateTimeForDisplay(str) {
        if (!str) return '-';
        return str.replace('T', ' ');
    }

    function openScheduleModal() {
        if (!scheduleModal || !scheduleModalBody || !warehouseSelect) return;

        var whId = warehouseSelect.value;
        if (!whId) {
            alert('Pilih Warehouse Terlebih Dahulu.');
            return;
        }

        var gateId = gateSelect ? gateSelect.value : '';

        var dateStr = '';
        if (plannedStartInput && plannedStartInput.value) {
            dateStr = plannedStartInput.value.split(/\s|T/)[0];
        } else {
            dateStr = new Date().toISOString().slice(0, 10);
        }

        scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--muted st-modal__message">Loading...</td></tr>';
        if (scheduleModalInfo) {
            scheduleModalInfo.textContent = 'Warehouse ID ' + whId + (gateId ? (', Gate ID ' + gateId) : '') + ' | Date ' + dateStr;
        }

        var fd = new FormData();
        fd.append('warehouse_id', whId);
        fd.append('planned_gate_id', gateId);
        fd.append('date', dateStr);

        postJson(urlSchedulePreview, fd)
            .then(function (data) {
                if (!data || !data.success) {
                    scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--danger st-modal__message">Gagal memuat jadwal</td></tr>';
                    scheduleModalInfo.textContent = '';
                    return;
                }

                var items = data.items || [];
                if (items.length === 0) {
                    scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--muted st-modal__message">Tidak ada slot scheduled / in progress pada tanggal ini.</td></tr>';
                    return;
                }

                var html = '';
                items.forEach(function (item, idx) {
                    var start = formatDateTimeForDisplay(item.planned_start);
                    var finish = formatDateTimeForDisplay(item.planned_finish);
                    var gate = item.gate || '-';
                    var status = (item.status || '').replace('_', ' ');
                    var safeStatus = status.charAt(0).toUpperCase() + status.slice(1);

                    html += '<tr class="schedule-row st-row-clickable" data-start="' + (item.planned_start || '') + '">';
                    html += '<td>' + (idx + 1) + '</td>';
                    html += '<td>' + start + '</td>';
                    html += '<td>' + (finish || '-') + '</td>';
                    html += '<td>' + gate + '</td>';
                    html += '<td>' + safeStatus + '</td>';
                    html += '</tr>';
                });

                scheduleModalBody.innerHTML = html;
            })
            .catch(function () {
                scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--danger st-modal__message">Gagal memuat jadwal</td></tr>';
            });

        scheduleModal.style.display = 'flex';
    }

    if (directionSelect) {
        directionSelect.addEventListener('change', function () {
            onDirectionChanged();
            filterVendors();
        });
    }
    if (vendorSearch) {
        vendorSearch.addEventListener('input', filterVendors);
        vendorSearch.addEventListener('focus', filterVendors);
    }
    if (vendorSuggestions) {
        vendorSuggestions.addEventListener('click', function (e) {
            var target = e.target.closest('.vendor-suggestion-item');
            if (!target) return;
            var id = target.getAttribute('data-id');
            var name = target.getAttribute('data-name');
            var code = target.getAttribute('data-code') || '';

            if (id && vendorSelect) {
                // Check if option exists, if not add it (handled dynamic sync)
                var exists = vendorSelect.querySelector('option[value="' + id + '"]');
                if (!exists) {
                    var newOpt = document.createElement('option');
                    newOpt.value = id;
                    newOpt.textContent = name;
                    newOpt.setAttribute('selected', 'selected');
                    vendorSelect.appendChild(newOpt);
                }
                vendorSelect.value = id;
            }
            if (vendorSearch && name) {
                vendorSearch.value = normalizeVendorText(name);
                // Optionally show code ?
            }
            clearVendorSuggestions();
        });
    }
    document.addEventListener('click', function (e) {
        if (vendorSearch && vendorSuggestions) {
            var inside = e.target === vendorSearch || e.target.closest('#vendor_suggestions');
            if (!inside) {
                clearVendorSuggestions();
            }
        }
    });

    if (gateSelect) {
        gateSelect.addEventListener('change', function () {
            onGateChanged();
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (plannedStartDateInput) {
        plannedStartDateInput.addEventListener('change', function () {
            syncPlannedStart();
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (plannedStartTimeInput) {
        plannedStartTimeInput.addEventListener('input', function () {
            syncPlannedStart();
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (plannedStartInput && plannedStartInput.value) {
        setPlannedStartValue(plannedStartInput.value);
    }

    if (plannedDurationInput) {
        plannedDurationInput.addEventListener('change', function () {
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
        plannedDurationInput.addEventListener('keyup', function () {
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (gateSelect && (gateSelect.value || '').trim() !== '') {
        onGateChanged();
    }

    if (durationUnitSelect) {
        durationUnitSelect.addEventListener('change', function () {
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (truckTypeSelect) {
        truckTypeSelect.addEventListener('change', function () {
            updateDurationFromTruckType();
        });
    }

    if (poInput) {
        var poDebounceTimer = null;

        poInput.addEventListener('input', function () {
            var q = (poInput.value || '').trim();
            setPoPreview(null);

            if (poDebounceTimer) clearTimeout(poDebounceTimer);
            poDebounceTimer = setTimeout(function () {
                getJson(String(urlPoSearch || '') + '?q=' + encodeURIComponent(q))
                    .then(function (data) {
                        if (!data || !data.success) {
                            closePoSuggestions();
                            return;
                        }
                        renderPoSuggestions(data.data || []);
                    })
                    .catch(function () {
                        closePoSuggestions();
                    });
            }, 250);
        });

        poInput.addEventListener('focus', function () {
            var q = (poInput.value || '').trim();
            if (q.length < 3) return; // Don't search on focus if empty/short
            getJson(String(urlPoSearch || '') + '?q=' + encodeURIComponent(q))
                .then(function (data) {
                    if (!data || !data.success) {
                        closePoSuggestions();
                        return;
                    }
                    renderPoSuggestions(data.data || []);
                })
                .catch(function () {
                    closePoSuggestions();
                });
        });

        // Auto-fetch detail on blur/change if user typed a full number directly
        function autoFetchDetail() {
            var val = (poInput.value || '').trim();
            if (val.length >= 5) {
                // Delay slightly to avoid conflict with suggestion click
                setTimeout(function() {
                    fetchPoDetail(val, function (data) {
                        if (data.success && data.data) {
                            setPoPreview(data.data);
                            if (data.data.direction && directionSelect) {
                                directionSelect.value = data.data.direction;
                                try {
                                    directionSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                } catch (e) {}
                                onDirectionChanged();
                            }
                        }
                    });
                }, 200);
            }
        }
        poInput.addEventListener('change', autoFetchDetail);
        poInput.addEventListener('blur', autoFetchDetail);
    }

    if (poSuggestions) {
        poSuggestions.addEventListener('click', function (e) {
            var item = e.target.closest('.po-item');
            if (!item || !poInput) return;
            var poNumber = item.getAttribute('data-po') || '';
            poInput.value = poNumber;
            closePoSuggestions();
            fetchPoDetail(poNumber, function (data) {
                if (data.success && data.data) {
                    setPoPreview(data.data);
                    if (data.data.direction && directionSelect) {
                        directionSelect.value = data.data.direction;
                        try {
                            directionSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        } catch (e) {}
                        onDirectionChanged();
                    }
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
        if (!poSuggestions || !poInput) return;
        if (e.target === poInput || poSuggestions.contains(e.target)) return;
        closePoSuggestions();
    });

    if (poInput && (poInput.value || '').trim() !== '') {
        fetchPoDetail((poInput.value || '').trim());
    }

    updateDurationFromTruckType();

    if (schedulePreviewBtn) {
        schedulePreviewBtn.addEventListener('click', openScheduleModal);
    }

    if (scheduleModal && scheduleModalBody) {
        scheduleModalBody.addEventListener('click', function (e) {
            var row = e.target.closest('.schedule-row');
            if (!row) return;
            var start = row.getAttribute('data-start') || '';
            if (!start) return;

            var parts = start.split(' ');
            if (parts.length >= 2) {
                var datePart = parts[0];
                var timePart = parts[1].slice(0, 5);
                var val = datePart + ' ' + timePart;
                setPlannedStartValue(val);
                updateRiskPreview();
                updateGateRecommendation();
                checkTimeOverlap();
                scheduleModal.style.display = 'none';
            }
        });
    }

    if (scheduleModalClose) {
        scheduleModalClose.addEventListener('click', function () {
            scheduleModal.style.display = 'none';
        });
    }

    if (scheduleModal) {
        scheduleModal.addEventListener('click', function (e) {
            if (e.target === scheduleModal) {
                scheduleModal.style.display = 'none';
            }
        });
    }

    filterVendors();
    filterGates();
    applyWarehouseLockState();
    updateRiskPreview();
    updateGateRecommendation();
    checkTimeOverlap();

    applySaveState();
});

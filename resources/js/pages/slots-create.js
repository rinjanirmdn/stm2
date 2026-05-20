document.addEventListener('DOMContentLoaded', function () {
    var directionSelect = document.getElementById('direction');
    var truckTypeSelect = document.getElementById('truck_type');
    var poInput = document.getElementById('po_number');
    var poSuggestions = document.getElementById('po_suggestions');
    var poLoading = document.getElementById('po_loading');
    var poStatus = document.getElementById('po_status');
    var poFeedback = document.getElementById('po_feedback');
    var poBypassSap = document.getElementById('po_bypass_sap');
    var poDetailRequestSeq = 0;
    var poLastAutoFilledValue = '';

    var vendorSearch = document.getElementById('vendor_search');
    var vendorNameInput = vendorSearch;
    var vendorSelect = document.getElementById('vendor_id');
    var vendorSuggestions = document.getElementById('vendor_suggestions');
    var vendorSearchAutoFill = vendorSearch ? vendorSearch.getAttribute('data-auto-fill') === '1' : false;

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

    function setPoLoading(isLoading) {
        if (!poLoading) return;
        if (isLoading) {
            poLoading.classList.add('show');
            if (poStatus) poStatus.classList.remove('show');
        } else {
            poLoading.classList.remove('show');
        }
    }

    function setPoStatus(type) {
        if (!poStatus) return;
        poStatus.classList.remove('show', 'valid', 'invalid');
        if (type === 'valid' || type === 'invalid') {
            poStatus.classList.add('show', type);
        }
    }

    function clearPoFeedback() {
        setPoLoading(false);
        setPoStatus('');
        if (poInput) poInput.classList.remove('st-input--invalid');
        if (poFeedback) {
            poFeedback.style.display = 'none';
            poFeedback.innerHTML = '';
            poFeedback.className = 'st-po-feedback st-mt-4';
        }
    }

    function showPoLoading() {
        setPoLoading(true);
        setPoStatus('');
        if (poFeedback) {
            poFeedback.className = 'st-po-feedback st-mt-4 st-po-feedback--searching';
            poFeedback.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Searching PO/SO number...';
            poFeedback.style.display = 'flex';
        }
    }

    function getDocTypeLabel(docType) {
        if (docType === 'so') return 'SO Number';
        if (docType === 'po') return 'PO Number';
        return 'PO/SO number';
    }

    function showPoValid(docType) {
        setPoLoading(false);
        setPoStatus('valid');
        if (poInput) poInput.classList.remove('st-input--invalid');
        if (poFeedback) {
            var label = getDocTypeLabel(docType);
            poFeedback.className = 'st-po-feedback st-mt-4 st-po-feedback--valid';
            poFeedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid — ' + label + ' found.';
            poFeedback.style.display = 'flex';
        }
    }

    function showPoInvalid() {
        setPoLoading(false);
        setPoStatus('invalid');
        if (poInput) poInput.classList.add('st-input--invalid');
        if (poFeedback) {
            poFeedback.className = 'st-po-feedback st-mt-4 st-po-feedback--invalid';
            poFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Invalid — PO/SO number not found in SAP. Please check and re-enter the number.';
            poFeedback.style.display = 'flex';
        }
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

        if (tt && isFinite(minutes) && minutes > 0) {
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
            var docTypeBadge = '';
            if (it.doc_type === 'so') {
                docTypeBadge = ' <span class="st-badge st-badge--warning st-badge--xs">SO</span>';
            } else if (it.doc_type === 'po') {
                docTypeBadge = ' <span class="st-badge st-badge--primary st-badge--xs">PO</span>';
            }
            div.innerHTML = '<div class="po-item__title">' + (it.po_number || '') + docTypeBadge + '</div>'
                + '<div class="po-item__sub">' + (it.vendor_name || '') + (it.plant ? (' • ' + it.plant) : '') + '</div>';
            div.style.cssText = 'padding:6px 8px;cursor:pointer;border-bottom:1px solid #f3f4f6;';
            poSuggestions.appendChild(div);
        });
        poSuggestions.style.display = 'block';
    }

    function fetchPoDetail(poNumber, callback) {
        if (!poNumber) {
            callback({ success: false });
            return;
        }
        showPoLoading();
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

    function applyPoDetail(data) {
        if (!data) return;
        if (vendorNameInput) {
            vendorNameInput.value = data.vendor_name || '';
        }
        if (vendorSearch) {
            vendorSearch.value = normalizeVendorText(data.vendor_name || '');
        }
        if (data.direction && directionSelect) {
            directionSelect.value = data.direction;
            try {
                directionSelect.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (e) { }
            onDirectionChanged();
        }
    }

    function tryAutoFillFromTypedPo(poNumber) {
        var po = String(poNumber || '').trim();
        if (po.length < 10) return;
        if (po === poLastAutoFilledValue) return;

        var reqSeq = ++poDetailRequestSeq;
        fetchPoDetail(po, function (data) {
            if (reqSeq !== poDetailRequestSeq) return;
            if (!poInput) return;
            if (String(poInput.value || '').trim() !== po) return;

            if (data.success && data.data) {
                applyPoDetail(data.data);
                poLastAutoFilledValue = po;
                closePoSuggestions();
                showPoValid(data.data.doc_type || null);
            } else {
                poLastAutoFilledValue = '';
                if (vendorNameInput) vendorNameInput.value = '';
                if (vendorSearch) vendorSearch.value = '';
                showPoInvalid();
            }
        });
    }

    function normalizeVendorText(v) {
        return (v || '').replace(/\s+/g, ' ').trim();
    }

    function filterVendors() {
        if (vendorSearchAutoFill) return;
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
        
        var driverContainer = document.getElementById('driver_number_container');
        var vendorTransporterContainer = document.getElementById('vendor_transporter_container');
        var destinationContainer = document.getElementById('destination_container');
        
        if (driverContainer && vendorTransporterContainer) {
            if (dir === 'outbound') {
                driverContainer.classList.add('st-hidden');
                vendorTransporterContainer.classList.remove('st-hidden');
                if (destinationContainer) destinationContainer.classList.remove('st-hidden');
            } else {
                driverContainer.classList.remove('st-hidden');
                vendorTransporterContainer.classList.add('st-hidden');
                if (destinationContainer) destinationContainer.classList.add('st-hidden');
            }
        }
        
        if (vendorSearchAutoFill) return;
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

    var useVendorTransporterCb = document.getElementById('use_vendor_transporter');
    var vendorTransporterSelectContainer = document.getElementById('vendor_transporter_select_container');
    if (useVendorTransporterCb && vendorTransporterSelectContainer) {
        useVendorTransporterCb.addEventListener('change', function() {
            if (this.checked) {
                vendorTransporterSelectContainer.classList.remove('st-hidden');
            } else {
                vendorTransporterSelectContainer.classList.add('st-hidden');
            }
        });
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

        dp.find('td.is-holiday').each(function () {
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
        dp.on('mouseenter.st-tooltip', 'td.is-holiday', function (event) {
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
        dp.on('mousemove.st-tooltip', 'td.is-holiday', function (event) {
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mouseleave.st-tooltip', 'td.is-holiday', function () {
            hideTimer = setTimeout(function () {
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
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !riskPreview) return;

        var whId = warehouseSelect.value;
        var gateId = gateSelect ? gateSelect.value : '';
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect ? durationUnitSelect.value : 'minutes';

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
                riskPreview.textContent = 'Risk could not be calculated.';
                uiRiskHigh = false;
                uiRiskPending = false;
                applySaveState();
            });
    }

    function checkTimeOverlap() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput) return;

        var whId = warehouseSelect.value;
        var gateId = gateSelect ? gateSelect.value : '';
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect ? durationUnitSelect.value : 'minutes';

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
                    var msg = data.message ? String(data.message) : 'This time conflicts with another slot at this gate.';
                    if (data.suggested_start) {
                        msg += ' Time was automatically adjusted to after ' + data.suggested_start + '.';
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
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !gateRecommendation) return;

        var whId = warehouseSelect.value;
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect ? durationUnitSelect.value : 'minutes';

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

                var recId = (data.gate_id_gates !== undefined && data.gate_id_gates !== null) ? String(data.gate_id_gates) : '';
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
            alert('Please select a warehouse first.');
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
                    scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--danger st-modal__message">Failed to load schedule</td></tr>';
                    scheduleModalInfo.textContent = '';
                    return;
                }

                var items = data.items || [];
                
                if (data.generated_at && scheduleModalInfo) {
                    var currentText = scheduleModalInfo.textContent.split(' | Last Update:')[0];
                    scheduleModalInfo.textContent = currentText + ' | Last Update: ' + data.generated_at;
                }

                if (items.length === 0) {
                    scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--muted st-modal__message">No scheduled / in-progress bookings on this date.</td></tr>';
                    return;
                }

                var html = '';
                items.forEach(function (item, idx) {
                    var startObj = new Date(item.planned_start.replace(/-/g, '/'));
                    var finishObj = item.planned_finish ? new Date(item.planned_finish.replace(/-/g, '/')) : null;
                    
                    var startTime = !isNaN(startObj) ? ('0' + startObj.getHours()).slice(-2) + ':' + ('0' + startObj.getMinutes()).slice(-2) : '-';
                    var finishTime = finishObj && !isNaN(finishObj) ? ('0' + finishObj.getHours()).slice(-2) + ':' + ('0' + finishObj.getMinutes()).slice(-2) : '-';
                    var timeStr = startTime + ' - ' + finishTime;
                    
                    var po = item.po_number || '-';
                    var truck = item.truck || '-';
                    var vendor = item.vendor_name || '-';
                    var status = (item.status || '').replace('_', ' ');
                    var safeStatus = status.charAt(0).toUpperCase() + status.slice(1);
                    
                    var badgeClass = 'st-badge--secondary';
                    if (item.status === 'scheduled') badgeClass = 'st-badge--info';
                    else if (item.status === 'waiting') badgeClass = 'st-badge--warning';
                    else if (item.status === 'in_progress') badgeClass = 'st-badge--primary';

                    html += '<tr class="schedule-row st-row-clickable" data-start="' + (item.planned_start || '') + '">';
                    html += '<td class="st-font-medium st-whitespace-nowrap">' + timeStr + '</td>';
                    html += '<td>' + po + '</td>';
                    html += '<td>' + truck + '</td>';
                    html += '<td style="max-width:200px;" class="st-text-ellipsis st-whitespace-nowrap" title="' + vendor + '">' + vendor + '</td>';
                    html += '<td><span class="st-badge st-badge--sm ' + badgeClass + '">' + safeStatus + '</span></td>';
                    html += '</tr>';
                });

                scheduleModalBody.innerHTML = html;
            })
            .catch(function () {
                scheduleModalBody.innerHTML = '<tr><td colspan="5" class="st-text--danger st-modal__message">Failed to load schedule</td></tr>';
            });

        scheduleModal.style.display = 'flex';
    }

    if (directionSelect) {
        directionSelect.addEventListener('change', function () {
            onDirectionChanged();
            filterVendors();
        });
    }
    if (vendorSearch && !vendorSearchAutoFill) {
        vendorSearch.addEventListener('input', filterVendors);
        vendorSearch.addEventListener('focus', filterVendors);
    }
    if (vendorSuggestions && !vendorSearchAutoFill) {
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
        if (vendorSearchAutoFill) return;
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

        function isBypassSap() {
            return poBypassSap && poBypassSap.checked;
        }

        var vendorNameManual = document.getElementById('vendor_name_manual');

        function toggleVendorBypass() {
            if (isBypassSap()) {
                if (vendorSearch) {
                    vendorSearch.removeAttribute('readonly');
                    vendorSearch.removeAttribute('disabled');
                    vendorSearch.placeholder = 'Type vendor name manually';
                }
                if (directionSelect) {
                    directionSelect.removeAttribute('disabled');
                }
                clearPoFeedback();
            } else {
                if (vendorSearch) {
                    vendorSearch.setAttribute('readonly', true);
                    vendorSearch.placeholder = 'Vendor will auto-fill from PO';
                }
                if (directionSelect) {
                    directionSelect.setAttribute('disabled', 'disabled');
                }
                var currentPo = poInput ? (poInput.value || '').trim() : '';
                if (currentPo.length >= 10) {
                    tryAutoFillFromTypedPo(currentPo);
                }
            }
        }

        if (poBypassSap) {
            poBypassSap.addEventListener('change', toggleVendorBypass);
            toggleVendorBypass(); // apply initial state
        }

        if (vendorSearch && vendorNameManual) {
            vendorSearch.addEventListener('input', function () {
                if (isBypassSap()) {
                    vendorNameManual.value = vendorSearch.value;
                }
            });
        }

        poInput.addEventListener('input', function () {
            var q = (poInput.value || '').trim();
            if (vendorNameInput) vendorNameInput.value = '';
            if (vendorSearch) vendorSearch.value = '';
            setPoStatus('');
            poLastAutoFilledValue = '';

            if (isBypassSap()) {
                clearPoFeedback();
                closePoSuggestions();
                return;
            }

            if (q.length < 2) {
                if (poDebounceTimer) clearTimeout(poDebounceTimer);
                clearPoFeedback();
                closePoSuggestions();
                return;
            }

            if (poDebounceTimer) clearTimeout(poDebounceTimer);
            poDebounceTimer = setTimeout(function () {
                if (q.length >= 10) {
                    tryAutoFillFromTypedPo(q);
                    return;
                }

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
            if (isBypassSap()) return;
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

        // Auto-fetch detail on blur/change — validate any input length
        function autoFetchDetail() {
            if (isBypassSap()) {
                clearPoFeedback();
                return;
            }
            var val = (poInput.value || '').trim();
            if (val === '') {
                clearPoFeedback();
                return;
            }
            // If already validated as valid, skip
            if (val === poLastAutoFilledValue) return;

            // Delay slightly to avoid conflict with suggestion click
            setTimeout(function () {
                // Re-check value in case user clicked a suggestion
                var current = (poInput.value || '').trim();
                if (current !== val) return;
                if (current === poLastAutoFilledValue) return;

                if (current.length >= 10) {
                    fetchPoDetail(current, function (data) {
                        if (data.success && data.data) {
                            applyPoDetail(data.data);
                            poLastAutoFilledValue = current;
                            showPoValid();
                        } else {
                            poLastAutoFilledValue = '';
                            if (vendorNameInput) vendorNameInput.value = '';
                            if (vendorSearch) vendorSearch.value = '';
                            showPoInvalid();
                        }
                    });
                } else {
                    // PO/SO number too short — invalid
                    poLastAutoFilledValue = '';
                    showPoInvalid();
                }
            }, 200);
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
                    applyPoDetail(data.data);
                    poLastAutoFilledValue = poNumber;
                    showPoValid();
                } else {
                    poLastAutoFilledValue = '';
                    if (vendorNameInput) vendorNameInput.value = '';
                    if (vendorSearch) vendorSearch.value = '';
                    showPoInvalid();
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
        fetchPoDetail((poInput.value || '').trim(), function (data) {
            if (data.success && data.data) {
                applyPoDetail(data.data);
                poLastAutoFilledValue = (poInput.value || '').trim();
                showPoValid();
            } else {
                showPoInvalid();
            }
        });
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

    onDirectionChanged();
    filterVendors();
    applyWarehouseLockState();
    updateRiskPreview();
    updateGateRecommendation();
    checkTimeOverlap();
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            if (directionSelect) directionSelect.removeAttribute('disabled');
        });
    }
    applySaveState();

    // Multiple PO Logic for Admin
    var poEntriesContainer = document.getElementById('po_entries_container');
    var btnAddPo = document.getElementById('btn_add_po');
    var searchTimeouts = {};

    function clearPoEntryValidation(container) {
        var poStatus = container.querySelector('.po-status');
        var poFeedback = container.querySelector('.po-feedback');
        var poHidden = container.querySelector('.po-number-hidden');
        if (poStatus) poStatus.classList.remove('show', 'valid', 'invalid');
        if (poFeedback) {
            poFeedback.className = 'st-po-feedback po-feedback st-mt-4';
            poFeedback.innerHTML = '';
            poFeedback.style.display = 'none';
        }
        if (poHidden) poHidden.value = '';
    }

    if (poEntriesContainer) {
        if (poBypassSap) {
            poBypassSap.addEventListener('change', function() {
                var entries = poEntriesContainer.querySelectorAll('.po-entry');
                if (poBypassSap.checked) {
                    entries.forEach(function(entry) {
                        var searchInput = entry.querySelector('.po-search-input');
                        var hiddenInput = entry.querySelector('.po-number-hidden');
                        clearPoEntryValidation(entry);
                        if (searchInput && hiddenInput) hiddenInput.value = searchInput.value.trim();
                    });
                } else {
                    entries.forEach(function(entry) {
                        clearPoEntryValidation(entry);
                    });
                }
            });
        }

        poEntriesContainer.addEventListener('input', function (e) {
            if (e.target.classList.contains('po-search-input')) {
                var inputEl = e.target;
                var container = inputEl.closest('.po-entry');
                var hiddenEl = container.querySelector('.po-number-hidden');
                var q = inputEl.value.trim();
                var index = container.dataset.poIndex;

                if (poBypassSap && poBypassSap.checked) {
                    hiddenEl.value = q;
                    clearPoEntryValidation(container);
                    return;
                }

                if (q.length < 2) {
                    clearPoEntryValidation(container);
                    return;
                }

                if (searchTimeouts[index]) clearTimeout(searchTimeouts[index]);
                
                var poStatus = container.querySelector('.po-status');
                var poFeedback = container.querySelector('.po-feedback');
                if (poStatus) poStatus.classList.remove('show', 'valid', 'invalid');
                if (poFeedback) {
                    poFeedback.className = 'st-po-feedback po-feedback st-mt-4 st-po-feedback--searching';
                    poFeedback.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Searching PO/SO number...';
                    poFeedback.style.display = 'flex';
                }

                searchTimeouts[index] = setTimeout(function () {
                    getJson(urlPoSearch + '?q=' + encodeURIComponent(q))
                        .then(function(data) {
                            if (data.success && data.data && data.data.length > 0) {
                                var po = null;
                                var qUpper = q.toUpperCase();
                                var qClean = q.replace(/^0+/, '').toUpperCase();
                                
                                for (var i = 0; i < data.data.length; i++) {
                                    var poNum = String(data.data[i].po_number || '').trim();
                                    var poNumUpper = poNum.toUpperCase();
                                    var poNumClean = poNum.replace(/^0+/, '').toUpperCase();
                                    if (poNumUpper === qUpper || poNumClean === qClean) {
                                        po = data.data[i];
                                        break;
                                    }
                                }

                                if (!po) {
                                    hiddenEl.value = '';
                                    if (poStatus) poStatus.classList.add('show', 'invalid');
                                    if (poFeedback) {
                                        poFeedback.className = 'st-po-feedback po-feedback st-mt-4 st-po-feedback--invalid';
                                        poFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> PO/SO not found in SAP.';
                                        poFeedback.style.display = 'flex';
                                    }
                                    updateSubmitState();
                                    return;
                                }

                                var entries = Array.from(poEntriesContainer.querySelectorAll('.po-entry'));
                                var firstEntry = entries[0];
                                var isValid = true;
                                var invalidMsg = '';

                                if (firstEntry && firstEntry !== container && firstEntry.dataset.poVendorName) {
                                    var firstVendorName = firstEntry.dataset.poVendorName || '';
                                    var currentVendorName = (po.vendor_name || '').trim();
                                    if (currentVendorName !== firstVendorName) {
                                        isValid = false;
                                        invalidMsg = 'Multiple PO/SO must be from the same vendor.';
                                    }
                                }

                                if (isValid) {
                                    var hiddenInputs = poEntriesContainer.querySelectorAll('.po-number-hidden');
                                    for (var i = 0; i < hiddenInputs.length; i++) {
                                        if (hiddenInputs[i] !== hiddenEl && hiddenInputs[i].value === po.po_number) {
                                            isValid = false;
                                            invalidMsg = 'PO/SO number is already added.';
                                            break;
                                        }
                                    }
                                }

                                if (isValid) {
                                    inputEl.value = po.po_number;
                                    hiddenEl.value = po.po_number;
                                    container.dataset.poVendorName = (po.vendor_name || '').trim();
                                    
                                    if (poStatus) poStatus.classList.add('show', 'valid');
                                    if (poFeedback) {
                                        poFeedback.className = 'st-po-feedback po-feedback st-mt-4 st-po-feedback--valid';
                                        poFeedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid — ' + (po.doc_type === 'so' ? 'SO' : 'PO') + ' found.';
                                        if (po.vendor_name) poFeedback.innerHTML += '<br>Vendor: ' + po.vendor_name;
                                        poFeedback.style.display = 'flex';
                                    }
                                    
                                    // Auto-fill vendor name
                                    if (container === firstEntry) {
                                        if (vendorNameInput) vendorNameInput.value = po.vendor_name || '';
                                        if (vendorSearch) {
                                            vendorSearch.value = normalizeVendorText(po.vendor_name || '');
                                        }
                                        if (directionSelect && po.direction) {
                                            directionSelect.value = po.direction;
                                            onDirectionChanged();
                                        }
                                    }
                                } else {
                                    hiddenEl.value = '';
                                    if (poStatus) poStatus.classList.add('show', 'invalid');
                                    if (poFeedback) {
                                        poFeedback.className = 'st-po-feedback po-feedback st-mt-4 st-po-feedback--invalid';
                                        poFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + invalidMsg;
                                        poFeedback.style.display = 'flex';
                                    }
                                }
                            } else {
                                hiddenEl.value = '';
                                if (poStatus) poStatus.classList.add('show', 'invalid');
                                if (poFeedback) {
                                    poFeedback.className = 'st-po-feedback po-feedback st-mt-4 st-po-feedback--invalid';
                                    poFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> PO/SO not found in SAP.';
                                    poFeedback.style.display = 'flex';
                                }
                            }
                        })
                        .catch(function(err) {
                            clearPoEntryValidation(container);
                        });
                }, 500);
            }
        });

        poEntriesContainer.addEventListener('blur', function (e) {
            if (e.target.classList.contains('po-search-input')) {
                if (e.target.value.trim() === '') {
                    clearPoEntryValidation(e.target.closest('.po-entry'));
                }
            }
        }, true);
        
        if (btnAddPo) {
            var poIndex = poEntriesContainer.querySelectorAll('.po-entry').length || 1;
            btnAddPo.addEventListener('click', function() {
                var html = '<div class="po-entry st-mb-8" data-po-index="' + poIndex + '" style="margin-bottom: 8px;">' +
                    '<div style="display: flex; gap: 8px; align-items: center; position: relative;">' +
                        '<div class="st-form-field--relative" style="flex: 1; position: relative; margin-bottom: 0;">' +
                            '<input type="text" class="st-input st-input--pr-40 po-search-input" placeholder="Search PO/SO number..." autocomplete="off" required>' +
                            '<input type="hidden" name="po_number[]" class="po-number-hidden">' +
                            '<span class="st-input-loader po-loading" aria-hidden="true"></span>' +
                            '<span class="st-input-status po-status" aria-hidden="true"></span>' +
                        '</div>' +
                        '<button type="button" class="btn-remove-po" title="Delete" style="background: transparent; color: #6b7280; border: 1px solid #d1d5db; border-radius: 6px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background=\'#fee2e2\'; this.style.color=\'#ef4444\'; this.style.borderColor=\'#fca5a5\';" onmouseout="this.style.background=\'transparent\'; this.style.color=\'#6b7280\'; this.style.borderColor=\'#d1d5db\';">' +
                            '<i class="fas fa-trash-alt"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="st-po-feedback po-feedback st-mt-4" style="display:none;"></div>' +
                '</div>';
                poEntriesContainer.insertAdjacentHTML('beforeend', html);
                poIndex++;
            });
        }

        poEntriesContainer.addEventListener('click', function(e) {
            var removeBtn = e.target.closest('.btn-remove-po');
            if (removeBtn) {
                var entry = removeBtn.closest('.po-entry');
                if (entry && poEntriesContainer.querySelectorAll('.po-entry').length > 1) {
                    entry.remove();
                    var entries = poEntriesContainer.querySelectorAll('.po-entry');
                    if (entries.length > 0 && vendorNameInput) {
                        vendorNameInput.value = entries[0].dataset.poVendorName || '';
                        if (vendorSearch) {
                            vendorSearch.value = normalizeVendorText(entries[0].dataset.poVendorName || '');
                        }
                    }
                }
            }
        });
        
        var existingInputs = poEntriesContainer.querySelectorAll('.po-search-input');
        existingInputs.forEach(function(input) {
            if (input.value.trim() !== '') {
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

});

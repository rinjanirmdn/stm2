document.addEventListener('DOMContentLoaded', function() {
        var arrivalInput = document.getElementById('arrival_time_input');
        var arrivalDateInput = document.getElementById('arrival_date_input');
        var arrivalTimeInput = document.getElementById('arrival_time_only_input');
        var gateSelect = document.getElementById('unplanned-gate');
        var warehouseSelect = document.getElementById('unplanned-warehouse');

        var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

        function syncWarehouseFromGate() {
            if (!warehouseSelect || !gateSelect) return;
            var selected = gateSelect.options[gateSelect.selectedIndex];
            if (!selected) return;
            warehouseSelect.value = selected.getAttribute('data-warehouse-id') || '';
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

        function syncArrivalValue() {
            if (!arrivalInput || !arrivalDateInput || !arrivalTimeInput) return;
            var dateVal = (arrivalDateInput.value || '').trim();
            var timeVal = (arrivalTimeInput.value || '').trim();
            if (dateVal && timeVal) {
                arrivalInput.value = dateVal + ' ' + timeVal;
            } else if (dateVal) {
                arrivalInput.value = dateVal;
            } else {
                arrivalInput.value = '';
            }
        }

        function initArrivalDatepicker() {
            return;
        }

        function initArrivalTimepicker() {
            if (!arrivalTimeInput) return;
            if (arrivalTimeInput.getAttribute('data-st-timepicker') === '1') return;
            if (typeof window.mdtimepicker !== 'function') return;
            arrivalTimeInput.setAttribute('data-st-timepicker', '1');

            arrivalTimeInput.addEventListener('keydown', function (event) { event.preventDefault(); });
            arrivalTimeInput.addEventListener('paste', function (event) { event.preventDefault(); });

            window.mdtimepicker('#arrival_time_only_input', {
                format: 'hh:mm',
                is24hour: true,
                theme: 'cyan',
                hourPadding: true
            });

            arrivalTimeInput.addEventListener('change', function () {
                syncArrivalValue();
            });
        }

        initArrivalDatepicker();
        initArrivalTimepicker();

        if (gateSelect) {
            gateSelect.addEventListener('change', syncWarehouseFromGate);
            if (gateSelect.value) {
                syncWarehouseFromGate();
            }
        }

        if (arrivalInput && arrivalInput.value) {
            var parts = arrivalInput.value.split(' ');
            if (arrivalDateInput) arrivalDateInput.value = parts[0] || '';
            if (arrivalTimeInput) arrivalTimeInput.value = (parts[1] || '').slice(0, 5);
        }

        // PO/SO Validation Feedback
        var poInput = document.getElementById('po_number');
        var poSuggestions = document.getElementById('po_suggestions');
        var poLoading = document.getElementById('po_loading');
        var poStatus = document.getElementById('po_status');
        var poFeedback = document.getElementById('po_feedback');
        var vendorNameInput = document.getElementById('vendor_name');

        var routesEl = document.getElementById('slot_routes_json');
        var slotRoutes = {};
        try {
            slotRoutes = routesEl ? JSON.parse(routesEl.textContent || '{}') : {};
        } catch (e) {
            slotRoutes = {};
        }
        var urlPoSearch = slotRoutes.po_search || '';
        var urlPoDetailTemplate = slotRoutes.po_detail_template || '';

        function csrfToken() {
            var el = document.querySelector('meta[name="csrf-token"]');
            return el ? el.getAttribute('content') : '';
        }

        function getJson(url) {
            return fetch(url, {
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
            }).then(function(res) { return res.json(); });
        }

        function setPoLoading(isLoading) {
            if (!poLoading) return;
            if (isLoading) { poLoading.classList.add('show'); if (poStatus) poStatus.classList.remove('show'); }
            else { poLoading.classList.remove('show'); }
        }

        function setPoStatus(type) {
            if (!poStatus) return;
            poStatus.classList.remove('show', 'valid', 'invalid');
            if (type === 'valid' || type === 'invalid') { poStatus.classList.add('show', type); }
        }

        function clearPoFeedback() {
            setPoLoading(false); setPoStatus('');
            if (poInput) poInput.classList.remove('st-input--invalid');
            if (poFeedback) { poFeedback.style.display = 'none'; poFeedback.innerHTML = ''; poFeedback.className = 'st-po-feedback st-mt-4'; }
        }

        function showPoLoading() {
            setPoLoading(true); setPoStatus('');
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
            setPoLoading(false); setPoStatus('valid');
            if (poInput) poInput.classList.remove('st-input--invalid');
            if (poFeedback) {
                var label = getDocTypeLabel(docType);
                poFeedback.className = 'st-po-feedback st-mt-4 st-po-feedback--valid';
                poFeedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid \u2014 ' + label + ' found.';
                poFeedback.style.display = 'flex';
            }
        }

        function showPoInvalid() {
            setPoLoading(false); setPoStatus('invalid');
            if (poInput) poInput.classList.add('st-input--invalid');
            if (poFeedback) {
                poFeedback.className = 'st-po-feedback st-mt-4 st-po-feedback--invalid';
                poFeedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Invalid \u2014 PO/SO number not found in SAP. Please check and re-enter the number.';
                poFeedback.style.display = 'flex';
            }
        }

        function closePoSuggestions() {
            if (!poSuggestions) return;
            poSuggestions.style.display = 'none';
            poSuggestions.innerHTML = '';
        }

        function renderPoSuggestions(items) {
            if (!poSuggestions) return;
            if (!items || !items.length) { closePoSuggestions(); return; }
            poSuggestions.innerHTML = '';
            items.slice(0, 5).forEach(function(it) {
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
                    + '<div class="po-item__sub">' + (it.vendor_name || '') + (it.plant ? (' \u2022 ' + it.plant) : '') + '</div>';
                div.style.cssText = 'padding:6px 8px;cursor:pointer;border-bottom:1px solid #f3f4f6;';
                poSuggestions.appendChild(div);
            });
            poSuggestions.style.display = 'block';
        }

        function fetchPoDetail(poNumber, callback) {
            if (!poNumber) { callback({ success: false }); return; }
            showPoLoading();
            var url = String(urlPoDetailTemplate || '').replace('__PO__', encodeURIComponent(poNumber));
            getJson(url)
                .then(function(data) {
                    if (data && data.success && data.data) { callback({ success: true, data: data.data }); }
                    else { callback({ success: false }); }
                })
                .catch(function() { callback({ success: false }); });
        }

        if (poInput) {
            var poDebounceTimer = null;
            var poLastAutoFilledValue = '';
            var poBypassSap = document.getElementById('po_bypass_sap');

            function isBypassSap() {
                return poBypassSap && poBypassSap.checked;
            }

            var vendorNameField = document.getElementById('vendor_name');
            var vendorNameManual = document.getElementById('vendor_name_manual');

            function toggleVendorBypass() {
                if (!vendorNameField) return;
                if (isBypassSap()) {
                    vendorNameField.removeAttribute('readonly');
                    vendorNameField.placeholder = 'Type vendor name manually';
                    clearPoFeedback();
                } else {
                    vendorNameField.setAttribute('readonly', true);
                    vendorNameField.placeholder = 'Vendor will auto-fill from PO';
                    var currentPo = poInput ? (poInput.value || '').trim() : '';
                    if (currentPo.length >= 10) {
                        fetchPoDetail(currentPo, function (data) {
                            if (data.success && data.data) {
                                showPoValid(data.data.doc_type || null);
                            } else {
                                showPoInvalid();
                            }
                        });
                    }
                }
            }

            if (poBypassSap) {
                poBypassSap.addEventListener('change', toggleVendorBypass);
                toggleVendorBypass();
            }

            if (vendorNameField && vendorNameManual) {
                vendorNameField.addEventListener('input', function () {
                    if (isBypassSap()) {
                        vendorNameManual.value = vendorNameField.value;
                    }
                });
            }

            // Vendor Transporter toggle (show on outbound)
            var directionSelect = document.getElementById('direction');
            var vtContainer = document.getElementById('vendor_transporter_container');
            var vtCheckbox = document.getElementById('use_vendor_transporter');
            var vtSelectContainer = document.getElementById('vendor_transporter_select_container');

            function toggleVendorTransporter() {
                if (!vtContainer || !directionSelect) return;
                if (directionSelect.value === 'outbound') {
                    vtContainer.classList.remove('st-hidden');
                } else {
                    vtContainer.classList.add('st-hidden');
                    if (vtCheckbox) vtCheckbox.checked = false;
                    if (vtSelectContainer) vtSelectContainer.classList.add('st-hidden');
                }
            }

            if (directionSelect) {
                directionSelect.addEventListener('change', toggleVendorTransporter);
                toggleVendorTransporter();
            }

            if (vtCheckbox && vtSelectContainer) {
                vtCheckbox.addEventListener('change', function () {
                    if (vtCheckbox.checked) {
                        vtSelectContainer.classList.remove('st-hidden');
                    } else {
                        vtSelectContainer.classList.add('st-hidden');
                    }
                });
            }

            poInput.addEventListener('input', function() {
                var q = (poInput.value || '').trim();
                clearPoFeedback();
                poLastAutoFilledValue = '';

                if (isBypassSap()) {
                    closePoSuggestions();
                    return;
                }

                if (q.length < 2) {
                    if (poDebounceTimer) clearTimeout(poDebounceTimer);
                    closePoSuggestions();
                    return;
                }

                if (poDebounceTimer) clearTimeout(poDebounceTimer);
                poDebounceTimer = setTimeout(function() {
                    if (q.length >= 10) {
                        fetchPoDetail(q, function(data) {
                            if (data.success && data.data) {
                                if (vendorNameInput && data.data.vendor_name) vendorNameInput.value = data.data.vendor_name;
                                poLastAutoFilledValue = q;
                                closePoSuggestions();
                                showPoValid(data.data.doc_type || null);
                            } else {
                                poLastAutoFilledValue = '';
                                showPoInvalid();
                            }
                        });
                        return;
                    }
                    getJson(String(urlPoSearch || '') + '?q=' + encodeURIComponent(q))
                        .then(function(data) {
                            if (!data || !data.success) { closePoSuggestions(); return; }
                            renderPoSuggestions(data.data || []);
                        })
                        .catch(function() { closePoSuggestions(); });
                }, 250);
            });

            function autoFetchDetail() {
                if (isBypassSap()) {
                    clearPoFeedback();
                    return;
                }
                var val = (poInput.value || '').trim();
                if (val === '') { clearPoFeedback(); return; }
                if (val === poLastAutoFilledValue) return;

                setTimeout(function() {
                    var current = (poInput.value || '').trim();
                    if (current !== val) return;
                    if (current === poLastAutoFilledValue) return;

                    if (current.length >= 10) {
                        fetchPoDetail(current, function(data) {
                            if (data.success && data.data) {
                                if (vendorNameInput && data.data.vendor_name) vendorNameInput.value = data.data.vendor_name;
                                poLastAutoFilledValue = current;
                                showPoValid(data.data.doc_type || null);
                            } else {
                                poLastAutoFilledValue = '';
                                showPoInvalid();
                            }
                        });
                    } else {
                        poLastAutoFilledValue = '';
                        showPoInvalid();
                    }
                }, 200);
            }
            poInput.addEventListener('change', autoFetchDetail);
            poInput.addEventListener('blur', autoFetchDetail);

            // Auto-validate on page load if value exists
            if ((poInput.value || '').trim().length >= 10) {
                autoFetchDetail();
            }
        }

        if (poSuggestions) {
            poSuggestions.addEventListener('click', function(e) {
                var item = e.target.closest('.po-item');
                if (!item || !poInput) return;
                var poNumber = item.getAttribute('data-po') || '';
                poInput.value = poNumber;
                closePoSuggestions();
                fetchPoDetail(poNumber, function(data) {
                    if (data.success && data.data) {
                        if (vendorNameInput && data.data.vendor_name) vendorNameInput.value = data.data.vendor_name;
                        showPoValid(data.data.doc_type || null);
                    } else {
                        showPoInvalid();
                    }
                });
            });
        }

        document.addEventListener('click', function(e) {
            if (!poSuggestions || !poInput) return;
            if (e.target === poInput || poSuggestions.contains(e.target)) return;
            closePoSuggestions();
        });
    });

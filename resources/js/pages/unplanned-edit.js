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
        var poEntriesContainer = document.getElementById('po_entries_container');
        var btnAddPo = document.getElementById('btn_add_po');
        var poBypassSap = document.getElementById('po_bypass_sap');
        var vendorSearch = document.getElementById('vendor_name'); // In unplanned, vendor_name is the text input for vendor search/manual
        var vendorNameInput = document.getElementById('vendor_name_manual');
        var directionSelect = document.getElementById('direction');

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

        function normalizeVendorText(v) {
            return (v || '').replace(/\s+/g, ' ').trim();
        }

        var searchTimeouts = {};

        function isBypassSap() {
            return poBypassSap && poBypassSap.checked;
        }

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

        function toggleVendorBypass() {
            if (!vendorSearch) return;
            var entries = poEntriesContainer ? poEntriesContainer.querySelectorAll('.po-entry') : [];
            if (isBypassSap()) {
                vendorSearch.removeAttribute('readonly');
                vendorSearch.removeAttribute('disabled');
                vendorSearch.placeholder = 'Type vendor name manually';
                entries.forEach(function(entry) {
                    var searchInput = entry.querySelector('.po-search-input');
                    var hiddenInput = entry.querySelector('.po-number-hidden');
                    clearPoEntryValidation(entry);
                    if (searchInput && hiddenInput) hiddenInput.value = searchInput.value.trim();
                });
            } else {
                vendorSearch.setAttribute('readonly', true);
                vendorSearch.placeholder = 'Vendor will auto-fill from PO';
                entries.forEach(function(entry) {
                    clearPoEntryValidation(entry);
                });
                // Re-trigger validation for existing fields
                var existingInputs = poEntriesContainer ? poEntriesContainer.querySelectorAll('.po-search-input') : [];
                existingInputs.forEach(function(input) {
                    if (input.value.trim() !== '') {
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            }
        }

        if (poBypassSap) {
            poBypassSap.addEventListener('change', toggleVendorBypass);
        }

        if (vendorSearch && vendorNameInput) {
            vendorSearch.addEventListener('input', function () {
                if (isBypassSap()) {
                    vendorNameInput.value = vendorSearch.value;
                }
            });
        }

        // Vendor Transporter toggle (show on outbound)
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

        if (poEntriesContainer) {
            poEntriesContainer.addEventListener('input', function (e) {
                if (e.target.classList.contains('po-search-input')) {
                    var inputEl = e.target;
                    var container = inputEl.closest('.po-entry');
                    var hiddenEl = container.querySelector('.po-number-hidden');
                    var q = inputEl.value.trim();
                    var index = container.dataset.poIndex;

                    if (isBypassSap()) {
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
                                            poFeedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid \u2014 ' + (po.doc_type === 'so' ? 'SO' : 'PO') + ' found.';
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
                                                if (typeof onDirectionChanged === 'function') onDirectionChanged();
                                                toggleVendorTransporter();
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
            
            // Initial validation is handled by toggleVendorBypass() below
        }

        if (poBypassSap) {
            toggleVendorBypass();
        }
    });

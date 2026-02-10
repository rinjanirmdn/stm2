document.addEventListener('DOMContentLoaded', function () {
    // Initialize routes from JSON
    var routesEl = document.getElementById('slot_routes_json');
    var slotRoutes = {};
    try {
        slotRoutes = routesEl ? JSON.parse(routesEl.textContent || '{}') : {};
    } catch (e) {
        slotRoutes = {};
    }

    var urlPoSearch = slotRoutes.po_search || '';
    var urlPoDetailTemplate = slotRoutes.po_detail_template || '';

    // PO/DO autocomplete
    var poInput = document.getElementById('po_number');
    var poSuggestions = document.getElementById('po_suggestions');
    var poLoading = document.getElementById('po_loading');
    var vendorNameInput = document.getElementById('vendor_name');
    var directionSelect = document.getElementById('direction');
    var poDebounceTimer = null;

    function setPoLoading(isLoading) {
        if (!poLoading) return;
        if (isLoading) {
            poLoading.classList.add('show');
        } else {
            poLoading.classList.remove('show');
        }
    }

    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
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
            div.classList.add('st-suggestion-item--compact');
            poSuggestions.appendChild(div);
        });
        poSuggestions.style.display = 'block';
    }

    function fetchPoDetail(poNumber, callback) {
        if (typeof callback !== 'function') {
            callback = function () {};
        }
        if (!poNumber) {
            callback({ success: false });
            return;
        }
        setPoLoading(true);
        getJson(String(urlPoDetailTemplate || '').replace('__PO__', encodeURIComponent(poNumber)))
            .then(function (data) {
                setPoLoading(false);
                if (data && data.success && data.data) {
                    callback({ success: true, data: data.data });
                } else {
                    callback({ success: false });
                }
            })
            .catch(function () {
                setPoLoading(false);
                callback({ success: false });
            });
    }

    if (poInput) {
        poInput.addEventListener('input', function () {
            var q = (poInput.value || '').trim();
            if (vendorNameInput) vendorNameInput.value = '';
            if (q.length > 10) {
                q = q.slice(0, 10);
                poInput.value = q;
            }

            if (poDebounceTimer) clearTimeout(poDebounceTimer);
            poDebounceTimer = setTimeout(function () {
                setPoLoading(true);
                getJson(String(urlPoSearch || '') + '?q=' + encodeURIComponent(q))
                    .then(function (data) {
                        setPoLoading(false);
                        if (!data || !data.success) {
                            closePoSuggestions();
                            return;
                        }
                        renderPoSuggestions(data.data || []);
                    })
                    .catch(function () {
                        setPoLoading(false);
                        closePoSuggestions();
                    });
            }, 250);
        });

        poInput.addEventListener('focus', function () {
            var q = (poInput.value || '').trim();
            if (q.length < 3) return;
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

        function autoFetchDetail() {
            var val = (poInput.value || '').trim();
            if (val.length >= 5) {
                setTimeout(function () {
                    fetchPoDetail(val, function (data) {
                        if (data.success && data.data) {
                            if (vendorNameInput) {
                                vendorNameInput.value = data.data.vendor_name || '';
                            }
                            if (data.data.direction && directionSelect) {
                                directionSelect.value = data.data.direction;
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
            if (!item) return;
            var po = item.getAttribute('data-po');
            if (poInput) poInput.value = po;
            closePoSuggestions();
            fetchPoDetail(po, function (data) {
                if (data.success && data.data) {
                    if (vendorNameInput) {
                        vendorNameInput.value = data.data.vendor_name || '';
                    }
                    if (data.data.direction && directionSelect) {
                        directionSelect.value = data.data.direction;
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

    var warehouseSelect = document.getElementById('unplanned-warehouse');
    var gateSelect = document.getElementById('unplanned-gate');
    var arrivalInput = document.getElementById('actual_arrival_input');
    var arrivalDateInput = document.getElementById('actual_arrival_date_input');
    var arrivalTimeInput = document.getElementById('actual_arrival_time_input');

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

    function applyDatepickerTooltips(inst, holidayData) {
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

        window.mdtimepicker('#actual_arrival_time_input', {
            format: 'hh:mm',
            is24hour: true,
            theme: 'cyan',
            hourPadding: true
        });

        arrivalTimeInput.addEventListener('change', function () {
            syncArrivalValue();
        });
    }

    function syncWarehouseFromGate() {
        if (!warehouseSelect || !gateSelect) return;
        var selected = gateSelect.options[gateSelect.selectedIndex];
        if (!selected) return;
        warehouseSelect.value = selected.getAttribute('data-warehouse-id') || '';
    }

    if (gateSelect) {
        gateSelect.addEventListener('change', function () {
            syncWarehouseFromGate();
            var enabled = !!(gateSelect.value || '').trim();
            if (arrivalDateInput) arrivalDateInput.disabled = !enabled;
            if (arrivalTimeInput) arrivalTimeInput.disabled = !enabled;
            if (enabled) {
                initArrivalDatepicker();
                initArrivalTimepicker();
            }
        });
    }

    if (arrivalInput && arrivalInput.value) {
        var parts = arrivalInput.value.split(' ');
        if (arrivalDateInput) arrivalDateInput.value = parts[0] || '';
        if (arrivalTimeInput) arrivalTimeInput.value = (parts[1] || '').slice(0, 5);
    }
});

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

    var oldPoItems = {};
    try {
        var oldPoItemsEl = document.getElementById('old_po_items_json');
        oldPoItems = oldPoItemsEl ? JSON.parse(oldPoItemsEl.textContent || '{}') : {};
    } catch (e) {
        oldPoItems = {};
    }

    // PO/DO autocomplete
    var poInput = document.getElementById('po_number');
    var poSuggestions = document.getElementById('po_suggestions');
    var poPreview = document.getElementById('po_preview');
    var poItemsGroup = document.getElementById('po_items_group');
    var directionSelect = document.getElementById('direction');
    var poDebounceTimer = null;

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
        poItemsGroup.style.display = 'none';
        poItemsGroup.innerHTML = '';
        if (!po || !Array.isArray(po.items) || po.items.length === 0) {
            return;
        }

        var items = po.items;
        var html = '';
        html += '<div class="st-font-semibold st-mb-6">PO Items & Quantity for This Unplanned (Optional)</div>';
        html += '<div class="st-table-wrapper st-table-wrapper--mt-6">';
        html += '<table class="st-table st-table--sm">';
        html += '<thead><tr>'
            + '<th class="st-table-col-70">Item</th>'
            + '<th>Material</th>'
            + '<th class="st-table-col-120 st-text-right">Qty PO</th>'
            + '<th class="st-table-col-120 st-text-right">Booked</th>'
            + '<th class="st-table-col-120 st-text-right">Remaining</th>'
            + '<th class="st-table-col-160">Qty This Unplanned</th>'
            + '</tr></thead><tbody>';

        items.forEach(function (it) {
            if (!it) return;
            var itemNo = String(it.item_no || '').trim();
            if (!itemNo) return;
            var mat = String(it.material || '').trim();
            var desc = String(it.description || '').trim();
            var uom = String(it.uom || '').trim();
            var qtyPo = (it.qty !== undefined && it.qty !== null) ? String(it.qty) : '-';
            var qtyBooked = (it.qty_booked !== undefined && it.qty_booked !== null) ? String(it.qty_booked) : '0';
            var remaining = (it.remaining_qty !== undefined && it.remaining_qty !== null) ? String(it.remaining_qty) : '-';

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
            html += '<td class="st-text-right">' + qtyBooked + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td class="st-text-right"><strong>' + remaining + '</strong>' + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td>';
            html += '<input type="number" step="0.001" min="0" name="po_items[' + itemNo + '][qty]" class="st-input st-input--maxw-140" value="' + (oldQty || '') + '" placeholder="0">';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        html += '<div class="st-text--small st-text--muted st-mt-6">Opsional: isi qty jika ingin tercatat sebagai pengiriman untuk PO ini.</div>';

        poItemsGroup.innerHTML = html;
        poItemsGroup.style.display = 'block';
    }

    function fetchPoDetail(poNumber, callback) {
        if (!poNumber) {
            callback({ success: false });
            return;
        }
        getJson(String(urlPoDetailTemplate || '').replace('__PO__', encodeURIComponent(poNumber)))
            .then(function (data) {
                if (data && data.success && data.data) {
                    callback({ success: true, data: data.data });
                } else {
                    callback({ success: false });
                }
            })
            .catch(function () { callback({ success: false }); });
    }

    if (poInput) {
        poInput.addEventListener('input', function () {
            var q = (poInput.value || '').trim();
            if (q.length > 10) {
                q = q.slice(0, 10);
                poInput.value = q;
            }

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
                            setPoPreview(data.data);
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
                    setPoPreview(data.data);
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
        clearPoSuggestions();
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

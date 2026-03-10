window.getIndonesiaHolidays = window.getIndonesiaHolidays || function () {
    try {
        var el = document.getElementById('indonesia_holidays_global');
        if (!el) return {};
        return JSON.parse(el.textContent || '{}');
    } catch (e) {
        return {};
    }
};

function stReadJson(id, fallback) {
    try {
        var el = document.getElementById(id);
        if (!el) return fallback;
        return JSON.parse(el.textContent || '{}') || fallback;
    } catch (e) {
        return fallback;
    }
}

function stGetVendorConfig() {
    return stReadJson('st-vendor-config', {});
}

function bootVendorDashboardDateRange() {
    // Same UI as My Bookings: jQuery daterangepicker w/ presets
    var rangePicker = document.getElementById('vd_reportrange');
    if (!rangePicker) return;

    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    // Prevent double boot
    if (rangePicker.getAttribute('data-st-daterange-boot') === '1') return;
    rangePicker.setAttribute('data-st-daterange-boot', '1');

    function dateClassForMoment(m) {
        if (!m) return '';
        var classes = [];
        try {
            if (typeof m.day === 'function' && m.day() === 0) classes.push('drp-sunday');
            if (holidayData) {
                var iso = (typeof m.format === 'function') ? m.format('YYYY-MM-DD') : '';
                if (iso && holidayData[iso]) classes.push('drp-holiday');
            }
        } catch (e) { }
        return classes.join(' ');
    }

    function resolvePickerCellIso(cellEl) {
        if (!cellEl || !window.jQuery || typeof window.moment !== 'function') return '';
        var cell = window.jQuery(cellEl);
        var day = parseInt(cell.text().trim(), 10);
        if (!Number.isFinite(day)) return '';

        var monthText = cell.closest('table').find('.month').first().text().trim();
        if (!monthText) return '';

        var baseMonth = window.moment(monthText, ['MMM YYYY', 'MMMM YYYY'], true);
        if (!baseMonth.isValid()) baseMonth = window.moment(monthText, ['MMM YYYY', 'MMMM YYYY'], false);
        if (!baseMonth.isValid()) return '';

        var month = baseMonth.month();
        var year = baseMonth.year();
        if (cell.hasClass('off')) {
            if (day >= 20) month -= 1;
            else month += 1;
            if (month < 0) { month = 11; year -= 1; }
            if (month > 11) { month = 0; year += 1; }
        }

        return String(year) + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    }

    function decoratePickerDays(picker) {
        if (!picker || !picker.container || !window.jQuery) return;
        picker.container.find('td.available').each(function () {
            var td = window.jQuery(this);
            var hasSunday = td.hasClass('drp-sunday');
            var hasHoliday = td.hasClass('drp-holiday');
            var tooltipText = '';

            if (hasHoliday) {
                var iso = resolvePickerCellIso(this);
                tooltipText = (iso && holidayData && holidayData[iso]) ? holidayData[iso] : 'Holiday';
            } else if (hasSunday) {
                tooltipText = 'Sunday';
            }

            if (tooltipText) {
                td.attr('data-tooltip', tooltipText);
                td.find('a, span').attr('data-tooltip', tooltipText);
            } else {
                td.removeAttr('data-tooltip');
                td.find('a, span').removeAttr('data-tooltip');
            }
        });
    }

    function bindPickerDecorators($el) {
        if (!$el || !$el.length) return;
        $el.off('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo');
        $el.on('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo', function (_ev, picker) {
            decoratePickerDays(picker);
        });
        var picker = $el.data('daterangepicker');
        if (picker) {
            setTimeout(function () { decoratePickerDays(picker); }, 0);
        }
    }

    function initDashboardDateRange() {
        var rangePicker = document.getElementById('vd_reportrange');
        var rangeStart = document.getElementById('vd-range-start');
        var rangeEnd = document.getElementById('vd-range-end');
        var dateRange = document.getElementById('vd-date-range');

        if (!rangePicker || !rangeStart || !rangeEnd) {
            return;
        }

        // Prevent double-init
        if (rangePicker.getAttribute('data-st-daterange-init') === '1') {
            return;
        }

        function depsReady() {
            return !!(
                window.jQuery &&
                window.jQuery.fn &&
                typeof window.jQuery.fn.daterangepicker === 'function' &&
                window.moment
            );
        }

        if (!depsReady()) {
            var attempts = parseInt(rangePicker.getAttribute('data-st-daterange-attempts') || '0', 10);
            if (attempts >= 100) {
                return;
            }
            rangePicker.setAttribute('data-st-daterange-attempts', String(attempts + 1));
            setTimeout(initDashboardDateRange, 50);
            return;
        }

        var $ = window.jQuery;
        var moment = window.moment;

        var startDate = moment();
        var endDate = moment();
        var hasInitial = false;

        if (rangeStart.value && moment(rangeStart.value, 'YYYY-MM-DD').isValid()) {
            startDate = moment(rangeStart.value, 'YYYY-MM-DD');
            hasInitial = true;
        }
        if (rangeEnd.value && moment(rangeEnd.value, 'YYYY-MM-DD').isValid()) {
            endDate = moment(rangeEnd.value, 'YYYY-MM-DD');
            hasInitial = true;
        }

        function updateRange(s, e) {
            $(rangePicker).find('span').first().html(s.format('DD-MM-YYYY') + ' - ' + e.format('DD-MM-YYYY'));
            rangeStart.value = s.format('YYYY-MM-DD');
            rangeEnd.value = e.format('YYYY-MM-DD');
        }

        try {
            rangePicker.setAttribute('data-st-daterange-init', '1');

            $(rangePicker).daterangepicker({
                startDate: startDate,
                endDate: endDate,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                locale: { format: 'DD-MM-YYYY' },
                alwaysShowCalendars: true,
                opens: 'left',
                isCustomDate: dateClassForMoment
            }, function (s, e, label) {
                updateRange(s, e);
                if (dateRange) {
                    var presetMap = {
                        'Today': 'today',
                        'Yesterday': 'yesterday',
                        'Last 7 Days': 'last_7_days',
                        'Last 30 Days': 'last_30_days',
                        'This Month': 'this_month',
                        'Last Month': 'last_month'
                    };
                    dateRange.value = presetMap[label] || 'custom';
                }
            });

            bindPickerDecorators($(rangePicker));

            if (hasInitial) {
                updateRange(startDate, endDate);
            } else {
                $(rangePicker).find('span').first().html('Select range');
            }

            // If user clicked before init completed, open immediately
            if (rangePicker.getAttribute('data-st-daterange-open') === '1') {
                rangePicker.removeAttribute('data-st-daterange-open');
                var drp = window.jQuery(rangePicker).data('daterangepicker');
                if (drp && typeof drp.show === 'function') drp.show();
            }
        } catch (error) {
        }
    }

    function openPicker() {
        if (rangePicker.getAttribute('data-st-daterange-init') !== '1') {
            rangePicker.setAttribute('data-st-daterange-open', '1');
            initDashboardDateRange();
            return;
        }

        try {
            var drp = window.jQuery(rangePicker).data('daterangepicker');
            if (drp && typeof drp.show === 'function') drp.show();
        } catch (ex) { }
    }

    var lastToggleAt = 0;
    function showPicker() {
        var now = Date.now();
        if (now - lastToggleAt < 250) return;
        lastToggleAt = now;

        if (rangePicker.getAttribute('data-st-daterange-init') !== '1') {
            rangePicker.setAttribute('data-st-daterange-open', '1');
            initDashboardDateRange();
            return;
        }

        try {
            var drp = window.jQuery(rangePicker).data('daterangepicker');
            if (!drp) return;
            if (drp.isShowing) return;
            if (typeof drp.show === 'function') drp.show();
        } catch (ex) { }
    }

    rangePicker.addEventListener('click', function (e) {
        // Prevent the same click from being treated as an outside-click by the plugin
        try {
            e.preventDefault();
            e.stopPropagation();
        } catch (ex) { }
        setTimeout(showPicker, 0);
    });

    rangePicker.addEventListener('mousedown', function (e) {
        // Prevent double-trigger (mousedown + click) from causing blink
        try {
            e.preventDefault();
            e.stopPropagation();
        } catch (ex) { }
    });

    rangePicker.addEventListener('touchstart', function (e) {
        try {
            e.stopPropagation();
        } catch (ex) { }
        setTimeout(showPicker, 0);
    }, { passive: true });

    rangePicker.style.pointerEvents = 'auto';

    // Backward compatibility: some browsers may swallow click on nested elements
    try {
        var left = rangePicker.querySelector('.date-range-input__left');
        if (left) {
            left.addEventListener('click', function (e) {
                try { e.preventDefault(); e.stopPropagation(); } catch (ex) { }
                setTimeout(showPicker, 0);
            });
            left.addEventListener('mousedown', function (e) {
                try { e.preventDefault(); e.stopPropagation(); } catch (ex) { }
                setTimeout(showPicker, 0);
            });
        }
        var span = rangePicker.querySelector('span');
        if (span) {
            span.addEventListener('click', function (e) {
                try { e.preventDefault(); e.stopPropagation(); } catch (ex) { }
                setTimeout(showPicker, 0);
            });
            span.addEventListener('mousedown', function (e) {
                try { e.preventDefault(); e.stopPropagation(); } catch (ex) { }
                setTimeout(showPicker, 0);
            });
        }
    } catch (e) { }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboardDateRange);
    } else {
        initDashboardDateRange();
    }
    window.addEventListener('load', initDashboardDateRange);
    setTimeout(initDashboardDateRange, 250);
    setTimeout(initDashboardDateRange, 750);
    setTimeout(initDashboardDateRange, 1500);
}

function initVendorDashboardCharts() {
    const statusMount = document.getElementById('vendor-status-overview-react');
    const ontimeMount = document.getElementById('vendor-ontime-react');
    const hasAny = !!statusMount || !!ontimeMount;
    if (!hasAny) return;

    function cssVar(name, fallback) {
        try {
            const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            return v || fallback;
        } catch (e) {
            return fallback;
        }
    }

    function clamp(n, min, max) {
        const x = Number(n);
        if (!Number.isFinite(x)) return min;
        return Math.min(max, Math.max(min, x));
    }

    if (statusMount) {
        const raw = stReadJson('vendor-status-overview-data', {});
        const s = (raw && raw.stats) ? raw.stats : {};
        const items = [
            { key: 'scheduled', label: 'Scheduled', value: +(s.scheduled || 0), color: cssVar('--scheduled', '#0ea5e9') },
            { key: 'waiting', label: 'Waiting', value: +(s.waiting || 0), color: cssVar('--waiting', '#f59e0b') },
            { key: 'in_progress', label: 'In Progress', value: +(s.in_progress || 0), color: cssVar('--in-progress', '#8b5cf6') },
            { key: 'completed', label: 'Completed', value: +(s.completed || 0), color: cssVar('--completed', '#10b981') },
        ];

        const maxV = Math.max(1, ...items.map((i) => i.value));
        const wrap = document.createElement('div');
        wrap.className = 'vd-mini-bar';

        items.forEach((it) => {
            const col = document.createElement('div');
            col.className = 'vd-mini-bar__col';

            const val = document.createElement('div');
            val.className = 'vd-mini-bar__val';
            val.textContent = String(it.value);
            val.style.color = it.color;

            const barWrap = document.createElement('div');
            barWrap.className = 'vd-mini-bar__barwrap';

            const bar = document.createElement('div');
            bar.className = 'vd-mini-bar__bar';
            bar.style.backgroundColor = it.color;
            bar.style.height = clamp((it.value / maxV) * 100, 4, 100) + '%';

            const label = document.createElement('div');
            label.className = 'vd-mini-bar__label';
            label.textContent = it.label;

            barWrap.appendChild(bar);
            col.appendChild(val);
            col.appendChild(barWrap);
            col.appendChild(label);
            wrap.appendChild(col);
        });

        statusMount.innerHTML = '';
        statusMount.appendChild(wrap);
    }

    if (ontimeMount) {
        const perf = stReadJson('vendor-ontime-data', {});
        const onTime = +(perf.on_time || 0);
        const late = +(perf.late || 0);
        const total = onTime + late;
        const pct = total > 0 ? (onTime / total) * 100 : 0;

        const onClr = cssVar('--completed', '#10b981');
        const lateClr = cssVar('--cancelled', '#ef4444');

        const wrap = document.createElement('div');
        wrap.className = 'vd-donut';

        const donut = document.createElement('div');
        donut.className = 'vd-donut__ring';
        donut.style.background = `conic-gradient(${onClr} 0 ${pct}%, ${lateClr} ${pct}% 100%)`;

        const center = document.createElement('div');
        center.className = 'vd-donut__center';
        center.innerHTML = `<div class="vd-donut__pct">${pct.toFixed(1)}%</div><div class="vd-donut__sub">On Time</div>`;

        donut.appendChild(center);

        const legend = document.createElement('div');
        legend.className = 'vd-donut__legend';
        legend.innerHTML = `
            <div class="vd-donut__li"><span class="vd-donut__dot" style="background:${onClr}"></span><span class="vd-donut__txt">On Time</span><span class="vd-donut__num">${onTime}</span></div>
            <div class="vd-donut__li"><span class="vd-donut__dot" style="background:${lateClr}"></span><span class="vd-donut__txt">Late</span><span class="vd-donut__num">${late}</span></div>
            <div class="vd-donut__li"><span class="vd-donut__dot" style="background:${cssVar('--text-secondary', '#94a3b8')}"></span><span class="vd-donut__txt">Total</span><span class="vd-donut__num">${total}</span></div>
        `;

        wrap.appendChild(donut);
        wrap.appendChild(legend);

        ontimeMount.innerHTML = '';
        ontimeMount.appendChild(wrap);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const bookingConfig = window.vendorBookingCreateConfig || stReadJson('vendor_booking_create_config', null);
    const availabilityConfig = window.vendorAvailabilityConfig || stReadJson('vendor_availability_config', null);

    if (bookingConfig) {
        initVendorBookingCreate(bookingConfig);
    }

    if (availabilityConfig) {
        initVendorAvailability(availabilityConfig);
    }

    bootVendorDashboardDateRange();
    initVendorNotifications();
    initVendorHeaderUserMenu();

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.vendor-alert--autodismiss').forEach(function (alert) {
        setTimeout(function () {
            alert.remove();
        }, 5000);
    });
});

function initVendorHeaderUserMenu() {
    const menuBtn = document.getElementById('vendor-user-menu-btn');
    const menu = document.getElementById('vendor-user-menu');
    const notifProxy = document.getElementById('vendor-user-menu-notif');
    const notifDropdown = document.getElementById('notification-dropdown');
    if (!menuBtn || !menu) return;

    function closeMenu() {
        menu.classList.remove('vendor-header__user-menu--open');
    }

    menuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.classList.toggle('vendor-header__user-menu--open');
    });

    if (notifProxy && notifDropdown) {
        notifProxy.addEventListener('click', function (e) {
            e.stopPropagation();
            // Toggle the same dropdown used by the main notification bell
            notifDropdown.classList.toggle('show');
        });
    }

    document.addEventListener('click', function (e) {
        if (!menu.classList.contains('vendor-header__user-menu--open')) return;
        if (menu.contains(e.target) || menuBtn.contains(e.target)) return;
        closeMenu();
    });
}

function initVendorBookingCreate(config) {
    const poSearchUrl = config.poSearchUrl || '';
    const poDetailUrl = config.poDetailUrl || '';
    const availableSlotsUrl = config.availableSlotsUrl || '';

    const poSearch = document.getElementById('po-search');
    const poHidden = document.getElementById('po-number-hidden');
    const poStatus = document.getElementById('po-status');
    const poMessage = document.getElementById('po-message');

    const plannedDate = document.getElementById('planned-date');
    const plannedTime = document.getElementById('planned-time');
    const plannedDurationInput = document.getElementById('planned-duration');
    const plannedStartInput = document.getElementById('planned-start');
    const truckTypeSelect = document.getElementById('truck-type-select');
    const miniAvailability = document.getElementById('mini-availability');
    const submitBtn = document.getElementById('submit-btn');
    const submitWarning = document.getElementById('submit-warning');
    const availabilityWarning = document.getElementById('availability-warning');
    const holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    if (!poSearch || !plannedDate || !plannedTime) {
        return;
    }

    let searchTimeout = null;

    // Function to show loading state
    function showLoading() {
        poStatus.classList.remove('show', 'valid', 'invalid');
        poMessage.classList.remove('valid', 'invalid');
        poMessage.textContent = 'Checking...';
    }

    // Function to show valid state
    function showValid(message) {
        poStatus.classList.remove('show', 'invalid');
        poStatus.classList.add('show', 'valid');
        poMessage.classList.remove('invalid');
        poMessage.classList.add('valid');
        poMessage.textContent = message || 'Data valid';
    }

    // Function to show invalid state
    function showInvalid(message) {
        poStatus.classList.remove('show', 'valid');
        poStatus.classList.add('show', 'invalid');
        poMessage.classList.remove('valid');
        poMessage.classList.add('invalid');
        poMessage.textContent = message || 'PO number not found / Invalid data';
    }

    // Function to clear validation
    function clearValidation() {
        poStatus.classList.remove('show', 'valid', 'invalid');
        poMessage.classList.remove('valid', 'invalid');
        poMessage.textContent = '';
        poHidden.value = '';
    }

    poSearch.addEventListener('input', function () {
        const q = this.value.trim();

        if (q.length < 2) {
            clearValidation();
            return;
        }

        clearTimeout(searchTimeout);
        showLoading();

        searchTimeout = setTimeout(function () {
            fetch(poSearchUrl + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        // PO found - valid
                        const po = data.data[0];
                        poSearch.value = po.po_number;
                        poHidden.value = po.po_number;
                        showValid('Data valid');
                    } else {
                        // PO not found - invalid
                        showInvalid('PO number not found / Invalid data');
                    }
                })
                .catch(err => {
                    console.error('Search error:', err);
                    showInvalid('Error checking PO number');
                });
        }, 500);
    });

    // Clear validation when user clears the input
    poSearch.addEventListener('blur', function () {
        if (this.value.trim() === '') {
            clearValidation();
        }
    });

    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function (e) {
            const alertBox = document.getElementById('booking-form-alert');
            if (alertBox) {
                alertBox.hidden = true;
                alertBox.textContent = '';
            }

            const poNumber = poHidden.value.trim();
            if (!poNumber) {
                e.preventDefault();
                if (alertBox) {
                    alertBox.textContent = 'Mohon lengkapi data: pilih PO/DO number.';
                    alertBox.hidden = false;
                }
                poSearch.focus();
                return false;
            }

            const requiredFields = [
                {
                    el: plannedDate,
                    value: (plannedDate.value || '').trim(),
                    message: 'Please select a date'
                },
                {
                    el: plannedTime,
                    value: (plannedTime.value || '').trim(),
                    message: 'Please select a time'
                },
                {
                    el: truckTypeSelect,
                    value: (truckTypeSelect && truckTypeSelect.value ? truckTypeSelect.value : '').trim(),
                    message: 'Please select a truck type'
                },
                {
                    el: bookingForm.querySelector('input[name="vehicle_number"]'),
                    value: ((bookingForm.querySelector('input[name="vehicle_number"]') || {}).value || '').trim(),
                    message: 'Please fill vehicle number'
                }
            ];

            for (let i = 0; i < requiredFields.length; i++) {
                const f = requiredFields[i];
                if (!f.value) {
                    e.preventDefault();
                    if (alertBox) {
                        alertBox.textContent = 'Mohon lengkapi data: ' + f.message;
                        alertBox.hidden = false;
                    }
                    if (f.el && typeof f.el.focus === 'function') {
                        f.el.focus();
                    }
                    return false;
                }
            }

            // Check if selected time is available
            const selectedTime = plannedTime.value.trim();
            if (selectedTime && miniAvailability) {
                const selectedSlot = miniAvailability.querySelector('.cb-availability-mini__slot.selected');
                if (selectedSlot && selectedSlot.classList.contains('unavailable')) {
                    e.preventDefault();
                    if (alertBox) {
                        alertBox.textContent = 'Selected time is not available. Please choose a different time slot.';
                        alertBox.hidden = false;
                    }
                    plannedTime.focus();
                    return false;
                }
            }
        });
    }

    function loadMiniAvailability() {
        if (!miniAvailability) return;

        // Check if truck type is selected
        if (!truckTypeSelect || !truckTypeSelect.value) {
            miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Select truck type first, then date to see available hours</div>';
            return;
        }

        var rawDate = plannedDate.dataset.isoValue || plannedDate.value || '';
        // Normalize DD-MM-YYYY to YYYY-MM-DD if needed
        if (rawDate && rawDate.indexOf('-') === 2) {
            var dp = rawDate.split('-');
            rawDate = dp[2] + '-' + dp[1] + '-' + dp[0];
            plannedDate.dataset.isoValue = rawDate;
        }
        const date = rawDate;
        if (!date) {
            miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Select date to see available hours</div>';
            return;
        }

        miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder"><i class="fas fa-spinner fa-spin"></i> Loading availability...</div>';

        var plannedDuration = (plannedDurationInput && plannedDurationInput.value) ? String(plannedDurationInput.value).trim() : '';
        if (!plannedDuration) {
            plannedDuration = '60';
        }

        fetch(availableSlotsUrl + '?date=' + encodeURIComponent(date) + '&planned_duration=' + encodeURIComponent(plannedDuration), {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.slots) {
                    miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Failed to load availability</div>';
                    return;
                }

                // Check if selected time is available
                const selectedTime = plannedTime.value;
                let selectedSlotAvailable = false;
                let selectedSlotGates = 0;

                if (selectedTime) {
                    const selectedSlot = data.slots.find(s => s.time === selectedTime);
                    if (selectedSlot) {
                        selectedSlotAvailable = selectedSlot.is_available;
                        selectedSlotGates = selectedSlot.available_gates;
                    }
                }

                // Render availability with status
                renderMiniAvailability(data.slots, selectedTime, selectedSlotAvailable, selectedSlotGates);
            })
            .catch(() => {
                miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Error loading availability</div>';
            });
    }

    function renderMiniAvailability(slots, selectedTime, isAvailable, availableGates) {
        let html = '<div class="cb-availability-mini__grid">';

        slots.forEach(slot => {
            const isSelected = slot.time === selectedTime;
            const statusClass = slot.is_available ? 'available' : 'unavailable';
            const selectedClass = isSelected ? 'selected' : '';

            html += `<div class="cb-availability-mini__slot ${statusClass} ${selectedClass}" data-time="${slot.time}">
                <div class="cb-availability-mini__time">${slot.time}</div>
                <div class="cb-availability-mini__status">
                    ${slot.is_available ?
                    '<i class="fas fa-check-circle"></i> Available' :
                    '<i class="fas fa-times-circle"></i> Unavailable'
                }
                </div>
            </div>`;
        });

        html += '</div>';

        miniAvailability.innerHTML = html;

        // Add click handlers for slots
        miniAvailability.querySelectorAll('.cb-availability-mini__slot').forEach(slotEl => {
            slotEl.addEventListener('click', function () {
                const time = this.dataset.time;
                if (plannedTime) {
                    plannedTime.value = time;
                    syncPlannedStart();
                    loadMiniAvailability(); // Refresh to show updated selection
                }
            });
        });
    }

    function syncPlannedDuration() {
        if (!plannedDurationInput || !truckTypeSelect) return;
        const selected = truckTypeSelect.options[truckTypeSelect.selectedIndex];
        const duration = selected ? selected.getAttribute('data-duration') : '';
        const durationValue = duration ? parseInt(duration) : 60;

        plannedDurationInput.value = durationValue;

        // Update form state
        updateFormState();
    }

    function updateFormState() {
        const hasTruckType = truckTypeSelect && truckTypeSelect.value;
        const hasDate = plannedDate && plannedDate.value;
        const hasTime = plannedTime && plannedTime.value;
        const canSubmit = hasTruckType && hasDate && hasTime;

        if (submitBtn) {
            submitBtn.disabled = !canSubmit;
        }

        if (submitWarning) {
            submitWarning.hidden = canSubmit;
        }

        if (availabilityWarning) {
            availabilityWarning.hidden = hasTruckType;
        }

        // Update mini availability placeholder
        if (miniAvailability && !hasTruckType) {
            miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Select truck type first, then date to see available hours</div>';
        }
    }

    function syncPlannedStart() {
        if (!plannedStartInput) return;
        const date = (plannedDate.dataset.isoValue || plannedDate.value || '').trim();
        const time = (plannedTime.value || '').trim();
        plannedStartInput.value = date && time ? date + ' ' + time : (date || '');
    }

    function toIsoDate(date) {
        if (!date) return '';
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function toDisplayDate(date) {
        if (!date) return '';
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();
        return `${d}-${m}-${y}`;
    }

    // ── Vendor toast helper ──
    function showVendorToast(message, type) {
        var existing = document.getElementById('vendor-picker-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.id = 'vendor-picker-toast';
        toast.className = 'vendor-picker-toast vendor-picker-toast--' + (type || 'warning');
        toast.innerHTML = '<i class="fas ' + (type === 'info' ? 'fa-info-circle' : 'fa-exclamation-triangle') + '"></i> ' + message;
        document.body.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('vendor-picker-toast--visible'); });
        setTimeout(function () {
            toast.classList.remove('vendor-picker-toast--visible');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    // ── Datepicker: daterangepicker (singleDatePicker) — identical to admin ──
    function annotateDaterangepickerDays(picker) {
        if (!picker || !picker.container) return;
        var tooltip = document.getElementById('vendor-datepicker-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'vendor-datepicker-tooltip';
            tooltip.className = 'vendor-datepicker-tooltip';
            document.body.appendChild(tooltip);
        }
        var hideTimer = null;
        picker.container.find('td.available').each(function () {
            var td = this;
            var cls = td.className || '';
            var text = '';
            if (cls.indexOf('drp-sunday') !== -1) text = 'Sunday';
            if (cls.indexOf('drp-holiday') !== -1) {
                var dataTitle = td.getAttribute('data-holiday-name');
                text = dataTitle || 'Holiday';
            }
            if (!text) return;
            td.addEventListener('mouseenter', function (ev) {
                if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
                tooltip.textContent = text;
                tooltip.classList.add('vendor-datepicker-tooltip--visible');
                tooltip.style.left = (ev.clientX + 12) + 'px';
                tooltip.style.top = (ev.clientY + 12) + 'px';
            });
            td.addEventListener('mousemove', function (ev) {
                if (!tooltip.classList.contains('vendor-datepicker-tooltip--visible')) return;
                tooltip.style.left = (ev.clientX + 12) + 'px';
                tooltip.style.top = (ev.clientY + 12) + 'px';
            });
            td.addEventListener('mouseleave', function () {
                hideTimer = setTimeout(function () {
                    tooltip.classList.remove('vendor-datepicker-tooltip--visible');
                }, 150);
            });
        });
    }

    function initVendorDatepicker() {
        if (plannedDate.getAttribute('data-st-datepicker') === '1') return;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.daterangepicker !== 'function') return;
        plannedDate.setAttribute('data-st-datepicker', '1');

        try { plannedDate.type = 'text'; } catch (e) { }
        plannedDate.setAttribute('readonly', 'readonly');

        var initialIso = plannedDate.value || '';
        if (initialIso && initialIso.indexOf('-') === 4) {
            var p = initialIso.split('-');
            plannedDate.value = p[2] + '-' + p[1] + '-' + p[0];
            plannedDate.dataset.isoValue = initialIso;
        }

        var startDate = initialIso && window.moment(initialIso, 'YYYY-MM-DD').isValid()
            ? window.moment(initialIso, 'YYYY-MM-DD')
            : window.moment();

        // Always set isoValue so loadMiniAvailability works on first call
        plannedDate.dataset.isoValue = startDate.format('YYYY-MM-DD');
        plannedDate.value = startDate.format('DD-MM-YYYY');

        window.jQuery(plannedDate).daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoApply: true,
            minDate: window.moment(),
            locale: { format: 'DD-MM-YYYY' },
            minYear: parseInt(window.moment().format('YYYY'), 10),
            maxYear: parseInt(window.moment().format('YYYY'), 10) + 2,
            startDate: startDate,
            isCustomDate: function (date) {
                var ds = date.format('YYYY-MM-DD');
                var isSunday = date.day() === 0;
                var isHoliday = holidayData[ds] || null;
                var cls = [];
                if (isSunday) cls.push('drp-sunday');
                if (isHoliday) cls.push('drp-holiday');
                return cls.length ? cls.join(' ') : '';
            }
        }, function (start) {
            var iso = start.format('YYYY-MM-DD');
            plannedDate.value = start.format('DD-MM-YYYY');
            plannedDate.dataset.isoValue = iso;

            var ds = iso;
            if (start.day() === 0) {
                showVendorToast('The selected date is Sunday', 'warning');
            } else if (holidayData[ds]) {
                showVendorToast('The selected date is a holiday: ' + holidayData[ds], 'warning');
            }

            syncPlannedStart();
            loadMiniAvailability();
        });

        // Inject holiday name data attributes + tooltips when picker opens
        window.jQuery(plannedDate).on('show.daterangepicker', function (ev, picker) {
            picker.container.find('td.drp-holiday').each(function () {
                var td = window.jQuery(this);
                var row = td.closest('tr');
                var table = row.closest('table');
                var monthEl = table.find('.month');
                if (!monthEl.length) return;
                var monthText = monthEl.text().trim();
                var parts = monthText.split(' ');
                if (parts.length < 2) return;
                var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                var mi = monthNames.indexOf(parts[0]);
                var yr = parseInt(parts[1], 10);
                if (mi < 0 || isNaN(yr)) return;
                var day = parseInt(td.text().trim(), 10);
                if (isNaN(day)) return;
                var ds = yr + '-' + String(mi + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var name = holidayData[ds] || '';
                if (name) td.attr('data-holiday-name', name);
            });
            annotateDaterangepickerDays(picker);
        });
    }

    // ── Timepicker with vendor rules ──
    var lastValidTime = plannedTime.value || '08:00';

    function getMinAllowedHour() {
        // 4 hours from now
        var now = new Date();
        now.setSeconds(0, 0);
        now.setTime(now.getTime() + 4 * 60 * 60 * 1000);
        return now;
    }

    function isTimeValid(timeStr) {
        if (!timeStr) return false;
        var parts = timeStr.split(':');
        var h = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        if (isNaN(h) || isNaN(m)) return false;

        // Rule: max 19:00
        if (h > 19 || (h === 19 && m > 0)) return false;
        // Rule: min 07:00
        if (h < 7) return false;

        // Rule: 4 hours from now (only if selected date is today)
        var dateVal = (plannedDate.dataset.isoValue || plannedDate.value || '').trim();
        if (dateVal) {
            var todayIso = toIsoDate(new Date());
            // Normalize DD-MM-YYYY to YYYY-MM-DD for comparison
            var dateIso = dateVal;
            if (dateVal.indexOf('-') === 2) {
                var dp = dateVal.split('-');
                dateIso = dp[2] + '-' + dp[1] + '-' + dp[0];
            }
            if (dateIso === todayIso) {
                var minDt = getMinAllowedHour();
                var minH = minDt.getHours();
                var minM = minDt.getMinutes();
                if (h < minH || (h === minH && m < minM)) return false;
            }
        }

        return true;
    }

    // ── Time validation UI helpers ──
    var timeErrorEl = document.getElementById('time-error');
    var timeErrorTimer = null;

    function validateAndHandleTime() {
        var val = plannedTime.value;
        if (!val) return;
        if (!isTimeValid(val)) {
            // Clear the input — don't keep invalid time
            plannedTime.value = '';
            // Determine error message
            var msg = '';
            var parts = val.split(':');
            var h = parseInt(parts[0], 10);
            if (h > 19 || (h === 19 && parseInt(parts[1], 10) > 0)) {
                msg = 'Waktu booking maksimal jam 19:00';
            } else if (h < 7) {
                msg = 'Waktu booking minimal jam 07:00';
            } else {
                msg = 'Booking harus minimal 4 jam dari sekarang';
            }
            // Show inline error below input
            if (timeErrorEl) {
                timeErrorEl.textContent = msg;
                timeErrorEl.hidden = false;
                if (timeErrorTimer) clearTimeout(timeErrorTimer);
                timeErrorTimer = setTimeout(function () {
                    timeErrorEl.hidden = true;
                }, 5000);
            }
        } else {
            lastValidTime = val;
            // Clear any previous error
            if (timeErrorEl) {
                timeErrorEl.hidden = true;
                if (timeErrorTimer) { clearTimeout(timeErrorTimer); timeErrorTimer = null; }
            }
        }
        syncPlannedStart();
        loadMiniAvailability();
    }

    function initVendorTimepicker() {
        if (plannedTime.getAttribute('data-st-timepicker') === '1') return;
        if (typeof window.mdtimepicker !== 'function') return;
        plannedTime.setAttribute('data-st-timepicker', '1');

        plannedTime.addEventListener('keydown', function (event) { event.preventDefault(); });
        plannedTime.addEventListener('paste', function (event) { event.preventDefault(); });

        // Use mdtimepicker — identical config to admin, with timeChanged callback for validation
        window.mdtimepicker('#planned-time', {
            format: 'hh:mm',
            is24hour: true,
            theme: 'cyan',
            hourPadding: true,
            events: {
                timeChanged: function () {
                    validateAndHandleTime();
                }
            }
        });
    }

    function bindFileSizeGuard() {
        if (!coaInput) return;
        coaInput.addEventListener('change', function () {
            if (!coaInput.files || !coaInput.files.length) {
                if (coaError) coaError.hidden = true;
                return;
            }
            const file = coaInput.files[0];
            const tooLarge = file.size > MAX_COA_BYTES;
            if (coaError) coaError.hidden = !tooLarge;
            if (tooLarge) {
                coaInput.value = '';
            }
        });
    }

    plannedDate.addEventListener('change', function () {
        syncPlannedStart();
        updateFormState();
        loadMiniAvailability();
    });

    plannedTime.addEventListener('change', function () {
        syncPlannedStart();
        updateFormState();
        loadMiniAvailability();
    });

    if (truckTypeSelect) {
        truckTypeSelect.addEventListener('change', function () {
            syncPlannedDuration();
            syncPlannedStart();
            loadMiniAvailability();
        });
    }

    syncPlannedDuration();
    syncPlannedStart();
    initVendorDatepicker();
    initVendorTimepicker();
    bindFileSizeGuard();
    // Validate initial time (e.g. default 08:00 may be invalid if it's afternoon)
    validateAndHandleTime();
    loadMiniAvailability();
}

function initVendorAvailability(config) {
    const selectedDate = config.selectedDate || '';
    const holidays = config.holidays || {};
    const availableSlotsUrl = config.availableSlotsUrl || '';
    const bookingCreateUrl = config.bookingCreateUrl || '';

    let currentDate = selectedDate ? new Date(selectedDate) : new Date();
    const today = new Date();
    const todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const holidayData = holidays;

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function toIsoDateLocal(dateObj) {
        return `${dateObj.getFullYear()}-${pad2(dateObj.getMonth() + 1)}-${pad2(dateObj.getDate())}`;
    }

    function getMinAllowedDateTime() {
        const now = new Date();
        now.setSeconds(0, 0);
        return new Date(now.getTime() + 4 * 60 * 60 * 1000);
    }

    function isTimeAllowed(dateStr, time) {
        if (!dateStr || !time) return true;
        if (time < '07:00' || time > '19:00') return false;
        const minAllowed = getMinAllowedDateTime();
        const selected = new Date(`${dateStr}T${time}:00`);
        return selected.getTime() >= minAllowed.getTime();
    }

    today.setHours(0, 0, 0, 0);

    const sidebar = document.getElementById('av-sidebar');
    const sidebarToggle = document.getElementById('av-sidebar-toggle');
    const mobileFilterBtn = document.getElementById('av-mobile-filter-btn');

    function toggleSidebar() {
        if (!sidebar) return;
        sidebar.classList.toggle('av-sidebar--open');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    if (mobileFilterBtn) {
        mobileFilterBtn.addEventListener('click', toggleSidebar);
    }

    renderMiniCalendar();
    loadAvailability();

    function renderMiniCalendar() {
        const container = document.getElementById('calendar-days');
        const monthLabel = document.getElementById('calendar-month');

        if (!container || !monthLabel) {
            return;
        }

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        monthLabel.textContent = new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        container.innerHTML = '';

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();

        let startDay = firstDay.getDay() - 1;
        if (startDay === -1) startDay = 6;

        for (let i = 0; i < startDay; i++) {
            const emptyDiv = document.createElement('div');
            container.appendChild(emptyDiv);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = toIsoDateLocal(date);
            const isToday = date.toDateString() === today.toDateString();
            const isSelected = dateStr === selectedDate;
            const isPast = date < todayMidnight;
            const isSunday = date.getDay() === 0;
            const isHoliday = holidayData[dateStr];

            const dayDiv = document.createElement('div');
            dayDiv.className = 'av-calendar__day';
            dayDiv.textContent = day;

            if (isToday) dayDiv.classList.add('av-calendar__day--today');
            if (isSelected) dayDiv.classList.add('av-calendar__day--selected');
            if (isPast) dayDiv.classList.add('av-calendar__day--disabled');
            if (isSunday) dayDiv.classList.add('av-calendar__day--sunday');
            if (isHoliday) dayDiv.classList.add('av-calendar__day--holiday');

            if (!isPast) {
                dayDiv.style.cursor = 'pointer';
                dayDiv.addEventListener('click', () => selectDate(dateStr));
            }

            if (isSunday) {
                dayDiv.setAttribute('data-vendor-tooltip', 'Sunday');
            }
            if (isHoliday) {
                dayDiv.setAttribute('data-vendor-tooltip', holidayData[dateStr]);
            }

            container.appendChild(dayDiv);
        }

        bindCalendarTooltips();
    }

    function bindCalendarTooltips() {
        const container = document.getElementById('calendar-days');
        if (!container) return;
        let hideTimer = null;
        let tooltip = document.getElementById('vendor-datepicker-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'vendor-datepicker-tooltip';
            tooltip.className = 'vendor-datepicker-tooltip';
            document.body.appendChild(tooltip);
        }

        container.querySelectorAll('.av-calendar__day').forEach(function (day) {
            day.addEventListener('mouseenter', function (event) {
                const text = day.getAttribute('data-vendor-tooltip') || '';
                if (!text) return;
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
                tooltip.textContent = text;
                tooltip.classList.add('vendor-datepicker-tooltip--visible');
                tooltip.style.left = (event.clientX + 12) + 'px';
                tooltip.style.top = (event.clientY + 12) + 'px';
            });
            day.addEventListener('mousemove', function (event) {
                if (!tooltip.classList.contains('vendor-datepicker-tooltip--visible')) return;
                tooltip.style.left = (event.clientX + 12) + 'px';
                tooltip.style.top = (event.clientY + 12) + 'px';
            });
            day.addEventListener('mouseleave', function () {
                hideTimer = setTimeout(function () {
                    tooltip.classList.remove('vendor-datepicker-tooltip--visible');
                }, 200);
            });
        });
    }

    window.changeMonth = function (direction) {
        currentDate.setMonth(currentDate.getMonth() + direction);
        renderMiniCalendar();
    };

    window.selectDate = function (dateStr) {
        const hiddenInput = document.getElementById('hidden-date');
        const form = document.getElementById('av-form');
        if (!hiddenInput || !form) return;
        hiddenInput.value = dateStr;
        form.submit();
    };

    function loadAvailability() {
        const options = arguments[0] || {};
        const silent = !!options.silent;
        const container = document.getElementById('availability-list');
        const date = selectedDate;
        if (!container) return;

        if (!silent || !container.dataset.rendered) {
            container.innerHTML = '<div class="av-empty"><i class="fas fa-spinner fa-spin av-empty__icon av-empty__icon--spinner"></i><p>Loading availability...</p></div>';
        }

        const timeout = setTimeout(() => {
            if (!silent) {
                container.innerHTML = '<div class="av-empty av-empty--error"><i class="fas fa-exclamation-triangle av-empty__icon av-empty__icon--alert"></i><p>Loading is taking longer than expected. Please try again.</p></div>';
            }
        }, 10000);

        function slotsHash(slots) {
            return JSON.stringify((slots || []).map(s => [
                String(s.time || ''),
                !!s.is_available,
                Number(s.available_gates || 0),
                !!s.disabled_by_admin,
                !!s.forced_by_admin,
                isTimeAllowed(date, s.time)
            ]));
        }

        fetch(availableSlotsUrl + '?date=' + date, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
            .then(r => {
                if (r.status === 401) {
                    console.warn('Session expired (401). Please log in again.');
                    // if (!window.__stReloadGuard || (Date.now() - window.__stReloadGuard) > 5000) {
                    //     window.__stReloadGuard = Date.now();
                    //     window.location.href = window.location.href;
                    // }
                    return;
                }
                if (!r.ok) {
                    throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                }
                return r.json();
            })
            .then(data => {
                clearTimeout(timeout);

                if (!data.success || !data.slots) {
                    if (!silent) {
                        container.innerHTML = '<p class="av-empty av-empty--error av-empty--compact">Failed to load</p>';
                    }
                    return;
                }

                const nextHash = slotsHash(data.slots);
                if (silent && container.dataset.slotsHash === nextHash) {
                    return;
                }
                container.dataset.slotsHash = nextHash;

                const allSlots = data.slots.slice().sort((a, b) => String(a.time || '').localeCompare(String(b.time || '')));

                if (allSlots.length === 0) {
                    container.innerHTML = '<p class="av-empty av-empty--compact">No available times</p>';
                    container.dataset.rendered = '1';
                    return;
                }

                // Split into two equal columns
                const midPoint = Math.ceil(allSlots.length / 2);
                const col1Slots = allSlots.slice(0, midPoint);
                const col2Slots = allSlots.slice(midPoint);

                // Build 2-column layout (split equally)
                let html = '<div class="av-shifts-grid">';

                // Column 1
                html += `
                    <div class="av-shift av-shift--col1">
                        <div class="av-shift__slots">
                `;
                col1Slots.forEach(slot => {
                    const forcedByAdmin = !!(slot && slot.forced_by_admin);
                    const isAllowed = forcedByAdmin || (!!(slot && slot.is_available) && isTimeAllowed(date, slot.time));
                    const label = isAllowed ? 'Available' : 'Not available';
                    html += `
                        <button type="button" class="av-available-item${isAllowed ? '' : ' cb-slot-btn--disabled'}" data-time="${slot.time}" ${isAllowed ? '' : 'disabled'}>
                            <span class="av-available-time">${slot.time}</span>
                            <span class="av-available-note">${label}</span>
                        </button>
                    `;
                });
                html += '</div></div>';

                // Column 2
                html += `
                    <div class="av-shift av-shift--col2">
                        <div class="av-shift__slots">
                `;
                if (col2Slots.length === 0) {
                    html += '<div class="av-empty av-empty--compact">No available times</div>';
                } else {
                    col2Slots.forEach(slot => {
                        const forcedByAdmin = !!(slot && slot.forced_by_admin);
                        const isAllowed = forcedByAdmin || (!!(slot && slot.is_available) && isTimeAllowed(date, slot.time));
                        const label = isAllowed ? 'Available' : 'Not available';
                        html += `
                            <button type="button" class="av-available-item${isAllowed ? '' : ' cb-slot-btn--disabled'}" data-time="${slot.time}" ${isAllowed ? '' : 'disabled'}>
                                <span class="av-available-time">${slot.time}</span>
                                <span class="av-available-note">${label}</span>
                            </button>
                        `;
                    });
                }
                html += '</div></div>';

                html += '</div>';
                container.innerHTML = html;
                container.dataset.rendered = '1';

                // Add click handlers for navigation to create booking
                container.querySelectorAll('.av-available-item[data-time]').forEach(btn => {
                    if (btn.hasAttribute('disabled')) {
                        return;
                    }
                    btn.addEventListener('click', () => {
                        const url = new URL(bookingCreateUrl, window.location.origin);
                        url.searchParams.set('date', date);
                        url.searchParams.set('time', btn.dataset.time);
                        window.location.href = url.toString();
                    });
                });
            })
            .catch(error => {
                clearTimeout(timeout);
                console.error('Error loading availability:', error);
                if (!silent) {
                    container.innerHTML = `<p class="av-empty av-empty--error av-empty--compact">Error loading availability: ${error.message}</p>`;
                }
            });
    }

    let availabilityAutoRefreshTimer = null;
    function startAvailabilityAutoRefresh() {
        if (availabilityAutoRefreshTimer) return;
        availabilityAutoRefreshTimer = window.setInterval(() => {
            if (document.hidden) return;
            loadAvailability({ silent: true });
        }, 2000);
    }

    startAvailabilityAutoRefresh();
}

function initVendorNotifications() {
    var vendorConfig = stGetVendorConfig();
    var notificationsCfg = vendorConfig && vendorConfig.notifications ? vendorConfig.notifications : {};

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Initialize Dashboard Date Range Picker
    function initDashboardDateRange() {
        var rangePicker = document.getElementById('vd_reportrange');
        var rangeStart = document.getElementById('vd-range-start');
        var rangeEnd = document.getElementById('vd-range-end');
        var dateRange = document.getElementById('vd-date-range');

        if (!rangePicker || !rangeStart || !rangeEnd) {
            console.warn('Date range picker elements not found', {
                rangePicker: !!rangePicker,
                rangeStart: !!rangeStart,
                rangeEnd: !!rangeEnd,
                dateRange: !!dateRange
            });
            return;
        }

        // Prevent double-init
        if (rangePicker.getAttribute('data-st-daterange-init') === '1') {
            return;
        }

        // Helper: check deps (vendor.js is loaded via Vite in <head>, deps are loaded later in <body>)
        function depsReady() {
            return !!(
                window.jQuery &&
                window.jQuery.fn &&
                typeof window.jQuery.fn.daterangepicker === 'function' &&
                window.moment
            );
        }

        // If deps not ready yet, keep retrying for a short time.
        if (!depsReady()) {
            var attempts = parseInt(rangePicker.getAttribute('data-st-daterange-attempts') || '0', 10);
            if (attempts >= 100) {
                console.error('Date range picker deps not ready after retries.');
                return;
            }
            rangePicker.setAttribute('data-st-daterange-attempts', String(attempts + 1));
            setTimeout(initDashboardDateRange, 50);
            return;
        }

        console.log('Initializing date range picker...');

        var $ = window.jQuery;
        var moment = window.moment;

        var startDate = moment();
        var endDate = moment();
        var hasInitial = false;

        if (rangeStart.value && moment(rangeStart.value, 'YYYY-MM-DD').isValid()) {
            startDate = moment(rangeStart.value, 'YYYY-MM-DD');
            hasInitial = true;
        }
        if (rangeEnd.value && moment(rangeEnd.value, 'YYYY-MM-DD').isValid()) {
            endDate = moment(rangeEnd.value, 'YYYY-MM-DD');
            hasInitial = true;
        }

        function updateRange(s, e) {
            // Match My Bookings display format
            $(rangePicker).find('span').first().html(s.format('DD-MM-YYYY') + ' - ' + e.format('DD-MM-YYYY'));
            rangeStart.value = s.format('YYYY-MM-DD');
            rangeEnd.value = e.format('YYYY-MM-DD');
        }

        // Initialize daterangepicker
        try {
            rangePicker.setAttribute('data-st-daterange-init', '1');

            $(rangePicker).daterangepicker({
                startDate: startDate,
                endDate: endDate,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                locale: { format: 'DD-MM-YYYY' },
                alwaysShowCalendars: true,
                opens: 'left'
            }, function (s, e, label) {
                updateRange(s, e);

                // Keep date_range param behavior (optional)
                if (dateRange) {
                    var presetMap = {
                        'Today': 'today',
                        'Yesterday': 'yesterday',
                        'Last 7 Days': 'last_7_days',
                        'Last 30 Days': 'last_30_days',
                        'This Month': 'this_month',
                        'Last Month': 'last_month'
                    };
                    dateRange.value = presetMap[label] || 'custom';
                }
            });

            console.log('Date range picker initialized successfully');

            if (hasInitial) {
                updateRange(startDate, endDate);
            } else {
                $(rangePicker).find('span').first().html('Select range');
            }

            // If user clicked before init completed, open immediately
            if (rangePicker.getAttribute('data-st-daterange-open') === '1') {
                rangePicker.removeAttribute('data-st-daterange-open');
                var drp = window.jQuery(rangePicker).data('daterangepicker');
                if (drp && typeof drp.show === 'function') drp.show();
            }
        } catch (error) {
            console.error('Error initializing date range picker:', error);
        }
    }

    // Init strategy:
    // - bind click first (so user click during load won't be ignored)
    // - run init on DOMContentLoaded + window load + short retries
    (function bindDashboardDateRangeBoot() {
        var rangePicker = document.getElementById('vd_reportrange');
        if (!rangePicker) return;

        if (rangePicker.getAttribute('data-st-daterange-boot') === '1') return;
        rangePicker.setAttribute('data-st-daterange-boot', '1');

        rangePicker.addEventListener('click', function (e) {
            // Ensure init has run; if not, mark to open once ready
            if (rangePicker.getAttribute('data-st-daterange-init') !== '1') {
                rangePicker.setAttribute('data-st-daterange-open', '1');
                initDashboardDateRange();
                return;
            }

            // If already initialized, force open
            try {
                var drp = window.jQuery(rangePicker).data('daterangepicker');
                if (drp && typeof drp.show === 'function') drp.show();
            } catch (ex) { }
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardDateRange);
        } else {
            initDashboardDateRange();
        }

        window.addEventListener('load', initDashboardDateRange);
        setTimeout(initDashboardDateRange, 250);
        setTimeout(initDashboardDateRange, 750);
        setTimeout(initDashboardDateRange, 1500);
    })();

    // Desktop notification handler - force rebind
    var desktopNotifBtn = document.getElementById('notification-btn');
    var desktopNotifDropdown = document.getElementById('notification-dropdown');

    console.log('Desktop notification btn:', desktopNotifBtn);
    console.log('Desktop notification dropdown:', desktopNotifDropdown);

    // Remove existing listeners and add new ones
    if (desktopNotifBtn && desktopNotifDropdown) {
        // Clone button to remove all existing event listeners
        var newBtn = desktopNotifBtn.cloneNode(true);
        desktopNotifBtn.parentNode.replaceChild(newBtn, desktopNotifBtn);

        // Add fresh event listener
        newBtn.addEventListener('click', function (e) {
            console.log('Desktop notification clicked');
            e.preventDefault();
            e.stopPropagation();

            // Toggle dropdown
            var isVisible = desktopNotifDropdown.style.display === 'block';
            desktopNotifDropdown.style.display = isVisible ? 'none' : 'block';
            desktopNotifDropdown.classList.toggle('show');

            console.log('Dropdown toggled:', !isVisible);
        });

        // Update reference
        desktopNotifBtn = newBtn;
    }

    // Mobile notification handler (dalam user menu)
    var mobileNotifBtn = document.getElementById('vendor-user-menu-notif');
    var mobileNotifDropdown = document.getElementById('vendor-user-menu-notification-dropdown');

    if (mobileNotifBtn && mobileNotifDropdown) {
        // Clone button to remove existing listeners
        var newMobileBtn = mobileNotifBtn.cloneNode(true);
        mobileNotifBtn.parentNode.replaceChild(newMobileBtn, mobileNotifBtn);

        newMobileBtn.addEventListener('click', function (e) {
            console.log('Mobile notification clicked');
            e.preventDefault();
            e.stopPropagation();
            mobileNotifDropdown.classList.toggle('show');
        });

        mobileNotifBtn = newMobileBtn;
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function (e) {
        // Close desktop dropdown
        if (desktopNotifDropdown && !desktopNotifDropdown.contains(e.target) && !desktopNotifBtn?.contains(e.target)) {
            desktopNotifDropdown.style.display = 'none';
            desktopNotifDropdown.classList.remove('show');
        }

        // Close mobile dropdown
        if (mobileNotifDropdown && !mobileNotifDropdown.contains(e.target) && !mobileNotifBtn?.contains(e.target)) {
            mobileNotifDropdown.classList.remove('show');
        }
    });

    // User menu handler
    var userMenuBtn = document.getElementById('vendor-user-menu-btn');
    var userMenu = document.getElementById('vendor-user-menu');

    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });
    }

    // Close user menu when clicking outside
    document.addEventListener('click', function (e) {
        if (userMenu && !userMenu.contains(e.target) && !userMenuBtn.contains(e.target)) {
            userMenu.classList.remove('show');
            // Also close mobile notification dropdown when user menu closes
            if (mobileNotifDropdown) {
                mobileNotifDropdown.classList.remove('show');
            }
        }
    });

    // Desktop notification actions
    var desktopMarkAllBtn = document.getElementById('notification-mark-all');
    var desktopClearBtn = document.getElementById('notification-clear');
    if (desktopMarkAllBtn) {
        desktopMarkAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.markAllAsRead();
        });
    }
    if (desktopClearBtn) {
        desktopClearBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.clearAllNotifications();
        });
    }

    // Mobile notification actions
    var mobileMarkAllBtn = document.getElementById('user-menu-notification-mark-all');
    var mobileClearBtn = document.getElementById('user-menu-notification-clear');
    if (mobileMarkAllBtn) {
        mobileMarkAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.markAllAsRead();
        });
    }
    if (mobileClearBtn) {
        mobileClearBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.clearAllNotifications();
        });
    }

    // Force hide mobile navigation di desktop
    function forceHideMobileNav() {
        var mobileNav = document.getElementById('vendor-mobile-nav');
        if (mobileNav && window.innerWidth >= 769) {
            mobileNav.style.display = 'none';
            mobileNav.style.visibility = 'hidden';
            mobileNav.style.opacity = '0';
            mobileNav.style.height = '0';
            mobileNav.style.width = '0';
            mobileNav.style.overflow = 'hidden';
            mobileNav.style.position = 'absolute';
            mobileNav.style.top = '-99999px';
            mobileNav.style.left = '-99999px';
            mobileNav.style.zIndex = '-9999';
        }
    }

    // Run on load
    forceHideMobileNav();

    // Run on resize
    window.addEventListener('resize', forceHideMobileNav);

    // Run every 100ms untuk double check
    // Polling removed — CSS + inline script in vendor.blade.php handles mobile nav hiding

    function normalizeUrl(u) {
        try {
            var s = String(u || '');
            if (!s) return '#';
            if (s.indexOf('://') !== -1) {
                var parsed = new URL(s);
                return parsed.pathname + parsed.search + parsed.hash;
            }
            return s;
        } catch (ex) {
            return '#';
        }
    }

    function updateNotificationBadge(deltaToRemove) {
        var badge = document.getElementById('notification-count');
        if (!badge) return;
        var current = parseInt(badge.textContent || '0', 10);
        if (!isFinite(current)) current = 0;
        var next = Math.max(0, current - (deltaToRemove || 0));
        if (next <= 0) {
            badge.remove();
            return;
        }
        badge.textContent = String(next);
    }

    // Notification sound using Web Audio API
    function playNotificationSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.setValueAtTime(1047, ctx.currentTime + 0.1);
            osc.frequency.setValueAtTime(1319, ctx.currentTime + 0.2);
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.5);
        } catch (e) { /* ignore if AudioContext not available */ }
    }

    window.markAsRead = function (id) {
        if (!id) return;
        var base = notificationsCfg.readBaseUrl || '/notifications';
        var url = String(base || '').replace(/\/$/, '') + '/' + String(id) + '/read';

        // Update UI immediately (optimistic UI update)
        var item = document.querySelector('[data-notification-id="' + id + '"]');
        if (item && item.classList.contains('notification-item--unread')) {
            item.classList.remove('notification-item--unread');
            updateNotificationBadge(1);
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        }).catch(function () { });
    };

    window.markAllAsRead = function () {
        var url = notificationsCfg.markAllUrl || '';
        if (!url) return;
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            if (response.ok) {
                document.querySelectorAll('.notification-item--unread').forEach(function (item) {
                    item.classList.remove('notification-item--unread');
                });
                // Update notification count
                var countBadge = document.getElementById('notification-count');
                if (countBadge) {
                    countBadge.textContent = '0';
                    countBadge.style.display = 'none';
                }
            }
        }).catch(function () { });
    };

    window.clearAllNotifications = function () {
        var url = notificationsCfg.clearUrl || '';
        if (!url) return;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        }).then(function () {
            var list = document.querySelector('.notification-list');
            if (list) {
                list.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash notification-empty__icon"></i><p>No notifications yet</p></div>';
            }
            var badge = document.getElementById('notification-count');
            if (badge) badge.remove();
        }).catch(function () { });
    };

    window.markAsReadAndGo = function (e, id, url) {
        try {
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
            }
            var target = normalizeUrl(url);
            window.markAsRead(id);
            setTimeout(function () {
                window.location.href = target;
            }, 80);
            return false;
        } catch (err) {
            return true;
        }
    };

    function initNotificationDropdown() {
        var notifBtn = document.getElementById('notification-btn');
        var notifDropdown = document.getElementById('notification-dropdown');
        if (!notifBtn || !notifDropdown) return;

        notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                notifDropdown.classList.remove('show');
            }
        });
    }

    function initNotificationActions() {
        var markAllBtn = document.getElementById('notification-mark-all');
        var clearBtn = document.getElementById('notification-clear');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                window.markAllAsRead();
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                window.clearAllNotifications();
            });
        }
    }

    function initNotificationToast() {
        var latestUrl = vendorConfig ? vendorConfig.latestUrl : null;
        var toast = document.getElementById('vendor-notification-toast');
        var toastText = document.getElementById('vendor-notification-toast-text');
        var toastTimer = null;
        var storageKey = 'vendor_last_notification_id';

        if (!latestUrl || !toast || !toastText) {
            return;
        }

        function showNotification(notification) {
            if (!notification) {
                return;
            }
            toastText.textContent = (notification.title || 'Notification') + ' - ' + (notification.message || '');
            toast.style.display = 'block';
            playNotificationSound();

            if (toastTimer) {
                clearTimeout(toastTimer);
            }
            toastTimer = setTimeout(function () {
                toast.style.display = 'none';
            }, 5000);
        }

        function checkLatest() {
            fetch(latestUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (resp) { return resp.json(); })
                .then(function (data) {
                    if (!data || !data.success || !data.notification) {
                        return;
                    }
                    var lastId = localStorage.getItem(storageKey);
                    if (!lastId) {
                        localStorage.setItem(storageKey, data.notification.id);
                        return;
                    }
                    if (lastId !== data.notification.id) {
                        localStorage.setItem(storageKey, data.notification.id);
                        showNotification(data.notification);
                    }
                })
                .catch(function () {
                    // ignore
                });
        }

        // Polling removed — notifications now arrive via WebSocket (echo.js private user channel)
        // checkLatest(); — no longer needed
    }

    initNotificationDropdown();
    initNotificationActions();
    // initNotificationToast() — removed, replaced by WebSocket (echo.js private user channel)
}

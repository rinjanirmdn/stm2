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

document.addEventListener('DOMContentLoaded', function () {
    const bookingConfig = window.vendorBookingCreateConfig || stReadJson('vendor_booking_create_config', null);
    const availabilityConfig = window.vendorAvailabilityConfig || stReadJson('vendor_availability_config', null);

    if (bookingConfig) {
        initVendorBookingCreate(bookingConfig);
    }

    if (availabilityConfig) {
        initVendorAvailability(availabilityConfig);
    }

    initVendorNotifications();
});

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
    const truckTypeSelect = document.querySelector('select[name="truck_type"]');
    const miniAvailability = document.getElementById('mini-availability');
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
            const poNumber = poHidden.value.trim();
            if (!poNumber) {
                e.preventDefault();
                alert('Please select a PO/DO number');
                poSearch.focus();
                return false;
            }
        });
    }

    function loadMiniAvailability() {
        if (!miniAvailability) return;
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

        fetch(availableSlotsUrl + '?date=' + encodeURIComponent(date), {
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

                const hours = data.slots.filter(slot => slot.time.endsWith(':00'));
                if (!hours.length) {
                    miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">No available hours</div>';
                    return;
                }

                const minAllowed = new Date();
                minAllowed.setSeconds(0, 0);
                minAllowed.setTime(minAllowed.getTime() + 4 * 60 * 60 * 1000);

                const selectedTime = (plannedTime.value || '').trim();
                let html = '<div class="cb-availability-mini__grid">';
                hours.forEach(slot => {
                    const slotDateTime = new Date(`${date}T${slot.time}:00`);
                    const meetsMin = slotDateTime.getTime() >= minAllowed.getTime();
                    const isAvailable = slot.is_available && meetsMin;
                    const isSelected = selectedTime === slot.time;
                    const classes = [
                        'cb-availability-mini__item',
                        isAvailable ? 'cb-availability-mini__item--available' : 'cb-availability-mini__item--busy',
                        isSelected ? 'cb-availability-mini__item--selected' : ''
                    ].join(' ').trim();
                    const statusLabel = isAvailable ? 'Available' : (meetsMin ? 'Not available' : 'Min 4 hours');
                    html += `
                        <button type="button" class="${classes}" data-time="${slot.time}" ${isAvailable ? '' : 'disabled'}>
                            <span class="cb-availability-mini__item-time">${slot.time}</span>
                            <span class="cb-availability-mini__item-status">${statusLabel}</span>
                        </button>
                    `;
                });
                html += '</div>';
                miniAvailability.innerHTML = html;

                miniAvailability.querySelectorAll('.cb-availability-mini__item[data-time]').forEach(btn => {
                    if (btn.hasAttribute('disabled')) return;
                    btn.addEventListener('click', () => {
                        plannedTime.value = btn.dataset.time;
                        syncPlannedStart();
                        loadMiniAvailability();
                    });
                });
            })
            .catch(() => {
                miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Error loading availability</div>';
            });
    }

    function syncPlannedDuration() {
        if (!plannedDurationInput || !truckTypeSelect) return;
        const selected = truckTypeSelect.options[truckTypeSelect.selectedIndex];
        const duration = selected ? selected.getAttribute('data-duration') : '';
        plannedDurationInput.value = duration ? duration : 60;
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
            if (cls.indexOf('drp-sunday') !== -1) text = 'Hari Minggu';
            if (cls.indexOf('drp-holiday') !== -1) {
                var dataTitle = td.getAttribute('data-holiday-name');
                text = dataTitle || 'Hari Libur';
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

        try { plannedDate.type = 'text'; } catch (e) {}
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
                showVendorToast('Tanggal yang dipilih adalah Hari Minggu', 'warning');
            } else if (holidayData[ds]) {
                showVendorToast('Tanggal yang dipilih adalah hari libur: ' + holidayData[ds], 'warning');
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
                var monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
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
        loadMiniAvailability();
    });

    plannedTime.addEventListener('change', function () {
        syncPlannedStart();
        loadMiniAvailability();
    });

    if (truckTypeSelect) {
        truckTypeSelect.addEventListener('change', function () {
            syncPlannedDuration();
            syncPlannedStart();
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
                dayDiv.setAttribute('data-vendor-tooltip', 'Hari Minggu');
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
        const container = document.getElementById('availability-list');
        const date = selectedDate;
        if (!container) return;

        container.innerHTML = '<div class="av-empty"><i class="fas fa-spinner fa-spin av-empty__icon av-empty__icon--spinner"></i><p>Loading availability...</p></div>';

        const timeout = setTimeout(() => {
            container.innerHTML = '<div class="av-empty av-empty--error"><i class="fas fa-exclamation-triangle av-empty__icon av-empty__icon--alert"></i><p>Loading is taking longer than expected. Please try again.</p></div>';
        }, 10000);

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
                    container.innerHTML = '<p class="av-empty av-empty--error av-empty--compact">Failed to load</p>';
                    return;
                }

                // Group slots by shift (2 columns: Shift 1 & Shift 2+3)
                const shifts = {
                    shift1: { label: 'Shift 1 (07:00 - 15:00)', icon: 'fa-sun', slots: [] },
                    shift2n3: { label: 'Shift 2 & 3 (15:00 - 07:00)', icon: 'fa-moon', slots: [] }
                };

                data.slots.forEach(slot => {
                    const hour = parseInt(slot.time.split(':')[0], 10);
                    if (hour >= 7 && hour < 15) {
                        shifts.shift1.slots.push(slot);
                    } else {
                        shifts.shift2n3.slots.push(slot);
                    }
                });

                // Get all available slots sorted by time
                const availableSlots = data.slots.filter(s => s.is_available).sort((a, b) => a.time.localeCompare(b.time));

                if (availableSlots.length === 0) {
                    container.innerHTML = '<p class="av-empty av-empty--compact">No available times</p>';
                    return;
                }

                // Split into two equal columns
                const midPoint = Math.ceil(availableSlots.length / 2);
                const col1Slots = availableSlots.slice(0, midPoint);
                const col2Slots = availableSlots.slice(midPoint);

                // Build 2-column layout (split equally)
                let html = '<div class="av-shifts-grid">';

                // Column 1
                html += `
                    <div class="av-shift av-shift--col1">
                        <div class="av-shift__slots">
                `;
                col1Slots.forEach(slot => {
                    const isAllowed = isTimeAllowed(date, slot.time);
                    html += `
                        <button type="button" class="av-available-item${isAllowed ? '' : ' cb-slot-btn--disabled'}" data-time="${slot.time}" ${isAllowed ? '' : 'disabled'}>
                            <span class="av-available-time">${slot.time}</span>
                            ${isAllowed ? '' : '<span class="av-available-note">Not available</span>'}
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
                    html += '<div class="av-empty av-empty--compact">No slots</div>';
                } else {
                    col2Slots.forEach(slot => {
                        const isAllowed = isTimeAllowed(date, slot.time);
                        html += `
                            <button type="button" class="av-available-item${isAllowed ? '' : ' cb-slot-btn--disabled'}" data-time="${slot.time}" ${isAllowed ? '' : 'disabled'}>
                                <span class="av-available-time">${slot.time}</span>
                                ${isAllowed ? '' : '<span class="av-available-note">Not available</span>'}
                            </button>
                        `;
                    });
                }
                html += '</div></div>';

                html += '</div>';
                container.innerHTML = html;

                // Add click handlers
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
                container.innerHTML = `<p class="av-empty av-empty--error av-empty--compact">Error loading availability: ${error.message}</p>`;
            });
    }
}

function initVendorNotifications() {
    var vendorConfig = stGetVendorConfig();
    var notificationsCfg = vendorConfig && vendorConfig.notifications ? vendorConfig.notifications : {};

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

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

    window.markAsRead = function (id) {
        if (!id) return;
        var base = notificationsCfg.readBaseUrl || '/notifications';
        var url = String(base || '').replace(/\/$/, '') + '/' + String(id) + '/read';
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        }).then(function () {
            var item = document.querySelector('[data-notification-id="' + id + '"]');
            if (item && item.classList.contains('notification-item--unread')) {
                item.classList.remove('notification-item--unread');
                updateNotificationBadge(1);
            }
        }).catch(function () { });
    };

    window.markAllAsRead = function () {
        var url = notificationsCfg.markAllUrl || '';
        if (!url) return;
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function () {
            document.querySelectorAll('.notification-item--unread').forEach(function (item) {
                item.classList.remove('notification-item--unread');
            });
            var badge = document.getElementById('notification-count');
            if (badge) badge.remove();
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

            if (toastTimer) {
                clearTimeout(toastTimer);
            }
            toastTimer = setTimeout(function () {
                toast.style.display = 'none';
            }, 3000);
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

        checkLatest();
        setInterval(checkLatest, 60 * 1000);
    }

    initNotificationDropdown();
    initNotificationActions();
    initNotificationToast();
}

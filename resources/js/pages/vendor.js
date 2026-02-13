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
        const date = plannedDate.value;
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

    function initVendorDatepicker() {
        if (plannedDate.getAttribute('data-st-datepicker') === '1') return;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.bootstrapMaterialDatePicker !== 'function') return;
        plannedDate.setAttribute('data-st-datepicker', '1');

        const holidays = {};
        if (holidayData && typeof holidayData === 'object') {
            Object.assign(holidays, holidayData);
        }

        // Initialize bootstrap-material-datetimepicker for date
        window.jQuery(plannedDate).bootstrapMaterialDatePicker({
            format: 'DD-MM-YYYY',
            time: false,
            minDate: new Date(),
            disabledDays: function(date) {
                const ds = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                const isSunday = date.getDay() === 0;
                return !isSunday && !holidays[ds];
            },
            weekStart: 1,
            lang: 'en',
            cancelText: 'Cancel',
            okText: 'OK'
        });

        // Handle date change
        window.jQuery(plannedDate).on('change', function(e, date) {
            if (date) {
                const isoDate = toIsoDate(date.toDate());
                plannedDate.dataset.isoValue = isoDate;
            }
            syncPlannedStart();
            loadMiniAvailability();
        });
    }

    function initVendorTimepicker() {
        if (plannedTime.getAttribute('data-st-timepicker') === '1') return;
        if (typeof window.mdtimepicker !== 'function') return;
        plannedTime.setAttribute('data-st-timepicker', '1');

        plannedTime.addEventListener('keydown', function (event) { event.preventDefault(); });
        plannedTime.addEventListener('paste', function (event) { event.preventDefault(); });

        // Use mdtimepicker with circular clock like admin portal
        window.mdtimepicker('#planned-time', {
            timeFormat: 'hh:mm',
            format: 'hh:mm',
            is24hour: true,
            theme: 'blue',
            hourPadding: true,
            autoSwitch: true,
            readOnly: true
        });

        // Handle time change
        plannedTime.addEventListener('timechanged', function(e) {
            syncPlannedStart();
            loadMiniAvailability();
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

            if (!isPast && !isSunday && !isHoliday) {
                dayDiv.style.cursor = 'pointer';
                dayDiv.addEventListener('click', () => selectDate(dateStr));
            }

            if (isSunday) {
                dayDiv.setAttribute('data-vendor-tooltip', 'Sunday - Not available');
            } else if (isHoliday) {
                dayDiv.setAttribute('data-vendor-tooltip', isHoliday + ' - Holiday');
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
                const available = data.slots.filter(s => s.is_available);
                if (!available.length) {
                    container.innerHTML = '<p class="av-empty av-empty--compact">No available times</p>';
                    return;
                }
                let html = '';
                available.forEach(slot => {
                    const isAllowed = isTimeAllowed(date, slot.time);
                    html += `
                        <button type="button" class="av-available-item${isAllowed ? '' : ' cb-slot-btn--disabled'}" data-time="${slot.time}" ${isAllowed ? '' : 'disabled'}>
                            <span class="av-available-time">${slot.time}</span>
                            ${isAllowed ? '' : '<span class="av-available-note">Not available</span>'}
                        </button>
                    `;
                });
                container.innerHTML = html;

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

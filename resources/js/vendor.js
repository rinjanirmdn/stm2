document.addEventListener('DOMContentLoaded', function () {
    const bookingConfig = window.vendorBookingCreateConfig || null;
    const availabilityConfig = window.vendorAvailabilityConfig || null;

    if (bookingConfig) {
        initVendorBookingCreate(bookingConfig);
    }

    if (availabilityConfig) {
        initVendorAvailability(availabilityConfig);
    }
});

function initVendorBookingCreate(config) {
    const poSearchUrl = config.poSearchUrl || '';
    const poDetailUrl = config.poDetailUrl || '';
    const availableSlotsUrl = config.availableSlotsUrl || '';

    const poSearch = document.getElementById('po-search');
    const poResults = document.getElementById('po-results');
    const poHidden = document.getElementById('po-number-hidden');
    const poLoading = document.getElementById('po-loading');
    const poItemsContainer = document.getElementById('po-items-container');
    const poItemsBody = document.getElementById('po-items-body');

    const plannedDate = document.getElementById('planned-date');
    const plannedTime = document.getElementById('planned-time');
    const plannedDurationInput = document.getElementById('planned-duration');
    const plannedStartInput = document.getElementById('planned-start');
    const truckTypeSelect = document.querySelector('select[name="truck_type"]');
    const miniAvailability = document.getElementById('mini-availability');
    const coaInput = document.querySelector('input[name="coa_pdf"]');
    const coaError = document.getElementById('coa-error');
    const MAX_COA_BYTES = 10 * 1024 * 1024;
    const holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    if (!poSearch || !plannedDate || !plannedTime) {
        return;
    }

    let searchTimeout = null;

    poSearch.addEventListener('input', function () {
        const q = this.value.trim();

        if (q.length < 2) {
            poResults.classList.remove('show');
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
            poLoading.classList.add('show');

            fetch(poSearchUrl + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    poLoading.classList.remove('show');

                    if (data.success && data.data.length > 0) {
                        poResults.innerHTML = data.data.map(po => `
                            <div class="cb-po-item" data-po="${po.po_number}">
                                <div class="cb-po-item__number">${po.po_number}</div>
                                <div class="cb-po-item__info">${po.vendor_name || ''} | ${po.direction || ''}</div>
                            </div>
                        `).join('');
                        poResults.classList.add('show');
                    } else {
                        poResults.innerHTML = '<div class="cb-po-item">No results found</div>';
                        poResults.classList.add('show');
                    }
                })
                .catch(err => {
                    poLoading.classList.remove('show');
                    console.error('Search error:', err);
                });
        }, 300);
    });

    poResults.addEventListener('click', function (e) {
        const item = e.target.closest('.cb-po-item');
        if (!item || !item.dataset.po) return;

        const poNumber = item.dataset.po;
        poSearch.value = poNumber;
        poHidden.value = poNumber;
        poResults.classList.remove('show');

        poLoading.classList.add('show');
        fetch(poDetailUrl + '/' + encodeURIComponent(poNumber))
            .then(r => r.json())
            .then(data => {
                poLoading.classList.remove('show');

                if (data.success && data.data.items) {
                    renderPoItems(data.data.items);
                }
            })
            .catch(err => {
                poLoading.classList.remove('show');
                console.error('Detail error:', err);
            });
    });

    function renderPoItems(items) {
        if (!items || items.length === 0) {
            poItemsContainer.classList.remove('is-visible');
            return;
        }

        poItemsBody.innerHTML = items.map((item, idx) => {
            const remaining = (parseFloat(item.qty_po) || 0) - (parseFloat(item.qty_gr_total) || 0);
            return `
                <tr>
                    <td>${item.item_no || (idx + 1)}</td>
                    <td>${item.material_name || item.description || '-'}</td>
                    <td>${item.qty_po || 0} ${item.unit_po || ''}</td>
                    <td>${item.qty_gr_total || 0}</td>
                    <td>${remaining.toFixed(2)}</td>
                    <td>
                        <input type="hidden" name="po_items[${idx}][item_no]" value="${item.item_no || ''}">
                        <input type="hidden" name="po_items[${idx}][material_code]" value="${item.material_code || ''}">
                        <input type="hidden" name="po_items[${idx}][material_name]" value="${item.material_name || item.description || ''}">
                        <input type="number"
                               name="po_items[${idx}][qty]"
                               value="${remaining > 0 ? remaining.toFixed(2) : 0}"
                               min="0"
                               max="${remaining}"
                               step="0.001">
                    </td>
                </tr>
            `;
        }).join('');

        poItemsContainer.classList.add('is-visible');
    }

    document.addEventListener('click', function (e) {
        if (!poSearch.contains(e.target) && !poResults.contains(e.target)) {
            poResults.classList.remove('show');
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
        const date = (plannedDate.value || '').trim();
        const time = (plannedTime.value || '').trim();
        plannedStartInput.value = date && time ? date + ' ' + time : (date || '');
    }

    function initVendorDatepicker() {
        if (plannedDate.getAttribute('data-st-datepicker') === '1') return;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
        plannedDate.setAttribute('data-st-datepicker', '1');

        let rafId = null;
        function scheduleReposition() {
            if (rafId) {
                cancelAnimationFrame(rafId);
            }
            rafId = requestAnimationFrame(repositionDatepicker);
        }

        function repositionDatepicker() {
            const dp = window.jQuery('#ui-datepicker-div');
            if (!dp.is(':visible')) return;
            const rect = plannedDate.getBoundingClientRect();
            dp.css({
                position: 'fixed',
                top: rect.bottom + 4,
                left: rect.left
            });
        }

        function applyVendorDatepickerTooltips(inst) {
            if (!inst || !inst.dpDiv) return;
            const dp = window.jQuery(inst.dpDiv);

            dp.find('td.is-holiday, td.is-sunday').each(function () {
                const cell = window.jQuery(this);
                const dayText = cell.find('a, span').first().text();
                if (!dayText) return;
                const fallbackYear = inst.drawYear ?? inst.selectedYear;
                const fallbackMonth = inst.drawMonth ?? inst.selectedMonth;
                const year = cell.data('year') ?? fallbackYear;
                const month = cell.data('month') ?? fallbackMonth;
                if (year === undefined || month === undefined) return;
                const ds = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayText).padStart(2, '0')}`;
                const jsDate = new Date(`${ds}T00:00:00`);
                const isSunday = jsDate.getDay() === 0;
                const holidayName = holidayData[ds];
                const title = holidayName ? `${holidayName} - Holiday` : (isSunday ? 'Sunday - Not available' : '');
                if (title) {
                    cell.attr('data-vendor-tooltip', title);
                    cell.find('a, span').attr('data-vendor-tooltip', title);
                }
                cell.removeAttr('title');
                cell.find('a, span').removeAttr('title');
            });
        }

        function bindVendorDatepickerHover(inst) {
            if (!inst || !inst.dpDiv) return;
            const dp = window.jQuery(inst.dpDiv);
            let hideTimer = null;
            let tooltip = document.getElementById('vendor-datepicker-tooltip');
            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.id = 'vendor-datepicker-tooltip';
                tooltip.className = 'vendor-datepicker-tooltip';
                document.body.appendChild(tooltip);
            }

            dp.off('mouseenter.vendor-tooltip mousemove.vendor-tooltip mouseleave.vendor-tooltip', 'td.is-holiday, td.is-sunday');
            dp.on('mouseenter.vendor-tooltip', 'td.is-holiday, td.is-sunday', function (event) {
                const text = window.jQuery(this).attr('data-vendor-tooltip') || '';
                if (!text) return;
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
                tooltip.textContent = text;
                tooltip.classList.add('vendor-datepicker-tooltip--visible');
                tooltip.style.left = `${event.clientX + 12}px`;
                tooltip.style.top = `${event.clientY + 12}px`;
            });
            dp.on('mousemove.vendor-tooltip', 'td.is-holiday, td.is-sunday', function (event) {
                tooltip.style.left = `${event.clientX + 12}px`;
                tooltip.style.top = `${event.clientY + 12}px`;
            });
            dp.on('mouseleave.vendor-tooltip', 'td.is-holiday, td.is-sunday', function () {
                hideTimer = setTimeout(function () {
                    tooltip.classList.remove('vendor-datepicker-tooltip--visible');
                }, 200);
            });
        }

        window.jQuery(plannedDate).datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            beforeShowDay: function (date) {
                const ds = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                const isSunday = date.getDay() === 0;
                if (holidayData[ds]) {
                    return [false, 'is-holiday', holidayData[ds]];
                }
                if (isSunday) {
                    return [false, 'is-sunday', 'Sunday - Not available'];
                }
                return [true, '', ''];
            },
            beforeShow: function (input, inst) {
                setTimeout(function () {
                    scheduleReposition();
                    document.removeEventListener('scroll', scheduleReposition, true);
                    window.removeEventListener('resize', scheduleReposition);
                    document.addEventListener('scroll', scheduleReposition, true);
                    window.addEventListener('resize', scheduleReposition);
                    applyVendorDatepickerTooltips(inst);
                    bindVendorDatepickerHover(inst);
                }, 0);
            },
            onChangeMonthYear: function () {
                setTimeout(function () {
                    scheduleReposition();
                    const inst = window.jQuery(plannedDate).data('datepicker');
                    applyVendorDatepickerTooltips(inst);
                    bindVendorDatepickerHover(inst);
                }, 0);
            },
            onClose: function () {
                document.removeEventListener('scroll', scheduleReposition, true);
                window.removeEventListener('resize', scheduleReposition);
            },
            onSelect: function () {
                syncPlannedStart();
                loadMiniAvailability();
            }
        });
    }

    function initVendorTimepicker() {
        if (plannedTime.getAttribute('data-st-timepicker') === '1') return;
        if (typeof window.mdtimepicker !== 'function') return;
        plannedTime.setAttribute('data-st-timepicker', '1');

        plannedTime.addEventListener('keydown', function (event) { event.preventDefault(); });
        plannedTime.addEventListener('paste', function (event) { event.preventDefault(); });

        function ensureTimepickerOkEnabled() {
            const wrapper = document.querySelector('.mdtp__wrapper');
            if (!wrapper) return;
            const buttons = wrapper.querySelectorAll('button');
            buttons.forEach((btn) => {
                if (btn.textContent.trim().toLowerCase() === 'ok') {
                    btn.disabled = false;
                    btn.removeAttribute('disabled');
                    btn.style.pointerEvents = 'auto';
                }
            });
        }

        window.mdtimepicker('#planned-time', {
            format: 'hh:mm',
            is24hour: true,
            theme: 'cyan',
            hourPadding: true
        });

        plannedTime.addEventListener('click', function () {
            setTimeout(ensureTimepickerOkEnabled, 0);
        });

        plannedTime.addEventListener('change', function () {
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
                    window.location.reload();
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

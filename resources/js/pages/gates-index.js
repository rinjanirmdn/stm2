function stReadJson(id, fallback) {
    try {
        var el = document.getElementById(id);
        if (!el) return fallback;
        return JSON.parse(el.textContent || '{}') || fallback;
    } catch (e) {
        return fallback;
    }
}

function stEscapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function stNormalizeUrl(u) {
    try {
        var s = String(u || '');
        if (!s) return '';
        if (s.indexOf('://') !== -1) {
            var parsed = new URL(s);
            return parsed.pathname + parsed.search + parsed.hash;
        }
        return s;
    } catch (ex) {
        return '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var config = stReadJson('gates_index_config', {});
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};
    var paramDate = config.paramDate || '';
    var gatesIndexUrl = stNormalizeUrl(config.gatesIndexUrl || window.location.pathname);
    var availabilityUrl = stNormalizeUrl(config.availabilityUrl || '');
    var disabledTimesUrl = stNormalizeUrl(config.disabledTimesUrl || '');
    var selectedWarehouseIds = Array.isArray(config.selectedWarehouseIds) ? config.selectedWarehouseIds : [];
    var currentDate = paramDate ? new Date(paramDate) : new Date();
    var today = new Date();
    var todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function toIsoDateLocal(dateObj) {
        return `${dateObj.getFullYear()}-${pad2(dateObj.getMonth() + 1)}-${pad2(dateObj.getDate())}`;
    }

    function renderMiniCalendar() {
        var container = document.getElementById('dock_calendar_days');
        var monthLabel = document.getElementById('dock_calendar_month');
        if (!container || !monthLabel) return;

        var year = currentDate.getFullYear();
        var month = currentDate.getMonth();
        monthLabel.textContent = new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        container.innerHTML = '';

        var firstDay = new Date(year, month, 1);
        var lastDay = new Date(year, month + 1, 0);
        var daysInMonth = lastDay.getDate();
        var startDay = firstDay.getDay() - 1;
        if (startDay === -1) startDay = 6;

        for (var i = 0; i < startDay; i++) {
            container.appendChild(document.createElement('div'));
        }

        for (var day = 1; day <= daysInMonth; day++) {
            var date = new Date(year, month, day);
            var dateStr = toIsoDateLocal(date);
            var isToday = date.toDateString() === today.toDateString();
            var isSelected = paramDate ? dateStr === paramDate : false;
            var isSunday = date.getDay() === 0;
            var isHoliday = holidayData[dateStr];

            var dayDiv = document.createElement('div');
            dayDiv.className = 'av-calendar__day';
            dayDiv.textContent = day;

            if (isToday) dayDiv.classList.add('av-calendar__day--today');
            if (isSelected) dayDiv.classList.add('av-calendar__day--selected');
            if (isSunday) dayDiv.classList.add('av-calendar__day--sunday');
            if (isHoliday) dayDiv.classList.add('av-calendar__day--holiday');

            dayDiv.addEventListener('click', function(ds) {
                return function() {
                    document.getElementById('selected_date_display').innerText = new Date(ds).toLocaleDateString('en-GB').replace(/\//g, '.');
                    var baseUrl = gatesIndexUrl || window.location.pathname;
                    window.location.href = baseUrl + '?date_from=' + encodeURIComponent(ds);
                };
            }(dateStr));

            if (isSunday) {
                dayDiv.setAttribute('data-tooltip', 'Sunday');
            } else if (isHoliday) {
                dayDiv.setAttribute('data-tooltip', isHoliday);
            }

            container.appendChild(dayDiv);
        }
    }

    var prevBtn = document.getElementById('dock_calendar_prev');
    var nextBtn = document.getElementById('dock_calendar_next');
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderMiniCalendar();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderMiniCalendar();
        });
    }

    renderMiniCalendar();

    var gateViewTabs = document.getElementById('gate_view_tabs');
    var gateShiftFilter = document.getElementById('gate_shift_filter');
    var schedulePanel = document.getElementById('gate_schedule_panel');
    var availabilityPanel = document.getElementById('gate_availability_panel');
    var availabilityList = document.getElementById('gate-availability-list');
    var availabilityLoaded = false;

    function getMinAllowedDateTime() {
        var now = new Date();
        now.setSeconds(0, 0);
        return new Date(now.getTime() + 4 * 60 * 60 * 1000);
    }

    function isTimeAllowed(dateStr, time) {
        if (!dateStr || !time) return true;
        if (time < '07:00' || time > '19:00') return false;
        var minAllowed = getMinAllowedDateTime();
        var selected = new Date(String(dateStr) + 'T' + String(time) + ':00');
        return selected.getTime() >= minAllowed.getTime();
    }

    function slotsHash(slots) {
        return JSON.stringify((slots || []).map(function (s) {
            var rawTime = String((s && s.time) || '');
            return [
                rawTime,
                !!(s && s.is_available),
                Number((s && s.available_gates) || 0),
                !!(s && s.disabled_by_admin),
                !!(s && s.forced_by_admin),
                isTimeAllowed(String(paramDate || ''), rawTime)
            ];
        }));
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? String(meta.getAttribute('content') || '') : '';
    }

    function renderAvailabilityLoading() {
        if (!availabilityList) return;
        availabilityList.innerHTML = '<div class="st-dock-availability__empty"><i class="fas fa-spinner fa-spin"></i><p>Loading availability...</p></div>';
    }

    function renderAvailabilityError(message) {
        if (!availabilityList) return;
        availabilityList.innerHTML = '<div class="st-dock-availability__empty st-dock-availability__empty--error"><i class="fas fa-exclamation-triangle"></i><p>' + stEscapeHtml(message || 'Failed to load availability.') + '</p></div>';
    }

    function renderAvailabilitySlots(slots) {
        if (!availabilityList) return;
        if (!Array.isArray(slots) || slots.length === 0) {
            availabilityList.innerHTML = '<div class="st-dock-availability__empty"><p>No availability data.</p></div>';
            return;
        }

        var normalizedSlots = slots.slice().sort(function (a, b) {
            return String((a && a.time) || '').localeCompare(String((b && b.time) || ''));
        });

        var visibleSlots = normalizedSlots;

        if (visibleSlots.length === 0) {
            availabilityList.innerHTML = '<div class="st-dock-availability__empty"><p>No available times.</p></div>';
            return;
        }

        var midPoint = Math.ceil(visibleSlots.length / 2);
        var col1Slots = visibleSlots.slice(0, midPoint);
        var col2Slots = visibleSlots.slice(midPoint);

        function renderSlotButton(slot) {
            var rawTime = String((slot && slot.time) || '');
            var time = stEscapeHtml(rawTime);
            var gates = Number((slot && slot.available_gates) || 0);
            var isServerAvailable = !!(slot && slot.is_available);
            var disabledByAdmin = !!(slot && slot.disabled_by_admin);
            var forcedByAdmin = !!(slot && slot.forced_by_admin);
            var timeAllowed = isTimeAllowed(String(paramDate || ''), rawTime);
            var isAvailable = forcedByAdmin || (isServerAvailable && timeAllowed);
            var isInactive = !isAvailable;
            var canToggle = true;
            var label = isInactive ? 'Not available' : ('Available (' + gates + ' gates)');

            return '<button type="button" class="st-dock-available-item' + (isInactive ? ' st-dock-available-item--inactive' : '') + (canToggle ? '' : ' st-dock-available-item--readonly') + '" data-time="' + time + '" data-gates="' + gates + '" data-can-toggle="' + (canToggle ? '1' : '0') + '" data-server-available="' + (isServerAvailable ? '1' : '0') + '" data-disabled-by-admin="' + (disabledByAdmin ? '1' : '0') + '" data-forced-by-admin="' + (forcedByAdmin ? '1' : '0') + '" data-time-allowed="' + (timeAllowed ? '1' : '0') + '">'
                + '<span class="st-dock-available-item__time">' + time + '</span>'
                + '<span class="st-dock-available-item__note">' + label + '</span>'
                + '<span class="st-dock-available-item__toggle" aria-hidden="true"></span>'
                + '</button>';
        }

        var html = '<div class="st-dock-availability-grid">';
        html += '<div class="st-dock-availability-col"><div class="st-dock-availability-col__slots">';
        col1Slots.forEach(function (slot) { html += renderSlotButton(slot); });
        html += '</div></div>';

        html += '<div class="st-dock-availability-col"><div class="st-dock-availability-col__slots">';
        if (col2Slots.length === 0) {
            html += '<div class="st-dock-availability__empty st-dock-availability__empty--compact"><p>No slots</p></div>';
        } else {
            col2Slots.forEach(function (slot) { html += renderSlotButton(slot); });
        }
        html += '</div></div>';
        html += '</div>';

        availabilityList.innerHTML = html;

        availabilityList.querySelectorAll('.st-dock-available-item__toggle').forEach(function (toggleEl) {
            toggleEl.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var row = this.closest('.st-dock-available-item');
                if (!row) return;

                var time = String(row.getAttribute('data-time') || '').trim();
                if (!time) return;
                if (String(row.getAttribute('data-can-toggle') || '0') !== '1') return;

                var note = row.querySelector('.st-dock-available-item__note');
                var gatesText = row.getAttribute('data-gates') || '0';
                var isCurrentlyInactive = row.classList.contains('st-dock-available-item--inactive');
                var disabledByAdmin = String(row.getAttribute('data-disabled-by-admin') || '0') === '1';
                var isServerAvailable = String(row.getAttribute('data-server-available') || '0') === '1';
                var timeAllowed = String(row.getAttribute('data-time-allowed') || '0') === '1';

                // OFF -> ON on default-not-available uses force_available
                // ON -> OFF always writes disabled=true
                var payload = {
                    date: String(paramDate),
                    time: time,
                    disabled: false,
                    force_available: false
                };
                if (!isCurrentlyInactive) {
                    payload.disabled = true;
                } else if (disabledByAdmin) {
                    payload.disabled = false;
                    payload.force_available = false;
                } else if (!isServerAvailable || !timeAllowed) {
                    payload.disabled = false;
                    payload.force_available = true;
                }

                row.setAttribute('disabled', 'disabled');

                var token = getCsrfToken();
                if (!disabledTimesUrl || token === '') {
                    row.removeAttribute('disabled');
                    return;
                }

                fetch(disabledTimesUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        date: payload.date,
                        time: payload.time,
                        disabled: payload.disabled,
                        force_available: payload.force_available
                    })
                })
                    .then(function (res) {
                        if (!res.ok) {
                            throw new Error('Failed to update disabled time');
                        }
                        return res.json();
                    })
                    .then(function (data) {
                        if (!data || !data.success) {
                            throw new Error('Invalid response');
                        }
                        loadGateAvailability({ silent: true, force: true });
                    })
                    .catch(function () {
                        if (note) {
                            note.textContent = row.classList.contains('st-dock-available-item--inactive') ? 'Not available' : ('Available (' + gatesText + ' gates)');
                        }
                    })
                    .finally(function () {
                        row.removeAttribute('disabled');
                    });
            });
        });
    }

    function loadGateAvailability(options) {
        options = options || {};
        var silent = !!options.silent;
        var force = !!options.force;
        if (!availabilityList || !availabilityUrl || !paramDate) return;

        if (!silent || !availabilityList.dataset.rendered) {
            renderAvailabilityLoading();
        }

        var query = new URLSearchParams();
        query.set('date', String(paramDate));
        selectedWarehouseIds.forEach(function (id) {
            if (String(id || '').trim() !== '') {
                query.append('warehouse_id[]', String(id));
            }
        });

        fetch(availabilityUrl + '?' + query.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Failed to load availability.');
                }
                return res.json();
            })
            .then(function (data) {
                if (!data || !data.success || !Array.isArray(data.slots)) {
                    if (!silent) {
                        renderAvailabilityError('Availability data is not valid.');
                    }
                    return;
                }

                var nextHash = slotsHash(data.slots);
                if (!force && silent && availabilityList.dataset.slotsHash === nextHash) {
                    return;
                }

                availabilityList.dataset.slotsHash = nextHash;
                renderAvailabilitySlots(data.slots);
                availabilityList.dataset.rendered = '1';
                availabilityLoaded = true;
            })
            .catch(function () {
                if (!silent) {
                    renderAvailabilityError('Failed to load availability.');
                }
            });
    }

    // Hook for global realtime sync in main.js to avoid full page reload on gates page
    window.ajaxReload = function (silent) {
        if (availabilityPanel && availabilityPanel.classList.contains('st-dock-view-panel--active')) {
            loadGateAvailability({ silent: !!silent, force: true });
        }
    };

    var availabilityAutoRefreshTimer = null;
    function startAvailabilityAutoRefresh() {
        if (availabilityAutoRefreshTimer) return;
        availabilityAutoRefreshTimer = window.setInterval(function () {
            if (document.hidden) return;
            if (!availabilityPanel || !availabilityPanel.classList.contains('st-dock-view-panel--active')) return;
            loadGateAvailability({ silent: true });
        }, 2000);
    }

    startAvailabilityAutoRefresh();

    if (gateViewTabs && schedulePanel && availabilityPanel) {
        gateViewTabs.addEventListener('click', function (event) {
            var tabBtn = event.target.closest('.st-dock-view-tab');
            if (!tabBtn) return;
            var view = tabBtn.getAttribute('data-view');
            if (!view) return;

            gateViewTabs.querySelectorAll('.st-dock-view-tab').forEach(function (btn) {
                btn.classList.remove('st-dock-view-tab--active');
            });
            tabBtn.classList.add('st-dock-view-tab--active');

            var isAvailability = view === 'availability';
            schedulePanel.classList.toggle('st-dock-view-panel--active', !isAvailability);
            availabilityPanel.classList.toggle('st-dock-view-panel--active', isAvailability);
            if (gateShiftFilter) {
                gateShiftFilter.style.visibility = isAvailability ? 'hidden' : 'visible';
                gateShiftFilter.style.pointerEvents = isAvailability ? 'none' : 'auto';
            }

            if (isAvailability && !availabilityLoaded) {
                loadGateAvailability();
            }
        });
    }

    // Legend collapsible toggle
    document.querySelectorAll('.st-legend-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            var group = this.closest('.st-legend-group');
            if (group) {
                group.classList.toggle('st-legend-group--collapsed');
            }
        });
    });

    // Gate active toggles (backup gates) - AJAX, no full page reload
    const gateToggles = document.querySelectorAll('.st-gate-active-toggle');
    gateToggles.forEach(t => {
        t.addEventListener('change', function () {
            const input = this;
            const formId = String(input.getAttribute('data-form-id') || '');
            const form = formId ? document.getElementById(formId) : null;
            if (!form) return;

            const col = input.closest('.st-dock-col-header');
            const idx = col && col.parentNode ? Array.from(col.parentNode.children).indexOf(col) : -1;
            if (idx >= 0) {
                const body = document.querySelector('.st-dock-grid-body');
                const gateCols = body ? body.querySelectorAll('.st-dock-gate-col') : [];
                if (gateCols[idx - 1]) {
                    gateCols[idx - 1].classList.toggle('st-hidden', !input.checked);
                }
            }

            const previous = !input.checked;
            input.disabled = true;

            fetch(form.getAttribute('action') || '', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('Failed to toggle gate');
                    }
                    return res.json();
                })
                .then(function (data) {
                    if (!data || data.success !== true) {
                        throw new Error('Invalid toggle response');
                    }
                })
                .catch(function () {
                    input.checked = previous;
                    if (idx >= 0) {
                        const body = document.querySelector('.st-dock-grid-body');
                        const gateCols = body ? body.querySelectorAll('.st-dock-gate-col') : [];
                        if (gateCols[idx - 1]) {
                            gateCols[idx - 1].classList.toggle('st-hidden', !input.checked);
                        }
                    }
                })
                .finally(function () {
                    input.disabled = false;
                });
        });
    });

    document.querySelectorAll('.st-dock-card').forEach(function(card) {
        var top = card.dataset.top;
        var height = card.dataset.height;
        if (top) {
            card.style.top = top + 'px';
        }
        if (height) {
            card.style.height = height + 'px';
        }
    });

    document.querySelectorAll('.st-dock-time-line[data-top]').forEach(function(line) {
        var top = line.dataset.top;
        if (top) {
            line.style.top = top + 'px';
        }
    });
});

window.openApproveModal = function (id, ticket) {
    const modal = document.getElementById('approveModal');
    const ticketSpan = document.getElementById('modalTicketNumber');
    const form = document.getElementById('approveForm');

    var config = stReadJson('gates_index_config', {});
    var baseUrl = stNormalizeUrl(config.bookingsBaseUrl || '/bookings').replace(/\/$/, '');

    ticketSpan.innerText = ticket;
    form.action = baseUrl + '/' + id + '/approve';
    modal.classList.add('active');
};

window.closeApproveModal = function () {
    const modal = document.getElementById('approveModal');
    modal.classList.remove('active');
};

window.openRejectModal = function (bookingId, ticketNumber) {
    const modal = document.getElementById('reject-modal');
    document.getElementById('reject-ticket').textContent = ticketNumber;
    var config = stReadJson('gates_index_config', {});
    var baseUrl = stNormalizeUrl(config.bookingsBaseUrl || '/bookings').replace(/\/$/, '');
    document.getElementById('reject-form').action = baseUrl + '/' + bookingId + '/reject';
    modal.classList.add('active');
};

window.closeRejectModal = function () {
    document.getElementById('reject-modal').classList.remove('active');
};

// Shift Filter Logic
var shiftFilter = document.getElementById('gate_shift_filter');
if (shiftFilter) {
    var shiftRanges = {
        full:   { start: 0, end: 23 },
        shift1: { start: 7, end: 14 },
        shift2: { start: 15, end: 22 },
        shift3: { start: 23, end: 6, wrap: true }
    };

    // Build ordered list of hours for a shift
    function getShiftHours(range) {
        var hours = [];
        if (range.wrap) {
            for (var h = range.start; h <= 23; h++) hours.push(h);
            for (var h2 = 0; h2 <= range.end; h2++) hours.push(h2);
        } else {
            for (var h3 = range.start; h3 <= range.end; h3++) hours.push(h3);
        }
        return hours;
    }

    // Map: for each hour in the shift, what is its slot index (0-based position in the visible grid)
    function buildHourToSlotIndex(hours) {
        var map = {};
        for (var i = 0; i < hours.length; i++) {
            map[hours[i]] = i;
        }
        return map;
    }

    var timeCol = document.querySelector('.st-dock-time-col');

    function applyShiftFilter(shift) {
        var range = shiftRanges[shift] || shiftRanges.full;
        var gridBody = document.querySelector('.st-dock-grid-body');
        var hours = getShiftHours(range);
        var hourMap = buildHourToSlotIndex(hours);
        var slotHeight = 60; // px per hour slot

        // Rebuild time column with correct hour order
        if (timeCol) {
            timeCol.innerHTML = '';
            hours.forEach(function(h) {
                var div = document.createElement('div');
                div.className = 'st-dock-time-slot';
                div.setAttribute('data-hour', h);
                div.textContent = (h < 10 ? '0' : '') + h + ':00';
                timeCol.appendChild(div);
            });
        }

        // Adjust gate column heights
        var colHeight = hours.length * slotHeight;
        document.querySelectorAll('.st-dock-gate-col').forEach(function(col) {
            col.style.height = colHeight + 'px';
            // Update background grid lines to match
            col.style.backgroundSize = '100% ' + slotHeight + 'px';
        });

        // Reposition slot cards
        document.querySelectorAll('.st-dock-card').forEach(function(card) {
            var origTop = parseInt(card.dataset.top, 10) || 0;
            var origHeight = parseInt(card.dataset.height, 10) || 60;
            var cardHour = Math.floor(origTop / 60);
            var minuteInHour = origTop % 60;

            // Check if card's hour is in the visible shift
            if (typeof hourMap[cardHour] !== 'undefined') {
                card.style.display = '';
                var newTop = hourMap[cardHour] * slotHeight + minuteInHour;
                card.style.top = newTop + 'px';
                card.style.height = origHeight + 'px';
            } else {
                card.style.display = 'none';
            }
        });

        // Reposition current time line
        document.querySelectorAll('.st-dock-time-line').forEach(function(line) {
            var origTop = parseInt(line.dataset.top, 10) || 0;
            var lineHour = Math.floor(origTop / 60);
            var minuteInHour = origTop % 60;

            if (typeof hourMap[lineHour] !== 'undefined') {
                line.style.display = '';
                line.style.top = (hourMap[lineHour] * slotHeight + minuteInHour) + 'px';
            } else {
                line.style.display = 'none';
            }
        });

        // Scroll to top
        if (gridBody) {
            gridBody.scrollTop = 0;
        }
    }

    shiftFilter.addEventListener('click', function(e) {
        var btn = e.target.closest('.st-dock-shift-btn');
        if (!btn) return;
        var shift = btn.getAttribute('data-shift');

        // Update active state
        shiftFilter.querySelectorAll('.st-dock-shift-btn').forEach(function(b) {
            b.classList.remove('st-dock-shift-btn--active');
        });
        btn.classList.add('st-dock-shift-btn--active');

        applyShiftFilter(shift);
    });

    // Default: scroll to current hour on load
    var gridBody = document.querySelector('.st-dock-grid-body');
    if (gridBody) {
        var nowHour = new Date().getHours();
        var scrollTo = (nowHour >= 7 ? nowHour : 7) * 60;
        setTimeout(function() { gridBody.scrollTop = scrollTo; }, 100);
    }
}

window.focusSlot = function (id) {
    const card = document.getElementById('slot-' + id);
    if (!card) {
        // If not in this gate grid, check if we need to search or just go to detail
        return;
    }

    // Scroll to the card
    card.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });

    // Highlight effect
    card.style.transition = 'all 0.3s ease';
    card.style.zIndex = '100';
    card.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.8)';
    card.style.transform = 'scale(1.05)';

    setTimeout(() => {
        card.style.boxShadow = '';
        card.style.transform = '';
        card.style.zIndex = '';
    }, 2000);
};

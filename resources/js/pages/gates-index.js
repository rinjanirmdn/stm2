function stReadJson(id, fallback) {
    try {
        var el = document.getElementById(id);
        if (!el) return fallback;
        return JSON.parse(el.textContent || '{}') || fallback;
    } catch (e) {
        return fallback;
    }
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

    // Legend collapsible toggle
    document.querySelectorAll('.st-legend-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            var group = this.closest('.st-legend-group');
            if (group) {
                group.classList.toggle('st-legend-group--collapsed');
            }
        });
    });

    // Interactive toggles demo
    const toggles = document.querySelectorAll('.st-dock-toggle input');
    toggles.forEach(t => {
        t.addEventListener('change', function() {
            const col = this.closest('.st-dock-col-header');
            const idx = Array.from(col.parentNode.children).indexOf(col);
            if (idx >= 0) {
                const body = document.querySelector('.st-dock-grid-body');
                const gateCols = body ? body.querySelectorAll('.st-dock-gate-col') : [];
                if (gateCols[idx - 1]) {
                    gateCols[idx - 1].classList.toggle('st-hidden', !this.checked);
                }
            }
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

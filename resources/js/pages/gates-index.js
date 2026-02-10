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
                dayDiv.setAttribute('data-tooltip', 'Sunday - Not available');
            } else if (isHoliday) {
                dayDiv.setAttribute('data-tooltip', isHoliday + ' - Holiday');
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

// Search Logic: Trigger on Enter, Reset on Clear
const searchInput = document.getElementById('gate_search_input');
if (searchInput) {
    let stGateSearchTimer = null;
    function queueGateSearch(val) {
        if (stGateSearchTimer) {
            clearTimeout(stGateSearchTimer);
        }
        stGateSearchTimer = setTimeout(function () {
            performSearch(val);
        }, 200);
    }

    searchInput.addEventListener('input', function() {
        queueGateSearch(this.value);
    });
}

function performSearch(query) {
    query = query.toLowerCase().trim();
    const cards = document.querySelectorAll('.st-dock-card');

    cards.forEach(card => {
        // Find ticket number
        const ticketElement = card.querySelector('div[style*="font-weight:700"]');
        const ticket = ticketElement ? ticketElement.innerText.toLowerCase() : '';

        // Find vendor name
        const vendorElement = card.querySelector('div[style*="opacity:0.9"]');
        const vendor = vendorElement ? vendorElement.innerText.toLowerCase() : '';

        if (query === '' || ticket.includes(query) || vendor.includes(query)) {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            card.style.filter = 'none';
        } else {
            card.style.opacity = '0.1';
            card.style.pointerEvents = 'none';
            card.style.filter = 'grayscale(100%)';
        }
    });
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

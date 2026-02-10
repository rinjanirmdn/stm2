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

function stBookingsBaseUrl() {
    var config = stReadJson('admin_bookings_index_config', {});
    return stNormalizeUrl(config.bookingsBaseUrl || '/bookings').replace(/\/$/, '');
}

document.addEventListener('DOMContentLoaded', function() {
    const bookingFilterForm = document.getElementById('booking-filter-form');
    if (bookingFilterForm) {
        const searchInput = bookingFilterForm.querySelector('input[name="search"]');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    bookingFilterForm.submit();
                }, 500);
            });
        }

        // Ensure Scheduled filter indicator (planned_start) is in sync with actual URL
        try {
            const url = new URL(window.location.href);
            const hasPlannedStartParam = url.searchParams.has('planned_start');
            const plannedStartInput = bookingFilterForm.querySelector('input[name="planned_start"]');

            if (plannedStartInput && !hasPlannedStartParam) {
                // Force-clear any stale value kept by browser/history
                plannedStartInput.value = '';

                // Hard-disable Scheduled filter indicator on this page
                var scheduledBtn = bookingFilterForm.querySelector('.st-filter-trigger[data-filter="planned_start"]');
                if (scheduledBtn) {
                    scheduledBtn.classList.remove('st-filter-trigger--active', 'is-filtered');
                }

                if (typeof window.stSyncFilterIndicatorsFromForms === 'function') {
                    window.stSyncFilterIndicatorsFromForms(bookingFilterForm);
                }
            }
        } catch (e) {
            // ignore URL parsing errors
        }
    }
});

window.openRejectModal = function (bookingId, ticketNumber) {
    const modal = document.getElementById('reject-modal');
    const ticketEl = document.getElementById('reject-ticket');
    const formEl = document.getElementById('reject-form');
    if (!modal || !ticketEl || !formEl) return;
    ticketEl.textContent = ticketNumber;
    formEl.action = stBookingsBaseUrl() + '/' + bookingId + '/reject';
    modal.classList.add('active');
};

window.closeRejectModal = function () {
    const modal = document.getElementById('reject-modal');
    if (!modal) return;
    modal.classList.remove('active');
};

document.addEventListener('click', function(e) {
    const trigger = e.target.closest ? e.target.closest('.st-action-trigger') : null;
    if (trigger) {
        e.preventDefault();
        e.stopPropagation();
        const menu = trigger.nextElementSibling;
        document.querySelectorAll('.st-action-menu.show').forEach(function(m) {
            if (m !== menu) m.classList.remove('show');
        });
        if (menu) menu.classList.toggle('show');
        return;
    }
    document.querySelectorAll('.st-action-menu.show').forEach(function(m) {
        m.classList.remove('show');
    });
});

window.openApproveModal = function (id, ticket) {
    const modal = document.getElementById('approveModal');
    const ticketSpan = document.getElementById('modalTicketNumber');
    const form = document.getElementById('approveForm');
    if (!modal || !ticketSpan || !form) return;
    ticketSpan.innerText = ticket;
    form.action = stBookingsBaseUrl() + '/' + id + '/approve';
    modal.classList.add('active');
};

window.closeApproveModal = function () {
    const modal = document.getElementById('approveModal');
    if (!modal) return;
    modal.classList.remove('active');
};

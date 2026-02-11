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
    var bookingFilterForm = document.getElementById('booking-filter-form');
    var isLoading = false;

    function buildQueryStringFromForm() {
        if (!bookingFilterForm) return '';
        var fd = new FormData(bookingFilterForm);
        var params = new URLSearchParams();
        fd.forEach(function (v, k) {
            var val = String(v || '').trim();
            if (val !== '') params.append(k, val);
        });
        return params.toString();
    }

    function setLoading(on) {
        isLoading = on;
        var tbody = bookingFilterForm ? bookingFilterForm.querySelector('tbody') : null;
        if (tbody) tbody.style.opacity = on ? '0.5' : '1';
    }

    function ajaxReload(pushState) {
        if (isLoading || !bookingFilterForm) return;
        setLoading(true);

        var qs = buildQueryStringFromForm();
        var url = window.location.pathname + (qs ? ('?' + qs) : '');

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newForm = doc.getElementById('booking-filter-form');
                var tbody = bookingFilterForm.querySelector('tbody');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tbody && newTbody) {
                    tbody.innerHTML = newTbody.innerHTML;
                }
                // Update pagination
                var pag = bookingFilterForm.closest('.st-card') || bookingFilterForm.parentElement;
                var curPag = pag ? pag.querySelector('.st-pagination') : null;
                var newCard = doc.querySelector('.st-card');
                var newPag = newCard ? newCard.querySelector('.st-pagination') : null;
                if (curPag && newPag) {
                    curPag.innerHTML = newPag.innerHTML;
                }
                // Update summary pills
                var curPills = document.querySelector('.st-form-field .st-flex.st-gap-10');
                var newPills = doc.querySelector('.st-form-field .st-flex.st-gap-10');
                if (curPills && newPills) {
                    curPills.innerHTML = newPills.innerHTML;
                }
                if (pushState) {
                    window.history.pushState(null, '', url);
                }
            })
            .catch(function (err) {
                console.error('AJAX reload failed:', err);
            })
            .finally(function () {
                setLoading(false);
            });
    }

    window.ajaxReload = ajaxReload;

    if (bookingFilterForm) {
        var searchInput = bookingFilterForm.querySelector('input[name="search"]');
        if (searchInput) {
            var timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    ajaxReload(true);
                }, 500);
            });
        }

        bookingFilterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            ajaxReload(true);
        });

        document.addEventListener('change', function (e) {
            var t = e.target;
            if (!t) return;
            if (t.matches('select[form="booking-filter-form"], input[type="date"][form="booking-filter-form"]')) {
                ajaxReload(true);
            }
        });

        window.addEventListener('popstate', function () {
            var params = new URLSearchParams(window.location.search);
            bookingFilterForm.querySelectorAll('input, select').forEach(function (el) {
                if (el.type === 'hidden' && el.name === 'status') return;
                if (el.name) el.value = params.get(el.name) || '';
            });
            ajaxReload(false);
        });

        // Ensure Scheduled filter indicator (planned_start) is in sync with actual URL
        try {
            var url = new URL(window.location.href);
            var hasPlannedStartParam = url.searchParams.has('planned_start');
            var plannedStartInput = bookingFilterForm.querySelector('input[name="planned_start"]');

            if (plannedStartInput && !hasPlannedStartParam) {
                plannedStartInput.value = '';
                var scheduledBtn = bookingFilterForm.querySelector('.st-filter-trigger[data-filter="planned_start"]');
                if (scheduledBtn) {
                    scheduledBtn.classList.remove('st-filter-trigger--active', 'is-filtered');
                }
                if (typeof window.stSyncFilterIndicatorsFromForms === 'function') {
                    window.stSyncFilterIndicatorsFromForms(bookingFilterForm);
                }
            }
        } catch (e) {}
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

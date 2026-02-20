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

    function appendControlToParams(params, el) {
        if (!el || !el.name || el.disabled) return;
        var tag = String(el.tagName || '').toLowerCase();
        var type = String(el.type || '').toLowerCase();

        if ((type === 'checkbox' || type === 'radio') && !el.checked) return;

        if (tag === 'select' && el.multiple) {
            Array.prototype.slice.call(el.options || []).forEach(function (opt) {
                if (!opt || !opt.selected) return;
                var val = String(opt.value || '').trim();
                if (val !== '') params.append(el.name, val);
            });
            return;
        }

        var val = String(el.value || '').trim();
        if (val !== '') params.append(el.name, val);
    }

    function buildQueryStringFromForm() {
        if (!bookingFilterForm) return '';
        var params = new URLSearchParams();

        // Include controls inside form
        bookingFilterForm.querySelectorAll('input, select, textarea').forEach(function (el) {
            appendControlToParams(params, el);
        });

        // Include controls outside form linked via form="booking-filter-form"
        var formId = String(bookingFilterForm.getAttribute('id') || '').trim();
        if (formId) {
            document.querySelectorAll('[form="' + CSS.escape(formId) + '"]').forEach(function (el) {
                appendControlToParams(params, el);
            });
        }

        // De-duplicate while preserving order
        var seen = new Set();
        var dedup = new URLSearchParams();
        params.forEach(function (v, k) {
            var sig = k + '::' + v;
            if (seen.has(sig)) return;
            seen.add(sig);
            dedup.append(k, v);
        });
        return dedup.toString();
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

                try {
                    if (typeof window.stSyncFilterIndicatorsFromForms === 'function') {
                        window.stSyncFilterIndicatorsFromForms(bookingFilterForm);
                    }
                } catch (e) {}
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
        var searchInput = document.querySelector('input[name="search"][form="booking-filter-form"]')
            || bookingFilterForm.querySelector('input[name="search"]');
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
            var formId = String(bookingFilterForm.getAttribute('id') || '').trim();
            var controls = Array.prototype.slice.call(bookingFilterForm.querySelectorAll('input, select, textarea'));
            if (formId) {
                controls = controls.concat(Array.prototype.slice.call(document.querySelectorAll('[form="' + CSS.escape(formId) + '"]')));
            }
            controls.forEach(function (el) {
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

        // Ensure Requested At filter indicator (created_at) is in sync with actual URL
        try {
            var url2 = new URL(window.location.href);
            var hasCreatedAtParam = url2.searchParams.has('created_at');
            var createdAtInput = bookingFilterForm.querySelector('input[name="created_at"]');

            if (createdAtInput && !hasCreatedAtParam) {
                createdAtInput.value = '';
                var createdAtBtn = bookingFilterForm.querySelector('.st-filter-trigger[data-filter="created_at"]');
                if (createdAtBtn) {
                    createdAtBtn.classList.remove('st-filter-trigger--active', 'is-filtered');
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
    const gateSelect = document.getElementById('modal_planned_gate_id');
    const availabilityBox = document.getElementById('modal_gate_availability');
    if (!modal || !ticketSpan || !form) return;
    ticketSpan.innerText = ticket;
    form.action = stBookingsBaseUrl() + '/' + id + '/approve';

    function updateGateAvailability(bookingId) {
        if (!availabilityBox || !gateSelect) return;
        const gateId = String(gateSelect.value || '').trim();
        if (!gateId) {
            availabilityBox.textContent = '';
            availabilityBox.className = 'st-text-12 st-mt-4';
            return;
        }

        let cfg = {};
        try {
            cfg = stReadJson('admin_bookings_index_config', {});
        } catch (e) { cfg = {}; }

        const checkUrl = String(cfg.checkGateUrl || '').trim();
        if (!checkUrl) return;

        const url = checkUrl + '?booking_id=' + encodeURIComponent(String(bookingId))
            + '&planned_gate_id=' + encodeURIComponent(String(gateId));

        availabilityBox.textContent = 'Checking availability...';
        availabilityBox.className = 'st-text-12 st-mt-4';

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const ok = !!(data && data.available);
                const label = (data && data.label) ? String(data.label) : (ok ? 'Available' : 'Not Available');
                const reason = (data && data.reason) ? String(data.reason) : '';
                availabilityBox.textContent = reason ? (label + ' - ' + reason) : label;
                availabilityBox.className = 'st-text-12 st-mt-4 ' + (ok ? 'st-text-success' : 'st-text-danger');
            })
            .catch(function () {
                availabilityBox.textContent = 'Not Available';
                availabilityBox.className = 'st-text-12 st-mt-4 st-text-danger';
            });
    }

    if (gateSelect) {
        const handler = function () {
            try { updateGateAvailability(id); } catch (e) {}
        };
        gateSelect.removeEventListener('change', handler);
        gateSelect.addEventListener('change', handler);
        if (gateSelect.value) {
            try { updateGateAvailability(id); } catch (e) {}
        } else if (availabilityBox) {
            availabilityBox.textContent = '';
            availabilityBox.className = 'st-text-12 st-mt-4';
        }
    }
    modal.classList.add('active');
};

window.closeApproveModal = function () {
    const modal = document.getElementById('approveModal');
    if (!modal) return;
    modal.classList.remove('active');
};

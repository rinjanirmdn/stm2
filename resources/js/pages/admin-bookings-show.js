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
    var config = stReadJson('admin_bookings_show_config', {});
    return stNormalizeUrl(config.bookingsBaseUrl || '/bookings').replace(/\/$/, '');
}

document.addEventListener('DOMContentLoaded', function () {
    var gateSel = document.getElementById('approval_planned_gate_id');
    var warehouseHidden = document.getElementById('approval_warehouse_id');

    // Gate and warehouse sync
    function syncWarehouseFromGate() {
        if (!gateSel || !warehouseHidden) return;
        var selected = gateSel.options[gateSel.selectedIndex];
        if (!selected) return;
        warehouseHidden.value = selected.getAttribute('data-warehouse-id') || '';
    }

    // Gate change listener
    if (gateSel) {
        gateSel.addEventListener('change', function() {
            syncWarehouseFromGate();
        });
        // Initial sync if gate is pre-selected
        if (gateSel.value) {
            syncWarehouseFromGate();
        }
    }
});

window.openApproveModal = function (id, ticket) {
    const modal = document.getElementById('approveModal');
    const ticketSpan = document.getElementById('modalTicketNumber');
    const form = document.getElementById('approveForm');
    const gateSelect = document.getElementById('modal_planned_gate_id');
    const warehouseHidden = document.getElementById('modal_warehouse_id');
    const availabilityBox = document.getElementById('modal_gate_availability');

    ticketSpan.innerText = ticket;
    form.action = stBookingsBaseUrl() + '/' + id + '/approve';

    // Gate and warehouse sync for modal
    function syncModalWarehouseFromGate() {
        if (!gateSelect || !warehouseHidden) return;
        const selected = gateSelect.options[gateSelect.selectedIndex];
        if (!selected) return;
        warehouseHidden.value = selected.getAttribute('data-warehouse-id') || '';
    }

    // Gate change listener for modal
    if (gateSelect) {
        gateSelect.addEventListener('change', function() {
            syncModalWarehouseFromGate();
            try { updateGateAvailability(id); } catch (e) {}
        });
        // Initial sync if gate is pre-selected
        if (gateSelect.value) {
            syncModalWarehouseFromGate();
            try { updateGateAvailability(id); } catch (e) {}
        }
    }

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
            const el = document.getElementById('admin_bookings_show_config');
            cfg = el ? (JSON.parse(el.textContent || '{}') || {}) : {};
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

    modal.classList.add('active');
};

window.closeApproveModal = function () {
    document.getElementById('approveModal').classList.remove('active');
};

window.openRejectModal = function (id, ticket) {
    const modal = document.getElementById('reject-modal');
    document.getElementById('reject-ticket').innerText = ticket;
    document.getElementById('reject-form').action = stBookingsBaseUrl() + '/' + id + '/reject';
    modal.classList.add('active');
};

window.closeRejectModal = function () {
    document.getElementById('reject-modal').classList.remove('active');
};

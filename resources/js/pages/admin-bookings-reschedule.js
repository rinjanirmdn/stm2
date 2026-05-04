function stReadJson(id, fallback) {
    try {
        var el = document.getElementById(id);
        if (!el) return fallback;
        return JSON.parse(el.textContent || '{}') || fallback;
    } catch (e) {
        return fallback;
    }
}

(function() {
    var config = stReadJson('admin_bookings_reschedule_config', {});
    var bookingId = config.bookingId || null;
    var checkGateUrl = String(config.checkGateUrl || '').trim();
    var calendarBaseUrl = String(config.calendarBaseUrl || '').trim();

    var gateSelect = document.getElementById('gate_select');
    var warehouseHidden = document.getElementById('warehouse_hidden');
    var availabilityBox = document.getElementById('reschedule_gate_availability');
    var dateInput = document.getElementById('planned_date');
    var timeInput = document.getElementById('planned_time');
    var durationInput = document.getElementById('planned_duration');
    var availabilityList = document.getElementById('reschedule_availability_list');

    function syncWarehouseFromGate() {
        if (!gateSelect || !warehouseHidden) return;
        var selected = gateSelect.options[gateSelect.selectedIndex];
        if (!selected) return;
        warehouseHidden.value = selected.getAttribute('data-warehouse-id') || '';
    }

    function updateGateAvailability() {
        if (!gateSelect || !availabilityBox || !checkGateUrl || !bookingId) return;
        var gateId = String(gateSelect.value || '').trim();
        if (!gateId) {
            availabilityBox.textContent = '';
            availabilityBox.className = 'st-text-12 st-mt-4';
            return;
        }

        var plannedDate = dateInput ? String(dateInput.value || '').trim() : '';
        var plannedTime = timeInput ? String(timeInput.value || '').trim() : '';
        var plannedDuration = durationInput ? String(durationInput.value || '').trim() : '';

        var params = 'booking_id=' + encodeURIComponent(String(bookingId))
            + '&planned_gate_id=' + encodeURIComponent(String(gateId));
        if (plannedDate && plannedTime && plannedDuration) {
            params += '&planned_date=' + encodeURIComponent(plannedDate)
                + '&planned_time=' + encodeURIComponent(plannedTime)
                + '&planned_duration=' + encodeURIComponent(plannedDuration);
        }

        var url = checkGateUrl + '?' + params;

        availabilityBox.textContent = 'Checking availability...';
        availabilityBox.className = 'st-text-12 st-mt-4';

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var ok = !!(data && data.available);
                var label = (data && data.label) ? String(data.label) : (ok ? 'Available' : 'Not Available');
                var reason = (data && data.reason) ? String(data.reason) : '';
                availabilityBox.textContent = reason ? (label + ' - ' + reason) : label;
                availabilityBox.className = 'st-text-12 st-mt-4 ' + (ok ? 'st-text-success' : 'st-text-danger');
            })
            .catch(function() {
                availabilityBox.textContent = 'Not Available';
                availabilityBox.className = 'st-text-12 st-mt-4 st-text-danger';
            });
    }

    function loadAvailabilityList() {
        if (!availabilityList || !calendarBaseUrl) return;
        var dateVal = dateInput ? String(dateInput.value || '').trim() : '';
        var gateId = gateSelect ? String(gateSelect.value || '').trim() : '';

        // Always use the warehouse tied to the currently selected gate
        var currentWarehouseId = null;
        if (warehouseHidden && String(warehouseHidden.value || '').trim() !== '') {
            currentWarehouseId = String(warehouseHidden.value || '').trim();
        } else if (config && config.warehouseId) {
            // Fallback to initial warehouse from config if hidden not set yet
            currentWarehouseId = String(config.warehouseId);
        }

        if (!dateVal || !gateId || !currentWarehouseId) {
            var missingParts = [];
            if (!dateVal) missingParts.push('date');
            if (!gateId) missingParts.push('gate');
            availabilityList.innerHTML = '<div class="st-info-message st-info-message--neutral">' +
                '<i class="fas fa-info-circle"></i> ' +
                '<span>Please select a <strong>' + missingParts.join('</strong> and <strong>') + '</strong> to view existing schedules.</span>' +
                '</div>';
            return;
        }

        // Format the date for display
        var dateParts = dateVal.split('-');
        var displayDate = dateParts.length === 3 ? (dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0]) : dateVal;

        // Get gate label for display
        var gateLabel = '';
        if (gateSelect && gateSelect.selectedIndex >= 0) {
            gateLabel = gateSelect.options[gateSelect.selectedIndex].textContent.trim();
        }

        var url = calendarBaseUrl + '?warehouse_id=' + encodeURIComponent(String(currentWarehouseId)) +
            '&date=' + encodeURIComponent(dateVal);

        availabilityList.innerHTML = '<div class="st-info-message st-info-message--neutral">' +
            '<i class="fas fa-spinner fa-spin"></i> ' +
            '<span>Loading schedules for <strong>' + displayDate + '</strong>...</span>' +
            '</div>';

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data || !data.success || !Array.isArray(data.gates)) {
                    availabilityList.innerHTML = '<div class="st-info-message st-info-message--error">' +
                        '<i class="fas fa-exclamation-circle"></i> ' +
                        '<span>Failed to load schedule data. Please try again.</span>' +
                        '</div>';
                    return;
                }

                // Find gate in response
                var gateBlock = null;
                data.gates.forEach(function(g) {
                    if (g && g.gate && String(g.gate.id) === String(gateId)) {
                        gateBlock = g;
                    }
                });

                if (!gateBlock || !Array.isArray(gateBlock.slots) || gateBlock.slots.length === 0) {
                    availabilityList.innerHTML = '<div class="st-info-message st-info-message--success">' +
                        '<i class="fas fa-check-circle"></i> ' +
                        '<span>No existing schedules on <strong>' + displayDate + '</strong>' +
                        (gateLabel ? ' at <strong>' + gateLabel + '</strong>' : '') +
                        '. All time slots are available.</span>' +
                        '</div>';
                    return;
                }

                // Sort slots by start_time
                var slots = gateBlock.slots.slice().sort(function(a, b) {
                    var ta = (a.start_time || '').localeCompare(b.start_time || '');
                    return ta;
                });

                var html = '<div class="st-info-message st-info-message--warning">' +
                    '<i class="fas fa-exclamation-triangle"></i> ' +
                    '<span>There ' + (slots.length === 1 ? 'is <strong>1</strong> existing schedule' : 'are <strong>' + slots.length + '</strong> existing schedules') +
                    ' on <strong>' + displayDate + '</strong>' +
                    (gateLabel ? ' at <strong>' + gateLabel + '</strong>' : '') +
                    '. Please choose a time that does not overlap.</span>' +
                    '</div>';

                html += '<ul class="reschedule-availability-ul">';
                slots.forEach(function(s) {
                    var time = (s.start_time || '') + ' - ' + (s.end_time || '');
                    var vendor = s.vendor_name || '-';
                    var status = s.status_label || s.status || '';
                    html += '<li class="reschedule-availability-item">' +
                        '<div class="reschedule-availability-time"><i class="fas fa-clock"></i> ' + time + '</div>' +
                        '<div class="reschedule-availability-meta">' +
                        '<span class="reschedule-availability-vendor"><i class="fas fa-building"></i> ' + vendor + '</span>' +
                        (status ? '<span class="reschedule-availability-status">' + status + '</span>' : '') +
                        '</div>' +
                        '</li>';
                });
                html += '</ul>';
                availabilityList.innerHTML = html;
            })
            .catch(function() {
                availabilityList.innerHTML = '<div class="st-info-message st-info-message--error">' +
                    '<i class="fas fa-exclamation-circle"></i> ' +
                    '<span>Network error. Failed to load schedules.</span>' +
                    '</div>';
            });
    }

    if (gateSelect) {
        gateSelect.addEventListener('change', function() {
            syncWarehouseFromGate();
            try { updateGateAvailability(); } catch (e) {}
            try { loadAvailabilityList(); } catch (e) {}
        });
        if (gateSelect.value) {
            syncWarehouseFromGate();
            try { updateGateAvailability(); } catch (e) {}
            try { loadAvailabilityList(); } catch (e) {}
        }
    }

    if (dateInput) {
        dateInput.addEventListener('change', function() {
            try { updateGateAvailability(); } catch (e) {}
            try { loadAvailabilityList(); } catch (e) {}
        });
    }

    if (timeInput) {
        timeInput.addEventListener('change', function() {
            try { updateGateAvailability(); } catch (e) {}
        });
    }

    if (durationInput) {
        durationInput.addEventListener('change', function() {
            try { updateGateAvailability(); } catch (e) {}
        });
    }
})();

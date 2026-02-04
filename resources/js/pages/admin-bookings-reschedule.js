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

document.addEventListener('DOMContentLoaded', function() {
    var config = stReadJson('admin_bookings_reschedule_config', {});
    var bookingId = config.bookingId || null;
    var defaultWarehouseId = config.warehouseId || '';
    var calendarBaseUrl = stNormalizeUrl(config.calendarBaseUrl || '/bookings/ajax/calendar');
    const dateInput = document.getElementById('planned_date');
    const timeInput = document.getElementById('planned_time');
    const durationInput = document.getElementById('planned_duration');
    const gateSelect = document.getElementById('gate_select');
    const availabilityCheck = document.getElementById('availability-check');
    const availabilityResult = document.getElementById('availability-result');
    const calendarPreview = document.getElementById('calendar-preview');
    const warehouseHidden = document.getElementById('warehouse_hidden');

    // Gate and warehouse sync
    function syncWarehouseFromGate() {
        const gateSelect = document.getElementById('gate_select');
        if (!gateSelect || !warehouseHidden) return;
        const selected = gateSelect.options[gateSelect.selectedIndex];
        if (!selected) return;
        warehouseHidden.value = selected.getAttribute('data-warehouse-id') || '';

        // Load availability after warehouse changes
        checkAvailability();
        loadCalendarPreview();
    }

    // Check availability when inputs change
    [dateInput, timeInput, durationInput, gateSelect].forEach(function(input) {
        input.addEventListener('change', checkAvailability);
    });

    function checkAvailability() {
        const gateId = gateSelect.value;
        const date = dateInput.value;
        const time = timeInput.value;
        const duration = durationInput.value;

        if (!date || !time || !duration) {
            availabilityCheck.style.display = 'none';
            return;
        }

        const plannedStart = date + ' ' + time + ':00';

        const warehouseId = (warehouseHidden && warehouseHidden.value) ? warehouseHidden.value : defaultWarehouseId;
        var params = `warehouse_id=${warehouseId}&gate_id=${gateId}&planned_start=${encodeURIComponent(plannedStart)}&planned_duration=${duration}`;
        if (bookingId) {
            params += `&exclude_slot_id=${bookingId}`;
        }

        fetch(`${calendarBaseUrl}?${params}`)
            .then(response => response.json())
            .then(data => {
                availabilityCheck.style.display = 'block';

                if (data.available !== false) {
                    availabilityResult.innerHTML = `
                        <div class="st-flex st-items-center st-gap-8 st-text-success">
                            <i class="fas fa-check-circle"></i>
                            <span>Time Slot Is Available</span>
                        </div>
                    `;
                } else {
                    availabilityResult.innerHTML = `
                        <div class="st-flex st-items-center st-gap-8 st-text-danger">
                            <i class="fas fa-times-circle"></i>
                            <span>${data.reason || 'Time Slot Is Not Available'}</span>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    function loadCalendarPreview() {
        const date = dateInput.value;

        const warehouseId = (warehouseHidden && warehouseHidden.value) ? warehouseHidden.value : defaultWarehouseId;

        if (!date) {
            return;
        }

        calendarPreview.innerHTML = '<p class="st-text-center st-text--muted st-p-20"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

        fetch(`${calendarBaseUrl}?warehouse_id=${warehouseId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.gates) {
                    renderCalendar(data.gates);
                }
            })
            .catch(error => {
                calendarPreview.innerHTML = '<p class="st-text-center st-text-danger st-p-20">Failed to load</p>';
            });
    }

    function renderCalendar(gates) {
        const hours = [];
        for (let h = 7; h < 23; h++) {
            hours.push(h.toString().padStart(2, '0') + ':00');
        }

        let html = '<div class="st-cal-preview__wrap"><table class="st-cal-preview__table">';
        html += '<thead><tr><th>Time</th>';

        gates.forEach(g => {
            html += `<th>${g.gate.name}</th>`;
        });
        html += '</tr></thead><tbody>';

        hours.forEach(hour => {
            html += `<tr><td class="st-font-semibold">${hour}</td>`;

            gates.forEach(g => {
                const slot = g.slots.find(s => s.start_time === hour);
                const occupiedSlot = g.slots.find(s => s.start_time < hour && s.end_time > hour);

                if (slot) {
                    const isPending = slot.status === 'pending_approval';
                    const isCurrentBooking = bookingId ? slot.id === bookingId : false;
                    const bgColor = isCurrentBooking ? '#fef3c7' : (isPending ? '#fce7f3' : '#dcfce7');

                    const stateClass = isPending ? 'st-cal-preview__pending' : 'st-cal-preview__occupied';
                    const currentBadge = isCurrentBooking ? '<div class="st-cal-preview__current">Current</div>' : '';
                    html += `<td class="${stateClass}">
                        <div class="st-font-semibold">${slot.start_time} - ${slot.end_time}</div>
                        <div class="st-text--sm st-text--muted">${slot.vendor_name}</div>
                        ${currentBadge}
                    </td>`;
                } else if (!occupiedSlot) {
                    html += `<td class="st-cal-preview__slot">
                        <i class="fas fa-check st-cal-preview__slot-icon"></i>
                    </td>`;
                } else {
                    html += `<td></td>`;
                }
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        calendarPreview.innerHTML = html;
    }

    // Gate change listener
    if (gateSelect) {
        gateSelect.addEventListener('change', function() {
            syncWarehouseFromGate();
        });
        // Initial sync if gate is pre-selected
        if (gateSelect.value) {
            syncWarehouseFromGate();
        }
    }

    // Initial load
    checkAvailability();
    loadCalendarPreview();
});

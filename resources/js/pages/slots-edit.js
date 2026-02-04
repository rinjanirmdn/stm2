document.addEventListener('DOMContentLoaded', function() {
        var warehouseSelect = document.getElementById('warehouse_id');
        var plannedStartInput = document.getElementById('planned_start_input');
        var plannedStartDateInput = document.getElementById('planned_start_date_input');
        var plannedStartTimeInput = document.getElementById('planned_start_time_input');
        var plannedDurationInput = document.querySelector('input[name="planned_duration"]');
        var durationUnitSelect = document.querySelector('select[name="duration_unit"]');
        var truckTypeSelect = document.getElementById('truck_type');
        var gateSelect = document.getElementById('planned_gate_id');

        var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

        function syncWarehouseFromGate() {
            if (!warehouseSelect || !gateSelect) return;
            var selected = gateSelect.options[gateSelect.selectedIndex];
            if (!selected) return;
            var wh = selected.getAttribute('data-warehouse-id') || '';
            warehouseSelect.value = wh;
        }

        function applyDatepickerTooltips(inst) {
            if (!inst || !inst.dpDiv) return;
            const dp = window.jQuery(inst.dpDiv);

            dp.find('td.is-holiday').each(function() {
                const cell = window.jQuery(this);
                const dayText = cell.find('a, span').first().text();
                if (!dayText) return;
                const fallbackYear = inst.drawYear ?? inst.selectedYear;
                const fallbackMonth = inst.drawMonth ?? inst.selectedMonth;
                const year = cell.data('year') ?? fallbackYear;
                const month = cell.data('month') ?? fallbackMonth;
                if (year === undefined || month === undefined) return;
                const ds = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayText).padStart(2, '0')}`;
                const title = holidayData[ds] || '';
                if (title) {
                    cell.attr('data-st-tooltip', title);
                    cell.find('a, span').attr('data-st-tooltip', title);
                }
                cell.removeAttr('title');
                cell.find('a, span').removeAttr('title');
            });
        }

        function bindDatepickerHover(inst) {
            if (!inst || !inst.dpDiv) return;
            const dp = window.jQuery(inst.dpDiv);
            let hideTimer = null;
            let tooltip = document.getElementById('st-datepicker-tooltip');
            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.id = 'st-datepicker-tooltip';
                tooltip.className = 'st-datepicker-tooltip';
                document.body.appendChild(tooltip);
            }

            dp.off('mouseenter.st-tooltip mousemove.st-tooltip mouseleave.st-tooltip', 'td.is-holiday');
            dp.on('mouseenter.st-tooltip', 'td.is-holiday', function(event) {
                const text = window.jQuery(this).attr('data-st-tooltip') || '';
                if (!text) return;
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
                tooltip.textContent = text;
                tooltip.classList.add('st-datepicker-tooltip--visible');
                tooltip.style.left = `${event.clientX + 12}px`;
                tooltip.style.top = `${event.clientY + 12}px`;
            });
            dp.on('mousemove.st-tooltip', 'td.is-holiday', function(event) {
                tooltip.style.left = `${event.clientX + 12}px`;
                tooltip.style.top = `${event.clientY + 12}px`;
            });
            dp.on('mouseleave.st-tooltip', 'td.is-holiday', function() {
                hideTimer = setTimeout(function() {
                    tooltip.classList.remove('st-datepicker-tooltip--visible');
                }, 300);
            });
        }

        function syncPlannedStart() {
            if (!plannedStartInput || !plannedStartDateInput || !plannedStartTimeInput) return;
            var dateVal = (plannedStartDateInput.value || '').trim();
            var timeVal = (plannedStartTimeInput.value || '').trim();
            if (dateVal && timeVal) {
                plannedStartInput.value = dateVal + ' ' + timeVal;
            } else if (dateVal) {
                plannedStartInput.value = dateVal;
            } else {
                plannedStartInput.value = '';
            }
        }

        function initEtaDatepicker() {
            return;
        }

        function initEtaTimepicker() {
            if (!plannedStartTimeInput) return;
            if (plannedStartTimeInput.getAttribute('data-st-timepicker') === '1') return;
            if (typeof window.mdtimepicker !== 'function') return;
            plannedStartTimeInput.setAttribute('data-st-timepicker', '1');

            plannedStartTimeInput.addEventListener('keydown', function (event) { event.preventDefault(); });
            plannedStartTimeInput.addEventListener('paste', function (event) { event.preventDefault(); });

            window.mdtimepicker('#planned_start_time_input', {
                format: 'hh:mm',
                is24hour: true,
                theme: 'cyan',
                hourPadding: true
            });

            plannedStartTimeInput.addEventListener('change', function () {
                syncPlannedStart();
            });
        }

        if (gateSelect && gateSelect.value) {
            syncWarehouseFromGate();
            initEtaDatepicker();
            initEtaTimepicker();
        }

        if (gateSelect) {
            gateSelect.addEventListener('change', function() {
                syncWarehouseFromGate();
                var enabled = !!gateSelect.value;
                if (plannedStartDateInput) plannedStartDateInput.disabled = !enabled;
                if (plannedStartTimeInput) plannedStartTimeInput.disabled = !enabled;
                if (enabled) {
                    initEtaDatepicker();
                    initEtaTimepicker();
                } else {
                    plannedStartInput.value = '';
                    if (plannedStartDateInput) plannedStartDateInput.value = '';
                    if (plannedStartTimeInput) plannedStartTimeInput.value = '';
                }
            });
        }

        if (plannedStartInput && plannedStartInput.value) {
            var parts = plannedStartInput.value.split(' ');
            if (plannedStartDateInput) plannedStartDateInput.value = parts[0] || '';
            if (plannedStartTimeInput) plannedStartTimeInput.value = (parts[1] || '').slice(0, 5);
        }

        // Logic for Truck Type Durations
        var typeDurations = {};
        try {
            typeDurations = JSON.parse(document.getElementById('truck_type_durations_json').textContent);
        } catch(e) {}

        if (truckTypeSelect && plannedDurationInput) {
            truckTypeSelect.addEventListener('change', function() {
                var val = truckTypeSelect.value;
                if (val && typeDurations[val]) {
                    plannedDurationInput.value = typeDurations[val];
                    if (durationUnitSelect) durationUnitSelect.value = 'minutes';
                }
            });
        }
    });

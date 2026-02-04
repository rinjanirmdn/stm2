document.addEventListener('DOMContentLoaded', function() {
        var arrivalInput = document.getElementById('arrival_time_input');
        var arrivalDateInput = document.getElementById('arrival_date_input');
        var arrivalTimeInput = document.getElementById('arrival_time_only_input');
        var gateSelect = document.getElementById('unplanned-gate');
        var warehouseSelect = document.getElementById('unplanned-warehouse');

        var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

        function syncWarehouseFromGate() {
            if (!warehouseSelect || !gateSelect) return;
            var selected = gateSelect.options[gateSelect.selectedIndex];
            if (!selected) return;
            warehouseSelect.value = selected.getAttribute('data-warehouse-id') || '';
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

        function syncArrivalValue() {
            if (!arrivalInput || !arrivalDateInput || !arrivalTimeInput) return;
            var dateVal = (arrivalDateInput.value || '').trim();
            var timeVal = (arrivalTimeInput.value || '').trim();
            if (dateVal && timeVal) {
                arrivalInput.value = dateVal + ' ' + timeVal;
            } else if (dateVal) {
                arrivalInput.value = dateVal;
            } else {
                arrivalInput.value = '';
            }
        }

        function initArrivalDatepicker() {
            return;
        }

        function initArrivalTimepicker() {
            if (!arrivalTimeInput) return;
            if (arrivalTimeInput.getAttribute('data-st-timepicker') === '1') return;
            if (typeof window.mdtimepicker !== 'function') return;
            arrivalTimeInput.setAttribute('data-st-timepicker', '1');

            arrivalTimeInput.addEventListener('keydown', function (event) { event.preventDefault(); });
            arrivalTimeInput.addEventListener('paste', function (event) { event.preventDefault(); });

            window.mdtimepicker('#arrival_time_only_input', {
                format: 'hh:mm',
                is24hour: true,
                theme: 'cyan',
                hourPadding: true
            });

            arrivalTimeInput.addEventListener('change', function () {
                syncArrivalValue();
            });
        }

        initArrivalDatepicker();
        initArrivalTimepicker();

        if (gateSelect) {
            gateSelect.addEventListener('change', syncWarehouseFromGate);
            if (gateSelect.value) {
                syncWarehouseFromGate();
            }
        }

        if (arrivalInput && arrivalInput.value) {
            var parts = arrivalInput.value.split(' ');
            if (arrivalDateInput) arrivalDateInput.value = parts[0] || '';
            if (arrivalTimeInput) arrivalTimeInput.value = (parts[1] || '').slice(0, 5);
        }
    });

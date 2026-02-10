document.addEventListener('DOMContentLoaded', function () {
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    function toIsoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function toDisplayDate(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    }

    function dmyToIso(value) {
        if (!value) return '';
        const parts = String(value).split('-');
        if (parts.length !== 3) return value;
        const [d, m, y] = parts;
        return `${y}-${m}-${d}`;
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
                cell.attr('data-vendor-tooltip', title);
                cell.find('a, span').attr('data-vendor-tooltip', title);
            }
            cell.removeAttr('title');
            cell.find('a, span').removeAttr('title');
        });
    }

    function bindDatepickerHover(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);
        let hideTimer = null;
        let tooltip = document.getElementById('vendor-datepicker-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'vendor-datepicker-tooltip';
            tooltip.className = 'vendor-datepicker-tooltip';
            document.body.appendChild(tooltip);
        }

        dp.off('mouseenter.vendor-tooltip mousemove.vendor-tooltip mouseleave.vendor-tooltip', 'td.is-holiday');
        dp.on('mouseenter.vendor-tooltip', 'td.is-holiday', function(event) {
            const text = window.jQuery(this).attr('data-vendor-tooltip') || '';
            if (!text) return;
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
            tooltip.textContent = text;
            tooltip.classList.add('vendor-datepicker-tooltip--visible');
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mousemove.vendor-tooltip', 'td.is-holiday', function(event) {
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mouseleave.vendor-tooltip', 'td.is-holiday', function() {
            hideTimer = setTimeout(function() {
                tooltip.classList.remove('vendor-datepicker-tooltip--visible');
            }, 300);
        });
    }

    function initDatepicker(el, beforeShowDay, onSelect) {
        if (!el) return;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
        if (el.getAttribute('data-vendor-datepicker') === '1') return;
        el.setAttribute('data-vendor-datepicker', '1');

        window.jQuery(el).datepicker({
            dateFormat: 'dd-mm-yy',
            beforeShowDay: beforeShowDay,
            beforeShow: function(input, inst) {
                setTimeout(function() {
                    applyDatepickerTooltips(inst);
                    bindDatepickerHover(inst);
                }, 0);
            },
            onChangeMonthYear: function(year, month, inst) {
                setTimeout(function() {
                    applyDatepickerTooltips(inst);
                    bindDatepickerHover(inst);
                }, 0);
            },
            onSelect: onSelect
        });

        const inst = window.jQuery(el).data('datepicker');
        if (inst) {
            applyDatepickerTooltips(inst);
            bindDatepickerHover(inst);
        }
    }

    // Date Range Picker (single date)
    var dateRangeInput = document.getElementById('date-range');
    if (dateRangeInput && window.jQuery && window.jQuery.fn.dateRangePicker) {
        var dateFrom = document.getElementById('date_from');
        var dateTo = document.getElementById('date_to');
        var initial = dateFrom && dateFrom.value ? dateFrom.value : '';
        if (initial) {
            const dt = new Date(initial);
            if (!isNaN(dt.getTime())) {
                dateRangeInput.value = toDisplayDate(dt);
            }
        }

        window.jQuery(dateRangeInput).dateRangePicker({
            autoClose: true,
            singleDate: true,
            showShortcuts: false,
            singleMonth: true,
            format: 'DD-MM-YYYY'
        }).bind('datepicker-change', function(event, obj) {
            var value = (obj && obj.value) ? obj.value : '';
            var iso = dmyToIso(value);
            if (dateFrom) dateFrom.value = iso;
            if (dateTo) dateTo.value = iso;
            dateRangeInput.value = value;
        });
    }

    var inputs = document.querySelectorAll('input.flatpickr-date');
    Array.prototype.slice.call(inputs).forEach(function (input) {
        initDatepicker(input, function(date) {
            const ds = toIsoDate(date);
            if (holidayData[ds]) {
                return [true, 'is-holiday', holidayData[ds]];
            }
            return [true, '', ''];
        });
    });
});

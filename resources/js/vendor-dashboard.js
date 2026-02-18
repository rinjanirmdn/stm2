/* Vendor Dashboard (Vendor Portal)
   - Single-field date range picker (admin-like) using jQuery daterangepicker
   - Status Overview bar chart heights driven by data attributes (no inline CSS)
*/

function stVendorWhenJQReady(fn) {
    const has = () => {
        const jq = window.jQuery;
        return !!(jq && jq.fn && jq.fn.daterangepicker && window.moment);
    };

    if (has()) {
        fn();
        return;
    }

    let tries = 0;
    const t = setInterval(() => {
        tries++;
        if (has() || tries > 40) {
            clearInterval(t);
            if (has()) fn();
        }
    }, 150);
}

function stFmtDisplay(iso) {
    if (!iso) return '';
    const p = String(iso).split('-');
    return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : String(iso);
}

function stMapRangeLabelToKey(label) {
    const s = String(label || '').toLowerCase().trim();
    if (s === 'today') return 'today';
    if (s === 'yesterday') return 'yesterday';
    if (s === 'last 7 days') return '7';
    if (s === 'last 30 days') return '30';
    if (s === 'this month') return 'this_month';
    if (s === 'last month') return 'last_month';
    return 'custom';
}

function stVendorHolidayData() {
    try {
        if (typeof window.getIndonesiaHolidays === 'function') {
            return window.getIndonesiaHolidays() || {};
        }
    } catch (e) {}
    return {};
}

function stVendorDateClasses(m, holidayData) {
    if (!m) return '';
    var classes = [];
    var iso = m.format('YYYY-MM-DD');
    if (m.day() === 0) classes.push('drp-sunday');
    if (holidayData && holidayData[iso]) classes.push('drp-holiday');
    return classes.join(' ');
}

function stVendorResolvePickerCellIso(cellEl) {
    if (!cellEl || !window.jQuery || typeof window.moment !== 'function') return '';
    var cell = window.jQuery(cellEl);
    var day = parseInt(cell.text().trim(), 10);
    if (!Number.isFinite(day)) return '';
    var monthText = cell.closest('table').find('.month').first().text().trim();
    if (!monthText) return '';

    var baseMonth = window.moment(monthText, ['MMM YYYY', 'MMMM YYYY'], true);
    if (!baseMonth.isValid()) baseMonth = window.moment(monthText, ['MMM YYYY', 'MMMM YYYY'], false);
    if (!baseMonth.isValid()) return '';

    var month = baseMonth.month();
    var year = baseMonth.year();
    if (cell.hasClass('off')) {
        if (day >= 20) month -= 1;
        else month += 1;
        if (month < 0) { month = 11; year -= 1; }
        if (month > 11) { month = 0; year += 1; }
    }

    return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
}

function stVendorDecoratePickerDays(picker, holidayData) {
    if (!picker || !picker.container || !window.jQuery) return;
    picker.container.find('td.available').each(function () {
        var td = window.jQuery(this);
        var hasSunday = td.hasClass('drp-sunday');
        var hasHoliday = td.hasClass('drp-holiday');
        var tooltipText = '';

        if (hasHoliday) {
            var iso = stVendorResolvePickerCellIso(this);
            tooltipText = (iso && holidayData && holidayData[iso]) ? holidayData[iso] : 'Holiday';
        } else if (hasSunday) {
            tooltipText = 'Sunday';
        }

        if (tooltipText) {
            td.attr('data-tooltip', tooltipText);
            td.find('a, span').attr('data-tooltip', tooltipText);
        } else {
            td.removeAttr('data-tooltip');
            td.find('a, span').removeAttr('data-tooltip');
        }
    });
}

function stVendorBindPickerDecorators($el, holidayData) {
    if (!$el || !$el.length) return;
    $el.off('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo');
    $el.on('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo', function (_ev, picker) {
        stVendorDecoratePickerDays(picker, holidayData);
    });
    var picker = $el.data('daterangepicker');
    if (picker) {
        setTimeout(function () {
            stVendorDecoratePickerDays(picker, holidayData);
        }, 0);
    }
}

function initVendorDashboardRangePicker() {
    const btn = document.getElementById('vd-range-picker');
    const labelEl = document.getElementById('vd-range-picker-label');
    const rangeStart = document.getElementById('vd-range-start');
    const rangeEnd = document.getElementById('vd-range-end');
    const rangeKey = document.getElementById('vd-date-range');

    if (!btn || !labelEl || !rangeStart || !rangeEnd || !rangeKey) {
        return;
    }

    stVendorWhenJQReady(() => {
        const $ = window.jQuery;
        const moment = window.moment;
        const holidayData = stVendorHolidayData();

        const startIso = rangeStart.value || moment().startOf('month').format('YYYY-MM-DD');
        const endIso = rangeEnd.value || moment().endOf('month').format('YYYY-MM-DD');

        const start = moment(startIso, 'YYYY-MM-DD');
        const end = moment(endIso, 'YYYY-MM-DD');

        const setLabel = (s, e) => {
            labelEl.textContent = `${s.format('DD-MM-YYYY')} — ${e.format('DD-MM-YYYY')}`;
        };

        setLabel(start, end);

        // Attach picker to the button-like element
        $(btn).daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: { format: 'DD-MM-YYYY' },
            alwaysShowCalendars: true,
            opens: 'left',
            isCustomDate: function (d) { return stVendorDateClasses(d, holidayData); }
        }, function (s, e, chosenLabel) {
            setLabel(s, e);
            rangeStart.value = s.format('YYYY-MM-DD');
            rangeEnd.value = e.format('YYYY-MM-DD');
            rangeKey.value = stMapRangeLabelToKey(chosenLabel);
        });

        stVendorBindPickerDecorators($(btn), holidayData);
    });
}

function initVendorDashboardStatusBars() {
    const reactRoot = document.getElementById('vendor-status-overview-react');
    if (reactRoot) return;

    const root = document.getElementById('statusBarChart');
    if (!root) return;

    const items = Array.from(root.querySelectorAll('.vd-bars-container .vd-bar-item'));
    if (items.length === 0) return;

    const counts = items.map((el) => {
        const n = parseFloat(el.getAttribute('data-count') || '0');
        return Number.isFinite(n) ? n : 0;
    });
    const max = Math.max(1, ...counts);

    items.forEach((el) => {
        const n = parseFloat(el.getAttribute('data-count') || '0');
        const v = Number.isFinite(n) ? n : 0;
        const pct = (v / max) * 100;
        const color = el.getAttribute('data-color') || '#94a3b8';
        el.style.setProperty('--bar-height', `${pct}%`);
        el.style.setProperty('--bar-color', color);

        // Keep legacy tooltip CSS disabled by not relying on data-count tooltip content
        el.setAttribute('data-count', String(v));
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initVendorDashboardRangePicker();
    initVendorDashboardStatusBars();
});

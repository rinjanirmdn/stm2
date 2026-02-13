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
            opens: 'left'
        }, function (s, e, chosenLabel) {
            setLabel(s, e);
            rangeStart.value = s.format('YYYY-MM-DD');
            rangeEnd.value = e.format('YYYY-MM-DD');
            rangeKey.value = stMapRangeLabelToKey(chosenLabel);
        });
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

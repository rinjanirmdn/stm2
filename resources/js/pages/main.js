window.__stm2_main_loaded = (window.__stm2_main_loaded || 0) + 1;
window.__stReloadGuard = (window.__stReloadGuard || 0);

window.getIndonesiaHolidays = window.getIndonesiaHolidays || function () {
    try {
        var el = document.getElementById('indonesia_holidays_global');
        if (!el) return {};
        return JSON.parse(el.textContent || '{}');
    } catch (e) {
        return {};
    }
};

function stReadJson(id, fallback) {
    try {
        var el = document.getElementById(id);
        if (!el) return fallback;
        return JSON.parse(el.textContent || '{}') || fallback;
    } catch (e) {
        return fallback;
    }
}

function stGetAppConfig() {
    return stReadJson('st-app-config', {});
}

// Optimasi: Debounce untuk event yang sering dipanggil
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Global Tooltip System - High z-index untuk selalu di atas semua elemen
(function () {
    var tooltipEl = null;
    var tooltipTarget = null;
    var tooltipTimer = null;

    function ensureTooltip() {
        if (tooltipEl) return tooltipEl;
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'st-global-tooltip';
        document.body.appendChild(tooltipEl);
        return tooltipEl;
    }

    function positionTooltip() {
        if (!tooltipEl || !tooltipTarget) return;
        var rect = tooltipTarget.getBoundingClientRect();
        var margin = 8;
        var vw = window.innerWidth || document.documentElement.clientWidth || 0;
        var vh = window.innerHeight || document.documentElement.clientHeight || 0;

        tooltipEl.style.left = '0px';
        tooltipEl.style.top = '0px';
        var tipRect = tooltipEl.getBoundingClientRect();
        var tipWidth = tipRect.width || 200;
        var tipHeight = tipRect.height || 30;

        // Position above by default
        var left = rect.left + (rect.width / 2) - (tipWidth / 2);
        left = Math.max(margin, Math.min(left, vw - margin - tipWidth));

        var top = rect.top - margin - tipHeight;
        if (top < margin) {
            // If doesn't fit above, position below
            top = rect.bottom + margin;
        }
        top = Math.max(margin, Math.min(top, vh - margin - tipHeight));

        tooltipEl.style.left = Math.round(left) + 'px';
        tooltipEl.style.top = Math.round(top) + 'px';
    }

    function showTooltip(target) {
        var text = target.getAttribute('data-tooltip') || target.getAttribute('title') || '';
        if (!text) return;

        var el = ensureTooltip();
        el.textContent = text;
        el.classList.add('show');
        tooltipTarget = target;

        // Remove title attribute to prevent browser tooltip
        if (target.getAttribute('title')) {
            target.setAttribute('data-original-title', target.getAttribute('title'));
            target.removeAttribute('title');
        }

        positionTooltip();
    }

    function hideTooltip() {
        if (!tooltipEl) return;
        tooltipEl.classList.remove('show');

        // Restore title if it was removed
        if (tooltipTarget && tooltipTarget.getAttribute('data-original-title')) {
            tooltipTarget.setAttribute('title', tooltipTarget.getAttribute('data-original-title'));
            tooltipTarget.removeAttribute('data-original-title');
        }

        tooltipTarget = null;
    }

    // Event delegation untuk semua elemen dengan data-tooltip
    document.addEventListener('mouseover', function (e) {
        var target = e.target && e.target.closest ? e.target.closest('[data-tooltip]') : null;
        if (!target || target === tooltipTarget) return;

        if (tooltipTimer) {
            clearTimeout(tooltipTimer);
            tooltipTimer = null;
        }

        tooltipTimer = setTimeout(function () {
            showTooltip(target);
        }, 200); // Delay 200ms untuk menghindari tooltip muncul terlalu cepat
    }, true);

    document.addEventListener('mouseout', function (e) {
        var target = e.target && e.target.closest ? e.target.closest('[data-tooltip]') : null;
        if (!target) return;

        var related = e.relatedTarget && e.relatedTarget.closest ? e.relatedTarget.closest('[data-tooltip]') : null;
        if (related === target) return;

        if (tooltipTimer) {
            clearTimeout(tooltipTimer);
            tooltipTimer = null;
        }

        hideTooltip();
    }, true);

    // Optimasi: Debounce scroll dan resize untuk performa lebih baik
    window.addEventListener('scroll', debounce(function () {
        positionTooltip();
    }, 10), true);

    window.addEventListener('resize', debounce(function () {
        positionTooltip();
    }, 100));
})();

document.addEventListener('DOMContentLoaded', function () {
    var stHolidayData = (typeof window.getIndonesiaHolidays === 'function')
        ? window.getIndonesiaHolidays()
        : {};

    function stToIsoDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function stGetHolidayName(date) {
        if (!date) return '';
        var ds = stToIsoDate(date);
        return stHolidayData && stHolidayData[ds] ? stHolidayData[ds] : '';
    }

    var app = document.querySelector('.st-app');
    var brand = document.querySelector('.st-sidebar__brand');
    var canInitSidebar = !!app && !!brand;

    function stEscapeForAttrSelector(value) {
        var raw = String(value || '');
        try {
            if (window.CSS && typeof window.CSS.escape === 'function') {
                return window.CSS.escape(raw);
            }
        } catch (e) { }
        return raw.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function stGetSortStateFromUrl() {
        var out = [];
        try {
            var params = new URLSearchParams(window.location.search || '');
            var sorts = params.getAll('sort[]');
            var dirs = params.getAll('dir[]');

            if (!sorts.length) {
                var singleSort = String(params.get('sort') || '').trim();
                var singleDir = String(params.get('dir') || '').trim().toLowerCase();
                if (singleSort && (singleDir === 'asc' || singleDir === 'desc')) {
                    out.push({ sort: singleSort, dir: singleDir });
                }
                return out;
            }

            sorts.forEach(function (s, i) {
                var sort = String(s || '').trim();
                var dir = String(dirs[i] || '').trim().toLowerCase();
                if (!sort) return;
                if (dir !== 'asc' && dir !== 'desc') return;
                out.push({ sort: sort, dir: dir });
            });
        } catch (e) { }
        return out;
    }

    function stSyncSortIndicatorsFromForms(root) {
        var scope = root || document;
        if (!scope || !scope.querySelectorAll) return false;

        // Ensure legacy text icons are upgraded before applying sort-direction states.
        try { stUpgradeTableHeaderIcons(scope); } catch (e) { }

        var forms = [];
        if (scope.matches && scope.matches('form')) {
            forms.push(scope);
        }
        forms = forms.concat(Array.prototype.slice.call(scope.querySelectorAll('form')));
        var appliedCount = 0;
        forms.forEach(function (form) {
            if (!form || !form.querySelector) return;

            var sorts = [];
            var dirs = [];

            var sortArr = form.querySelectorAll('input[name="sort[]"]');
            var dirArr = form.querySelectorAll('input[name="dir[]"]');
            if (sortArr && sortArr.length) {
                sortArr.forEach(function (el) { sorts.push(String(el.value || '').trim()); });
                dirArr.forEach(function (el) { dirs.push(String(el.value || '').trim().toLowerCase()); });
            } else {
                var sortInput = form.querySelector('input[name="sort"]');
                var dirInput = form.querySelector('input[name="dir"]');
                if (sortInput && dirInput) {
                    sorts = [String(sortInput.value || '').trim()];
                    dirs = [String(dirInput.value || '').trim().toLowerCase()];
                }
            }

            var btns = form.querySelectorAll('.st-sort-trigger');
            btns.forEach(function (btn) {
                if (!btn) return;
                btn.classList.remove('is-sorted', 'sort-asc', 'sort-desc');
                btn.removeAttribute('data-dir');
                stClearSortSvgState(btn);
            });

            sorts.forEach(function (sortKey, i) {
                sortKey = String(sortKey || '').trim();
                var dir = String(dirs[i] || '').trim().toLowerCase();
                if (!sortKey) return;
                if (dir !== 'asc' && dir !== 'desc') return;
                var selector = '.st-sort-trigger[data-sort="' + stEscapeForAttrSelector(sortKey) + '"]';
                var activeBtn = form.querySelector(selector);
                if (!activeBtn) return;
                activeBtn.classList.add('is-sorted');
                activeBtn.classList.add(dir === 'asc' ? 'sort-asc' : 'sort-desc');
                activeBtn.setAttribute('data-dir', dir);
                appliedCount += 1;

                try {
                    stApplySortSvgState(activeBtn, dir);
                } catch (e) { }
            });
        });

        // Fallback: some pages keep active sort only in URL on initial document render.
        // Do not use URL fallback for form-scoped sync, because URL may still contain stale
        // sort params before pushState runs and can re-activate a just-cleared direction.
        var isDocumentScope = scope === document;
        if (appliedCount === 0 && isDocumentScope) {
            stGetSortStateFromUrl().forEach(function (item) {
                if (!item || !item.sort) return;
                var selector = '.st-sort-trigger[data-sort="' + stEscapeForAttrSelector(item.sort) + '"]';
                var activeBtn = scope.querySelector(selector);
                if (!activeBtn) return;
                activeBtn.classList.add('is-sorted');
                activeBtn.classList.add(item.dir === 'asc' ? 'sort-asc' : 'sort-desc');
                activeBtn.setAttribute('data-dir', item.dir);
                try {
                    stApplySortSvgState(activeBtn, item.dir);
                } catch (e) { }
            });
        }
        return true;
    }

    function bindDateInputSubmitNormalizer() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || !form.querySelectorAll) return;
            var inputs = form.querySelectorAll('input[data-st-datepicker="1"]');
            inputs.forEach(function (input) {
                var iso = input.dataset ? input.dataset.isoValue : '';
                if (iso) {
                    input.value = iso;
                }
            });
        });
    }

    function initMdTimePickers() {
        if (typeof window.mdtimepicker !== 'function') {
            return false;
        }

        var inputs = document.querySelectorAll('input[type="time"], input[name="time_from"], input[name="time_to"], input[id$="_time_input"], input[id$="_time"]');
        Array.prototype.slice.call(inputs).forEach(function (input, index) {
            if (!input || input.type === 'hidden' || input.getAttribute('data-st-mdtimepicker') === '1') {
                return;
            }
            input.setAttribute('data-st-mdtimepicker', '1');
            if (!input.id) {
                input.id = 'st-timepicker-' + index;
            }

            try {
                input.type = 'text';
            } catch (e) { }
            try {
                input.setAttribute('autocomplete', 'off');
            } catch (e) { }
            try {
                input.setAttribute('readonly', 'readonly');
            } catch (e) { }

            input.addEventListener('keydown', function (event) { event.preventDefault(); });
            input.addEventListener('paste', function (event) { event.preventDefault(); });

            window.mdtimepicker('#' + input.id, {
                format: 'hh:mm',
                is24hour: true,
                theme: 'cyan',
                hourPadding: true
            });
        });

        return true;
    }

    function stFindAssociatedForm(el) {
        if (!el) return null;
        try {
            var f = el.closest ? el.closest('form') : null;
            if (f) return f;
        } catch (e) { }
        try {
            if (typeof stGetFormForFilterAction === 'function') {
                var f2 = stGetFormForFilterAction(el);
                if (f2) return f2;
            }
        } catch (e) { }
        return null;
    }

    function stFindFieldsForFormKey(form, key) {
        if (!key) return [];
        var selector = '[name="' + CSS.escape(key) + '"]';
        var selectorArr = '[name="' + CSS.escape(key + '[]') + '"]';
        var out = [];

        if (form && form.querySelectorAll) {
            try {
                out = out.concat(Array.prototype.slice.call(form.querySelectorAll(selector + ', ' + selectorArr)));
            } catch (e) { }
        }

        // Also support controls outside <form> linked via form="id"
        try {
            var formId = form && form.getAttribute ? String(form.getAttribute('id') || '').trim() : '';
            if (formId) {
                out = out.concat(Array.prototype.slice.call(document.querySelectorAll('[form="' + CSS.escape(formId) + '"]' + selector + ', [form="' + CSS.escape(formId) + '"]' + selectorArr)));
            }
        } catch (e) { }

        return out;
    }

    function stApplySortSvgState(btn, dir) {
        if (!btn) return;
        var up = btn.querySelector ? btn.querySelector('.icon-box svg .arrow-up') : null;
        var down = btn.querySelector ? btn.querySelector('.icon-box svg .arrow-down') : null;
        if (!up && !down) return;
        var primary = getComputedStyle(document.documentElement).getPropertyValue('--st-primary-color').trim() || '#2563eb';
        var gray = '#d1d5db';
        if (dir === 'asc') {
            if (up) up.style.stroke = primary;
            if (down) down.style.stroke = gray;
        } else if (dir === 'desc') {
            if (down) down.style.stroke = primary;
            if (up) up.style.stroke = gray;
        }
    }

    function stClearSortSvgState(btn) {
        if (!btn) return;
        var up = btn.querySelector ? btn.querySelector('.icon-box svg .arrow-up') : null;
        var down = btn.querySelector ? btn.querySelector('.icon-box svg .arrow-down') : null;
        [up, down].forEach(function (el) {
            if (!el || !el.style) return;
            el.style.removeProperty('stroke');
            el.style.removeProperty('stroke-width');
        });
    }

    function stHasNonEmptyValue(el) {
        if (!el) return false;
        var tag = (el.tagName || '').toLowerCase();
        var type = String(el.type || '').toLowerCase();
        try {
            if (tag === 'select') {
                if (el.multiple) {
                    return Array.prototype.slice.call(el.options || []).some(function (opt) {
                        return opt && opt.selected && String(opt.value || '').trim() !== '';
                    });
                }
                return String(el.value || '').trim() !== '';
            }
            if (type === 'checkbox' || type === 'radio') {
                return !!el.checked;
            }
            return String(el.value || '').trim() !== '';
        } catch (e) { }
        return false;
    }

    function stSyncFilterIndicatorsFromForms(root) {
        var scope = root || document;
        var triggers = scope.querySelectorAll('.st-filter-trigger[data-filter]');
        if (!triggers || triggers.length === 0) return;

        triggers.forEach(function (btn) {
            if (!btn) return;
            var key = String(btn.getAttribute('data-filter') || '').trim();
            if (!key) return;

            var form = stFindAssociatedForm(btn);
            var fields = stFindFieldsForFormKey(form, key);

            if ((!fields || fields.length === 0) && form) {
                if (key === 'arrival_presence') {
                    fields = [];
                    fields = fields.concat(stFindFieldsForFormKey(form, 'arrival_from'));
                    fields = fields.concat(stFindFieldsForFormKey(form, 'arrival_to'));
                }
            }

            // If we can't find an associated form, try global lookup by name as a fallback
            if (!fields || fields.length === 0) {
                try {
                    fields = Array.prototype.slice.call(document.querySelectorAll('[name="' + CSS.escape(key) + '"], [name="' + CSS.escape(key + '[]') + '"]'));
                } catch (e) {
                    fields = [];
                }
            }

            // If field names don't match data-filter (common on some pages), infer from the filter panel contents
            if ((!fields || fields.length === 0) && typeof stFindExistingFilterPanel === 'function') {
                try {
                    var panel = stFindExistingFilterPanel(btn, key);
                    if (panel && panel.querySelectorAll) {
                        fields = Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea'));
                    }
                } catch (e) {
                    // ignore
                }
            }

            var active = false;
            fields.forEach(function (el) {
                if (active) return;
                // Ignore buttons and hidden inputs inside panels
                try {
                    var tag = (el.tagName || '').toLowerCase();
                    var type = String(el.type || '').toLowerCase();
                    if (tag === 'button') return;
                    if (type === 'button' || type === 'submit' || type === 'reset') return;
                    if (type === 'hidden') return;
                } catch (e) { }
                try {
                    var tag2 = (el.tagName || '').toLowerCase();
                    if (tag2 === 'input') {
                        var n = String(el.getAttribute('name') || '');
                        var id = String(el.id || '');
                        var isRange = (n && /_range$/i.test(n)) || (id && /_range$/i.test(id)) || (n && n.toLowerCase().indexOf('date_range') !== -1);
                        // For planned_start filter we only want to look at its own value,
                        // not any inferred date range pair (date_from/date_to)
                        if (isRange && key !== 'planned_start' && typeof getRangeFieldPair === 'function') {
                            var pair = getRangeFieldPair(el);
                            var fromVal = pair && pair.from ? String(pair.from.value || '').trim() : '';
                            var toVal = pair && pair.to ? String(pair.to.value || '').trim() : '';
                            if (fromVal !== '' || toVal !== '') {
                                active = true;
                            }
                            return;
                        }
                    }
                } catch (e) { }
                if (stHasNonEmptyValue(el)) active = true;
            });

            if (active) {
                btn.classList.add('is-filtered');
                btn.classList.add('st-filter-trigger--active');
            } else {
                btn.classList.remove('is-filtered');
                btn.classList.remove('st-filter-trigger--active');
            }
        });
    }

    function toIsoDate(d) {
        if (!d) return '';
        var Y = d.getFullYear();
        var M = ('0' + (d.getMonth() + 1)).slice(-2);
        var D = ('0' + d.getDate()).slice(-2);
        return Y + '-' + M + '-' + D;
    }

    function toDisplayDate(d) {
        if (!d) return '';
        var D = ('0' + d.getDate()).slice(-2);
        var M = ('0' + (d.getMonth() + 1)).slice(-2);
        var Y = d.getFullYear();
        return D + '-' + M + '-' + Y;
    }

    function isoToDate(value) {
        if (!value) return null;
        var parts = String(value).split('-');
        if (parts.length !== 3) return null;
        var y = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10) - 1;
        var d = parseInt(parts[2], 10);
        if (!isFinite(y) || !isFinite(m) || !isFinite(d)) return null;
        return new Date(y, m, d);
    }

    function normalizeDmyToIso(value) {
        if (!value) return '';
        var parts = String(value).split('-');
        if (parts.length === 3) {
            var d = parts[0];
            var m = parts[1];
            var y = parts[2];
            if (y.length === 4) {
                return y + '-' + m + '-' + d;
            }
        }
        return value;
    }

    function setDisplayValue(input, isoValue) {
        if (!input) return;
        input.dataset.isoValue = isoValue || '';
        if (isoValue) {
            var parts = isoValue.split('-');
            if (parts.length === 3) {
                input.value = parts[2] + '-' + parts[1] + '-' + parts[0];
                return;
            }
        }
        input.value = isoValue || '';
    }

    function stToDisplayDatePickerClasses(momentDate) {
        if (!momentDate) return '';
        var classes = [];
        var iso = momentDate.format('YYYY-MM-DD');
        if (momentDate.day() === 0) {
            classes.push('drp-sunday');
        }
        if (stHolidayData && stHolidayData[iso]) {
            classes.push('drp-holiday');
        }
        return classes.join(' ');
    }

    function stResolveDaterangepickerCellIso(cellEl) {
        if (!cellEl || !window.jQuery || typeof window.moment !== 'function') return '';
        var cell = window.jQuery(cellEl);
        var day = parseInt(cell.text().trim(), 10);
        if (!isFinite(day)) return '';

        var table = cell.closest('table');
        var monthText = table.find('.month').first().text().trim();
        if (!monthText) return '';

        var baseMonth = window.moment(monthText, ['MMM YYYY', 'MMMM YYYY'], true);
        if (!baseMonth.isValid()) {
            baseMonth = window.moment(monthText, ['MMM YYYY', 'MMMM YYYY'], false);
        }
        if (!baseMonth.isValid()) return '';

        var month = baseMonth.month();
        var year = baseMonth.year();
        if (cell.hasClass('off')) {
            if (day >= 20) {
                month -= 1;
            } else {
                month += 1;
            }
            if (month < 0) {
                month = 11;
                year -= 1;
            }
            if (month > 11) {
                month = 0;
                year += 1;
            }
        }

        return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    }

    function stDecorateDaterangepickerDays(picker) {
        if (!picker || !picker.container || !window.jQuery) return;
        picker.container.find('td.available').each(function () {
            var td = window.jQuery(this);
            var hasSunday = td.hasClass('drp-sunday');
            var hasHoliday = td.hasClass('drp-holiday');
            var tooltipText = '';

            if (hasHoliday) {
                var ds = stResolveDaterangepickerCellIso(this);
                tooltipText = (ds && stHolidayData && stHolidayData[ds]) ? stHolidayData[ds] : 'Holiday';
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

    function stBindDaterangepickerDecorators($input) {
        if (!$input || !$input.length) return;
        $input.off('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo');
        $input.on('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo', function (ev, picker) {
            stDecorateDaterangepickerDays(picker);
        });

        var picker = $input.data('daterangepicker');
        if (picker) {
            setTimeout(function () {
                stDecorateDaterangepickerDays(picker);
            }, 0);
        }
    }

    function initPredefinedDateRange(containerSelector, startInputSelector, endInputSelector) {
        var reportRange = window.jQuery(containerSelector);
        if (reportRange.length) {
            var start = window.moment();
            var end = window.moment();

            var hiddenStart = window.jQuery(startInputSelector).val();
            var hiddenEnd = window.jQuery(endInputSelector).val();
            var hasInitial = false;
            if (hiddenStart && window.moment(hiddenStart, 'YYYY-MM-DD').isValid()) {
                start = window.moment(hiddenStart, 'YYYY-MM-DD');
                hasInitial = true;
            }
            if (hiddenEnd && window.moment(hiddenEnd, 'YYYY-MM-DD').isValid()) {
                end = window.moment(hiddenEnd, 'YYYY-MM-DD');
                hasInitial = true;
            }

            function updateRange(start, end) {
                var label = start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY');
                window.jQuery(containerSelector + ' span').html(label);
                window.jQuery(startInputSelector).val(start.format('YYYY-MM-DD'));
                window.jQuery(endInputSelector).val(end.format('YYYY-MM-DD'));
            }

            function cb(start, end) {
                updateRange(start, end);
                var form = reportRange.closest('form');
                if (form.length) {
                    if (reportRange.attr('data-auto-submit') !== 'false') {
                        form.submit();
                    }
                }
            }

            reportRange.daterangepicker({
                startDate: start,
                endDate: end,
                locale: {
                    format: 'DD-MM-YYYY'
                },
                ranges: {
                    'Today': [window.moment(), window.moment()],
                    'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                    'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                    'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                    'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                    'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')]
                },
                isCustomDate: stToDisplayDatePickerClasses
            }, cb);

            stBindDaterangepickerDecorators(reportRange);

            if (hasInitial) {
                updateRange(start, end);
            } else {
                window.jQuery(containerSelector + ' span').html('Select range');
            }
        }
    }

    function initDatePickers() {
        // 1. Single Date Pickers
        var dateInputs = document.querySelectorAll('input[type="date"], input[id$="_date_input"], input[id$="_date"], input[name$="_date"], input[name="date_from"], input[name="date_to"], input[name="range_start"], input[name="range_end"], input[name="planned_date"], input[name="schedule_date"]');

        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.daterangepicker === 'function' && typeof window.moment === 'function') {
            Array.prototype.slice.call(dateInputs).forEach(function (input) {
                // Skip if already initialized, hidden, or part of a range picker
                if (!input || input.type === 'hidden' || input.getAttribute('data-st-datepicker') === '1' || input.id === 'range_start' || input.id === 'range_end') {
                    return;
                }
                input.setAttribute('data-st-datepicker', '1');

                // Force text type
                try { input.type = 'text'; } catch (e) { }

                if (input.value) {
                    setDisplayValue(input, input.value);
                }

                // Init single date picker
                window.jQuery(input).daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true,
                    autoApply: true,
                    locale: {
                        format: 'DD-MM-YYYY'
                    },
                    minYear: 1901,
                    maxYear: parseInt(window.moment().format('YYYY'), 10) + 5,
                    isCustomDate: stToDisplayDatePickerClasses
                }, function (start, end, label) {
                    // Trigger change event for other listeners
                    var iso = start.format('YYYY-MM-DD');
                    setDisplayValue(input, iso);
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });

                stBindDaterangepickerDecorators(window.jQuery(input));
            });

            // 2. Predefined Range Pickers
            // Dashboard
            initPredefinedDateRange('#reportrange', '#range_start', '#range_end');

            // Slots - ETA Range
            initPredefinedDateRange('#eta_reportrange', '#date_from', '#date_to');

            // Slots - Arrival Range
            initPredefinedDateRange('#arrival_reportrange', '#arrival_from', '#arrival_to');

            // Reports - Transactions
            initPredefinedDateRange('#transaction_reportrange', '#date_from', '#date_to');

            // Activity Logs
            initPredefinedDateRange('#logs_reportrange', '#date_from', '#date_to');

            // Vendor Bookings
            initPredefinedDateRange('#vendor_reportrange', '#date_from', '#date_to');

            // Admin Booking Requests
            initPredefinedDateRange('#booking_reportrange', '#date_from', '#date_to');

            return true;
        }
        return false;
    }

    function initDatePickersWhenReady() {
        if (initDatePickers()) {
            return;
        }

        var attempts = 0;
        var timer = setInterval(function () {
            attempts += 1;
            if (initDatePickers() || attempts >= 20) {
                clearInterval(timer);
            }
        }, 150);
    }

    function getRangeFieldPair(input) {
        var form = input ? (input.form || input.closest('form')) : null;
        if (!form) return { from: null, to: null };

        var rangeStart = form.querySelector('input[name="range_start"], #range_start');
        var rangeEnd = form.querySelector('input[name="range_end"], #range_end');
        if (rangeStart && rangeEnd) {
            return { from: rangeStart, to: rangeEnd };
        }

        var name = String(input.getAttribute('name') || input.id || '').toLowerCase();
        var from = null;
        var to = null;

        if (name.indexOf('arrival_date') !== -1) {
            from = form.querySelector('input[name="arrival_date_from"]') || form.querySelector('input[name="arrival_from"]');
            to = form.querySelector('input[name="arrival_date_to"]') || form.querySelector('input[name="arrival_to"]');
        } else if (name.indexOf('arrival') !== -1) {
            from = form.querySelector('input[name="arrival_from"]') || form.querySelector('input[name="arrival_date_from"]');
            to = form.querySelector('input[name="arrival_to"]') || form.querySelector('input[name="arrival_date_to"]');
        } else if (name.indexOf('planned_start') !== -1) {
            from = form.querySelector('input[name="date_from"]');
            to = form.querySelector('input[name="date_to"]');
        } else {
            from = form.querySelector('input[name="date_from"]');
            to = form.querySelector('input[name="date_to"]');
        }

        return { from: from, to: to };
    }

    function initRangePickers() {
        var inputs = document.querySelectorAll('input[id$="_range"], input[name$="_range"], input[id*="date_range"], input[name*="date_range"]');
        Array.prototype.slice.call(inputs).forEach(function (input) {
            if (!input || input.getAttribute('data-st-range-init') === '1') {
                return;
            }

            var pair = getRangeFieldPair(input);
            var from = pair.from;
            var to = pair.to;
            input.setAttribute('data-st-range-init', '1');

            var initialFrom = from && from.value ? from.value : '';
            var initialTo = to && to.value ? to.value : '';
            if (initialFrom && initialTo) {
                var startDisplay = toDisplayDate(isoToDate(initialFrom));
                var endDisplay = toDisplayDate(isoToDate(initialTo));
                input.value = startDisplay + ' - ' + endDisplay;
            } else if (initialFrom) {
                input.value = toDisplayDate(isoToDate(initialFrom));
            }

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dateRangePicker) {
                window.jQuery(input).dateRangePicker({
                    autoClose: true,
                    showShortcuts: false,
                    singleMonth: true,
                    format: 'DD-MM-YYYY'
                }).bind('datepicker-change', function (event, obj) {
                    var start = obj && obj.date1 ? toIsoDate(obj.date1) : '';
                    var end = obj && obj.date2 ? toIsoDate(obj.date2) : '';
                    if (from) from.value = start;
                    if (to) to.value = end;
                    if (start && end) {
                        input.value = toDisplayDate(obj.date1) + ' - ' + toDisplayDate(obj.date2);
                    } else {
                        input.value = start ? toDisplayDate(obj.date1) : '';
                    }
                });
                return;
            }

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.datepicker) {
                window.jQuery(input).datepicker({
                    dateFormat: 'dd-mm-yy',
                    onSelect: function (dateText) {
                        var iso = normalizeDmyToIso(dateText);
                        if (from) from.value = iso;
                        if (to) to.value = iso;
                        input.value = dateText;
                    }
                });
            }
        });

        return true;
    }

    function initRangePickersWhenReady() {
        if (initRangePickers()) {
            return;
        }

        var attempts = 0;
        var timer = setInterval(function () {
            attempts += 1;
            if (initRangePickers() || attempts >= 20) {
                clearInterval(timer);
            }
        }, 150);
    }

    function initDateRangePickerOpeners() {
        if (!window.jQuery) {
            return false;
        }

        var inputs = document.querySelectorAll('input[id$="_range"], input[name$="_range"], input[id*="date_range"], input[name*="date_range"]');
        Array.prototype.slice.call(inputs).forEach(function (input) {
            if (!input || input.getAttribute('data-st-range-open') === '1') {
                return;
            }
            input.setAttribute('data-st-range-open', '1');

            function openRangePicker() {
                var picker = window.jQuery(input).data('dateRangePicker');
                if (picker && typeof picker.open === 'function') {
                    picker.open();
                }
            }

            input.addEventListener('click', openRangePicker);
            input.addEventListener('focus', openRangePicker);
        });

        return true;
    }

    function initDateRangePickerOpenersWhenReady() {
        if (initDateRangePickerOpeners()) {
            return;
        }

        var attempts = 0;
        var timer = setInterval(function () {
            attempts += 1;
            if (initDateRangePickerOpeners() || attempts >= 20) {
                clearInterval(timer);
            }
        }, 150);
    }

    function stGetMultiSortState(form) {
        if (!form) return [];
        var sortArr = form.querySelectorAll('input[name="sort[]"]');
        var dirArr = form.querySelectorAll('input[name="dir[]"]');
        var out = [];
        if (sortArr && sortArr.length) {
            sortArr.forEach(function (el, i) {
                var key = String(el.value || '').trim();
                var dir = String((dirArr[i] && dirArr[i].value) || '').trim().toLowerCase();
                if (!key) return;
                if (dir !== 'asc' && dir !== 'desc') return;
                out.push({ sort: key, dir: dir });
            });
            return out;
        }
        var s = form.querySelector('input[name="sort"]');
        var d = form.querySelector('input[name="dir"]');
        var key2 = String((s && s.value) || '').trim();
        var dir2 = String((d && d.value) || '').trim().toLowerCase();
        if (key2 && (dir2 === 'asc' || dir2 === 'desc')) {
            out.push({ sort: key2, dir: dir2 });
        }
        return out;
    }

    function stIsMultiSortForm(form) {
        if (!form) return false;
        try {
            if (form.getAttribute && String(form.getAttribute('data-multi-sort') || '') === '1') return true;
            if (form.querySelector && form.querySelector('input[name="sort[]"]')) return true;
            if (form.querySelector && form.querySelector('.st-sort-trigger')) return true;
        } catch (e) { }
        return false;
    }

    function stSetMultiSortState(form, state) {
        if (!form) return;
        try {
            form.querySelectorAll('input[name="sort[]"], input[name="dir[]"]').forEach(function (el) { el.remove(); });
        } catch (e) { }

        (state || []).forEach(function (p) {
            if (!p || !p.sort || !p.dir) return;
            var s = document.createElement('input');
            s.type = 'hidden';
            s.name = 'sort[]';
            s.value = String(p.sort);
            var d = document.createElement('input');
            d.type = 'hidden';
            d.name = 'dir[]';
            d.value = String(p.dir);
            form.appendChild(s);
            form.appendChild(d);
        });

        // Remove legacy single sort inputs to avoid conflicting params (only on multi-sort forms)
        if (stIsMultiSortForm(form)) {
            try {
                var s1 = form.querySelector('input[name="sort"]');
                var d1 = form.querySelector('input[name="dir"]');
                if (s1) s1.remove();
                if (d1) d1.remove();
            } catch (e) { }
        }
    }

    // Restore sidebar collapsed state, collapse by default on small desktop screens
    // Mobile (<=768px) uses drawer pattern, not collapsed icon-only
    if (canInitSidebar && window.innerWidth > 768) {
        try {
            var saved = window.localStorage ? localStorage.getItem('stSidebarCollapsed') : null;
            if (saved === '1' || (!saved && window.innerWidth <= 1200)) {
                app.classList.add('st-app--sidebar-collapsed');
            }
        } catch (e) {
            // ignore storage errors
        }
    }

    function toggleSidebar() {
        if (!canInitSidebar) return;
        var collapsed = app.classList.toggle('st-app--sidebar-collapsed');
        try {
            if (window.localStorage) {
                localStorage.setItem('stSidebarCollapsed', collapsed ? '1' : '0');
            }
        } catch (e) {
            // ignore storage errors
        }
    }

    if (canInitSidebar) {
        brand.addEventListener('click', function (e) {
            e.preventDefault();
            toggleSidebar();
        });
    }

    function initDateTimeLocalPickers() {
        return false;
    }

    function scheduleAutoClose(el, delay) {
        if (!el) return;
        if (el._stAutoCloseTimer) {
            clearTimeout(el._stAutoCloseTimer);
        }
        el._stAutoCloseTimer = setTimeout(function () {
            try {
                el.blur();
            } catch (e) { }
        }, delay);
    }

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t || !t.matches) return;
        if (t.matches('input[type="date"], input[type="time"]')) {
            try {
                t.blur();
            } catch (e) { }
        }
        if (t.matches('input[type="datetime-local"]')) {
            scheduleAutoClose(t, 0);
        }
    });

    document.addEventListener('input', function (e) {
        var t = e.target;
        if (!t || !t.matches) return;
        if (t.matches('input[type="datetime-local"]')) {
            var val = (t.value || '').trim();
            var isComplete = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(val);
            scheduleAutoClose(t, isComplete ? 0 : 700);
        }
    });

    function initTimePickers() {
        return initMdTimePickers();
    }

    function initTimePickersWhenReady() {
        if (initTimePickers()) {
            return;
        }

        var attempts = 0;
        var timer = setInterval(function () {
            attempts += 1;
            if (initTimePickers() || attempts >= 20) {
                clearInterval(timer);
                if (attempts >= 20) {
                    initMdTimePickers();
                }
            }
        }, 150);
    }

    function initAutoSubmitDateRangeFields() {
        document.addEventListener('change', function (e) {
            var t = e.target;
            if (!t || !t.getAttribute) return;
            if (t.closest && t.closest('.st-filter-panel')) return;

            var name = String(t.getAttribute('name') || '').trim();
            if (!name) return;

            var isRangeField =
                name === 'date_from' || name === 'date_to' ||
                name === 'range_start' || name === 'range_end' ||
                name === 'arrival_from' || name === 'arrival_to' ||
                name === 'planned_start_from' || name === 'planned_start_to' ||
                name === 'arrival_date_from' || name === 'arrival_date_to';

            if (!isRangeField) return;

            var form = stFindAssociatedForm(t) || t.form;
            if (!form) return;

            // Use shared submit handler to support AJAX if available
            if (typeof stSubmitOrAjax === 'function') {
                stSubmitOrAjax(form);
            } else {
                try {
                    form.submit();
                } catch (err) {
                    console.error('Form submit failed:', err);
                }
            }
        }, true);
    }

    initDateTimeLocalPickers();
    initDatePickersWhenReady();
    initAutoSubmitDateRangeFields();
    initTimePickersWhenReady();
    initDateRangePickerOpenersWhenReady();
    bindDateInputSubmitNormalizer();
    initRangePickersWhenReady();

    var stActiveSortPanel = null;
    var stActiveSortForm = null;
    var stActiveSortKey = '';

    var stActiveFilterPanel = null;
    var stActiveFilterKey = '';

    function stCloseSortPanel() {
        if (stActiveSortPanel) {
            stActiveSortPanel.style.display = 'none';
        }
        stActiveSortPanel = null;
        stActiveSortForm = null;
        stActiveSortKey = '';
    }

    function stCloseFilterPanel() {
        if (stActiveFilterPanel) {
            stActiveFilterPanel.style.display = 'none';
        }
        stActiveFilterPanel = null;
        stActiveFilterKey = '';
    }

    function stGetLabelForSortTrigger(btn) {
        var head = btn.closest('.st-colhead') || btn.closest('.st-filter-header') || btn.closest('th');
        if (head) {
            var labelEl = head.querySelector('.st-colhead__label') || head.querySelector('span');
            if (labelEl && (labelEl.textContent || '').trim() !== '') {
                return (labelEl.textContent || '').trim();
            }
        }
        var key = String(btn.getAttribute('data-sort') || '').trim();
        if (key === '') return 'Sort';
        return key.replace(/[_\-]+/g, ' ');
    }

    function stPositionPanelAtTrigger(panel, trigger, desiredWidth) {
        var rect = trigger.getBoundingClientRect();
        var vw = window.innerWidth || document.documentElement.clientWidth || 1024;
        var left = rect.left;
        var top = rect.bottom + 6;

        panel.style.position = 'fixed';
        panel.style.top = top + 'px';
        panel.style.left = left + 'px';

        panel.style.display = 'block';
        var pw = desiredWidth || panel.offsetWidth || 220;

        if (left + pw > vw - 8) {
            left = vw - pw - 8;
        }
        if (left < 8) {
            left = 8;
        }
        panel.style.left = left + 'px';
    }

    function stFindExistingSortPanel(btn, sortKey) {
        var head = btn.closest('.st-colhead') || btn.closest('.st-filter-header') || btn.closest('th');
        if (head) {
            var local = head.querySelector('.st-sort-panel[data-sort-panel="' + sortKey + '"]');
            if (local) return local;
        }
        return document.querySelector('.st-sort-panel[data-sort-panel="' + sortKey + '"]');
    }

    function stFindExistingFilterPanel(btn, filterKey) {
        var head = btn.closest('.st-colhead') || btn.closest('.st-filter-header') || btn.closest('th');
        if (head) {
            var local = head.querySelector('.st-filter-panel[data-filter-panel="' + filterKey + '"]');
            if (local) return local;
        }
        return document.querySelector('.st-filter-panel[data-filter-panel="' + filterKey + '"]');
    }

    function stEnsureDynamicSortPanel() {
        var id = 'st-dynamic-sort-panel';
        var panel = document.getElementById(id);
        if (panel) return panel;

        panel = document.createElement('div');
        panel.id = id;
        panel.className = 'st-sort-panel st-sort-panel--dynamic';
        panel.style.display = 'none';
        panel.style.zIndex = '1000';
        panel.style.minWidth = '220px';
        panel.style.maxWidth = '260px';
        panel.style.padding = '8px';
        panel.style.borderRadius = '10px';

        document.body.appendChild(panel);
        return panel;
    }

    function stRenderDynamicSortPanel(panel, label, sortKey, type) {
        panel.setAttribute('data-sort-panel', sortKey);
        panel.innerHTML = '';

        var title = document.createElement('div');
        title.className = 'st-sort-panel__title';
        title.textContent = ('SORT ' + label).toUpperCase();
        panel.appendChild(title);

        var optAsc = document.createElement('button');
        optAsc.type = 'button';
        optAsc.className = 'st-sort-option';
        optAsc.setAttribute('data-sort', sortKey);
        optAsc.setAttribute('data-dir', 'asc');

        var optDesc = document.createElement('button');
        optDesc.type = 'button';
        optDesc.className = 'st-sort-option';
        optDesc.setAttribute('data-sort', sortKey);
        optDesc.setAttribute('data-dir', 'desc');

        if (type === 'duration') {
            optAsc.textContent = 'Tercepat';
            optDesc.textContent = 'Terlama';
        } else {
            optAsc.textContent = type === 'date' ? 'Terlama' : 'A-Z';
            optDesc.textContent = type === 'date' ? 'Terbaru' : 'Z-A';
        }

        panel.appendChild(optAsc);
        panel.appendChild(optDesc);
    }

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target) return;

        var btn = target.closest ? target.closest('.st-sort-trigger') : null;
        if (!btn) {
            var insidePanel = target.closest && target.closest('.st-sort-panel');
            if (!insidePanel) {
                stCloseSortPanel();
            }
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        try { btn.blur(); } catch (err) { }

        var sortKey = String(btn.getAttribute('data-sort') || '').trim();
        if (!sortKey) return;

        // Make sure clicked header already uses SVG icon markup for direction highlighting.
        try { stUpgradeTableHeaderIcons(btn.closest('thead') || document); } catch (err) { }

        if (stActiveSortPanel && stActiveSortKey === sortKey && stActiveSortPanel.style.display !== 'none') {
            stCloseSortPanel();
            return;
        }

        stCloseSortPanel();

        var form = btn.closest('form');
        if (!form) {
            form = document.querySelector('form');
        }

        var useMulti = stIsMultiSortForm(form);
        stActiveSortForm = form;
        stActiveSortKey = sortKey;

        if (useMulti) {
            var state = stGetMultiSortState(form);
            var idx = state.findIndex(function (p) { return p && p.sort === sortKey; });
            if (idx >= 0) {
                if (state[idx].dir === 'asc') {
                    state[idx].dir = 'desc';
                } else {
                    state.splice(idx, 1);
                }
            } else {
                state.push({ sort: sortKey, dir: 'asc' });
            }

            stSetMultiSortState(form, state);
            stSyncSortIndicatorsFromForms(form);
        } else {
            // Single-sort toggle (legacy)
            var sortInput = form ? form.querySelector('input[name="sort"]') : null;
            var dirInput = form ? form.querySelector('input[name="dir"]') : null;
            if (!sortInput || !dirInput) {
                return;
            }

            // Clear visual sort state immediately (avoid indicator sticking on previous column)
            try {
                form.querySelectorAll('.st-sort-trigger').forEach(function (b) {
                    if (b) b.classList.remove('is-sorted', 'sort-asc', 'sort-desc');
                });
            } catch (err) { }

            var currentSort = String(sortInput.value || '').trim();
            var currentDir = String(dirInput.value || 'desc').trim().toLowerCase();
            if (currentDir !== 'asc' && currentDir !== 'desc') currentDir = 'desc';

            if (currentSort === sortKey) {
                if (currentDir === 'asc') {
                    dirInput.value = 'desc';
                } else {
                    sortInput.value = '';
                    dirInput.value = '';
                }
            } else {
                sortInput.value = sortKey;
                dirInput.value = 'asc';
            }

            // Show new state immediately
            btn.classList.remove('sort-asc', 'sort-desc');
            var newSort = String(sortInput.value || '').trim();
            var newDir = String(dirInput.value || '').trim().toLowerCase();
            if (newSort === sortKey && (newDir === 'asc' || newDir === 'desc')) {
                btn.classList.add('is-sorted');
                btn.classList.add(newDir === 'asc' ? 'sort-asc' : 'sort-desc');
                btn.setAttribute('data-dir', newDir);
                stApplySortSvgState(btn, newDir);
            } else {
                btn.classList.remove('is-sorted');
                btn.removeAttribute('data-dir');
                stClearSortSvgState(btn);
            }
        }

        // If the page supports AJAX reload (e.g. Slots), avoid full page refresh
        try {
            if (typeof window.ajaxReload === 'function') {
                window.ajaxReload(true);
                return;
            }
        } catch (err) { }

        try {
            form.submit();
        } catch (err) {
            console.error('Form submit failed:', err);
            // window.location.reload(); // Disabled to prevent refresh loop
        }
    }, true);

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target) return;

        var btn = target.closest ? target.closest('.st-filter-trigger') : null;
        if (!btn) {
            var insidePanel = target.closest && target.closest('.st-filter-panel');
            if (!insidePanel) {
                stCloseFilterPanel();
            }
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        try { btn.blur(); } catch (err) { }

        var filterKey = String(btn.getAttribute('data-filter') || '').trim();
        if (!filterKey) return;

        if (stActiveFilterPanel && stActiveFilterKey === filterKey && stActiveFilterPanel.style.display !== 'none') {
            stCloseFilterPanel();
            return;
        }

        stCloseFilterPanel();

        var panel = stFindExistingFilterPanel(btn, filterKey);
        if (!panel) return;

        // Only force fixed positioning for panels that are already fixed (or explicitly opted-in).
        // Many pages (e.g., Slots) use position:absolute within a relative header, which should be preserved.
        var pos = '';
        try { pos = (window.getComputedStyle(panel).position || '').toLowerCase(); } catch (e) { }
        var forceFixed = pos === 'fixed' || String(panel.getAttribute('data-st-position') || '') === 'fixed';
        if (forceFixed) {
            stPositionPanelAtTrigger(panel, btn, 260);
        } else {
            panel.style.display = 'block';
        }
        panel.style.zIndex = panel.style.zIndex || '1000';
        stActiveFilterPanel = panel;
        stActiveFilterKey = filterKey;
    }, true);

    function stGetFormForFilterAction(el) {
        if (!el) return null;
        var form = el.closest ? el.closest('form') : null;
        if (form) return form;
        // Fallback: many filter inputs use form="..." attribute
        var formId = '';
        try { formId = String(el.getAttribute('form') || '').trim(); } catch (e) { }
        if (formId) {
            var byId = document.getElementById(formId);
            if (byId && byId.tagName === 'FORM') return byId;
        }
        return document.querySelector('form');
    }

    function stSubmitOrAjax(form) {
        if (!form) return;
        try {
            if (typeof window.ajaxReload === 'function') {
                window.ajaxReload(true);
                return;
            }
        } catch (err) { }
        try {
            form.submit();
        } catch (err) {
            console.error('Form submit failed:', err);
            // window.location.reload(); // Disabled to prevent refresh loop
        }
    }

    function stClearFilterField(form, key) {
        if (!form || !key) return;
        var selector = '[name="' + CSS.escape(key) + '"]';
        var selectorArr = '[name="' + CSS.escape(key + '[]') + '"]';
        var els = [];
        try {
            els = Array.prototype.slice.call(form.querySelectorAll(selector + ', ' + selectorArr));
        } catch (e) {
            els = [];
        }
        els.forEach(function (el) {
            if (!el) return;
            var tag = (el.tagName || '').toLowerCase();
            var type = String(el.type || '').toLowerCase();
            if (tag === 'select') {
                try {
                    if (el.multiple) {
                        Array.prototype.slice.call(el.options || []).forEach(function (opt) { opt.selected = false; });
                    } else {
                        el.value = '';
                    }
                } catch (e) { }
                return;
            }
            if (type === 'checkbox' || type === 'radio') {
                try { el.checked = false; } catch (e) { }
                return;
            }
            try { el.value = ''; } catch (e) { }
        });
    }

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target) return;
        var clearBtn = target.closest ? target.closest('.st-filter-clear') : null;
        if (!clearBtn) return;
        e.preventDefault();
        e.stopPropagation();

        var key = String(clearBtn.getAttribute('data-filter') || '').trim();
        if (!key) return;

        var form = stGetFormForFilterAction(clearBtn);
        // Prefer clearing the actual fields inside the panel (works even when data-filter != field name,
        // e.g. combined panels like whgate).
        var panel = null;
        try { panel = clearBtn.closest ? clearBtn.closest('.st-filter-panel') : null; } catch (err) { panel = null; }
        if (panel && panel.querySelectorAll) {
            try {
                // Clear inputs
                panel.querySelectorAll('input, textarea').forEach(function (inp) {
                    if (!inp) return;
                    var type = String(inp.type || '').toLowerCase();
                    if (type === 'button' || type === 'submit' || type === 'reset') return;
                    if (type === 'radio') {
                        // Reset radios: prefer empty value option if present
                        try {
                            if (String(inp.value || '') === '') {
                                inp.checked = true;
                            } else {
                                inp.checked = false;
                            }
                        } catch (e2) { }
                        return;
                    }
                    if (type === 'checkbox') {
                        try { inp.checked = false; } catch (e2) { }
                        return;
                    }
                    try { inp.value = ''; } catch (e2) { }
                });

                // Clear selects
                panel.querySelectorAll('select').forEach(function (sel) {
                    if (!sel) return;
                    try {
                        if (sel.multiple) {
                            Array.prototype.slice.call(sel.options || []).forEach(function (opt) { opt.selected = false; });
                        } else {
                            sel.value = '';
                        }
                    } catch (e2) { }
                });
            } catch (err2) { }
        } else {
            // Fallback: clear by key if we can't find the panel
            stClearFilterField(form, key);
        }

        // Update visual state immediately
        try {
            var trigger = document.querySelector('.st-filter-trigger[data-filter="' + CSS.escape(key) + '"]');
            if (trigger) {
                trigger.classList.remove('is-filtered', 'st-filter-trigger--active');
            }
        } catch (e) { }

        try { stSyncFilterIndicatorsFromForms(form || document); } catch (e) { }

        stCloseFilterPanel();
        stSubmitOrAjax(form);
    }, true);

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t) return;
        var panel = t.closest ? t.closest('.st-filter-panel') : null;
        if (!panel) return;
        var tag = (t.tagName || '').toLowerCase();
        var type = String(t.type || '').toLowerCase();
        var isSelect = tag === 'select';
        var isDateLikeInput = tag === 'input' && (
            type === 'date' ||
            t.getAttribute('data-st-datepicker') === '1' ||
            t.getAttribute('data-st-mddatepicker') === '1'
        );
        if (!isSelect && !isDateLikeInput) return;

        var form = stGetFormForFilterAction(t);
        try { stSyncFilterIndicatorsFromForms(form || document); } catch (e) { }
        stCloseFilterPanel();
        stSubmitOrAjax(form);
    }, true);

    document.addEventListener('keydown', function (e) {
        var t = e.target;
        if (!t) return;
        if (e.key !== 'Enter') return;
        var panel = t.closest ? t.closest('.st-filter-panel') : null;
        if (!panel) return;
        // Only for text-like inputs
        var type = String(t.type || '').toLowerCase();
        if (type === 'textarea') return;
        if (type === 'button' || type === 'submit' || type === 'reset') return;
        e.preventDefault();
        var form = stGetFormForFilterAction(t);
        try { stSyncFilterIndicatorsFromForms(form || document); } catch (e) { }
        stCloseFilterPanel();
        stSubmitOrAjax(form);
    }, true);

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target) return;
        var opt = target.closest ? target.closest('.st-sort-option') : null;
        if (!opt) return;

        e.preventDefault();
        e.stopPropagation();

        var sortKey = String(opt.getAttribute('data-sort') || '').trim();
        var dir = String(opt.getAttribute('data-dir') || 'asc').trim().toLowerCase();
        if (!sortKey) return;
        if (dir !== 'asc' && dir !== 'desc') dir = 'asc';

        var form = stActiveSortForm || opt.closest('form') || document.querySelector('form');
        if (!form) return;

        if (stIsMultiSortForm(form)) {
            var state2 = stGetMultiSortState(form);
            var idx2 = state2.findIndex(function (p) { return p && p.sort === sortKey; });
            if (idx2 >= 0) {
                state2[idx2].dir = dir;
            } else {
                state2.push({ sort: sortKey, dir: dir });
            }
            stSetMultiSortState(form, state2);
            stSyncSortIndicatorsFromForms(form);
        } else {
            var sortInput2 = form.querySelector('input[name="sort"]');
            var dirInput2 = form.querySelector('input[name="dir"]');
            if (sortInput2) sortInput2.value = sortKey;
            if (dirInput2) dirInput2.value = dir;
            stSyncSortIndicatorsFromForms(form);
        }

        // If the page supports AJAX reload (e.g. Slots), avoid full page refresh
        try {
            if (typeof window.ajaxReload === 'function') {
                window.ajaxReload(true);
                stCloseSortPanel();
                return;
            }
        } catch (err) { }

        stCloseSortPanel();
        try {
            form.submit();
        } catch (err) {
            if (!window.__stReloadGuard || (Date.now() - window.__stReloadGuard) > 5000) {
                window.__stReloadGuard = Date.now();
                window.location.href = window.location.href;
            }
        }
    }, true);

    function stIconSvgSort() {
        return '<span class="icon-box" aria-hidden="true">'
            + '<svg width="18" height="18" viewBox="0 0 24 24" fill="none">'
            + '<path class="arrow-up" d="M8 20V4M8 4L4 8M8 4L12 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            + '<path class="arrow-down" d="M16 4V20M16 20L12 16M16 20L20 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            + '</svg>'
            + '</span>';
    }

    function stIconSvgFilter() {
        return '<span class="icon-box" aria-hidden="true">'
            + '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>'
            + '</span>';
    }

    function stUpgradeTableHeaderIcons(root) {
        var scope = root || document;

        scope.querySelectorAll('.st-sort-trigger').forEach(function (btn) {
            if (!btn) return;
            if (!btn.querySelector('.icon-box')) {
                // Clear legacy placeholder text (including mojibake variants) so SVG state styling is visible.
                btn.textContent = '';
                btn.insertAdjacentHTML('beforeend', stIconSvgSort());
            }
            btn.setAttribute('data-st-svg', '1');
            btn.classList.add('btn-header', 'btn-sort');
            if (btn.classList.contains('st-sort-trigger--active')) {
                btn.classList.add('is-sorted');
            }
        });

        scope.querySelectorAll('.st-filter-trigger').forEach(function (btn) {
            if (!btn) return;
            if (!btn.querySelector('.icon-box')) {
                // Clear legacy placeholder text (including mojibake variants) so SVG state styling is visible.
                btn.textContent = '';
                btn.insertAdjacentHTML('beforeend', stIconSvgFilter());
            }
            btn.setAttribute('data-st-svg', '1');
            btn.classList.add('btn-header', 'btn-filter');
            if (btn.classList.contains('st-filter-trigger--active')) {
                btn.classList.add('is-filtered');
            }
        });
    }

    stUpgradeTableHeaderIcons(document);

    stSyncSortIndicatorsFromForms(document);

    stSyncFilterIndicatorsFromForms(document);

    function stSyncActiveStateClasses(root) {
        var scope = root || document;
        stSyncSortIndicatorsFromForms(scope);
        stSyncFilterIndicatorsFromForms(scope);
    }

    function stSyncActiveStateForTrigger(btn) {
        if (!btn || !btn.classList) return;
        if (btn.classList.contains('st-sort-trigger')) {
            // prefer syncing from form inputs to keep dir accurate
            var form = stFindAssociatedForm(btn) || (btn.closest ? btn.closest('form') : null);
            if (form) stSyncSortIndicatorsFromForms(form);
        }
        if (btn.classList.contains('st-filter-trigger')) {
            if (btn.classList.contains('st-filter-trigger--active')) {
                btn.classList.add('is-filtered');
            } else {
                btn.classList.remove('is-filtered');
            }
        }
    }

    stSyncActiveStateClasses(document);

    // NOTE: Global MutationObserver on the whole document can easily flood the main thread
    // (large DOM + frequent class mutations) and cause "Page Unresponsive".
    // We intentionally disable it and rely on explicit updates (click handlers / ajax reload hooks).

    document.addEventListener('submit', function (e) {
        var form = e && e.target;
        if (!form || !form.querySelectorAll) return;

        var triggers = form.querySelectorAll('.st-filter-trigger');
        if (!triggers || triggers.length === 0) return;

        triggers.forEach(function (btn) {
            if (!btn) return;
            var key = String(btn.getAttribute('data-filter') || '').trim();
            if (!key) return;

            var field = form.querySelector('[name="' + CSS.escape(key) + '"]')
                || form.querySelector('[name="' + CSS.escape(key + '[]') + '"]');

            var hasValue = false;
            if (field) {
                if (field.tagName === 'SELECT') {
                    hasValue = String(field.value || '').trim() !== '';
                } else if (field.type === 'checkbox' || field.type === 'radio') {
                    hasValue = !!field.checked;
                } else {
                    hasValue = String(field.value || '').trim() !== '';
                }
            }

            if (hasValue) {
                btn.classList.add('is-filtered');
            } else {
                btn.classList.remove('is-filtered');
            }
        });
    }, true);

    var stAppConfig = stGetAppConfig();
    var stNotificationsCfg = stAppConfig && stAppConfig.notifications ? stAppConfig.notifications : {};

    function stGetCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function stNormalizeUrl(u) {
        try {
            var s = String(u || '');
            if (!s) return '#';
            if (s.indexOf('://') !== -1) {
                var parsed = new URL(s);
                return parsed.pathname + parsed.search + parsed.hash;
            }
            return s;
        } catch (ex) {
            return '#';
        }
    }

    // Notification sound using Web Audio API
    function stPlayNotificationSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.setValueAtTime(1047, ctx.currentTime + 0.1);
            osc.frequency.setValueAtTime(1319, ctx.currentTime + 0.2);
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.5);
        } catch (e) { /* ignore if AudioContext not available */ }
    }

    function stUpdateNotificationBadge(delta) {
        var badge = document.querySelector('.st-notification-badge');
        if (!badge) return;
        var current = parseInt(badge.textContent || '0', 10);
        if (!isFinite(current)) current = 0;
        var next = Math.max(0, current - (delta || 0));
        if (next <= 0) {
            badge.remove();
            return;
        }
        badge.textContent = String(next);
    }

    window.markAsRead = function (id) {
        if (!id) return;
        var base = stNotificationsCfg.readBaseUrl || '/notifications';
        var url = String(base || '').replace(/\/$/, '') + '/' + String(id) + '/read';
        // Update UI immediately
        var item = document.querySelector('[data-notification-id="' + id + '"]');
        if (item && item.classList.contains('st-notification-item--unread')) {
            item.classList.remove('st-notification-item--unread');
            stUpdateNotificationBadge(1);
        }
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': stGetCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        }).catch(function () { });
    };

    window.markAllAsRead = function () {
        var url = stNotificationsCfg.markAllUrl || '';
        if (!url) return;
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            if (response.ok) {
                document.querySelectorAll('.st-notification-item--unread').forEach(function (item) {
                    item.classList.remove('st-notification-item--unread');
                });
                // Update notification count
                var countBadge = document.getElementById('st-notification-count');
                if (countBadge) {
                    countBadge.textContent = '0';
                    countBadge.style.display = 'none';
                }
            }
        }).catch(function () { });
    };

    window.clearAllNotifications = function () {
        var url = stNotificationsCfg.clearUrl || '';
        if (!url) return;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': stGetCsrfToken(),
                'Accept': 'application/json'
            }
        }).then(function () {
            var list = document.querySelector('.st-notification-list');
            if (list) {
                list.innerHTML = '<div class="st-notification-empty"><i class="fas fa-bell-slash st-notification-empty__icon"></i><p class="st-notification-empty__text">No notifications yet</p></div>';
            }
            var badge = document.querySelector('.st-notification-badge');
            if (badge) badge.remove();
        }).catch(function () { });
    };

    window.markAsReadAndGo = function (e, id, url) {
        try {
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
            }
            var target = stNormalizeUrl(url);
            window.markAsRead(id);
            setTimeout(function () {
                window.location.href = target;
            }, 80);
            return false;
        } catch (err) {
            return true;
        }
    };

    function initNotificationDropdown() {
        var stNotifBtn = document.getElementById('st-notification-btn');
        var stNotifDropdown = document.getElementById('st-notification-dropdown');
        if (!stNotifBtn || !stNotifDropdown) return;

        stNotifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            stNotifDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (!stNotifDropdown.contains(e.target) && !stNotifBtn.contains(e.target)) {
                stNotifDropdown.classList.remove('show');
            }
        });
    }

    function initNotificationActions() {
        var stMarkAllBtn = document.getElementById('st-notification-mark-all');
        var stClearBtn = document.getElementById('st-notification-clear');
        if (stMarkAllBtn) {
            stMarkAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                window.markAllAsRead();
            });
        }
        if (stClearBtn) {
            stClearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                window.clearAllNotifications();
            });
        }
    }

    function initReminderBanner() {
        var reminderUrl = stAppConfig ? stAppConfig.reminderUrl : null;
        var banner = document.getElementById('st-reminder-banner');
        var countEl = document.getElementById('st-reminder-count');
        var listEl = document.getElementById('st-reminder-list');
        var toast = document.getElementById('st-reminder-toast');
        var toastText = document.getElementById('st-reminder-toast-text');
        var toastTimer = null;

        if (!reminderUrl || !banner || !countEl || !listEl) {
            return;
        }

        function showToast(items) {
            if (!toast || !toastText) {
                return;
            }
            if (!items || !items.length) {
                toast.style.display = 'none';
                return;
            }

            var first = items[0];
            var label = first.request_number || first.po_number || ('Request #' + first.id);
            var minutes = typeof first.minutes_to_start === 'number'
                ? Math.max(first.minutes_to_start, 0)
                : null;
            var countdown = minutes !== null ? (minutes + ' minutes to start') : 'Starts soon';
            toastText.textContent = items.length + ' pending booking(s). Nearest: ' + label + ' - ' + countdown + '.';
            toast.style.display = 'block';

            if (toastTimer) {
                clearTimeout(toastTimer);
            }
            toastTimer = setTimeout(function () {
                toast.style.display = 'none';
            }, 5000);
        }

        function renderReminder(items) {
            // Only show items within 60 minutes (H-1 hour)
            var urgentItems = (items || []).filter(function (item) {
                return typeof item.minutes_to_start === 'number' && item.minutes_to_start <= 60;
            });

            if (!urgentItems.length) {
                banner.style.display = 'none';
                listEl.innerHTML = '';
                countEl.textContent = '0';
                showToast([]);
                return;
            }

            // Replace items with filtered urgent items
            items = urgentItems;
            countEl.textContent = items.length;
            banner.style.display = 'block';

            listEl.innerHTML = items.map(function (item) {
                var label = item.request_number || item.po_number || ('Request #' + item.id);
                var supplier = item.supplier_name ? (' - ' + item.supplier_name) : '';
                var minutes = typeof item.minutes_to_start === 'number'
                    ? Math.max(item.minutes_to_start, 0)
                    : null;
                var countdown = minutes !== null ? (' (' + minutes + ' min)') : '';
                var timeText = item.planned_start ? ('Planned: ' + item.planned_start) : 'Planned: -';
                return (
                    '<a href="' + item.show_url + '" class="st-reminder-item">' +
                    '<span class="st-reminder-item__label">' + label + supplier + '</span>' +
                    '<span class="st-reminder-item__time">' + timeText + countdown + '</span>' +
                    '</a>'
                );
            }).join('');

            showToast(items);
        }

        function fetchReminders() {
            fetch(reminderUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (resp) { return resp.json(); })
                .then(function (data) {
                    if (!data || !data.success) {
                        renderReminder([]);
                        return;
                    }
                    renderReminder(data.items || []);
                })
                .catch(function () {
                    renderReminder([]);
                });
        }

        fetchReminders();
        setInterval(fetchReminders, 5 * 60 * 1000); // Check every 5 minutes
    }

    function initNotificationToast() {
        var latestUrl = stAppConfig ? stAppConfig.latestUrl : null;
        var toast = document.getElementById('st-notification-toast');
        var toastText = document.getElementById('st-notification-toast-text');
        var toastTimer = null;
        var storageKey = 'st_last_notification_id';

        if (!latestUrl || !toast || !toastText) {
            return;
        }

        function showNotification(notification) {
            if (!notification) {
                return;
            }
            toastText.textContent = (notification.title || 'Notification') + ' - ' + (notification.message || '');
            toast.style.display = 'block';
            stPlayNotificationSound();

            if (toastTimer) {
                clearTimeout(toastTimer);
            }
            toastTimer = setTimeout(function () {
                toast.style.display = 'none';
            }, 5000);
        }

        function checkLatest() {
            fetch(latestUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (resp) { return resp.json(); })
                .then(function (data) {
                    if (!data || !data.success || !data.notification) {
                        return;
                    }
                    var lastId = localStorage.getItem(storageKey);
                    if (!lastId) {
                        localStorage.setItem(storageKey, data.notification.id);
                        return;
                    }
                    if (lastId !== data.notification.id) {
                        localStorage.setItem(storageKey, data.notification.id);
                        showNotification(data.notification);
                    }
                })
                .catch(function () {
                    // ignore
                });
        }

        checkLatest();
        setInterval(checkLatest, 15 * 1000); // Check every 15 seconds
    }

    function initPwa() {
        var pwa = stAppConfig && stAppConfig.pwa ? stAppConfig.pwa : {};
        if (pwa.enabled && pwa.swUrl && 'serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register(pwa.swUrl)
                    .then(function (registration) {
                        registration.addEventListener('updatefound', function () {
                            var newWorker = registration.installing;
                            newWorker.addEventListener('statechange', function () {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    if (confirm('App updated. Reload to use the latest version?')) {
                                        window.location.reload();
                                    }
                                }
                            });
                        });
                    })
                    .catch(function () {
                        // ignore
                    });
            });
        }

        var deferredPrompt = null;
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;

            var installBtn = document.getElementById('pwa-install-btn');
            if (installBtn) {
                installBtn.style.display = 'block';
                installBtn.addEventListener('click', function () {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function () {
                        deferredPrompt = null;
                    });
                });
            }
        });
    }

    function initAppInstalledHandler() {
        window.addEventListener('appinstalled', function () {
            var installBtn = document.getElementById('pwa-install-btn');
            if (installBtn) {
                installBtn.style.display = 'none';
            }
        });
    }

    function initSidebarToggle() {
        var desktopToggle = document.getElementById('desktop-menu-toggle');
        var mobileToggle = document.getElementById('mobile-menu-toggle');
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('mobile-menu-overlay');
        var body = document.body;

        function checkScreenSize() {
            if (window.innerWidth > 768) {
                // Clean up mobile sidebar state when resizing to desktop
                if (sidebar) sidebar.classList.remove('st-sidebar--mobile-open');
                if (overlay) overlay.classList.remove('st-sidebar__overlay--active');
                document.body.style.overflow = '';
            }
        }

        window.addEventListener('resize', checkScreenSize);

        if (desktopToggle) {
            desktopToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
        }

        if (mobileToggle && sidebar && overlay) {
            mobileToggle.addEventListener('click', function () {
                sidebar.classList.toggle('st-sidebar--mobile-open');
                overlay.classList.toggle('st-sidebar__overlay--active');
                document.body.style.overflow = sidebar.classList.contains('st-sidebar--mobile-open') ? 'hidden' : '';
            });

            overlay.addEventListener('click', function () {
                sidebar.classList.remove('st-sidebar--mobile-open');
                overlay.classList.remove('st-sidebar__overlay--active');
                document.body.style.overflow = '';
            });

            var sidebarLinks = sidebar.querySelectorAll('.st-sidebar__link');
            sidebarLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    sidebar.classList.remove('st-sidebar--mobile-open');
                    overlay.classList.remove('st-sidebar__overlay--active');
                    document.body.style.overflow = '';
                });
            });
        }
    }

    function initFontPreload() {
        if (!('requestIdleCallback' in window)) return;
        requestIdleCallback(function () {
            var fontLink = document.createElement('link');
            fontLink.rel = 'preload';
            fontLink.as = 'font';
            fontLink.href = 'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZ9hiJ-Ek-_EeA.woff2';
            fontLink.crossOrigin = 'anonymous';
            document.head.appendChild(fontLink);
        });
    }

    function initBfcacheResync() {
        window.addEventListener('pageshow', function (event) {
            if (!event || !event.persisted) return;

            if (typeof window.ajaxReload === 'function') {
                try {
                    window.ajaxReload(false);
                    return;
                } catch (e) {
                    // fallback below
                }
            }

            window.location.reload();
        });
    }

    function initGlobalRealtimeSync() {
        var realtimeCfg = stAppConfig && stAppConfig.realtime ? stAppConfig.realtime : {};
        if (!realtimeCfg || realtimeCfg.enabled === false) return;

        var versionUrl = String(realtimeCfg.versionUrl || '').trim();
        if (!versionUrl) return;

        var pollMs = parseInt(realtimeCfg.pollMs, 10);
        if (!isFinite(pollMs) || pollMs < 10000) {
            pollMs = 20000;
        }

        var storageKey = 'st_realtime_global_version';
        var pendingRefresh = false;
        var isChecking = false;
        var hasBootstrappedVersion = false;

        function canRefreshNow() {
            if (document.hidden) return false;
            var active = document.activeElement;
            if (!active) return true;
            var tag = String(active.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return false;
            if (active.isContentEditable) return false;
            return true;
        }

        function performRefresh() {
            if (window.__stRealtimeReloading) return;
            window.__stRealtimeReloading = 1;

            if (typeof window.ajaxReload === 'function') {
                try {
                    window.ajaxReload(false);
                    window.__stRealtimeReloading = 0;
                    return;
                } catch (e) {
                    window.__stRealtimeReloading = 0;
                }
            }

            window.location.reload();
        }

        function handleVersion(nextVersion) {
            var current = String(nextVersion || '').trim();
            if (!current) return;

            var prev = '';
            try {
                prev = String(sessionStorage.getItem(storageKey) || '');
            } catch (e) {
                prev = '';
            }

            if (!hasBootstrappedVersion) {
                hasBootstrappedVersion = true;
                try { sessionStorage.setItem(storageKey, current); } catch (e) { }
                pendingRefresh = false;
                return;
            }

            if (!prev) {
                try { sessionStorage.setItem(storageKey, current); } catch (e) { }
                return;
            }

            if (prev === current) {
                if (pendingRefresh && canRefreshNow()) {
                    pendingRefresh = false;
                    performRefresh();
                }
                return;
            }

            try { sessionStorage.setItem(storageKey, current); } catch (e) { }

            if (canRefreshNow()) {
                performRefresh();
            } else {
                pendingRefresh = true;
            }
        }

        function checkVersion() {
            if (document.hidden) return;
            if (typeof navigator !== 'undefined' && navigator.onLine === false) return;
            if (isChecking) return;
            isChecking = true;
            fetch(versionUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.success) return;
                    handleVersion(data.version || '');
                })
                .catch(function () {
                    // ignore transient errors
                })
                .finally(function () {
                    isChecking = false;
                });
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) return;
            if (pendingRefresh && canRefreshNow()) {
                pendingRefresh = false;
                performRefresh();
                return;
            }
            checkVersion();
        });

        document.addEventListener('focusin', function () {
            if (!pendingRefresh) return;
            if (!canRefreshNow()) return;
            pendingRefresh = false;
            performRefresh();
        });

        checkVersion();
        setInterval(checkVersion, pollMs);
    }

    initNotificationDropdown();
    initNotificationActions();
    initReminderBanner();
    initNotificationToast();
    initBfcacheResync();
    initGlobalRealtimeSync();
    initPwa();
    initAppInstalledHandler();
    initSidebarToggle();
    initFontPreload();

    window.stPrintTicket = function (url) {
        try {
            var finalUrl = url;
            if (finalUrl && finalUrl.indexOf('autoprint=1') === -1) {
                finalUrl = finalUrl + (finalUrl.indexOf('?') >= 0 ? '&' : '?') + 'autoprint=1';
            }

            var win = window.open(finalUrl, '_blank');
            if (win) {
                try { win.focus(); } catch (e) { }
                return;
            }

            var existing = document.getElementById('st-print-iframe');
            if (existing && existing.parentNode) {
                existing.parentNode.removeChild(existing);
            }

            var iframe = document.createElement('iframe');
            iframe.id = 'st-print-iframe';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.style.opacity = '0';
            iframe.style.pointerEvents = 'none';
            iframe.src = finalUrl;

            iframe.onload = function () {
                try {
                    setTimeout(function () {
                        if (iframe.contentWindow) {
                            try {
                                if (iframe.contentDocument) {
                                    iframe.contentDocument.title = '';
                                }
                            } catch (e) {
                                // ignore
                            }
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        }
                    }, 600);
                } catch (e) {
                    // ignore
                }
            };

            document.body.appendChild(iframe);
        } catch (e) {
            // ignore
        }
    };
});

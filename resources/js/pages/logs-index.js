document.addEventListener('DOMContentLoaded', function() {
    var dateRangeInput = document.getElementById('date_range');
    var dateFromInput = document.getElementById('date_from');
    var dateToInput = document.getElementById('date_to');
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    function toDisplayDate(value) {
        if (!value) return '';
        var parts = String(value).split('-');
        if (parts.length !== 3) return value;
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }

    function toIsoDate(value) {
        if (!value) return '';
        var parts = String(value).split('-');
        if (parts.length !== 3) return value;
        return parts[2].length === 4 ? parts[2] + '-' + parts[1] + '-' + parts[0] : value;
    }

    function dateClassForMoment(m) {
        if (!m) return '';
        var classes = [];
        var iso = m.format('YYYY-MM-DD');
        if (m.day() === 0) classes.push('drp-sunday');
        if (holidayData && holidayData[iso]) classes.push('drp-holiday');
        return classes.join(' ');
    }

    function resolvePickerCellIso(cellEl) {
        if (!cellEl || !window.jQuery || typeof window.moment !== 'function') return '';
        var cell = window.jQuery(cellEl);
        var day = parseInt(cell.text().trim(), 10);
        if (!isFinite(day)) return '';
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

    function decoratePickerDays(picker) {
        if (!picker || !picker.container || !window.jQuery) return;
        picker.container.find('td.available').each(function () {
            var td = window.jQuery(this);
            var hasSunday = td.hasClass('drp-sunday');
            var hasHoliday = td.hasClass('drp-holiday');
            var tooltipText = '';

            if (hasHoliday) {
                var iso = resolvePickerCellIso(this);
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

    function bindPickerDecorators($el) {
        if (!$el || !$el.length) return;
        $el.off('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo');
        $el.on('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo', function (_ev, picker) {
            decoratePickerDays(picker);
        });
        var picker = $el.data('daterangepicker');
        if (picker) {
            setTimeout(function () { decoratePickerDays(picker); }, 0);
        }
    }

    if (dateRangeInput && window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.daterangepicker === 'function' && typeof window.moment === 'function') {
        var $ = window.jQuery;
        var moment = window.moment;
        var initial = dateFromInput && dateFromInput.value ? dateFromInput.value : '';
        var start = initial && moment(initial, 'YYYY-MM-DD').isValid() ? moment(initial, 'YYYY-MM-DD') : moment();
        if (initial) {
            dateRangeInput.value = toDisplayDate(initial);
        }

        var $el = $(dateRangeInput);
        $el.daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoApply: true,
            startDate: start,
            minYear: 1901,
            maxYear: parseInt(moment().format('YYYY'), 10) + 5,
            locale: { format: 'DD-MM-YYYY' },
            isCustomDate: dateClassForMoment
        }, function(startDate) {
            var value = startDate.format('DD-MM-YYYY');
            var iso = startDate.format('YYYY-MM-DD');
            if (dateFromInput) dateFromInput.value = iso;
            if (dateToInput) dateToInput.value = iso;
            dateRangeInput.value = value;
        });

        bindPickerDecorators($el);
    }

    // Auto-submit form on input change
    var logsFilterForm = document.getElementById('logs-filter-form');
    if (!logsFilterForm) return;

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

    function collectQueryControls() {
        var controls = Array.prototype.slice.call(logsFilterForm.querySelectorAll('input, select, textarea'));
        var topForm = document.querySelector('.st-card.st-mb-12 form[method="GET"]');
        if (topForm) {
            controls = controls.concat(Array.prototype.slice.call(topForm.querySelectorAll('input, select, textarea')));
        }
        return controls;
    }

    function buildQueryStringFromForm() {
        var params = new URLSearchParams();

        collectQueryControls().forEach(function (el) {
            appendControlToParams(params, el);
        });

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
        var tbody = logsFilterForm.querySelector('tbody');
        if (tbody) tbody.style.opacity = on ? '0.5' : '1';
    }

    function ajaxReload(pushState) {
        if (isLoading) return;
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
                var newForm = doc.getElementById('logs-filter-form');
                var tbody = logsFilterForm.querySelector('tbody');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tbody && newTbody) {
                    tbody.innerHTML = newTbody.innerHTML;
                }
                var curPag = logsFilterForm.querySelector('.st-pagination');
                var newPag = newForm ? newForm.querySelector('.st-pagination') : null;
                if (curPag && newPag) {
                    curPag.innerHTML = newPag.innerHTML;
                }
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

    logsFilterForm.addEventListener('change', function(e) {
        if (e.target.tagName === 'SELECT') {
            ajaxReload(true);
        }
    });

    var textInputs = logsFilterForm.querySelectorAll('input[type="text"]');
    textInputs.forEach(function(input) {
        var timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                ajaxReload(true);
            }, 500);
        });
    });

    logsFilterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxReload(true);
    });

    window.addEventListener('popstate', function () {
        var params = new URLSearchParams(window.location.search);
        collectQueryControls().forEach(function (el) {
            if (el.type === 'hidden' && (el.name === 'sort[]' || el.name === 'dir[]')) return;
            if (el.name) el.value = params.get(el.name) || '';
        });
        ajaxReload(false);
    });
});

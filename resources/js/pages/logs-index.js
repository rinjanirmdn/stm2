document.addEventListener('DOMContentLoaded', function() {
    var dateRangeInput = document.getElementById('date_range');
    var dateFromInput = document.getElementById('date_from');
    var dateToInput = document.getElementById('date_to');

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

    if (dateRangeInput && window.jQuery && window.jQuery.fn.dateRangePicker) {
        var initial = dateFromInput && dateFromInput.value ? dateFromInput.value : '';
        if (initial) {
            dateRangeInput.value = toDisplayDate(initial);
        }

        window.jQuery(dateRangeInput).dateRangePicker({
            autoClose: true,
            singleDate: true,
            showShortcuts: false,
            singleMonth: true,
            format: 'DD-MM-YYYY'
        }).bind('datepicker-change', function(event, obj) {
            var value = (obj && obj.value) ? obj.value : '';
            var iso = toIsoDate(value);
            if (dateFromInput) dateFromInput.value = iso;
            if (dateToInput) dateToInput.value = iso;
            dateRangeInput.value = value;
        });
    }

    // Auto-submit form on input change
    var logsFilterForm = document.getElementById('logs-filter-form');
    if (!logsFilterForm) return;

    var isLoading = false;

    function buildQueryStringFromForm() {
        var fd = new FormData(logsFilterForm);
        var params = new URLSearchParams();
        fd.forEach(function (v, k) {
            var val = String(v || '').trim();
            if (val !== '') params.append(k, val);
        });
        return params.toString();
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
        logsFilterForm.querySelectorAll('input, select').forEach(function (el) {
            if (el.type === 'hidden') return;
            if (el.name) el.value = params.get(el.name) || '';
        });
        ajaxReload(false);
    });
});

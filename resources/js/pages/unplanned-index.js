document.addEventListener('DOMContentLoaded', function () {
    var filterForm = document.getElementById('unplanned-filter-form');

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

    // Initialize single-date range picker for arrival date
    var arrivalRangeInput = document.querySelector('input#unplanned_arrival_range');
    if (arrivalRangeInput && window.jQuery && window.jQuery.fn.dateRangePicker) {
        var fromInput = document.querySelector('input[name="arrival_from"]');
        var toInput = document.querySelector('input[name="arrival_to"]');
        var initial = fromInput && fromInput.value ? fromInput.value : '';
        if (initial) {
            arrivalRangeInput.value = toDisplayDate(initial);
        }

        window.jQuery(arrivalRangeInput).dateRangePicker({
            autoClose: true,
            singleDate: true,
            showShortcuts: false,
            singleMonth: true,
            format: 'DD-MM-YYYY'
        }).bind('datepicker-change', function (event, obj) {
            var value = (obj && obj.value) ? obj.value : '';
            var iso = toIsoDate(value);
            if (fromInput) fromInput.value = iso;
            if (toInput) toInput.value = iso;
            arrivalRangeInput.value = value;
            setTimeout(function () {
                if (typeof window.ajaxReload === 'function') {
                    window.ajaxReload(true);
                } else if (filterForm) {
                    filterForm.submit();
                }
            }, 100);
        });
    }

    if (!filterForm) return;

    var isLoading = false;

    function buildQueryStringFromForm() {
        var fd = new FormData(filterForm);
        var params = new URLSearchParams();
        fd.forEach(function (v, k) {
            var val = String(v || '').trim();
            if (val !== '') params.append(k, val);
        });
        return params.toString();
    }

    function setLoading(on) {
        isLoading = on;
        var tbody = filterForm.querySelector('tbody');
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
                var newForm = doc.getElementById('unplanned-filter-form');
                var tbody = filterForm.querySelector('tbody');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tbody && newTbody) {
                    tbody.innerHTML = newTbody.innerHTML;
                }
                var curPag = filterForm.querySelector('.st-pagination');
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

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxReload(true);
    });

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t) return;
        if (t.matches('select[form="unplanned-filter-form"], input[type="date"][form="unplanned-filter-form"]')) {
            ajaxReload(true);
        }
    });

    window.addEventListener('popstate', function () {
        var params = new URLSearchParams(window.location.search);
        filterForm.querySelectorAll('input, select').forEach(function (el) {
            if (el.type === 'hidden') return;
            if (el.name) el.value = params.get(el.name) || '';
        });
        ajaxReload(false);
    });

    // NOTE: Filter panel toggle/clear/sort/indicator handled globally in resources/js/pages/main.js
    // Mark panels as fixed-position so they will be positioned above sticky table headers.
    try {
        filterForm.querySelectorAll('.st-filter-panel').forEach(function (p) {
            if (!p) return;
            p.setAttribute('data-st-position', 'fixed');
        });
    } catch (e) { }
});

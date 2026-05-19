import { highlightSearchInTable } from '../utils/search-highlight.js';

document.addEventListener('DOMContentLoaded', function() {
    // Listen to predefined date range picker initialized by main.js
    var logsReportRange = document.getElementById('logs_reportrange');
    if (logsReportRange && window.jQuery) {
        window.jQuery(logsReportRange).on('apply.daterangepicker', function(ev, picker) {
            if (picker && picker.startDate && picker.endDate) {
                var dateFromInput = document.getElementById('date_from');
                var dateToInput = document.getElementById('date_to');
                if (dateFromInput) dateFromInput.value = picker.startDate.format('YYYY-MM-DD');
                if (dateToInput) dateToInput.value = picker.endDate.format('YYYY-MM-DD');
            }
            if (typeof window.ajaxReload === 'function') {
                window.ajaxReload(true);
            }
        });
    }

    // === Live Search + Filter Logic ===
    var logsFilterForm = document.getElementById('logs-filter-form');
    if (!logsFilterForm) return;

    var dateFromInput = document.getElementById('date_from');
    var dateToInput = document.getElementById('date_to');
    var searchInput = document.getElementById('logs-search-input');
    var typeFilter = document.getElementById('logs-type-filter');
    var featureFilter = document.getElementById('logs-feature-filter');
    var isLoading = false;
    var searchTimeout = null;

    function syncHiddenInputs() {
        // Sync top controls into hidden form inputs
        var hiddenQ = logsFilterForm.querySelector('input[name="q"]');
        var hiddenType = logsFilterForm.querySelector('input[name="type"]');
        var hiddenFeature = logsFilterForm.querySelector('input[name="feature"]');
        var hiddenDateFrom = logsFilterForm.querySelector('input[name="date_from"]');
        var hiddenDateTo = logsFilterForm.querySelector('input[name="date_to"]');

        if (hiddenQ && searchInput) hiddenQ.value = searchInput.value;
        if (hiddenType && typeFilter) hiddenType.value = typeFilter.value;
        if (hiddenFeature && featureFilter) hiddenFeature.value = featureFilter.value;
        if (hiddenDateFrom && dateFromInput) hiddenDateFrom.value = dateFromInput.value;
        if (hiddenDateTo && dateToInput) hiddenDateTo.value = dateToInput.value;
    }

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

    function buildQueryStringFromForm() {
        syncHiddenInputs();

        var params = new URLSearchParams();
        var controls = Array.prototype.slice.call(logsFilterForm.querySelectorAll('input, select, textarea'));

        controls.forEach(function (el) {
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
        if (tbody) tbody.style.opacity = on ? '0.45' : '1';
        if (tbody) tbody.style.pointerEvents = on ? 'none' : '';
    }

    // Highlight matching search terms in the table (uses shared utility)
    function highlightSearchTerm(searchTerm) {
        var tbody = logsFilterForm.querySelector('tbody');
        highlightSearchInTable(tbody, searchTerm);
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

                // Apply highlight after content loaded
                var term = searchInput ? searchInput.value.trim() : '';
                highlightSearchTerm(term);
            })
            .catch(function (err) {
                console.error('AJAX reload failed:', err);
            })
            .finally(function () {
                setLoading(false);
            });
    }

    window.ajaxReload = ajaxReload;

    // Live search: filter as you type (debounced 350ms)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                ajaxReload(true);
            }, 350);
        });

        // Prevent form submission on Enter key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                ajaxReload(true);
            }
        });
    }

    // Live type filter: filter on change
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            ajaxReload(true);
        });
    }

    // Live feature filter: filter on change
    if (featureFilter) {
        featureFilter.addEventListener('change', function() {
            ajaxReload(true);
        });
    }

    // Sort triggers in table headers
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
        if (searchInput) searchInput.value = params.get('q') || '';
        if (typeFilter) typeFilter.value = params.get('type') || '';
        if (featureFilter) featureFilter.value = params.get('feature') || '';
        if (dateFromInput) dateFromInput.value = params.get('date_from') || '';
        if (dateToInput) dateToInput.value = params.get('date_to') || '';
        ajaxReload(false);
    });

    // Initial highlight on page load
    if (searchInput && searchInput.value.trim().length >= 2) {
        highlightSearchTerm(searchInput.value.trim());
    }
});

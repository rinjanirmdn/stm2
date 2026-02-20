document.addEventListener('DOMContentLoaded', function() {
    var userFilterForm = document.getElementById('user-filter-form');
    if (!userFilterForm) return;

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

    function buildQueryStringFromForm() {
        var params = new URLSearchParams();

        userFilterForm.querySelectorAll('input, select, textarea').forEach(function (el) {
            appendControlToParams(params, el);
        });

        var formId = String(userFilterForm.getAttribute('id') || '').trim();
        if (formId) {
            document.querySelectorAll('[form="' + CSS.escape(formId) + '"]').forEach(function (el) {
                appendControlToParams(params, el);
            });
        }

        // Users page keeps global search box in another form; preserve it on sort/filter AJAX.
        var globalSearch = document.querySelector('.st-card.st-mb-12 input[name="q"]');
        if (globalSearch) {
            appendControlToParams(params, globalSearch);
        }

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
        var tbody = userFilterForm.querySelector('tbody');
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
                var newForm = doc.getElementById('user-filter-form');
                var tbody = userFilterForm.querySelector('tbody');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tbody && newTbody) {
                    tbody.innerHTML = newTbody.innerHTML;
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

    userFilterForm.addEventListener('change', function(e) {
        if (e.target.tagName === 'SELECT') {
            ajaxReload(true);
        }
    });

    var textInputs = userFilterForm.querySelectorAll('input[type="text"]');
    textInputs.forEach(function(input) {
        var timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                ajaxReload(true);
            }, 500);
        });
    });

    userFilterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxReload(true);
    });

    window.addEventListener('popstate', function () {
        var params = new URLSearchParams(window.location.search);
        var controls = Array.prototype.slice.call(userFilterForm.querySelectorAll('input, select, textarea'));
        var formId = String(userFilterForm.getAttribute('id') || '').trim();
        if (formId) {
            controls = controls.concat(Array.prototype.slice.call(document.querySelectorAll('[form="' + CSS.escape(formId) + '"]')));
        }
        controls.forEach(function (el) {
            if (el.type === 'hidden') return;
            if (el.name) el.value = params.get(el.name) || '';
        });
        var globalSearch = document.querySelector('.st-card.st-mb-12 input[name="q"]');
        if (globalSearch) {
            globalSearch.value = params.get('q') || '';
        }
        ajaxReload(false);
    });
});

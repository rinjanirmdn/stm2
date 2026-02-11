document.addEventListener('DOMContentLoaded', function() {
    var userFilterForm = document.getElementById('user-filter-form');
    if (!userFilterForm) return;

    var isLoading = false;

    function buildQueryStringFromForm() {
        var fd = new FormData(userFilterForm);
        var params = new URLSearchParams();
        fd.forEach(function (v, k) {
            var val = String(v || '').trim();
            if (val !== '') params.append(k, val);
        });
        return params.toString();
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
        userFilterForm.querySelectorAll('input, select').forEach(function (el) {
            if (el.type === 'hidden') return;
            if (el.name) el.value = params.get(el.name) || '';
        });
        ajaxReload(false);
    });
});

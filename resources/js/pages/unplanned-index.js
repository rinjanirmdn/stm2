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
                if (filterForm) {
                    filterForm.submit();
                }
            }, 100);
        });
    }

    if (!filterForm) return;
    // NOTE: Filter panel toggle/clear/sort/indicator handled globally in resources/js/pages/main.js
    // Mark panels as fixed-position so they will be positioned above sticky table headers.
    try {
        filterForm.querySelectorAll('.st-filter-panel').forEach(function (p) {
            if (!p) return;
            p.setAttribute('data-st-position', 'fixed');
        });
    } catch (e) { }
});

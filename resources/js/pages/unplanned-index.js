document.addEventListener('DOMContentLoaded', function () {
    var filterForm = document.getElementById('unplanned-filter-form');

    // Initialize single-date range picker for arrival date
    var arrivalRangeInput = document.querySelector('input#unplanned_arrival_range');
    if (arrivalRangeInput && window.jQuery && window.jQuery.fn.dateRangePicker) {
        var fromInput = document.querySelector('input[name="arrival_from"]');
        var toInput = document.querySelector('input[name="arrival_to"]');
        var initial = fromInput && fromInput.value ? fromInput.value : '';
        if (initial) {
            arrivalRangeInput.value = initial;
        }

        window.jQuery(arrivalRangeInput).dateRangePicker({
            autoClose: true,
            singleDate: true,
            showShortcuts: false,
            singleMonth: true,
            format: 'YYYY-MM-DD'
        }).bind('datepicker-change', function (event, obj) {
            var value = (obj && obj.value) ? obj.value : '';
            if (fromInput) fromInput.value = value;
            if (toInput) toInput.value = value;
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

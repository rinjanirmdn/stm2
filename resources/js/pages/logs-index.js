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
    const logsFilterForm = document.getElementById('logs-filter-form');
    if (logsFilterForm) {
        // Auto-submit on select change
        logsFilterForm.addEventListener('change', function(e) {
            if (e.target.tagName === 'SELECT') {
                logsFilterForm.submit();
            }
        });

        // Auto-submit on input with debounce for text inputs
        const textInputs = logsFilterForm.querySelectorAll('input[type="text"]');
        textInputs.forEach(function(input) {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    logsFilterForm.submit();
                }, 500); // 500ms debounce
            });
        });
    }
});

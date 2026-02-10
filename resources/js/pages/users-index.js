document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on input change
    const userFilterForm = document.getElementById('user-filter-form');
    if (userFilterForm) {
        // Auto-submit on select change
        userFilterForm.addEventListener('change', function(e) {
            if (e.target.tagName === 'SELECT') {
                userFilterForm.submit();
            }
        });

        // Auto-submit on input with debounce for text inputs
        const textInputs = userFilterForm.querySelectorAll('input[type="text"]');
        textInputs.forEach(function(input) {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    userFilterForm.submit();
                }, 500); // 500ms debounce
            });
        });
    }
});

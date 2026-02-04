document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            var icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    var roleSelect = document.getElementById('role');
    var vendorField = document.getElementById('vendor_code_field');

    function syncVendorField() {
        if (!roleSelect || !vendorField) return;
        var role = String(roleSelect.value || '');
        vendorField.style.display = role === 'vendor' ? 'block' : 'none';
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', syncVendorField);
        syncVendorField();
    }
});

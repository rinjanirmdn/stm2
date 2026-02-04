document.addEventListener('DOMContentLoaded', function() {
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

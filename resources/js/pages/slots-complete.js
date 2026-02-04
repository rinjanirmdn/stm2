document.addEventListener('DOMContentLoaded', function () {
    var items = [];
    try {
        var el = document.getElementById('truck_types_json');
        items = el ? JSON.parse(el.textContent || '[]') : [];
    } catch (e) {
        items = [];
    }

    var count = Array.isArray(items) ? items.length : 0;
    console.log('Complete page - Truck types count:', count);
    console.log('Complete page - Using standard dropdown instead of autocomplete');
});

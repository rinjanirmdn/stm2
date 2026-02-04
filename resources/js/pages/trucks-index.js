function stReadJson(id, fallback) {
    try {
        var el = document.getElementById(id);
        if (!el) return fallback;
        return JSON.parse(el.textContent || '{}') || fallback;
    } catch (e) {
        return fallback;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var config = stReadJson('trucks_index_config', {});
    var baseUrl = String(config.baseUrl || '/trucks').replace(/\/$/, '');
    var storeUrl = config.storeUrl || baseUrl;
    var tableBody = document.getElementById('truck-table-body');
    if (!tableBody) return;

    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr[data-row-id]'));
    var emptyRow = tableBody.querySelector('.truck-empty-row');

    var searchInput = document.getElementById('truck-search');
    var pageSizeSelect = document.getElementById('truck-page-size');

    function applyFilter() {
        var term = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var pageSizeVal = pageSizeSelect ? pageSizeSelect.value : '10';
        var pageSize = pageSizeVal === 'all' ? Infinity : parseInt(pageSizeVal, 10);
        if (!pageSize || pageSize <= 0) pageSize = Infinity;

        var visibleRows = [];
        rows.forEach(function (row) {
            var tt = (row.getAttribute('data-truck-type') || '').toLowerCase();
            var matches = true;
            if (term) {
                matches = tt.indexOf(term) !== -1;
            }
            if (matches) {
                visibleRows.push(row);
            }
        });

        var anyVisible = visibleRows.length > 0;
        if (emptyRow) {
            emptyRow.style.display = anyVisible ? 'none' : '';
        }

        rows.forEach(function (row) {
            row.style.display = 'none';
        });

        var counter = 0;
        visibleRows.forEach(function (row) {
            if (counter < pageSize) {
                row.style.display = '';
                counter++;
            }
        });

        var number = 1;
        Array.prototype.slice.call(tableBody.querySelectorAll('tr[data-row-id]')).forEach(function (row) {
            if (row.style.display === 'none') return;
            var cell = row.querySelector('td');
            if (cell) {
                cell.textContent = String(number);
                number++;
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            applyFilter();
        });
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            applyFilter();
        });
    }

    applyFilter();

    // Modal functionality
    var modal = document.getElementById('truck-modal');
    var modalTitle = document.getElementById('truck-modal-title');
    var modalForm = document.getElementById('truck-form');
    var truckIdInput = document.getElementById('truck-id');
    var truckTypeInput = document.getElementById('truck-type-input');
    var truckDurationInput = document.getElementById('truck-duration-input');
    var btnAdd = document.getElementById('btn-add-truck');
    var btnClose = document.getElementById('truck-modal-close');
    var btnCancel = document.getElementById('truck-modal-cancel');

    function openModal(isEdit, data) {
        if (!modal) return;

        if (isEdit && data) {
            modalTitle.textContent = 'Edit Truck';
            truckIdInput.value = data.id || '';
            truckTypeInput.value = data.truckType || '';
            truckDurationInput.value = data.duration || '';
            modalForm.action = baseUrl + '/' + data.id + '/edit';
        } else {
            modalTitle.textContent = 'Add Truck';
            truckIdInput.value = '';
            truckTypeInput.value = '';
            truckDurationInput.value = '';
            modalForm.action = storeUrl;
        }

        modal.style.display = 'flex';
        truckTypeInput.focus();
    }

    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
    }

    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            openModal(false);
        });
    }

    if (btnClose) {
        btnClose.addEventListener('click', closeModal);
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Edit buttons
    document.querySelectorAll('.btn-edit-truck').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var data = {
                id: this.getAttribute('data-id'),
                truckType: this.getAttribute('data-truck-type'),
                duration: this.getAttribute('data-duration')
            };
            openModal(true, data);
        });
    });
});

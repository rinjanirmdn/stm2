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
    var config = stReadJson('transporters_index_config', {});
    var baseUrl = String(config.baseUrl || '/master/transporters').replace(/\/$/, '');
    var storeUrl = config.storeUrl || baseUrl;

    var tableBody = document.getElementById('transporter-table-body');
    if (!tableBody) return;

    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr[data-row-id]'));
    var emptyRow = tableBody.querySelector('.transporter-empty-row');

    var searchInput = document.getElementById('transporter-search');
    var statusSelect = document.getElementById('transporter-status');
    var pageSizeSelect = document.getElementById('transporter-page-size');

    function applyFilter() {
        var term = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var statusFilter = statusSelect ? statusSelect.value : '';
        var pageSizeVal = pageSizeSelect ? pageSizeSelect.value : '25';
        var pageSize = pageSizeVal === 'all' ? Infinity : parseInt(pageSizeVal, 10);
        if (!pageSize || pageSize <= 0) pageSize = Infinity;

        var visibleRows = [];
        rows.forEach(function (row) {
            var name = (row.getAttribute('data-name') || '').toLowerCase();
            var status = row.getAttribute('data-status') || '';
            
            var matchesSearch = true;
            if (term) {
                matchesSearch = name.indexOf(term) !== -1;
            }

            var matchesStatus = true;
            if (statusFilter) {
                matchesStatus = status === statusFilter;
            }

            if (matchesSearch && matchesStatus) {
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
        rows.forEach(function (row) {
            if (row.style.display === 'none') return;
            var cell = row.querySelector('td');
            if (cell) {
                cell.textContent = String(number);
                number++;
            }
        });
        
        // Optional: Implement highlight search if function is imported, else just filter
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilter);
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', applyFilter);
    }
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', applyFilter);
    }

    applyFilter();

    // Modal functionality
    var modal = document.getElementById('transporter-modal');
    var modalTitle = document.getElementById('transporter-modal-title');
    var modalForm = document.getElementById('transporter-form');
    var nameInput = document.getElementById('transporter-name-input');
    var statusActiveInput = document.getElementById('transporter-status-active');
    var statusInactiveInput = document.getElementById('transporter-status-inactive');
    var btnAdd = document.getElementById('btn-add-transporter');
    var btnClose = document.getElementById('transporter-modal-close');
    var btnCancel = document.getElementById('transporter-modal-cancel');

    function openModal(isEdit, data) {
        if (!modal) return;

        if (isEdit && data) {
            modalTitle.textContent = 'Edit Transporter';
            nameInput.value = data.name || '';
            if (data.status === '1') {
                statusActiveInput.checked = true;
            } else {
                statusInactiveInput.checked = true;
            }
            modalForm.action = baseUrl + '/' + data.id + '/edit';
        } else {
            modalTitle.textContent = 'Add Transporter';
            nameInput.value = '';
            statusActiveInput.checked = true;
            modalForm.action = storeUrl;
        }

        modal.style.display = 'flex';
        nameInput.focus();
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
    document.querySelectorAll('.btn-edit-transporter').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var data = {
                id: this.getAttribute('data-id'),
                name: this.getAttribute('data-name'),
                status: this.getAttribute('data-status')
            };
            openModal(true, data);
        });
    });

    // Delete confirmation dialog
    var deleteDialog = document.getElementById('deleteTransporterDialog');
    var deleteForm = document.getElementById('delete-transporter-form');
    var deleteNameEl = document.getElementById('deleteTransporterName');
    var confirmDeleteNo = document.getElementById('confirmDeleteNo');

    function showDeleteDialog(url, name) {
        if (!deleteDialog || !deleteForm) return;
        deleteForm.setAttribute('action', url);
        if (deleteNameEl) deleteNameEl.textContent = name;
        deleteDialog.classList.remove('st-hidden');
        deleteDialog.style.display = 'flex';
    }

    function hideDeleteDialog() {
        if (!deleteDialog) return;
        deleteDialog.style.display = 'none';
        deleteDialog.classList.add('st-hidden');
    }

    if (confirmDeleteNo) {
        confirmDeleteNo.addEventListener('click', hideDeleteDialog);
    }

    if (deleteDialog) {
        deleteDialog.addEventListener('click', function(e) {
            if (e.target === deleteDialog) hideDeleteDialog();
        });
    }

    document.querySelectorAll('.btn-delete-transporter').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.getAttribute('data-delete-url');
            var name = this.getAttribute('data-name');
            showDeleteDialog(url, name);
        });
    });
});

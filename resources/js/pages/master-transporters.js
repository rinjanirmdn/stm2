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

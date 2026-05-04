import { highlightSearchInTable } from '../utils/search-highlight.js';

document.addEventListener('DOMContentLoaded', function() {
    var userFilterForm = document.getElementById('user-filter-form');
    if (!userFilterForm) return;

    var isLoading = false;

    function appendControlToParams(params, el) {
        if (!el || !el.name || el.disabled) return;
        var tag = String(el.tagName || '').toLowerCase();
        var type = String(el.type || '').toLowerCase();

        if ((type === 'checkbox' || type === 'radio') && !el.checked) return;

        if (tag === 'select' && el.multiple) {
            Array.prototype.slice.call(el.options || []).forEach(function (opt) {
                if (!opt || !opt.selected) return;
                var val = String(opt.value || '').trim();
                if (val !== '') params.append(el.name, val);
            });
            return;
        }

        var val = String(el.value || '').trim();
        if (val !== '') params.append(el.name, val);
    }

    function buildQueryStringFromForm() {
        var params = new URLSearchParams();

        userFilterForm.querySelectorAll('input, select, textarea').forEach(function (el) {
            appendControlToParams(params, el);
        });

        var formId = String(userFilterForm.getAttribute('id') || '').trim();
        if (formId) {
            document.querySelectorAll('[form="' + CSS.escape(formId) + '"]').forEach(function (el) {
                appendControlToParams(params, el);
            });
        }

        // Users page keeps global search box in another form; preserve it on sort/filter AJAX.
        var globalSearch = document.querySelector('.st-card.st-mb-12 input[name="q"]');
        if (globalSearch) {
            appendControlToParams(params, globalSearch);
        }

        var seen = new Set();
        var dedup = new URLSearchParams();
        params.forEach(function (v, k) {
            var sig = k + '::' + v;
            if (seen.has(sig)) return;
            seen.add(sig);
            dedup.append(k, v);
        });
        return dedup.toString();
    }

    function setLoading(on) {
        isLoading = on;
        var tbody = userFilterForm.querySelector('tbody');
        if (tbody) tbody.style.opacity = on ? '0.5' : '1';
    }

    function ajaxReload(pushState) {
        if (isLoading) return;
        setLoading(true);

        var qs = buildQueryStringFromForm();
        var url = window.location.pathname + (qs ? ('?' + qs) : '');

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newForm = doc.getElementById('user-filter-form');
                var tbody = userFilterForm.querySelector('tbody');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tbody && newTbody) {
                    tbody.innerHTML = newTbody.innerHTML;
                }
                if (pushState) {
                    window.history.pushState(null, '', url);
                }

                // Apply search highlight after content loaded
                var globalSearchEl = document.querySelector('.st-card.st-mb-12 input[name="q"]');
                var term = globalSearchEl ? globalSearchEl.value.trim() : '';
                highlightSearchInTable(userFilterForm.querySelector('tbody'), term);
            })
            .catch(function (err) {
                console.error('AJAX reload failed:', err);
            })
            .finally(function () {
                setLoading(false);
            });
    }

    window.ajaxReload = ajaxReload;

    userFilterForm.addEventListener('change', function(e) {
        if (e.target.tagName === 'SELECT') {
            ajaxReload(true);
        }
    });

    var textInputs = userFilterForm.querySelectorAll('input[type="text"]');
    textInputs.forEach(function(input) {
        var timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                ajaxReload(true);
            }, 500);
        });
    });

    userFilterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxReload(true);
    });

    // Debounced search for the global search box outside the form
    var globalSearchInput = document.querySelector('.st-card.st-mb-12 input[name="q"]');
    if (globalSearchInput) {
        var globalSearchTimer = null;
        globalSearchInput.addEventListener('input', function () {
            clearTimeout(globalSearchTimer);
            globalSearchTimer = setTimeout(function () {
                ajaxReload(true);
            }, 400);
        });
        globalSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(globalSearchTimer);
                ajaxReload(true);
            }
        });
    }

    window.addEventListener('popstate', function () {
        var params = new URLSearchParams(window.location.search);
        var controls = Array.prototype.slice.call(userFilterForm.querySelectorAll('input, select, textarea'));
        var formId = String(userFilterForm.getAttribute('id') || '').trim();
        if (formId) {
            controls = controls.concat(Array.prototype.slice.call(document.querySelectorAll('[form="' + CSS.escape(formId) + '"]')));
        }
        controls.forEach(function (el) {
            if (el.type === 'hidden') return;
            if (el.name) el.value = params.get(el.name) || '';
        });
        var globalSearch = document.querySelector('.st-card.st-mb-12 input[name="q"]');
        if (globalSearch) {
            globalSearch.value = params.get('q') || '';
        }
        ajaxReload(false);
    });

    // Delete user confirmation dialog
    var deleteDialog = document.getElementById('deleteUserDialog');
    var deleteForm = document.getElementById('delete-user-form');
    var deleteNameEl = document.getElementById('deleteUserName');
    var confirmDeleteNo = document.getElementById('confirmDeleteUserNo');

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

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-delete-user');
        if (!btn) return;
        var url = btn.getAttribute('data-delete-url');
        var name = btn.getAttribute('data-user-name');
        showDeleteDialog(url, name);
    });

    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var url = deleteForm.getAttribute('action');
            var csrfToken = deleteForm.querySelector('input[name="_token"]');
            
            if (!url || !csrfToken) return;

            var submitBtn = document.getElementById('confirmDeleteUserYes');
            var originalText = '';
            if (submitBtn) {
                originalText = submitBtn.textContent;
                submitBtn.textContent = 'Deleting...';
                submitBtn.disabled = true;
            }

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.value,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                if (res.ok) {
                    hideDeleteDialog();
                    if (typeof window.showToast === 'function') {
                        window.showToast('User deleted permanently', true);
                    } else if (typeof p === 'function') {
                        p('User deleted permanently', true); // minified fallback
                    } else {
                        // Inline fallback
                        var t = document.createElement('div');
                        t.className = 'st-toast st-toast--success';
                        t.textContent = 'User deleted permanently';
                        document.body.appendChild(t);
                        requestAnimationFrame(function(){ t.classList.add('st-toast--visible'); });
                        setTimeout(function(){ t.classList.remove('st-toast--visible'); setTimeout(function(){ t.remove(); }, 300); }, 3500);
                    }
                    ajaxReload(false);
                } else {
                    res.json().then(function(data) {
                        alert(data.message || 'Failed to delete user.');
                    }).catch(function() {
                        alert('Failed to delete user.');
                    });
                }
            })
            .catch(function(err) {
                console.error('Delete error:', err);
                alert('Network error. Please try again.');
            })
            .finally(function() {
                if (submitBtn) {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });
        });
    }

    // Initial highlight on page load
    var searchInputHL = document.querySelector('.st-card.st-mb-12 input[name="q"]');
    if (searchInputHL && searchInputHL.value.trim().length >= 2) {
        highlightSearchInTable(userFilterForm.querySelector('tbody'), searchInputHL.value.trim());
    }

    // ===== Row click to navigate to user edit =====
    document.addEventListener('click', function(e) {
        var row = e.target.closest('tr[data-href]');
        if (!row) return;
        // Don't navigate if clicking interactive elements
        if (e.target.closest('a') || e.target.closest('button') || e.target.closest('label') ||
            e.target.closest('input') || e.target.closest('.st-switch') || e.target.closest('.tw-actionbar')) return;
        window.location.href = row.getAttribute('data-href');
    });

    // ===== AJAX Toggle Active/Inactive =====
    function showToast(message, isSuccess) {
        var existing = document.querySelector('.st-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.className = 'st-toast ' + (isSuccess ? 'st-toast--success' : 'st-toast--error');
        toast.textContent = message;
        document.body.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(function() {
            toast.classList.add('st-toast--visible');
        });

        setTimeout(function() {
            toast.classList.remove('st-toast--visible');
            setTimeout(function() { toast.remove(); }, 300);
        }, 2500);
    }

    document.addEventListener('change', function(e) {
        var checkbox = e.target.closest('.st-toggle-active');
        if (!checkbox) return;

        var url = checkbox.getAttribute('data-toggle-url');
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) return;

        // Disable checkbox during request
        checkbox.disabled = true;
        var switchLabel = checkbox.closest('.st-switch');
        if (switchLabel) switchLabel.style.opacity = '0.5';

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        })
        .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
        .then(function(result) {
            if (result.ok && result.data.success) {
                // Update checkbox state to match server
                checkbox.checked = result.data.is_active;
                // Update title
                if (switchLabel) {
                    switchLabel.title = result.data.is_active ? 'Click to Deactivate' : 'Click to Activate';
                }
                showToast(result.data.message || 'Status updated', true);
            } else {
                // Revert checkbox
                checkbox.checked = !checkbox.checked;
                showToast(result.data.message || 'Failed to update status', false);
            }
        })
        .catch(function(err) {
            // Revert checkbox on error
            checkbox.checked = !checkbox.checked;
            showToast('Network error. Please try again.', false);
            console.error('Toggle error:', err);
        })
        .finally(function() {
            checkbox.disabled = false;
            if (switchLabel) switchLabel.style.opacity = '1';
        });
    });

    // ===== Add User Modal =====
    var addUserModal = document.getElementById('addUserModal');
    var btnOpenAddUser = document.getElementById('btnOpenAddUser');
    var btnCloseAddUser = document.getElementById('btnCloseAddUser');
    var modalRoleSelect = document.getElementById('modal-role');
    var modalVendorField = document.getElementById('modal-vendor-code-field');

    function openAddUserModal() {
        if (!addUserModal) return;
        addUserModal.classList.remove('st-hidden');
        addUserModal.style.display = 'flex';
    }

    function closeAddUserModal() {
        if (!addUserModal) return;
        addUserModal.style.display = 'none';
        addUserModal.classList.add('st-hidden');
    }

    if (btnOpenAddUser) {
        btnOpenAddUser.addEventListener('click', openAddUserModal);
    }

    if (btnCloseAddUser) {
        btnCloseAddUser.addEventListener('click', closeAddUserModal);
    }

    if (addUserModal) {
        addUserModal.addEventListener('click', function(e) {
            if (e.target === addUserModal) closeAddUserModal();
        });
    }

    // Vendor code field toggle in modal
    function syncModalVendorField() {
        if (!modalRoleSelect || !modalVendorField) return;
        var role = String(modalRoleSelect.value || '');
        modalVendorField.style.display = role === 'vendor' ? 'block' : 'none';
    }

    if (modalRoleSelect) {
        modalRoleSelect.addEventListener('change', syncModalVendorField);
        syncModalVendorField();
    }

    // Password toggle in modal
    addUserModal && addUserModal.querySelectorAll('.btn-toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            var icon = this.querySelector('i');
            if (!input || !icon) return;

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
});

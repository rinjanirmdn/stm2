document.addEventListener('DOMContentLoaded', function () {
    // Tooltips handled globally in resources/js/pages/main.js (st-global-tooltip)
    function readJsonScript(id, fallback) {
        var el = document.getElementById(id);
        if (!el) return fallback;
        try {
            return JSON.parse(el.textContent || 'null') ?? fallback;
        } catch (e) {
            return fallback;
        }
    }

    var slotConfig = readJsonScript('slots_index_config', {});
    var suggestUrl = slotConfig.suggestUrl || '';

    // Initialize jQuery UI datepicker for date inputs
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};
    var dateInputs = document.querySelectorAll('input[type="date"][form="slot-filter-form"]');
    dateInputs.forEach(function(input) {
        if (!window.jQuery || !window.jQuery.fn.datepicker) return;
        if (input.getAttribute('data-st-datepicker') === '1') return;
        input.setAttribute('data-st-datepicker', '1');
        try { input.type = 'text'; } catch (e) {}

        window.jQuery(input).datepicker({
            dateFormat: 'yy-mm-dd',
            beforeShowDay: function(date) {
                var ds = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                if (holidayData[ds]) {
                    return [true, 'is-holiday', holidayData[ds]];
                }
                return [true, '', ''];
            }
        });
    });

    // Action Menu Toggle
    document.addEventListener('click', function(e) {
        if (e.target.closest('.st-action-trigger')) {
            e.preventDefault();
            e.stopPropagation();
            const trigger = e.target.closest('.st-action-trigger');
            const menu = trigger.nextElementSibling;

            // Close all other open menus
            document.querySelectorAll('.st-action-menu.show').forEach(m => {
                if (m !== menu) m.classList.remove('show');
            });

            menu.classList.toggle('show');
        } else {
            // Click outside, close all
            document.querySelectorAll('.st-action-menu.show').forEach(m => {
                m.classList.remove('show');
            });
        }
    });

    var filterForm = document.getElementById('slot-filter-form');
    if (!filterForm) return;

    var tbody = filterForm.querySelector('tbody');
    var isLoading = false;

    function setLoading(loading) {
        isLoading = loading;
        if (tbody) {
            tbody.style.opacity = loading ? '0.5' : '';
        }
    }

    function buildQueryStringFromForm() {
        var fd = new FormData(filterForm);
        var params = new URLSearchParams();
        fd.forEach(function (value, key) {
            if (value === '' || value === null || typeof value === 'undefined') return;
            params.append(key, value);
        });
        return params.toString();
    }

    function syncFormFromUrl() {
        var params = new URLSearchParams(window.location.search);
        Array.prototype.slice.call(filterForm.elements).forEach(function (el) {
            if (!el || !el.name) return;
            var name = el.name;
            var values = params.getAll(name);
            if (!values || values.length === 0) {
                if (name === 'page_size') {
                    el.value = '10';
                } else {
                    el.value = '';
                }
                return;
            }
            el.value = values[0];
        });
    }

    // Global variables for confirmation dialog
    let pendingCancelUrl = null;
    let currentSlotNumber = '';

    // Show confirmation dialog
    function showConfirmDialog(slotNumber = '') {
        const dialog = document.getElementById('customConfirmDialog');
        const slotNumberElement = document.getElementById('slotNumber');
        const rejectReason = document.getElementById('rejectReason');
        const form = document.getElementById('cancel-booking-form');

        if (dialog) {
            dialog.style.display = 'flex';
            if (slotNumberElement && slotNumber) {
                currentSlotNumber = slotNumber;
                slotNumberElement.textContent = slotNumber;
            }
            if (form && pendingCancelUrl) {
                form.setAttribute('action', pendingCancelUrl);
            }
            if (rejectReason) {
                rejectReason.value = '';
                rejectReason.focus();
            }
        }
    }

    // Hide confirmation dialog
    function hideConfirmDialog() {
        const dialog = document.getElementById('customConfirmDialog');
        if (dialog) {
            dialog.style.display = 'none';
        }
        pendingCancelUrl = null;
    }

    // Setup event listeners for confirmation dialog
    (function setupCancelBookingDialog() {
        const dialog = document.getElementById('customConfirmDialog');
        const btnNo = document.getElementById('confirmRejectNo');
        const form = document.getElementById('cancel-booking-form');
        const reasonEl = document.getElementById('rejectReason');

        if (btnNo) {
            btnNo.addEventListener('click', function () {
                hideConfirmDialog();
            });
        }

        if (dialog) {
            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) {
                    hideConfirmDialog();
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                const reason = reasonEl ? reasonEl.value.trim() : '';
                if (!reason) {
                    e.preventDefault();
                    if (reasonEl) {
                        reasonEl.focus();
                    }
                }
            });
        }
    })();

    function bindCancelConfirm() {
        document.querySelectorAll('.btn-cancel-slot').forEach(function (btn) {
            if (btn.getAttribute('data-confirm-bound') === '1') return;
            btn.setAttribute('data-confirm-bound', '1');

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                pendingCancelUrl = this.getAttribute('href');

                // Extract slot number from the row (adjust selector as needed)
                const row = this.closest('tr');
                let slotNumber = 'this booking';
                if (row) {
                    const slotCell = row.querySelector('td:nth-child(2)'); // Adjust index if needed
                    if (slotCell) {
                        slotNumber = slotCell.textContent.trim();
                    }
                }

                showConfirmDialog(slotNumber);
            });
        });
    }

    function ajaxReload(pushState) {
        if (isLoading) return;

        // Update indicators immediately (do not wait for network)
        try { setupActiveFilters(); } catch (e) {}
        try { setupSorting(); } catch (e) {}

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
                var newForm = doc.getElementById('slot-filter-form');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tbody && newTbody) {
                    tbody.innerHTML = newTbody.innerHTML;
                }
                bindCancelConfirm();
                syncActionTooltips();
                setupActiveFilters(); // Refresh active filter indicators
                setupSorting(); // Refresh active sort indicators
                if (pushState) {
                    window.history.pushState(null, '', url);
                }
            })
            .catch(function (err) {
                console.error('AJAX reload failed:', err);
                // window.location.href = url; // Disabled to prevent refresh loop
            })
            .finally(function () {
                setLoading(false);
            });
    }

    var openPanel = null;

    function closeOpenPanel() {
        if (openPanel) {
            openPanel.style.display = 'none';
            if (openPanel._stOrigParent) {
                try {
                    if (openPanel._stOrigNext && openPanel._stOrigNext.parentNode === openPanel._stOrigParent) {
                        openPanel._stOrigParent.insertBefore(openPanel, openPanel._stOrigNext);
                    } else {
                        openPanel._stOrigParent.appendChild(openPanel);
                    }
                } catch (e) {
                    // ignore
                }
                openPanel._stOrigParent = null;
                openPanel._stOrigNext = null;
            }
            openPanel = null;
        }
    }

    function openPanelAtTrigger(trigger, panel, panelWidth) {
        var rect = trigger.getBoundingClientRect();
        var viewportWidth = window.innerWidth;
        var viewportHeight = window.innerHeight;
        var width = panelWidth || 280;
        var margin = 8;

        // Portal panel to body to avoid "fixed inside transformed parent" issues
        if (!panel._stOrigParent) {
            panel._stOrigParent = panel.parentNode;
            panel._stOrigNext = panel.nextSibling;
        }
        if (panel.parentNode !== document.body) {
            document.body.appendChild(panel);
        }

        // Make visible to measure actual height
        panel.style.position = 'fixed';
        panel.style.display = 'block';
        panel.style.visibility = 'hidden';
        panel.style.width = width + 'px';

        var measuredHeight = panel.offsetHeight || 300;

        // Horizontal: align left edge with trigger; if overflow right, align right edge to trigger
        var left = rect.left;
        if (left + width > viewportWidth - margin) {
            left = rect.right - width;
        }
        left = Math.max(margin, Math.min(left, viewportWidth - margin - width));

        // Vertical: prefer below; if overflow bottom, flip above
        var top = rect.bottom + 6;
        if (top + measuredHeight > viewportHeight - margin) {
            top = rect.top - 6 - measuredHeight;
        }
        top = Math.max(margin, Math.min(top, viewportHeight - margin - measuredHeight));

        panel.style.visibility = '';
        panel.style.top = Math.round(top) + 'px';
        panel.style.left = Math.round(left) + 'px';
        panel.style.zIndex = '9999';
        panel.style.maxHeight = Math.max(120, (viewportHeight - top - margin)) + 'px';

        openPanel = panel;
    }

    function setupDropdownFilter(filterName, panelWidth) {
        var trigger = document.querySelector('.st-filter-trigger[data-filter="' + filterName + '"]');
        var panel = document.querySelector('.st-filter-panel[data-filter-panel="' + filterName + '"]');
        if (!trigger || !panel) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            if (openPanel === panel) {
                closeOpenPanel();
            } else {
                closeOpenPanel();
                openPanelAtTrigger(trigger, panel, panelWidth);
            }
        });

        var clearBtn = panel.querySelector('.st-filter-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();

                // Clear all selects in panel
                var selects = panel.querySelectorAll('select');
                Array.prototype.slice.call(selects).forEach(function (s) {
                    if (!s) return;
                    try { s.value = ''; } catch (e) {}
                });

                // Clear all inputs in panel (including hidden ones)
                var inputs = panel.querySelectorAll('input');
                Array.prototype.slice.call(inputs).forEach(function (i) {
                    if (!i) return;
                    try { i.value = ''; } catch (e) {}
                });

                ajaxReload(true);
            });
        }

        var selects = panel.querySelectorAll('select');
        Array.prototype.slice.call(selects).forEach(function (s) {
            if (!s) return;
            s.addEventListener('change', function () {
                ajaxReload(true);
            });
        });

        var inputs = panel.querySelectorAll('input');
        Array.prototype.slice.call(inputs).forEach(function (i) {
            if (!i) return;
            if (i.type === 'hidden') return;
            i.addEventListener('change', function () {
                ajaxReload(true);
            });
            i.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    ajaxReload(true);
                }
            });
            i.addEventListener('change', function () {
                ajaxReload(true);
            });
        });
    }

    function setupActiveFilters() {
        // Clear all active filter indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-filter', 'st-filter-header--active-filter');
        });
        document.querySelectorAll('.st-filter-trigger').forEach(function(btn) {
            btn.classList.remove('st-filter-trigger--active');
            btn.classList.remove('is-filtered');
        });

        // Check each filter field for active values
        var activeFilters = [];

        // Text input filters
        var textFilters = ['truck', 'mat_doc', 'vendor', 'po_number'];
        textFilters.forEach(function(filterName) {
            var input = filterForm.querySelector('input[name="' + filterName + '"]');
            if (input && input.value && input.value.trim() !== '') {
                activeFilters.push(filterName);
            }
        });

        // Select filters (multiple and single)
        var selectFilters = ['warehouse_id[]', 'gate[]', 'direction[]', 'late[]', 'status[]', 'blocking[]', 'target_status[]'];
        selectFilters.forEach(function(filterName) {
            var selects = filterForm.querySelectorAll('select[name="' + filterName + '"]');
            selects.forEach(function(select) {
                if (select.value && select.value !== '') {
                    // Map filter names to trigger data-filter attributes
                    var triggerName = filterName.replace('[]', '').replace('_id', '');
                    if (triggerName === 'warehouse') triggerName = 'whgate';
                    if (triggerName === 'gate') triggerName = 'whgate';
                    if (!activeFilters.includes(triggerName)) {
                        activeFilters.push(triggerName);
                    }
                }
            });
        });

        // Date range filters
        var dateFilters = ['date_from', 'date_to', 'arrival_from', 'arrival_to'];
        dateFilters.forEach(function(filterName) {
            var input = filterForm.querySelector('input[name="' + filterName + '"]');
            if (input && input.value && input.value.trim() !== '') {
                var triggerName = filterName.includes('arrival') ? 'arrival_presence' : 'planned_start';
                if (!activeFilters.includes(triggerName)) {
                    activeFilters.push(triggerName);
                }
            }
        });

        // Number range filters
        var numberFilters = ['lead_time_min', 'lead_time_max'];
        numberFilters.forEach(function(filterName) {
            var input = filterForm.querySelector('input[name="' + filterName + '"]');
            if (input && input.value && input.value.trim() !== '') {
                if (!activeFilters.includes('lead_time')) {
                    activeFilters.push('lead_time');
                }
            }
        });

        // Apply active indicators
        activeFilters.forEach(function(filterName) {
            var activeFilterBtn = document.querySelector('.st-filter-trigger[data-filter="' + filterName + '"]');
            if (activeFilterBtn) {
                activeFilterBtn.classList.add('st-filter-trigger--active');
                activeFilterBtn.classList.add('is-filtered');
            }
        });
    }

    function setupSorting() {
        var sortInput = filterForm.querySelector('input[name="sort"]');
        var dirInput = filterForm.querySelector('input[name="dir"]');
        if (!sortInput || !dirInput) return;

        var currentSort = String(sortInput.value || '');
        var currentDir = String(dirInput.value || 'desc');

        // Clear all active indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-sort', 'st-filter-header--active-sort');
        });
        document.querySelectorAll('.st-sort-trigger').forEach(function(btn) {
            btn.classList.remove('st-sort-trigger--active');
        });

        // Set active indicator for current sort
        if (currentSort) {
            var activeSortBtn = document.querySelector('.st-sort-trigger[data-sort="' + currentSort + '"]');
            if (activeSortBtn) {
                activeSortBtn.classList.add('st-sort-trigger--active');
            }
        }

        document.querySelectorAll('.st-sort-trigger').forEach(function (btn) {
            if (!btn || btn.getAttribute('data-sort-bound') === '1') return;
            btn.setAttribute('data-sort-bound', '1');

            var key = String(btn.getAttribute('data-sort') || '');
            if (!key) return;

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var nowSort = String(sortInput.value || '');
                var nowDir = String(dirInput.value || 'desc');
                var nextDir = 'asc';
                var nextSort = key;

                // 3-step toggle: asc -> desc -> off
                if (nowSort === key) {
                    if (nowDir === 'asc') {
                        nextDir = 'desc';
                    } else {
                        nextSort = '';
                        nextDir = '';
                    }
                } else {
                    nextDir = (key === 'lead_time') ? 'desc' : 'asc';
                }

                sortInput.value = nextSort;
                dirInput.value = nextDir;
                ajaxReload(true);
            });
        });
    }

    // Ensure form is synced with URL parameters on load (clears browser-restored values for hidden inputs)
    syncFormFromUrl();

    setupDropdownFilter('status', 260);
    setupDropdownFilter('direction', 220);
    setupDropdownFilter('late', 220);
    setupDropdownFilter('blocking', 240);
    setupDropdownFilter('whgate', 320);
    setupDropdownFilter('planned_start', 320);
    setupDropdownFilter('truck', 260);
    setupDropdownFilter('mat_doc', 260);
    setupDropdownFilter('vendor', 260);
    setupDropdownFilter('arrival_presence', 280);
    setupDropdownFilter('lead_time', 280);
    setupDropdownFilter('target_status', 240);
    setupActiveFilters(); // Initialize active filter indicators
    setupSorting();

    // Filter gates based on warehouse selection
    function filterGateOptions() {
        var warehouseSelects = document.querySelectorAll('select[name="warehouse_id[]"]');
        var gateSelect = document.querySelector('select[name="gate[]"]');
        if (!warehouseSelects || !gateSelect) return;

        // Get selected warehouse IDs
        var selectedWarehouses = [];
        warehouseSelects.forEach(function(select) {
            if (select.value) {
                selectedWarehouses.push(select.value);
            }
        });

        // Filter gate options
        var options = gateSelect.querySelectorAll('option[data-warehouse-id]');
        options.forEach(function(option) {
            var warehouseId = option.getAttribute('data-warehouse-id');
            if (selectedWarehouses.length === 0 || selectedWarehouses.includes(warehouseId)) {
                option.hidden = false;
            } else {
                option.hidden = true;
                if (option.selected) {
                    option.selected = false;
                }
            }
        });
    }

    // Bind warehouse filter change event
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'warehouse_id[]') {
            filterGateOptions();
        }
    });

    // Initial filter
    filterGateOptions();

    document.addEventListener('click', function (e) {
        var isTrigger = e.target && e.target.closest ? e.target.closest('.st-filter-trigger') : null;
        var isPanel = e.target && e.target.closest ? e.target.closest('.st-filter-panel') : null;
        if (!isTrigger && !isPanel) {
            closeOpenPanel();
        }
    });

    bindCancelConfirm();

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxReload(true);
    });

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t) return;
        if (t.matches('select[form="slot-filter-form"], input[type="date"][form="slot-filter-form"]')) {
            ajaxReload(true);
            return;
        }
        if (t.closest && t.closest('#slot-filter-form') && t.matches('select')) {
            ajaxReload(true);
            return;
        }
    });

    window.addEventListener('popstate', function () {
        syncFormFromUrl();
        ajaxReload(false);
    });

    // Global search suggestions (no auto-submit)
    var searchInput = document.querySelector('input[name="q"][form="slot-filter-form"]');
    var suggestionBox = document.getElementById('slot-search-suggestions');
    if (searchInput && suggestionBox) {
        function hideSuggestions() {
            suggestionBox.style.display = 'none';
            suggestionBox.innerHTML = '';
        }

        var searchDebounceTimer = null;
        function queueSearchReload() {
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }
            searchDebounceTimer = setTimeout(function () {
                ajaxReload(true);
            }, 500);
        }

        window.selectSuggestion = function (text) {
            searchInput.value = text;
            hideSuggestions();
            ajaxReload(true);
        };

        searchInput.addEventListener('input', function () {
            var raw = (searchInput.value || '');
            var value = raw.trim();

            if (value.length === 0) {
                hideSuggestions();
                queueSearchReload();
                return;
            }

            queueSearchReload();

            fetch(suggestUrl + '?q=' + encodeURIComponent(value), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.length > 0) {
                        var html = '';
                        data.forEach(function (item) {
                            var t = (item && item.text) ? item.text : '';
                            var h = (item && item.highlighted) ? item.highlighted : t;
                            html += '<div class="st-suggestion-item" onclick="selectSuggestion(\'' + String(t).replace(/'/g, "\\'") + '\')">' + h + '</div>';
                        });
                        suggestionBox.innerHTML = html;
                        suggestionBox.style.display = 'block';
                    } else {
                        hideSuggestions();
                    }
                })
                .catch(function () {
                    hideSuggestions();
                });
        });

        document.addEventListener('click', function (e) {
            var inBox = e.target && e.target.closest ? e.target.closest('#slot-search-suggestions') : null;
            var inInput = e.target === searchInput;
            if (!inBox && !inInput) {
                hideSuggestions();
            }
        });
    }
});

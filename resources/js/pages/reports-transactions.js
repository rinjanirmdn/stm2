import { highlightSearchInTable } from '../utils/search-highlight.js';

document.addEventListener('DOMContentLoaded', function () {
    function readJsonScript(id, fallback) {
        var el = document.getElementById(id);
        if (!el) return fallback;
        try {
            return JSON.parse(el.textContent || 'null') ?? fallback;
        } catch (e) {
            return fallback;
        }
    }

    var config = readJsonScript('reports_transactions_config', {});
    var suggestUrl = config.suggestUrl || '';
    var baseUrl = config.baseUrl || window.location.pathname;
    var form = document.getElementById('transactions-filter-form');
    if (!form) return;

    var isLoading = false;
    var excelLink = document.getElementById('transactions-excel-link');
    var openPanel = null;
    var tableBody = form.querySelector('tbody');

    function setLoading(loading) {
        isLoading = loading;
        if (tableBody) {
            tableBody.style.opacity = loading ? '0.5' : '';
        }
    }

    function toIsoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function applyDatepickerTooltips(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);

        dp.find('td.is-holiday').each(function() {
            const cell = window.jQuery(this);
            const dayText = cell.find('a, span').first().text();
            if (!dayText) return;
            const fallbackYear = inst.drawYear ?? inst.selectedYear;
            const fallbackMonth = inst.drawMonth ?? inst.selectedMonth;
            const year = cell.data('year') ?? fallbackYear;
            const month = cell.data('month') ?? fallbackMonth;
            if (year === undefined || month === undefined) return;
            const ds = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayText).padStart(2, '0')}`;
            const title = holidayData[ds] || '';
            if (title) {
                cell.attr('data-st-tooltip', title);
                cell.find('a, span').attr('data-st-tooltip', title);
            }
            cell.removeAttr('title');
            cell.find('a, span').removeAttr('title');
        });
    }

    function bindDatepickerHover(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);
        let hideTimer = null;
        let tooltip = document.getElementById('st-datepicker-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'st-datepicker-tooltip';
            tooltip.className = 'st-datepicker-tooltip';
            document.body.appendChild(tooltip);
        }

        dp.off('mouseenter.st-tooltip mousemove.st-tooltip mouseleave.st-tooltip', 'td.is-holiday');
        dp.on('mouseenter.st-tooltip', 'td.is-holiday', function(event) {
            const text = window.jQuery(this).attr('data-st-tooltip') || '';
            if (!text) return;
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
            tooltip.textContent = text;
            tooltip.classList.add('st-datepicker-tooltip--visible');
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mousemove.st-tooltip', 'td.is-holiday', function(event) {
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mouseleave.st-tooltip', 'td.is-holiday', function() {
            hideTimer = setTimeout(function() {
                tooltip.classList.remove('st-datepicker-tooltip--visible');
            }, 300);
        });
    }

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

        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            appendControlToParams(params, el);
        });

        var formId = String(form.getAttribute('id') || '').trim();
        if (formId) {
            document.querySelectorAll('[form="' + CSS.escape(formId) + '"]').forEach(function (el) {
                appendControlToParams(params, el);
            });
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

    function updateExcelLink() {
        if (!excelLink) return;
        var qs = buildQueryStringFromForm();
        var params = new URLSearchParams(qs);
        params.set('export', 'excel');
        params.set('page_size', 'all');
        excelLink.setAttribute('data-export-url', window.location.pathname + '?' + params.toString());
    }

    /**
     * Two-step download: ask server to generate the file (returns JSON with URL),
     * then redirect browser to the static file URL so Apache serves it directly.
     */
    function staticDownload(url, btn) {
        var originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin st-mr-2"></i> Generating...';
        }
        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Export failed: ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data && data.download_url) {
                // Navigate to the static file — Apache serves it with correct filename
                window.location.href = data.download_url;
            } else {
                throw new Error('No download URL in response');
            }
        })
        .catch(function(err) {
            console.error('Export error:', err);
            alert('Export gagal: ' + err.message);
        })
        .finally(function() {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    function syncFormFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var controls = Array.prototype.slice.call(form.elements);
        var formId = String(form.getAttribute('id') || '').trim();
        if (formId) {
            controls = controls.concat(Array.prototype.slice.call(document.querySelectorAll('[form="' + CSS.escape(formId) + '"]')));
        }
        controls.forEach(function (el) {
            if (!el || !el.name) return;
            var name = el.name;
            var values = params.getAll(name);
            if (!values || values.length === 0) {
                if (name === 'page_size') {
                    el.value = 'all';
                } else {
                    el.value = '';
                }
                return;
            }
            el.value = values[0];
        });
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
                var newForm = doc.getElementById('transactions-filter-form');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tableBody && newTbody) {
                    tableBody.innerHTML = newTbody.innerHTML;
                }
                updateExcelLink();
                if (pushState) {
                    window.history.pushState(null, '', url);
                }

                // Apply search highlight after content loaded
                var searchEl = document.querySelector('input[name="q"]');
                var term = searchEl ? searchEl.value.trim() : '';
                highlightSearchInTable(tableBody, term);

                // Upgrade Icons and Sync Active States (for header icons after reload)
                try {
                    if (typeof stUpgradeTableHeaderIcons === 'function') {
                        stUpgradeTableHeaderIcons(document);
                    }
                    if (typeof stSyncActiveStateClasses === 'function') {
                        stSyncActiveStateClasses(document);
                    }
                } catch (e) {
                    console.error('State sync failed:', e);
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

    window.ajaxReload = ajaxReload;

    function applyLocalFilter(term) {
        if (!tableBody) return;
        var q = (term || '').toLowerCase().trim();
        var rows = tableBody.querySelectorAll('tr');

        if (!q) {
            rows.forEach(function (tr) { tr.style.display = ''; });
            return;
        }

        rows.forEach(function (tr) {
            var text = (tr.textContent || '').toLowerCase();
            tr.style.display = text.indexOf(q) !== -1 ? '' : 'none';
        });
    }

    function closeOpenPanel() {
        if (openPanel) {
            openPanel.style.display = 'none';
            openPanel = null;
        }
    }

    document.addEventListener('click', function () {
        closeOpenPanel();
    });

    // NOTE: Filter panel toggle/clear/indicator handled globally in resources/js/pages/main.js
    // Mark panels as fixed-position so they will be positioned above sticky table headers.
    try {
        var formForPanels = document.getElementById('transactions-filter-form');
        if (formForPanels) {
            formForPanels.querySelectorAll('.st-filter-panel').forEach(function (p) {
                if (!p) return;
                p.setAttribute('data-st-position', 'fixed');
            });
        }
    } catch (e) {}

    var searchInput = document.querySelector('input[name="q"]');
    var suggestionBox = document.getElementById('transaction-search-suggestions');


    if (searchInput && suggestionBox) {
        function hideSuggestions() {
            suggestionBox.style.display = 'none';
            suggestionBox.innerHTML = '';
        }

        document.addEventListener('keydown', function (e) {
            var isCtrlF = (e.ctrlKey || e.metaKey) && (e.key === 'f' || e.key === 'F');
            if (isCtrlF) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });

        window.selectSuggestion = function (text) {
            searchInput.value = text;
            applyLocalFilter(text);
            hideSuggestions();
            ajaxReload(true);
        };

        var searchDebounceTimer = null;
        function queueSearchReload() {
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }
            searchDebounceTimer = setTimeout(function () {
                ajaxReload(true);
            }, 500);
        }

        searchInput.addEventListener('input', function () {
            var value = searchInput.value || '';
            applyLocalFilter(value);
            var trimmed = value.trim();

            if (trimmed.length === 0) {
                hideSuggestions();
                queueSearchReload();
                return;
            }

            queueSearchReload();

            fetch(suggestUrl + '?q=' + encodeURIComponent(trimmed), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.length > 0) {
                        var html = '';
                        data.forEach(function (item) {
                            html += '<div class="st-suggestion-item--compact" onclick="selectSuggestion(\'' + String(item.text || '').replace(/'/g, "\\'") + '\')">' + (item.highlighted || item.text || '') + '</div>';
                        });
                        suggestionBox.innerHTML = html;
                        suggestionBox.style.display = 'block';
                    } else {
                        suggestionBox.innerHTML = '<div class="st-suggestion-empty">No suggestions for "' + trimmed.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '"</div>';
                        suggestionBox.style.display = 'block';
                    }
                })
                .catch(function () {
                    suggestionBox.innerHTML = '<div class="st-suggestion-empty">Error loading suggestions</div>';
                    suggestionBox.style.display = 'block';
                });
        });

        document.addEventListener('click', function (e) {
            var isInside = e.target === searchInput || (e.target.closest && e.target.closest('#transaction-search-suggestions'));
            if (!isInside) {
                hideSuggestions();
            }
        });
    }

    updateExcelLink();

    // Export Excel button click handler
    if (excelLink) {
        excelLink.addEventListener('click', function(e) {
            e.preventDefault();
            var url = excelLink.getAttribute('data-export-url');
            if (!url) return;
            staticDownload(url, excelLink);
        });
    }

    // Download Template button click handler
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('#btn-download-template');
        if (!btn) return;
        e.preventDefault();
        var url = btn.getAttribute('data-export-url');
        staticDownload(url, btn);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxReload(true);
    });

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t) return;
        // Auto-apply on select change
        if (t.matches('select[form="transactions-filter-form"]')) {
            ajaxReload(true);
            return;
        }
        if (t.matches('input[name="page_size"][form="transactions-filter-form"]')) {
            ajaxReload(true);
            return;
        }
    });

    // Add Enter key support for text inputs
    document.addEventListener('keydown', function (e) {
        var t = e.target;
        if (!t) return;
        if (e.key === 'Enter' && t.matches('input[type="text"][form="transactions-filter-form"], input[type="number"][form="transactions-filter-form"]')) {
            e.preventDefault();
            ajaxReload(true);
            return;
        }
    });

    window.addEventListener('popstate', function () {
        syncFormFromUrl();
        ajaxReload(false);
    });

    // Date range logic
    var dateRangeInput = document.getElementById('date_range');
    var arrivalDateRangeInput = document.getElementById('arrival_reportrange');
    var dateFromInput = document.getElementById('date_from');
    var dateToInput = document.getElementById('date_to');
    var arrivalDateFromInput = document.getElementById('arrival_from');
    var arrivalDateToInput = document.getElementById('arrival_to');
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    function formatDateDisplay(dateStr) {
        if (!dateStr) return '';
        try {
            var date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            var day = String(date.getDate()).padStart(2, '0');
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var year = date.getFullYear();
            return day + "-" + month + "-" + year;
        } catch (e) {
            return dateStr;
        }
    }

    function dmyToIso(value) {
        if (!value) return '';
        var parts = String(value).split('-');
        if (parts.length !== 3) return value;
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }

    function dateClassForMoment(m) {
        if (!m) return '';
        var classes = [];
        var iso = m.format('YYYY-MM-DD');
        if (m.day() === 0) classes.push('drp-sunday');
        if (holidayData && holidayData[iso]) classes.push('drp-holiday');
        return classes.join(' ');
    }

    function resolvePickerCellIso(cellEl) {
        if (!cellEl || !window.jQuery || typeof window.moment !== 'function') return '';
        var cell = window.jQuery(cellEl);
        var day = parseInt(cell.text().trim(), 10);
        if (!isFinite(day)) return '';
        var monthText = cell.closest('table').find('.month').first().text().trim();
        if (!monthText) return '';

        var baseMonth = window.moment(monthText, ['MMM YYYY', 'MMMM YYYY'], true);
        if (!baseMonth.isValid()) baseMonth = window.moment(monthText, ['MMM YYYY', 'MMMM YYYY'], false);
        if (!baseMonth.isValid()) return '';

        var month = baseMonth.month();
        var year = baseMonth.year();
        if (cell.hasClass('off')) {
            if (day >= 20) month -= 1;
            else month += 1;
            if (month < 0) { month = 11; year -= 1; }
            if (month > 11) { month = 0; year += 1; }
        }
        return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    }

    function decoratePickerDays(picker) {
        if (!picker || !picker.container || !window.jQuery) return;
        picker.container.find('td.available').each(function () {
            var td = window.jQuery(this);
            var hasSunday = td.hasClass('drp-sunday');
            var hasHoliday = td.hasClass('drp-holiday');
            var tooltipText = '';

            if (hasHoliday) {
                var iso = resolvePickerCellIso(this);
                tooltipText = (iso && holidayData && holidayData[iso]) ? holidayData[iso] : 'Holiday';
            } else if (hasSunday) {
                tooltipText = 'Sunday';
            }

            if (tooltipText) {
                td.attr('data-tooltip', tooltipText);
                td.find('a, span').attr('data-tooltip', tooltipText);
            } else {
                td.removeAttr('data-tooltip');
                td.find('a, span').removeAttr('data-tooltip');
            }
        });
    }

    function bindPickerDecorators($el) {
        if (!$el || !$el.length) return;
        $el.off('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo');
        $el.on('show.daterangepicker.st-dateinfo showCalendar.daterangepicker.st-dateinfo', function (_ev, picker) {
            decoratePickerDays(picker);
        });
        var picker = $el.data('daterangepicker');
        if (picker) {
            setTimeout(function () { decoratePickerDays(picker); }, 0);
        }
    }

    if (dateRangeInput && dateFromInput && dateToInput && window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.daterangepicker === 'function' && typeof window.moment === 'function') {
        var $ = window.jQuery;
        var moment = window.moment;
        var initial = dateFromInput.value || '';
        var start = initial && moment(initial, 'YYYY-MM-DD').isValid() ? moment(initial, 'YYYY-MM-DD') : moment();
        if (initial) {
            dateRangeInput.value = formatDateDisplay(initial);
        }

        var $scheduledPicker = $(dateRangeInput);
        $scheduledPicker.daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoApply: true,
            startDate: start,
            minYear: 1901,
            maxYear: parseInt(moment().format('YYYY'), 10) + 5,
            locale: { format: 'DD-MM-YYYY' },
            isCustomDate: dateClassForMoment
        }, function(startDate) {
            var value = startDate.format('DD-MM-YYYY');
            var iso = startDate.format('YYYY-MM-DD');
            dateFromInput.value = iso;
            dateToInput.value = iso;
            dateRangeInput.value = value;
            ajaxReload(true);
        });

        bindPickerDecorators($scheduledPicker);
    }

    // Predefined Arrival Range Hook
    if (arrivalDateRangeInput && window.jQuery) {
        window.jQuery(arrivalDateRangeInput).on('apply.daterangepicker', function() {
            if (typeof window.ajaxReload === 'function') {
                window.ajaxReload(true);
            }
        });
    }

    // Global Transactions Range Hook
    var globalRangeInput = document.getElementById('transaction_reportrange');
    if (globalRangeInput && window.jQuery) {
        window.jQuery(globalRangeInput).on('apply.daterangepicker', function() {
            if (typeof window.ajaxReload === 'function') {
                window.ajaxReload(true);
            }
        });
    }

    // Handle individual filter clearing related to date ranges
    document.addEventListener('click', function(e) {
        var clearBtn = e.target.closest('.st-filter-clear');
        if (!clearBtn) return;
        var filter = clearBtn.getAttribute('data-filter');

        if (filter === 'arrival_presence' && arrivalDateRangeInput) {
            var label = arrivalDateRangeInput.querySelector('span');
            if (label) label.textContent = 'Select range';
        }
    }, true);

    // Reset all filters button
    document.getElementById('clear-date-range').addEventListener('click', function() {
        // Clear all active filter indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-filter', 'st-filter-header--active-filter');
        });
        document.querySelectorAll('.st-filter-trigger').forEach(function(btn) {
            btn.classList.remove('st-filter-trigger--active');
        });

        // Clear all active sort indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-sort', 'st-filter-header--active-sort');
        });
        document.querySelectorAll('.st-sort-trigger').forEach(function(btn) {
            btn.classList.remove('st-sort-trigger--active');
        });

        // Redirect to clean page with all filters reset
        window.location.href = baseUrl;
    });

    // Close panels when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.st-colhead') && !e.target.closest('.st-filter-header')) {
            document.querySelectorAll('.st-filter-panel').forEach(p => p.style.display = 'none');
            document.querySelectorAll('.st-sort-panel').forEach(p => p.style.display = 'none');
        }
    });

    // Initial highlight on page load
    var searchInputHL = document.querySelector('input[name="q"]');
    if (searchInputHL && searchInputHL.value.trim().length >= 2) {
        highlightSearchInTable(tableBody, searchInputHL.value.trim());
    }

    // Offline Import Logic
    var btnImportOffline = document.getElementById('btn-import-offline');
    var modalImportOffline = document.getElementById('modal-import-offline');
    var btnImportClose = document.getElementById('modal-import-close');
    var btnImportCancel = document.getElementById('btn-import-cancel');
    var formImportOffline = document.getElementById('form-import-offline');
    var btnImportSubmit = document.getElementById('btn-import-submit');
    var alertImportOffline = document.getElementById('import-offline-alert');

    function openModalImport() {
        var modal = document.getElementById('modal-import-offline');
        if (!modal) return;
        modal.style.display = 'flex';
        var alert = document.getElementById('import-offline-alert');
        if (alert) {
            alert.classList.add('st-hidden');
            alert.innerHTML = '';
        }
        var form = document.getElementById('form-import-offline');
        if (form) form.reset();
    }

    function closeModalImport() {
        var modal = document.getElementById('modal-import-offline');
        if (!modal) return;
        modal.style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('#btn-import-offline')) {
            openModalImport();
        } else if (e.target.closest('#modal-import-close') || e.target.closest('#btn-import-cancel')) {
            closeModalImport();
        }
    });

    if (formImportOffline) {
        formImportOffline.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(formImportOffline);
            var token = formImportOffline.querySelector('input[name="_token"]').value;
            
            btnImportSubmit.disabled = true;
            btnImportSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin st-mr-2"></i> Uploading...';
            if (alertImportOffline) {
                alertImportOffline.classList.add('st-hidden');
            }

            fetch('/reports/offline-import/upload', {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                if (!res.ok) {
                    return res.json().then(function(err) { throw err; });
                }
                return res.json();
            })
            .then(function(data) {
                if (data.success) {
                    if (alertImportOffline) {
                        alertImportOffline.className = 'st-alert st-alert--success st-mt-4';
                        alertImportOffline.innerHTML = '<i class="fa-solid fa-check-circle st-mr-2"></i> ' + data.message;
                        alertImportOffline.classList.remove('st-hidden');
                    }
                    setTimeout(function() {
                        closeModalImport();
                        window.location.reload();
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Error occurred');
                }
            })
            .catch(function(err) {
                if (alertImportOffline) {
                    var msg = err.message || (err.errors ? JSON.stringify(err.errors) : 'Gagal upload data');
                    alertImportOffline.className = 'st-alert st-alert--danger st-mt-4';
                    alertImportOffline.innerHTML = '<i class="fa-solid fa-exclamation-circle st-mr-2"></i> ' + msg;
                    alertImportOffline.classList.remove('st-hidden');
                }
            })
            .finally(function() {
                btnImportSubmit.disabled = false;
                btnImportSubmit.innerHTML = 'Upload & Import';
            });
        });
    }

});

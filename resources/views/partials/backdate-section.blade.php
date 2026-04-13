{{-- Backdate Section - Only visible for Admin and Section Head --}}
@php
    $__bdUser = auth()->user();
    $__bdAllowed = false;
    $__bdRoleName = '';
    if ($__bdUser) {
        // Primary: Spatie hasRole
        if (method_exists($__bdUser, 'hasRole') && $__bdUser->hasRole(['Admin', 'Section Head'])) {
            $__bdAllowed = true;
            $__bdRoleName = $__bdUser->hasRole('Admin') ? 'Admin' : 'Section Head';
        }
        // Fallback: role_id column
        if (!$__bdAllowed && $__bdUser->role_id) {
            $__bdRoleName = \Illuminate\Support\Facades\DB::table('md_roles')->where('id', $__bdUser->role_id)->value('roles_name') ?? '';
            if (in_array($__bdRoleName, ['Admin', 'Section Head'])) {
                $__bdAllowed = true;
            }
        }
    }
@endphp
@if($__bdAllowed)
<div class="st-border st-rounded-10 st-p-16 st-mb-16 st-backdate-section" style="border-color: #f59e0b; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
    <div class="st-flex st-align-center st-gap-8 st-mb-12">
        <div class="st-flex st-align-center st-justify-center" style="width:28px;height:28px;border-radius:50%;background:#f59e0b;color:#fff;font-size:14px;">
            <i class="fas fa-history"></i>
        </div>
        <div class="st-font-semibold st-text-14" style="color:#92400e;">
            <i class="fas fa-bolt st-mr-4" style="color:#f59e0b;"></i> Backdate
            <span class="st-text--xs st-font-normal" style="color:#a16207;">({{ $__bdRoleName }})</span>
        </div>
    </div>

    <div class="st-flex st-align-center st-gap-8 st-mb-10">
        <label class="st-flex st-align-center st-gap-8 st-cursor-pointer" style="user-select:none;">
            <input type="checkbox" id="backdate_toggle" name="use_backdate" value="1" class="st-checkbox"
                   {{ old('use_backdate') ? 'checked' : '' }}
                   onchange="document.getElementById('backdate_fields').style.display = this.checked ? 'block' : 'none'; if(!this.checked) document.getElementById('backdate_datetime').value = '';">
            <span class="st-text--sm st-font-semibold" style="color:#92400e;">Use Backdate</span>
        </label>
    </div>

    <div id="backdate_fields" style="display: {{ old('use_backdate') ? 'block' : 'none' }};">
        <div class="st-flex st-align-center st-gap-6 st-mb-10 st-p-8 st-rounded-6" style="background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.3);">
            <i class="fas fa-exclamation-triangle" style="color:#d97706;font-size:12px;"></i>
            <span class="st-text--xs" style="color:#92400e;">Backdate time must be in the <strong>past</strong>. Future dates will be rejected.</span>
        </div>

        <div class="st-form-field">
            <label class="st-label st-text--sm" style="color:#92400e;">
                <i class="far fa-calendar-alt st-mr-4"></i> Date & Time
            </label>
            <input type="hidden" name="backdate_datetime" id="backdate_datetime" value="{{ old('backdate_datetime') }}">
            <div class="st-flex st-gap-8" style="max-width:320px;">
                <input type="text" id="backdate_date_input" class="st-input" placeholder="Select Date" autocomplete="off" style="border-color:#f59e0b;">
                <input type="text" id="backdate_time_input" class="st-input" placeholder="Select Time" autocomplete="off" inputmode="none" readonly style="border-color:#f59e0b;">
            </div>
            
            <div id="backdate_error" class="st-text--xs st-mt-4" style="color:#dc2626; display:none;">
                <i class="fas fa-times-circle"></i> <span></span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function initBackdateSection() {
        var toggle = document.getElementById('backdate_toggle');
        var dtInput = document.getElementById('backdate_datetime');
        var errorDiv = document.getElementById('backdate_error');
        if (!toggle || !dtInput) return;

        var $dateInput = $('#backdate_date_input');
        var $timeInput = $('#backdate_time_input');
        
        // Parse existing value if any
        if (dtInput.value) {
            var m = moment(dtInput.value, 'YYYY-MM-DDTHH:mm');
            if (m.isValid()) {
                $dateInput.val(m.format('DD-MM-YYYY'));
                $timeInput.val(m.format('HH:mm'));
            } else {
                // Fallback parsing for other formats
                var d = new Date(dtInput.value);
                if (!isNaN(d.getTime())) {
                    $dateInput.val(moment(d).format('DD-MM-YYYY'));
                    $timeInput.val(moment(d).format('HH:mm'));
                }
            }
        }

        // Initialize daterangepicker if jQuery is available
        if ($dateInput.length && typeof $.fn.daterangepicker === 'function') {
            var drpOptions = {
                singleDatePicker: true,
                autoUpdateInput: true,
                autoApply: true,
                maxDate: moment(),
                locale: { format: 'DD-MM-YYYY' }
            };
            if ($dateInput.val()) {
                drpOptions.startDate = moment($dateInput.val(), 'DD-MM-YYYY');
            }
            // Destroy any existing attached instance first
            if ($dateInput.data('daterangepicker')) {
                $dateInput.data('daterangepicker').remove();
            }
            $dateInput.daterangepicker(drpOptions);
            $dateInput.on('apply.daterangepicker change', function(ev, picker) {
                if (picker && picker.startDate) $(this).val(picker.startDate.format('DD-MM-YYYY'));
                updateHidden();
            });
        }

        // Initialize mdtimepicker
        if ($timeInput.length) {
            $timeInput.off('timechanged change');
            if (typeof window.mdtimepicker === 'function') {
                window.mdtimepicker($timeInput[0], {
                    format: 'hh:mm',
                    is24hour: true,
                    theme: 'cyan',
                    hourPadding: true
                });
            }
            $timeInput.on('timechanged change', function(e) {
                if (e.type === 'timechanged' && e.time) {
                    $(this).val(e.time);
                }
                updateHidden();
            });
        }
        
        // Remove old change listener (native)
        var $dtInput = $(dtInput);
        $dtInput.off('change.backdate');
        
        function validateBackdateValue(val) {
            if (!val) {
                errorDiv.style.display = 'none';
                return true;
            }
            var selected = new Date(val);
            var now = new Date();
            if (selected >= now) {
                errorDiv.querySelector('span').textContent = 'Backdate must be in the past, not future.';
                errorDiv.style.display = 'block';
                $dateInput[0].style.borderColor = '#dc2626';
                $timeInput[0].style.borderColor = '#dc2626';
                return false;
            } else {
                errorDiv.style.display = 'none';
                $dateInput[0].style.borderColor = '#f59e0b';
                $timeInput[0].style.borderColor = '#f59e0b';
                return true;
            }
        }
        
        $dtInput.on('change.backdate', function() {
            validateBackdateValue(this.value);
        });

        function updateHidden() {
            var dVal = $dateInput.val();
            var tVal = $timeInput.val();
            if (dVal && tVal) {
                var m = moment(dVal + ' ' + tVal, 'DD-MM-YYYY HH:mm');
                if (m.isValid()) {
                    var isoVal = m.format('YYYY-MM-DDTHH:mm');
                    dtInput.value = isoVal;
                    $dtInput.trigger('change.backdate');
                }
            } else {
                dtInput.value = '';
                $dtInput.trigger('change.backdate');
            }
        }

        // Validate on form submit
        var form = dtInput.closest('form');
        if (form) {
            form.removeEventListener('submit', window._bdSubmitHandler);
            
            window._bdSubmitHandler = function(e) {
                if (toggle.checked && dtInput.value) {
                    if (!validateBackdateValue(dtInput.value)) {
                        e.preventDefault();
                    }
                }
                if (toggle.checked && (!dtInput.value || !$dateInput.val() || !$timeInput.val())) {
                    e.preventDefault();
                    errorDiv.querySelector('span').textContent = 'Please select both backdate Date and Time.';
                    errorDiv.style.display = 'block';
                    $dateInput[0].style.borderColor = '#dc2626';
                    $timeInput[0].style.borderColor = '#dc2626';
                }
            };
            
            form.addEventListener('submit', window._bdSubmitHandler);
        }
    }

    // Run init immediately
    if (window.jQuery) {
        initBackdateSection();
    } else {
        document.addEventListener('DOMContentLoaded', initBackdateSection);
    }
})();
</script>
@endif

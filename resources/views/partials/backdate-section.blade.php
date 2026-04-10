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
            <input
                type="datetime-local"
                id="backdate_datetime"
                name="backdate_datetime"
                class="st-input"
                value="{{ old('backdate_datetime') }}"
                style="border-color:#f59e0b; max-width:320px;"
                max="{{ now()->format('Y-m-d\TH:i') }}"
            >
            <div id="backdate_error" class="st-text--xs st-mt-4" style="color:#dc2626; display:none;">
                <i class="fas fa-times-circle"></i> <span></span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var toggle = document.getElementById('backdate_toggle');
        var dtInput = document.getElementById('backdate_datetime');
        var errorDiv = document.getElementById('backdate_error');
        if (!toggle || !dtInput) return;

        // Set max to current time on page load
        function updateMax() {
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            dtInput.max = now.toISOString().slice(0, 16);
        }
        updateMax();
        setInterval(updateMax, 30000);

        // Validate on change
        dtInput.addEventListener('change', function() {
            if (!this.value) {
                errorDiv.style.display = 'none';
                return;
            }
            var selected = new Date(this.value);
            var now = new Date();
            if (selected >= now) {
                errorDiv.querySelector('span').textContent = 'Backdate must be in the past, not future.';
                errorDiv.style.display = 'block';
                this.style.borderColor = '#dc2626';
            } else {
                errorDiv.style.display = 'none';
                this.style.borderColor = '#f59e0b';
            }
        });

        // Validate on form submit
        var form = dtInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (toggle.checked && dtInput.value) {
                    var selected = new Date(dtInput.value);
                    var now = new Date();
                    if (selected >= now) {
                        e.preventDefault();
                        errorDiv.querySelector('span').textContent = 'Cannot submit: backdate must be in the past.';
                        errorDiv.style.display = 'block';
                        dtInput.style.borderColor = '#dc2626';
                        dtInput.focus();
                    }
                }
                if (toggle.checked && !dtInput.value) {
                    e.preventDefault();
                    errorDiv.querySelector('span').textContent = 'Please select a backdate time.';
                    errorDiv.style.display = 'block';
                    dtInput.style.borderColor = '#dc2626';
                    dtInput.focus();
                }
            });
        }
    });
})();
</script>
@endif

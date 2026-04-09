{{-- Backdate section - Admin & Section Head only --}}
@if(auth()->user() && auth()->user()->hasAnyRole(['Admin', 'Section Head']))
<div class="st-backdate-section st-mb-16">
    <div class="st-backdate-toggle">
        <label class="st-flex st-align-center st-gap-10 st-cursor-pointer">
            <input type="checkbox" id="backdate_toggle" class="st-checkbox" {{ old('backdate_datetime') ? 'checked' : '' }}>
            <span class="st-flex st-align-center st-gap-8">
                <i class="fas fa-history st-text--warning"></i>
                <span class="st-font-semibold st-text--sm">Use Backdate</span>
                <span class="st-badge st-badge--warning st-text--xs">Admin Only</span>
            </span>
        </label>
    </div>
    <div id="backdate_fields" class="st-backdate-fields" style="display: {{ old('backdate_datetime') ? 'block' : 'none' }};">
        <div class="st-alert st-alert--warning st-mb-12 st-mt-12">
            <span class="st-alert__icon"><i class="fa-solid fa-exclamation-triangle"></i></span>
            <span class="st-alert__text st-text--sm">
                <strong>Backdate Mode:</strong> The timestamp will be set to the date/time you specify below instead of the current time.
                Backdate time must be in the <strong>past</strong>.
            </span>
        </div>
        <div class="st-form-field">
            <label class="st-label">Backdate Date & Time <span class="st-text--danger-dark">*</span></label>
            <input
                type="datetime-local"
                name="backdate_datetime"
                id="backdate_datetime"
                class="st-input"
                value="{{ old('backdate_datetime') }}"
                max="{{ now()->format('Y-m-d\TH:i') }}"
            >
            <div id="backdate_error" class="st-text--small st-text--danger st-mt-4" style="display: none;"></div>
        </div>
    </div>
</div>

<style>
.st-backdate-section {
    border: 1.5px dashed var(--warning-border, #f59e0b);
    border-radius: 10px;
    padding: 16px 18px;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.04) 0%, rgba(245, 158, 11, 0.08) 100%);
    transition: all 0.25s ease;
}
.st-backdate-section:has(#backdate_toggle:checked) {
    border-color: #d97706;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.06) 0%, rgba(245, 158, 11, 0.12) 100%);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.10);
}
.st-backdate-toggle {
    user-select: none;
}
.st-backdate-fields {
    animation: st-backdate-slide 0.25s ease;
}
@keyframes st-backdate-slide {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}
.st-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #f59e0b;
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('backdate_toggle');
    var fields = document.getElementById('backdate_fields');
    var datetimeInput = document.getElementById('backdate_datetime');
    var errorDiv = document.getElementById('backdate_error');

    if (toggle && fields) {
        toggle.addEventListener('change', function() {
            fields.style.display = this.checked ? 'block' : 'none';
            if (!this.checked && datetimeInput) {
                datetimeInput.value = '';
                if (errorDiv) errorDiv.style.display = 'none';
            }
            if (this.checked && datetimeInput) {
                datetimeInput.setAttribute('required', 'required');
                // Set max to current time
                var now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                datetimeInput.max = now.toISOString().slice(0, 16);
            } else if (datetimeInput) {
                datetimeInput.removeAttribute('required');
            }
        });

        // Initial state
        if (toggle.checked && datetimeInput) {
            datetimeInput.setAttribute('required', 'required');
        }
    }

    if (datetimeInput && errorDiv) {
        datetimeInput.addEventListener('change', function() {
            var val = new Date(this.value);
            var now = new Date();
            if (val > now) {
                errorDiv.textContent = 'Backdate time must be in the past.';
                errorDiv.style.display = 'block';
                this.setCustomValidity('Backdate time must be in the past.');
            } else {
                errorDiv.style.display = 'none';
                this.setCustomValidity('');
            }
        });
    }
});
</script>
@endif

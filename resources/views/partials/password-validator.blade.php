{{-- Password Strength Validator Partial --}}
{{-- Usage: @include('partials.password-validator', ['passwordId' => 'password', 'confirmId' => 'password_confirmation', 'submitBtnSelector' => '#mySubmitBtn', 'isOptional' => false]) --}}
@php
    $pwdId = $passwordId ?? 'password';
    $cfmId = $confirmId ?? 'password_confirmation';
    $uid = 'pwv_' . str_replace(['-', '.'], '_', $pwdId);
    $btnSelector = $submitBtnSelector ?? '';
    $optional = $isOptional ?? false;
@endphp

<div id="{{ $uid }}_checklist" class="pwd-validator" style="display:none;">
    <div class="pwd-validator__title">
        <i class="fas fa-shield-halved"></i> Password Requirements
    </div>
    <div class="pwd-validator__rules">
        <div id="{{ $uid }}_len" class="pwd-validator__rule pwd-validator__rule--fail">
            <i class="fas fa-circle-xmark pwd-validator__ico"></i>
            Min. 8 characters
        </div>
        <div id="{{ $uid }}_upper" class="pwd-validator__rule pwd-validator__rule--fail">
            <i class="fas fa-circle-xmark pwd-validator__ico"></i>
            Uppercase (A-Z)
        </div>
        <div id="{{ $uid }}_lower" class="pwd-validator__rule pwd-validator__rule--fail">
            <i class="fas fa-circle-xmark pwd-validator__ico"></i>
            Lowercase (a-z)
        </div>
        <div id="{{ $uid }}_num" class="pwd-validator__rule pwd-validator__rule--fail">
            <i class="fas fa-circle-xmark pwd-validator__ico"></i>
            Number (0-9)
        </div>
        <div id="{{ $uid }}_sym" class="pwd-validator__rule pwd-validator__rule--fail">
            <i class="fas fa-circle-xmark pwd-validator__ico"></i>
            Symbol (!@#$% etc.)
        </div>
    </div>
</div>

<div id="{{ $uid }}_mismatch" class="pwd-validator pwd-validator--mismatch" style="display:none;">
    <i class="fas fa-triangle-exclamation"></i>
    Passwords do not match
</div>

<style>
    .pwd-validator {
        margin-top: 6px;
        margin-bottom: 4px;
        padding: 8px 10px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 0.78em;
    }
    .pwd-validator__title {
        font-weight: 600;
        color: #475569;
        margin-bottom: 5px;
        font-size: 0.95em;
    }
    .pwd-validator__title i {
        margin-right: 3px;
        color: #6366f1;
    }
    .pwd-validator__rules {
        display: flex;
        flex-wrap: wrap;
        gap: 3px 14px;
    }
    .pwd-validator__rule {
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        transition: color 0.2s;
    }
    .pwd-validator__rule--fail {
        color: #94a3b8;
    }
    .pwd-validator__rule--pass {
        color: #16a34a;
    }
    .pwd-validator__rule--pass .pwd-validator__ico {
        color: #16a34a;
    }
    .pwd-validator__rule--fail .pwd-validator__ico {
        color: #cbd5e1;
    }
    .pwd-validator__ico {
        font-size: 0.9em;
        flex-shrink: 0;
    }
    .pwd-validator--mismatch {
        background: #fef2f2;
        border-color: #fecaca;
        color: #dc2626;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }
    .pwd-validator--mismatch i {
        color: #ef4444;
    }
    /* Disabled submit button when password requirements not met */
    .st-btn--disabled,
    .st-btn--disabled:hover {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>

<script>
(function() {
    var pwdId = @json($pwdId);
    var cfmId = @json($cfmId);
    var uid = @json($uid);
    var btnSelector = @json($btnSelector);
    var isOptional = @json($optional);

    function el(id) { return document.getElementById(id); }

    function setRule(ruleId, pass) {
        var div = el(ruleId);
        if (!div) return;
        var icon = div.querySelector('.pwd-validator__ico');
        if (pass) {
            div.className = 'pwd-validator__rule pwd-validator__rule--pass';
            if (icon) { icon.classList.remove('fa-circle-xmark'); icon.classList.add('fa-circle-check'); }
        } else {
            div.className = 'pwd-validator__rule pwd-validator__rule--fail';
            if (icon) { icon.classList.remove('fa-circle-check'); icon.classList.add('fa-circle-xmark'); }
        }
    }

    function validate() {
        var pwd = el(pwdId);
        var cfm = el(cfmId);
        var checklist = el(uid + '_checklist');
        var mismatch = el(uid + '_mismatch');
        if (!pwd) return;

        var val = pwd.value || '';
        var hasContent = val.length > 0;

        // Show checklist when user starts typing
        if (checklist) checklist.style.display = hasContent ? 'block' : 'none';

        var lenOk = val.length >= 8;
        var upperOk = /[A-Z]/.test(val);
        var lowerOk = /[a-z]/.test(val);
        var numOk = /[0-9]/.test(val);
        var symOk = /[^A-Za-z0-9\s]/.test(val);

        setRule(uid + '_len', lenOk);
        setRule(uid + '_upper', upperOk);
        setRule(uid + '_lower', lowerOk);
        setRule(uid + '_num', numOk);
        setRule(uid + '_sym', symOk);

        // Confirm password mismatch
        var matchOk = true;
        if (cfm && mismatch) {
            var cfmVal = cfm.value || '';
            if (cfmVal.length > 0 && val !== cfmVal) {
                mismatch.style.display = 'flex';
                matchOk = false;
            } else {
                mismatch.style.display = 'none';
            }
        }

        // Disable/enable submit button
        if (btnSelector) {
            var btn = document.querySelector(btnSelector);
            if (btn) {
                var allPass = lenOk && upperOk && lowerOk && numOk && symOk && matchOk;
                if (isOptional && !hasContent) {
                    // Password is optional and empty — allow submit
                    btn.disabled = false;
                    btn.classList.remove('st-btn--disabled');
                } else if (hasContent) {
                    btn.disabled = !allPass;
                    if (!allPass) {
                        btn.classList.add('st-btn--disabled');
                    } else {
                        btn.classList.remove('st-btn--disabled');
                    }
                } else {
                    // Required but empty — browser required attr handles this
                    btn.disabled = false;
                    btn.classList.remove('st-btn--disabled');
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var pwd = el(pwdId);
        var cfm = el(cfmId);
        if (pwd) {
            pwd.addEventListener('input', validate);
            pwd.addEventListener('focus', validate);
        }
        if (cfm) {
            cfm.addEventListener('input', validate);
        }
    });
})();
</script>

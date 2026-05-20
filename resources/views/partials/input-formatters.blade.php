{{-- Global Input Formatters: Vehicle Number & Phone Number --}}
<script>
(function() {
    window.initializeInputFormatters = function(container) {
        var root = container || document;

        // ====================================================================
        // VEHICLE NUMBER FORMATTER (Indonesian Plate: B 1234 YKK)
        // Format: [1-2 letters] [1-4 digits] [1-3 letters]
        // ====================================================================
        var vehicleInputs = root.querySelectorAll(
            'input[name="vehicle_number"], input[name="vehicle_number_snap"]'
        );

        vehicleInputs.forEach(function(input) {
            if (input.dataset.formatterInitialzed) return;
            input.dataset.formatterInitialzed = "true";
            // Create validation message element
            var errMsg = document.createElement('div');
            errMsg.className = 'st-input-format-error';
            errMsg.style.cssText = 'position: absolute; bottom: -15px; left: 0; font-size: 10px; color: #dc2626; margin-top: 0; display: none; white-space: nowrap; z-index: 10;';
            input.parentNode.insertBefore(errMsg, input.nextSibling);
            input.parentNode.style.position = 'relative';

            // Set attributes
            input.setAttribute('placeholder', input.getAttribute('placeholder') || 'e.g., B 1234 ABC');
            input.setAttribute('maxlength', '12');
            input.style.textTransform = 'uppercase';

            // Auto-format on input
            input.addEventListener('input', function(e) {
                var raw = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                var formatted = formatVehicleNumber(raw);
                this.value = formatted;
                validateVehicleNumber(formatted, errMsg, input);
            });

            // Validate on blur
            input.addEventListener('blur', function() {
                this.value = this.value.trim();
                validateVehicleNumber(this.value, errMsg, input);
            });

            // Validate existing value
            if (input.value.trim()) {
                var raw = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                input.value = formatVehicleNumber(raw);
            }
        });

        function formatVehicleNumber(raw) {
            if (!raw) return '';

            var result = '';
            var part = 0; // 0=letters, 1=digits, 2=letters(suffix)
            var i = 0;

            // Part 1: Leading letters (1-2 chars)
            while (i < raw.length && /[A-Z]/.test(raw[i]) && result.length < 2) {
                result += raw[i];
                i++;
            }

            // If we have letters and next char is digit, add space
            if (result.length > 0 && i < raw.length && /[0-9]/.test(raw[i])) {
                result += ' ';
            }

            // Part 2: Digits (1-4 chars)
            var digitCount = 0;
            while (i < raw.length && /[0-9]/.test(raw[i]) && digitCount < 4) {
                result += raw[i];
                digitCount++;
                i++;
            }

            // If we have digits and next char is letter, add space
            if (digitCount > 0 && i < raw.length && /[A-Z]/.test(raw[i])) {
                result += ' ';
            }

            // Part 3: Trailing letters (1-3 chars)
            var suffixCount = 0;
            while (i < raw.length && /[A-Z]/.test(raw[i]) && suffixCount < 3) {
                result += raw[i];
                suffixCount++;
                i++;
            }

            return result;
        }

        function validateVehicleNumber(value, errEl, inputEl) {
            if (!value || value.trim() === '') {
                errEl.style.display = 'none';
                inputEl.classList.remove('st-input--invalid');
                return true;
            }

            // Regex: 1-2 letters, space, 1-4 digits, space, 1-3 letters
            var regex = /^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/;
            if (regex.test(value.trim())) {
                errEl.style.display = 'none';
                inputEl.classList.remove('st-input--invalid');
                return true;
            } else {
                errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Invalid format. Example: B 1234 YKK, AB 12 C';
                errEl.style.display = 'block';
                inputEl.classList.add('st-input--invalid');
                return false;
            }
        }


        // ====================================================================
        // PHONE NUMBER FORMATTER (Indonesian: +62xxx)
        // Auto-convert 0xxx → +62xxx
        // ====================================================================
        var phoneInputs = root.querySelectorAll('input[name="driver_number"]');

        phoneInputs.forEach(function(input) {
            if (input.dataset.formatterInitialzed) return;
            input.dataset.formatterInitialzed = "true";
            // Create validation message element
            var errMsg = document.createElement('div');
            errMsg.className = 'st-input-format-error';
            errMsg.style.cssText = 'position: absolute; bottom: -15px; left: 0; font-size: 10px; color: #dc2626; margin-top: 0; display: none; white-space: nowrap; z-index: 10;';
            input.parentNode.insertBefore(errMsg, input.nextSibling);
            input.parentNode.style.position = 'relative';

            // Set attributes
            input.setAttribute('placeholder', input.getAttribute('placeholder') || '08xxxxxxxxxx');
            input.setAttribute('maxlength', '15');
            input.setAttribute('inputmode', 'tel');

            // Auto-format on input
            input.addEventListener('input', function() {
                var val = this.value.replace(/[^\d+]/g, '');

                // If user pastes +62 or 62 at the start, replace it with 0
                if (val.startsWith('+62')) {
                    val = '0' + val.substring(3);
                } else if (val.startsWith('62')) {
                    val = '0' + val.substring(2);
                }

                // Strip any remaining + signs after replacing leading +62
                val = val.replace(/\+/g, '');

                this.value = val;
                validatePhoneNumber(val, errMsg, input);
            });

            // Auto-convert on blur
            input.addEventListener('blur', function() {
                var val = this.value.trim();

                // Final check on blur
                if (val.startsWith('+62')) val = '0' + val.substring(3);
                if (val.startsWith('62')) val = '0' + val.substring(2);
                val = val.replace(/\+/g, '');

                this.value = val;
                validatePhoneNumber(val, errMsg, input);
            });

            // Validate existing value
            if (input.value.trim()) {
                var val = input.value.trim();
                if (val.startsWith('+62')) val = '0' + val.substring(3);
                if (val.startsWith('62')) val = '0' + val.substring(2);
                input.value = val.replace(/\+/g, '');
            }
        });

        function validatePhoneNumber(value, errEl, inputEl) {
            if (!value || value.trim() === '') {
                errEl.style.display = 'none';
                inputEl.classList.remove('st-input--invalid');
                return true;
            }

            // Must start with 08, followed by 8-13 digits (total 10-15 chars)
            var regex = /^08\d{8,13}$/;
            if (regex.test(value.trim())) {
                errEl.style.display = 'none';
                inputEl.classList.remove('st-input--invalid');
                return true;
            } else {
                var msg = '';
                if (!value.startsWith('08')) {
                    msg = 'Phone number must start with 08 (example: 0812xxxxxx).';
                } else if (value.length < 10) {
                    msg = 'Number too short. Minimum 10 digits.';
                } else if (value.length > 15) {
                    msg = 'Number too long. Maximum 15 digits.';
                } else {
                    msg = 'Invalid format. Example: 0812xxxxxx';
                }
                errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
                errEl.style.display = 'block';
                inputEl.classList.add('st-input--invalid');
                return false;
            }
        }


        // ====================================================================
        // EMAIL FORMATTER & VALIDATION
        // ====================================================================
        var emailInputs = root.querySelectorAll('input[type="email"], input[name="email"]');
        emailInputs.forEach(function(input) {
            if (input.dataset.emailInit) return;
            input.dataset.emailInit = "true";

            var errMsg = document.createElement('div');
            errMsg.className = 'st-input-format-error';
            errMsg.style.cssText = 'position: absolute; bottom: -15px; left: 0; font-size: 10px; color: #dc2626; margin-top: 0; display: none; white-space: nowrap; z-index: 10;';
            input.parentNode.insertBefore(errMsg, input.nextSibling);
            input.parentNode.style.position = 'relative';

            input.addEventListener('input', function() {
                validateEmail(this.value, errMsg, input);
            });
            input.addEventListener('blur', function() {
                this.value = this.value.trim();
                validateEmail(this.value, errMsg, input);
            });
            if (input.value.trim()) validateEmail(input.value.trim(), errMsg, input);
        });

        function validateEmail(value, errEl, inputEl) {
            if (!value || value.trim() === '') {
                errEl.style.display = 'none';
                inputEl.classList.remove('st-input--invalid');
                return true;
            }
            var regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (regex.test(value.trim())) {
                errEl.style.display = 'none';
                inputEl.classList.remove('st-input--invalid');
                return true;
            } else {
                errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Invalid email format.';
                errEl.style.display = 'block';
                inputEl.classList.add('st-input--invalid');
                return false;
            }
        }

        // ====================================================================
        // PASSWORD VALIDATION
        // ====================================================================
        var passwordInputs = root.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(function(input) {
            if (!['password', 'new_password'].includes(input.name)) return;
            if (input.dataset.pwInit) return;
            input.dataset.pwInit = "true";

            var errMsg = document.createElement('div');
            errMsg.className = 'st-input-format-error';
            errMsg.style.cssText = 'position: absolute; bottom: -15px; left: 0; font-size: 10px; color: #dc2626; margin-top: 0; display: none; white-space: nowrap; z-index: 10;';

            if (input.parentNode.classList.contains('st-input-wrap')) {
                input.parentNode.parentNode.insertBefore(errMsg, input.parentNode.nextSibling);
                input.parentNode.parentNode.style.position = 'relative';
            } else {
                input.parentNode.insertBefore(errMsg, input.nextSibling);
                input.parentNode.style.position = 'relative';
            }

            input.addEventListener('input', function() {
                validatePassword(this.value, errMsg, input);
            });
            input.addEventListener('blur', function() {
                validatePassword(this.value, errMsg, input);
            });
        });

        function validatePassword(value, errEl, inputEl) {
            if (!value || value === '') {
                errEl.style.display = 'none';
                inputEl.classList.remove('st-input--invalid');
                return true;
            }

            var msgs = [];
            if (value.length < 8) msgs.push('Min. 8 characters.');
            if (!/[A-Z]/.test(value)) msgs.push('Must contain uppercase letter.');
            if (!/[a-z]/.test(value)) msgs.push('Must contain lowercase letter.');
            if (!/[0-9]/.test(value)) msgs.push('Must contain number.');
            if (!/[^A-Za-z0-9\s]/.test(value)) msgs.push('Must contain symbol.');

            if (msgs.length === 0) {
                errEl.style.display = 'none';
                inputEl.classList.remove('st-input--invalid');
                return true;
            } else {
                errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msgs.join(' ');
                errEl.style.display = 'block';
                inputEl.classList.add('st-input--invalid');
                return false;
            }
        }

        // ====================================================================
        // FORM SUBMIT VALIDATION
        // Block form submission if format is invalid
        // ====================================================================
        root.querySelectorAll('form').forEach(function(form) {
            if (form.dataset.formatterInitialzed) return;
            form.dataset.formatterInitialzed = "true";
            var hasFormatter = form.querySelector(
                'input[name="vehicle_number"], input[name="vehicle_number_snap"], input[name="driver_number"]'
            );
            if (!hasFormatter) return;

            form.addEventListener('submit', function(e) {
                var valid = true;

                // Validate vehicle numbers
                form.querySelectorAll('input[name="vehicle_number"], input[name="vehicle_number_snap"]').forEach(function(inp) {
                    if (inp.value.trim()) {
                        var errEl = inp.parentNode.querySelector('.st-input-format-error');
                        if (errEl && !validateVehicleNumber(inp.value, errEl, inp)) {
                            valid = false;
                            inp.focus();
                        }
                    }
                });

                // Validate phone numbers
                form.querySelectorAll('input[name="driver_number"]').forEach(function(inp) {
                    if (inp.value.trim()) {
                        var errEl = inp.parentNode.querySelector('.st-input-format-error');
                        if (errEl && !validatePhoneNumber(inp.value, errEl, inp)) {
                            valid = false;
                            if (valid) inp.focus();
                        }
                    }
                });

                // Validate emails
                form.querySelectorAll('input[type="email"], input[name="email"]').forEach(function(inp) {
                    if (inp.value.trim()) {
                        var container = inp.parentNode.classList.contains('st-input-wrap') ? inp.parentNode.parentNode : inp.parentNode;
                        var errEl = container.querySelector('.st-input-format-error');
                        if (errEl && !validateEmail(inp.value, errEl, inp)) {
                            valid = false;
                            if (valid) inp.focus(); // Wait, this if (valid) is buggy, I'll just focus if input requires
                            // Actually just focus on the first invalid:
                        }
                    }
                });

                // Validate passwords
                form.querySelectorAll('input[type="password"]').forEach(function(inp) {
                    if (!['password', 'new_password'].includes(inp.name)) return;
                    if (inp.value) {
                        var container = inp.parentNode.classList.contains('st-input-wrap') ? inp.parentNode.parentNode : inp.parentNode;
                        var errEl = container.querySelector('.st-input-format-error');
                        if (errEl && !validatePassword(inp.value, errEl, inp)) {
                            valid = false;
                        }
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    // Optionally grab the first invalid input and focus it
                    var firstInvalid = form.querySelector('.st-input--invalid');
                    if (firstInvalid) firstInvalid.focus();
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.initializeInputFormatters();
    });
})();
</script>

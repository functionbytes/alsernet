/**
 * PhoneInput.js — mc-phone-input wrapper over jackocnr/intl-tel-input v17
 *
 * Public API: declarative — drop `data-mc-phone-input` onto an `<input>`,
 * the script auto-initialises on DOMContentLoaded.
 *
 * Supported data-attributes (all optional unless noted):
 *   data-mc-phone-input              required  marker attribute
 *   data-default-country             iso2; defaults to "us"
 *   data-only-countries              comma-separated iso2 list (e.g. "us,ca,gb")
 *   data-exclude-countries           comma-separated iso2 list
 *   data-preferred-countries         comma-separated iso2 list (default: "us,gb")
 *   data-name-raw                    name attr for the raw-user-input hidden field
 *                                    (default = `${input.name}_raw`)
 *   data-channel                     sms|whatsapp|telegram — drives tint via CSS
 *   data-allow-sms-only              "true" hint — informational only; restrict
 *                                    via data-only-countries for actual gating
 *   data-show-validation             "false" disables the ✓/✗ trailing slot
 *   data-validate-on                 "blur" (default) | "change"
 *
 * On submit: the visible input's value is the user-typed local format; a sibling
 * hidden input with the same `name` carries the E.164 canonical form. The
 * `name_raw` field captures the raw typed string for diagnostics.
 *
 * Exposes `window.McPhoneInput` with:
 *   .get(el)                returns the iti instance for the element
 *   .getE164(el)            current E.164 string (or null)
 *   .setCountry(el, iso2)   programmatic flag swap
 *   .validate(el)           force re-validate
 *
 * Trap notes:
 *  - iti lazy-loads utils.js when needed; we point it at /core/phoneinput/utils.js.
 *  - iti's `separateDialCode: true` shifts the input's padding-left dynamically;
 *    our token-themed CSS preserves this by NOT overriding `padding-left` on
 *    `.mc-phone-input .iti--separate-dial-code input`.
 *  - `prefers-reduced-motion` is honoured: iti's dropdown has CSS-only animation
 *    which we override to instant via `prefers-reduced-motion: reduce` query.
 */
(function () {
    'use strict';

    if (typeof window.intlTelInput !== 'function') {
        // Library not loaded — silently skip; the page will still render the
        // bare input.
        return;
    }

    var UTILS_SCRIPT = '/core/phoneinput/utils.js';
    var DEFAULT_PREFERRED = ['us', 'gb'];

    function csv(value) {
        if (!value) return null;
        return value.split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean);
    }

    function createHiddenInput(name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value || '';
        return input;
    }

    function createTrailingSlot() {
        var slot = document.createElement('span');
        slot.className = 'mc-phone-input__trailing';
        slot.setAttribute('aria-hidden', 'true');
        return slot;
    }

    function setSlotState(slot, state) {
        // state ∈ {idle, valid, invalid, warning}
        slot.dataset.state = state;
        slot.innerHTML = '';
        if (state === 'valid') {
            slot.innerHTML = '<svg viewBox="0 0 16 16" width="14" height="14" fill="none"><path d="M3 8.5l3.5 3.5 6.5-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        } else if (state === 'invalid') {
            slot.innerHTML = '<svg viewBox="0 0 16 16" width="14" height="14" fill="none"><path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        } else if (state === 'warning') {
            slot.innerHTML = '<svg viewBox="0 0 16 16" width="14" height="14" fill="none"><path d="M8 2.5l6 11H2l6-11z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 7v3.5M8 11.7v.1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
        }
    }

    function findOrCreateHelp(input) {
        var wrapper = input.closest('.mc-phone-input');
        if (!wrapper) return null;
        var existing = wrapper.querySelector('.mc-phone-input__help');
        if (existing) return existing;
        var help = document.createElement('div');
        help.className = 'mc-phone-input__help';
        wrapper.appendChild(help);
        return help;
    }

    function setHelpMessage(input, message, tone) {
        var help = findOrCreateHelp(input);
        if (!help) return;
        help.textContent = message || '';
        help.dataset.tone = tone || '';
    }

    function init(input) {
        if (input.__mcPhoneInputInited) return;
        input.__mcPhoneInputInited = true;

        // Build wrapper if not already wrapping us — keeps trailing slot + help
        // adjacent regardless of whether the page author wrapped manually.
        var wrapper = input.closest('.mc-phone-input');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'mc-phone-input';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }

        // Propagate optional channel tint to wrapper (CSS reads [data-channel]).
        if (input.dataset.channel) {
            wrapper.dataset.channel = input.dataset.channel;
        }

        // Resolve options from data-*.
        var initialCountry = (input.dataset.defaultCountry || 'us').toLowerCase();
        var onlyCountries  = csv(input.dataset.onlyCountries);
        var excludeCountries = csv(input.dataset.excludeCountries);
        var preferredCountries = csv(input.dataset.preferredCountries) || DEFAULT_PREFERRED;
        var visibleName = input.getAttribute('name') || '';
        var rawName = input.dataset.nameRaw || (visibleName ? visibleName + '_raw' : '');
        var showValidation = input.dataset.showValidation !== 'false';
        var validateOn = input.dataset.validateOn || 'blur';

        // Hidden inputs: the visible <input> name is REUSED for the E.164 value
        // — we rename the visible one to `${name}_local` so the form payload
        // carries the canonical E.164 under the original name.
        var e164Hidden = null;
        var rawHidden = null;
        if (visibleName) {
            input.setAttribute('name', visibleName + '_local');
            e164Hidden = createHiddenInput(visibleName, '');
            wrapper.appendChild(e164Hidden);
            if (rawName) {
                rawHidden = createHiddenInput(rawName, input.value || '');
                wrapper.appendChild(rawHidden);
            }
        }

        // Trailing slot + help line.
        var trailingSlot = null;
        if (showValidation) {
            trailingSlot = createTrailingSlot();
            wrapper.appendChild(trailingSlot);
        }
        findOrCreateHelp(input); // pre-create so layout is stable

        // Init intl-tel-input.
        var itiOpts = {
            initialCountry: initialCountry,
            separateDialCode: true,
            nationalMode: true,
            autoPlaceholder: 'polite',
            preferredCountries: preferredCountries,
            utilsScript: UTILS_SCRIPT,
        };
        if (onlyCountries && onlyCountries.length)     itiOpts.onlyCountries = onlyCountries;
        if (excludeCountries && excludeCountries.length) itiOpts.excludeCountries = excludeCountries;

        var iti;
        try {
            iti = window.intlTelInput(input, itiOpts);
        } catch (err) {
            // Library threw on init — leave the bare input so the page is still
            // usable, surface a console warning for ops.
            console.warn('[mc-phone-input] intl-tel-input init failed:', err);
            return;
        }

        // Validation logic, runs on the configured trigger.
        function runValidation() {
            var typed = input.value.trim();

            // Sync raw hidden.
            if (rawHidden) rawHidden.value = typed;

            // Empty + not required → idle.
            if (!typed) {
                if (e164Hidden) e164Hidden.value = '';
                wrapper.dataset.state = input.required ? 'invalid' : 'idle';
                if (trailingSlot) setSlotState(trailingSlot, input.required ? 'invalid' : 'idle');
                if (input.required) setHelpMessage(input, wrapper.dataset.msgRequired || 'Phone number is required.', 'danger');
                else setHelpMessage(input, wrapper.dataset.msgHint || '', '');
                return;
            }

            // utils.js may still be loading on first interaction — if so, defer
            // the validation pass; iti's promise resolves once utils are ready.
            if (!window.intlTelInputUtils) {
                if (iti.promise && iti.promise.then) {
                    iti.promise.then(runValidation);
                }
                return;
            }

            var isValid = iti.isValidNumber();
            if (isValid) {
                var e164 = iti.getNumber(intlTelInputUtils.numberFormat.E164);
                if (e164Hidden) e164Hidden.value = e164;
                wrapper.dataset.state = 'valid';
                if (trailingSlot) setSlotState(trailingSlot, 'valid');
                setHelpMessage(input, wrapper.dataset.msgValid || '', 'success');
            } else {
                if (e164Hidden) e164Hidden.value = '';
                wrapper.dataset.state = 'invalid';
                if (trailingSlot) setSlotState(trailingSlot, 'invalid');
                var errCode = iti.getValidationError();
                setHelpMessage(input, errorMessageFor(errCode, wrapper), 'danger');
            }
        }

        // Wire the trigger.
        if (validateOn === 'change') {
            input.addEventListener('input', debounce(runValidation, 250));
        } else {
            input.addEventListener('blur', runValidation);
        }
        input.addEventListener('countrychange', runValidation);

        // Stamp the iti instance for external lookup.
        input.__mcPhoneInputIti = iti;
        input.__mcPhoneInputE164 = e164Hidden;
        input.__mcPhoneInputRaw = rawHidden;
    }

    function errorMessageFor(code, wrapper) {
        // libphonenumber-js error codes via iti.getValidationError():
        //   0 = IS_POSSIBLE, 1 = INVALID_COUNTRY_CODE, 2 = TOO_SHORT,
        //   3 = TOO_LONG, 4 = NOT_A_NUMBER, -99 = utils not loaded
        var lookup = {
            1: wrapper.dataset.msgInvalidCountry || 'That country code does not match a real country.',
            2: wrapper.dataset.msgTooShort || 'Number is too short for the selected country.',
            3: wrapper.dataset.msgTooLong || 'Number is too long for the selected country.',
            4: wrapper.dataset.msgNotANumber || 'That does not look like a phone number.',
        };
        return lookup[code] || (wrapper.dataset.msgInvalid || 'Phone number format is not valid.');
    }

    function debounce(fn, wait) {
        var timer;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(null, args); }, wait);
        };
    }

    // Public API.
    window.McPhoneInput = {
        get: function (el) { return el && el.__mcPhoneInputIti; },
        getE164: function (el) { return el && el.__mcPhoneInputE164 ? el.__mcPhoneInputE164.value : null; },
        setCountry: function (el, iso2) { if (el && el.__mcPhoneInputIti) el.__mcPhoneInputIti.setCountry(iso2); },
        validate: function (el) { if (el) el.dispatchEvent(new Event('blur')); },
        initAll: function (root) {
            (root || document).querySelectorAll('input[data-mc-phone-input]').forEach(init);
        },
    };

    // Auto-init on ready.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            window.McPhoneInput.initAll();
        });
    } else {
        window.McPhoneInput.initAll();
    }

    // Re-init on dynamic DOM changes (forms loaded via fetch, modal opens, etc.)
    // Page authors can call window.McPhoneInput.initAll(container) on demand.
})();

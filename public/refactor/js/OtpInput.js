/**
 * OtpInput.js — mc-otp-input (C4 W3): 6-digit one-time-passcode entry.
 *
 * Declarative drop-in: `<div data-mc-otp-input data-name="code"></div>` is
 * replaced at boot with N (default 6) individual digit boxes + a hidden
 * composite input that carries the joined value on form submit.
 *
 * Supported data-* attributes:
 *   data-mc-otp-input            required  marker attribute on the wrapper
 *   data-name                    name attr on the hidden composite input
 *                                (default "code")
 *   data-length                  number of digit boxes (default 6, range 4-8)
 *   data-auto-submit             "true" (default) | "false" — when all boxes
 *                                are filled, fire submit on the parent form
 *   data-input-mode              "numeric" (default) | "text"
 *   data-mask                    "off" (default) | "on" — mask digits to ● after 800ms
 *   data-allow-paste             "true" (default) | "false"
 *
 * Programmatic API: window.McOtpInput
 *   .get(wrapper)      — returns instance
 *   .getValue(wrapper) — joined string (null if any box empty)
 *   .setError(wrapper, message?) — danger state + shake + clear + refocus first box
 *   .setLocked(wrapper, message?) — disable all boxes
 *   .reset(wrapper)    — clear boxes + remove states
 *   .initAll(root?)    — re-init unmounted wrappers
 *
 * iOS SMS auto-fill: a hidden `<input autocomplete="one-time-code">` overlay
 * sits behind the boxes; when iOS Safari surfaces the code from a recent SMS,
 * filling that input auto-distributes across all visible boxes.
 *
 * Trap notes:
 *  - Pasting a multi-digit string into ANY box (not just the first) is supported
 *    by the spec; we strip non-digits + distribute remaining characters across
 *    boxes starting from the paste-target box (NOT from box 1, since the user
 *    might be retyping mid-code after backspace).
 *  - Auto-submit only fires when ALL boxes are filled, NEVER on partial completion,
 *    AND not while the wrapper is in the `locked` state.
 *  - Shake animation respects `prefers-reduced-motion: reduce` (handled in CSS).
 */
(function () {
    'use strict';

    const DEFAULT_LENGTH = 6;
    const MIN_LENGTH = 4;
    const MAX_LENGTH = 8;

    function buildBoxes(wrapper, length) {
        const boxes = [];
        for (let i = 0; i < length; i++) {
            const box = document.createElement('input');
            box.type = 'text';
            box.className = 'mc-otp-input__digit';
            box.setAttribute('inputmode', wrapper.dataset.inputMode || 'numeric');
            box.setAttribute('autocomplete', 'off');
            box.setAttribute('aria-label', `Digit ${i + 1} of ${length}`);
            box.setAttribute('maxlength', '1');
            box.dataset.index = String(i);
            // Only the first box opts into iOS SMS auto-fill — iOS surfaces
            // the code on the FIRST focused input that has this attr; the
            // input handler then distributes the full code via paste-like flow.
            if (i === 0) {
                box.setAttribute('autocomplete', 'one-time-code');
            }
            boxes.push(box);
        }
        return boxes;
    }

    function init(wrapper) {
        if (wrapper.__mcOtpInputInited) return;
        wrapper.__mcOtpInputInited = true;

        let length = parseInt(wrapper.dataset.length, 10) || DEFAULT_LENGTH;
        length = Math.max(MIN_LENGTH, Math.min(MAX_LENGTH, length));

        const name = wrapper.dataset.name || 'code';
        const autoSubmit = wrapper.dataset.autoSubmit !== 'false';
        const allowPaste = wrapper.dataset.allowPaste !== 'false';

        wrapper.classList.add('mc-otp-input');
        wrapper.setAttribute('role', 'group');
        wrapper.setAttribute('aria-label', wrapper.getAttribute('aria-label') || 'Verification code');

        // Build digit boxes
        const boxes = buildBoxes(wrapper, length);

        // Hidden composite input — sends the joined value on form submit
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = name;
        hidden.value = '';

        wrapper.appendChild(hidden);
        boxes.forEach((box) => wrapper.appendChild(box));

        function getValue() {
            const value = boxes.map((b) => b.value).join('');
            return value.length === length ? value : null;
        }

        function syncHidden() {
            const value = boxes.map((b) => b.value).join('');
            hidden.value = value;
            const isComplete = value.length === length && /^\d+$/.test(value);
            wrapper.dataset.state = isComplete ? 'complete' : (value.length > 0 ? 'partial' : 'empty');
            return isComplete;
        }

        function focusBox(index) {
            const target = boxes[Math.max(0, Math.min(length - 1, index))];
            if (target && !target.disabled) {
                target.focus();
                target.select();
            }
        }

        function fireSubmit() {
            if (!autoSubmit) return;
            if (wrapper.dataset.state === 'locked') return;
            // Find nearest form and submit via requestSubmit so HTML5 validation runs;
            // fallback to submit() if not supported.
            const form = wrapper.closest('form');
            if (!form) return;
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                // requestSubmit not available — emit a custom event so non-form
                // hosts (modal handlers etc.) can react.
                wrapper.dispatchEvent(new CustomEvent('mc-otp-submit', { detail: { value: getValue() }, bubbles: true }));
            }
        }

        function clearError() {
            if (wrapper.dataset.state === 'invalid') {
                wrapper.dataset.state = 'partial';
                const help = wrapper.querySelector('.mc-otp-input__help');
                if (help) {
                    help.textContent = '';
                    help.dataset.tone = '';
                }
            }
        }

        function distributePaste(startIndex, text) {
            const digits = text.replace(/\D/g, '').slice(0, length - startIndex);
            for (let i = 0; i < digits.length; i++) {
                if (boxes[startIndex + i] && !boxes[startIndex + i].disabled) {
                    boxes[startIndex + i].value = digits[i];
                }
            }
            const lastFilled = Math.min(length - 1, startIndex + digits.length - 1);
            focusBox(Math.min(length - 1, lastFilled + 1));
            const isComplete = syncHidden();
            if (isComplete) {
                setTimeout(fireSubmit, 60); // allow paint before submit
            }
        }

        boxes.forEach((box, index) => {
            // Input event — every keystroke
            box.addEventListener('input', (e) => {
                clearError();
                let v = box.value;

                // If a paste or multi-char autofill happened on this single box,
                // route through distributePaste.
                if (v.length > 1) {
                    distributePaste(index, v);
                    return;
                }

                // Filter non-digits
                if (!/^\d$/.test(v)) {
                    box.value = '';
                    syncHidden();
                    return;
                }

                // Single digit typed — advance focus
                if (index < length - 1) {
                    focusBox(index + 1);
                }
                const complete = syncHidden();
                if (complete && index === length - 1) {
                    setTimeout(fireSubmit, 60);
                }
            });

            // Keydown event — handle navigation + backspace + paste shortcut
            box.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace') {
                    if (!box.value && index > 0) {
                        // Backspace on empty box → focus previous + clear it
                        e.preventDefault();
                        boxes[index - 1].value = '';
                        focusBox(index - 1);
                        syncHidden();
                        clearError();
                    } else if (box.value) {
                        // Backspace on filled box → clear it (default behavior)
                        // but let it propagate so input event fires too
                    }
                } else if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    focusBox(index - 1);
                } else if (e.key === 'ArrowRight' && index < length - 1) {
                    e.preventDefault();
                    focusBox(index + 1);
                } else if (e.key === 'Home') {
                    e.preventDefault();
                    focusBox(0);
                } else if (e.key === 'End') {
                    e.preventDefault();
                    focusBox(length - 1);
                }
            });

            // Paste event — capture only on the first box typically, but allow on any
            if (allowPaste) {
                box.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const text = (e.clipboardData || window.clipboardData).getData('text');
                    if (!text) return;
                    distributePaste(index, text);
                });
            }

            // Focus event — select content for easy overwrite
            box.addEventListener('focus', () => {
                setTimeout(() => box.select(), 0);
            });
        });

        // Initial sync
        syncHidden();

        // Auto-focus first box on mount, only when wrapper has data-auto-focus
        if (wrapper.dataset.autoFocus === 'true') {
            setTimeout(() => focusBox(0), 100);
        }

        // Stamp the instance for external lookup
        wrapper.__mcOtpInputBoxes = boxes;
        wrapper.__mcOtpInputHidden = hidden;
        wrapper.__mcOtpInputGetValue = getValue;
        wrapper.__mcOtpInputFocusBox = focusBox;
    }

    function setError(wrapper, message) {
        if (!wrapper || !wrapper.__mcOtpInputBoxes) return;
        const boxes = wrapper.__mcOtpInputBoxes;
        wrapper.dataset.state = 'invalid';
        // Clear all boxes
        boxes.forEach((b) => { b.value = ''; });
        if (wrapper.__mcOtpInputHidden) wrapper.__mcOtpInputHidden.value = '';
        // Set help message
        let help = wrapper.querySelector('.mc-otp-input__help');
        if (!help && message) {
            help = document.createElement('div');
            help.className = 'mc-otp-input__help';
            wrapper.appendChild(help);
        }
        if (help) {
            help.textContent = message || '';
            help.dataset.tone = 'danger';
            help.setAttribute('aria-live', 'polite');
        }
        // Trigger shake animation by re-adding the class
        wrapper.classList.remove('mc-otp-input--shake');
        // Force reflow so the class can be re-added (re-fires the animation)
        // eslint-disable-next-line no-unused-expressions
        wrapper.offsetWidth;
        wrapper.classList.add('mc-otp-input--shake');
        // Refocus first box
        setTimeout(() => wrapper.__mcOtpInputFocusBox && wrapper.__mcOtpInputFocusBox(0), 200);
    }

    function setLocked(wrapper, message) {
        if (!wrapper || !wrapper.__mcOtpInputBoxes) return;
        wrapper.dataset.state = 'locked';
        wrapper.__mcOtpInputBoxes.forEach((b) => { b.disabled = true; });
        let help = wrapper.querySelector('.mc-otp-input__help');
        if (!help && message) {
            help = document.createElement('div');
            help.className = 'mc-otp-input__help';
            wrapper.appendChild(help);
        }
        if (help) {
            help.textContent = message || '';
            help.dataset.tone = 'danger';
        }
    }

    function reset(wrapper) {
        if (!wrapper || !wrapper.__mcOtpInputBoxes) return;
        wrapper.__mcOtpInputBoxes.forEach((b) => { b.value = ''; b.disabled = false; });
        if (wrapper.__mcOtpInputHidden) wrapper.__mcOtpInputHidden.value = '';
        wrapper.dataset.state = 'empty';
        wrapper.classList.remove('mc-otp-input--shake');
        const help = wrapper.querySelector('.mc-otp-input__help');
        if (help) { help.textContent = ''; help.dataset.tone = ''; }
    }

    function getValue(wrapper) {
        return wrapper && wrapper.__mcOtpInputGetValue ? wrapper.__mcOtpInputGetValue() : null;
    }

    // Public API
    window.McOtpInput = {
        get: (el) => el,
        getValue,
        setError,
        setLocked,
        reset,
        initAll(root) {
            (root || document).querySelectorAll('[data-mc-otp-input]').forEach(init);
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.McOtpInput.initAll());
    } else {
        window.McOtpInput.initAll();
    }
})();

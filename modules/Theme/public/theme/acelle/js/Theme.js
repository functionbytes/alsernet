/**
 * Theme — manages light / dark / system color scheme.
 *
 * Three modes (per the GitHub / Linear / VSCode pattern):
 *   - 'light'  — paint light, ignore OS
 *   - 'dark'   — paint dark, ignore OS
 *   - 'system' — follow OS `prefers-color-scheme`, repaint live when it flips
 *
 * Storage:
 *   localStorage.mc-theme  ∈ {'light', 'dark', 'system'} OR null (== 'system')
 *
 * Public API:
 *   McTheme.setMode(mode)        — explicit choice; persists + repaints + updates UI
 *   McTheme.getMode()            — current chosen mode (light / dark / system)
 *   McTheme.getEffectiveMode()   — resolved paint mode (light / dark) after system → OS lookup
 *
 * The blocking inline script in `layouts/{app,admin}.blade.php` already
 * applies an early `data-theme` attribute before paint to prevent flash;
 * this class takes over once the DOM is ready and keeps the attribute in
 * sync with mode + OS changes.
 */
class Theme {
    constructor() {
        this.mode = 'system';            // user-chosen mode
        this._mql = null;                // OS preference MediaQueryList
        this._osListener = null;
    }

    init() {
        // 1. Resolve initial mode from localStorage (default = 'system' when absent).
        var saved = localStorage.getItem('mc-theme');
        this.mode = (saved === 'light' || saved === 'dark' || saved === 'system') ? saved : 'system';

        // 2. Set up OS-preference listener (always, to support 'system' mode).
        if (window.matchMedia) {
            this._mql = window.matchMedia('(prefers-color-scheme: dark)');
            this._osListener = function () {
                if (this.mode === 'system') this.apply();
            }.bind(this);
            // addEventListener for modern browsers; addListener fallback for older WebKit.
            if (this._mql.addEventListener) {
                this._mql.addEventListener('change', this._osListener);
            } else if (this._mql.addListener) {
                this._mql.addListener(this._osListener);
            }
        }

        // 3. Paint.
        this.apply();

        // 4. Bind the new dropdown trigger + menu items if present in DOM.
        this._bindDropdown();
    }

    /**
     * Apply the effective theme to the document + sync UI markers.
     */
    apply() {
        var effective = this.getEffectiveMode();
        document.documentElement.setAttribute('data-theme', effective);
        this._syncTriggerIcon(effective);
        this._syncMenuActive(this.mode);
    }

    setMode(mode) {
        if (mode !== 'light' && mode !== 'dark' && mode !== 'system') return;
        this.mode = mode;
        // 'system' wipes the explicit choice — semantics: "follow OS from now on".
        // We still write the literal 'system' marker so reload picks it up + the
        // dropdown active state is restorable (vs. ambiguous "no preference").
        localStorage.setItem('mc-theme', mode);
        this.apply();
    }

    getMode() {
        return this.mode;
    }

    getEffectiveMode() {
        if (this.mode === 'light' || this.mode === 'dark') return this.mode;
        // mode === 'system' → resolve via OS preference (default light when no MQL).
        if (this._mql && this._mql.matches) return 'dark';
        return 'light';
    }

    _bindDropdown() {
        var menu = document.querySelector('[data-mc-theme-menu]');
        if (!menu) return;
        var self = this;
        menu.querySelectorAll('[data-mc-theme-mode]').forEach(function (item) {
            item.addEventListener('click', function (evt) {
                evt.preventDefault();
                var pickedMode = item.getAttribute('data-mc-theme-mode');
                self.setMode(pickedMode);
                // Close the dropdown — defer to canonical Dropdown.js when present.
                var dropdown = item.closest('[data-dropdown]');
                if (dropdown) dropdown.classList.remove('is-open');
            });
        });
    }

    _syncTriggerIcon(effective) {
        // Trigger shows the EFFECTIVE icon (sun when light, moon when dark) regardless
        // of whether the user picked Light/Dark/System. The dropdown body shows which
        // mode was chosen via the check mark on the active item.
        var lightIcon = document.getElementById('theme-icon-light');
        var darkIcon = document.getElementById('theme-icon-dark');
        if (lightIcon && darkIcon) {
            if (effective === 'dark') {
                lightIcon.classList.add('mc-hidden');
                darkIcon.classList.remove('mc-hidden');
            } else {
                lightIcon.classList.remove('mc-hidden');
                darkIcon.classList.add('mc-hidden');
            }
        }
    }

    _syncMenuActive(mode) {
        var menu = document.querySelector('[data-mc-theme-menu]');
        if (!menu) return;
        menu.querySelectorAll('[data-mc-theme-mode]').forEach(function (item) {
            var picked = item.getAttribute('data-mc-theme-mode');
            item.classList.toggle('is-active', picked === mode);
        });
    }

    /**
     * Back-compat — the prior single-button toggle had a `toggle()` method.
     * Some inline scripts may still call it. Map to a sensible 2-state cycle
     * (light ↔ dark, breaks 'system' state) so old callers don't crash.
     * New consumers should use `setMode()` instead.
     */
    toggle() {
        var effective = this.getEffectiveMode();
        this.setMode(effective === 'light' ? 'dark' : 'light');
    }
}

window.McTheme = new Theme();

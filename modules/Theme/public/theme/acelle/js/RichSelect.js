/**
 * RichSelect — Custom dropdown with label + description + optional icon per option.
 * Replaces native <select> when options need more context.
 *
 * Usage (declarative):
 *   <div class="mc-rich-select" data-rich-select>
 *       <button class="mc-rich-select-trigger" type="button" data-rich-select-trigger>
 *           <span class="mc-rich-select-value mc-rich-select-placeholder">Select...</span>
 *           <span class="material-symbols-rounded mc-rich-select-arrow">expand_more</span>
 *       </button>
 *       <input type="hidden" name="field_name" data-rich-select-input>
 *       <div class="mc-rich-select-dropdown">
 *           <div class="mc-rich-select-option" data-value="abc" data-label="Option 1" data-desc="Description here">
 *               <div class="mc-rich-select-option-icon">
 *                   <span class="material-symbols-rounded">list</span>
 *               </div>
 *               <div class="mc-rich-select-option-content">
 *                   <div class="mc-rich-select-option-label">Option 1</div>
 *                   <div class="mc-rich-select-option-desc">Description here</div>
 *               </div>
 *           </div>
 *       </div>
 *   </div>
 *
 * Usage (programmatic):
 *   new McRichSelect(element, {
 *       placeholder: 'Select a list...',
 *       onChange: (value, label) => { ... }
 *   });
 */
class RichSelect {
    constructor(el, options = {}) {
        this.el = typeof el === 'string' ? document.querySelector(el) : el;
        if (!this.el) return;

        this.trigger = this.el.querySelector('[data-rich-select-trigger]');
        this.valueEl = this.el.querySelector('.mc-rich-select-value');
        this.hiddenInput = this.el.querySelector('[data-rich-select-input]');
        this.dropdown = this.el.querySelector('.mc-rich-select-dropdown');
        this.options = this.el.querySelectorAll('.mc-rich-select-option');

        this.placeholder = options.placeholder || this.valueEl?.textContent || 'Select...';
        this.onChange = options.onChange || null;
        this.selectedValue = this.hiddenInput?.value || '';

        this._bindEvents();
    }

    /**
     * Initialize all [data-rich-select] elements on the page.
     */
    static init() {
        document.querySelectorAll('[data-rich-select]').forEach(function(el) {
            if (!el._richSelect) {
                el._richSelect = new RichSelect(el);
            }
        });
    }

    /**
     * Bind events.
     */
    _bindEvents() {
        var self = this;

        // Toggle dropdown on trigger click
        this.trigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self.toggle();
        });

        // Select option on click
        this.options.forEach(function(opt) {
            opt.addEventListener('click', function(e) {
                e.stopPropagation();
                self.select(opt);
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!self.el.contains(e.target)) {
                self.close();
            }
        });

        // Close on Escape
        this.el.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                self.close();
                self.trigger.focus();
            }
        });
    }

    /**
     * Toggle dropdown open/closed.
     */
    toggle() {
        if (this.el.classList.contains('open')) {
            this.close();
        } else {
            this.open();
        }
    }

    /**
     * Open dropdown.
     */
    open() {
        // Close any other open rich selects first
        document.querySelectorAll('.mc-rich-select.open').forEach(function(el) {
            if (el._richSelect) el._richSelect.close();
        });
        this.el.classList.add('open');
    }

    /**
     * Close dropdown.
     */
    close() {
        this.el.classList.remove('open');
    }

    /**
     * Select an option.
     * @param {Element} optionEl - The option element
     */
    select(optionEl) {
        var value = optionEl.dataset.value;
        var label = optionEl.dataset.label || optionEl.querySelector('.mc-rich-select-option-label')?.textContent || '';

        // Update hidden input
        if (this.hiddenInput) {
            this.hiddenInput.value = value;
        }

        // Update trigger label
        if (this.valueEl) {
            this.valueEl.textContent = label;
            this.valueEl.classList.remove('mc-rich-select-placeholder');
        }

        // Update selected state
        this.options.forEach(function(opt) { opt.classList.remove('selected'); });
        optionEl.classList.add('selected');

        this.selectedValue = value;
        this.close();

        // Callback
        if (this.onChange) {
            this.onChange(value, label);
        }

        // Dispatch change event
        this.el.dispatchEvent(new CustomEvent('change', {
            detail: { value: value, label: label }
        }));
    }

    /**
     * Get current value.
     */
    getValue() {
        return this.selectedValue;
    }

    /**
     * Reset to placeholder state.
     */
    reset() {
        this.selectedValue = '';
        if (this.hiddenInput) this.hiddenInput.value = '';
        if (this.valueEl) {
            this.valueEl.textContent = this.placeholder;
            this.valueEl.classList.add('mc-rich-select-placeholder');
        }
        this.options.forEach(function(opt) { opt.classList.remove('selected'); });
    }
}

// Export
window.McRichSelect = RichSelect;

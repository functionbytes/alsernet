/**
 * McMultiSelect — Styled multi-select dropdown.
 *
 * Replaces native <select multiple> with checkbox-based dropdown.
 * Auto-init via data-multiselect attribute.
 *
 * Usage:
 *   <div class="mc-multiselect" data-multiselect data-placeholder="All subscribers">
 *       <div class="mc-multiselect-trigger" data-multiselect-trigger>
 *           <span class="mc-multiselect-label">All subscribers</span>
 *           <span class="material-symbols-rounded mc-multiselect-arrow">expand_more</span>
 *       </div>
 *       <div class="mc-multiselect-dropdown">
 *           <label class="mc-checkbox-label">
 *               <input type="checkbox" class="mc-checkbox" name="items[]" value="a"> Item A
 *           </label>
 *       </div>
 *   </div>
 *
 * Events: 'multiselect:change' on the element with detail: { values: [...] }
 */
class McMultiSelect {
    constructor(el) {
        this.el = typeof el === 'string' ? document.querySelector(el) : el;
        if (!this.el) return;

        this.trigger = this.el.querySelector('[data-multiselect-trigger]');
        this.dropdown = this.el.querySelector('.mc-multiselect-dropdown');
        this.labelEl = this.el.querySelector('.mc-multiselect-label');
        this.placeholder = this.el.dataset.placeholder || 'Select...';

        this._bindEvents();
        this._updateLabel();
    }

    _bindEvents() {
        // Toggle dropdown
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });

        // Stay open on dropdown click
        this.dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Close on outside click
        document.addEventListener('click', () => this.close());

        // Escape closes
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.close();
        });

        // Checkbox change → update label
        this.el.addEventListener('change', () => {
            this._updateLabel();
            this.el.dispatchEvent(new CustomEvent('multiselect:change', {
                bubbles: true,
                detail: { values: this.getValues() }
            }));
        });
    }

    toggle() { this.el.classList.toggle('open'); }
    open() { this.el.classList.add('open'); }
    close() { this.el.classList.remove('open'); }

    getValues() {
        return Array.from(this.el.querySelectorAll('.mc-checkbox:checked'))
            .map(cb => cb.value)
            .filter(v => v);
    }

    _updateLabel() {
        const checked = this.el.querySelectorAll('.mc-checkbox:checked');
        if (checked.length === 0) {
            this.labelEl.textContent = this.placeholder;
            this.labelEl.style.color = 'var(--color-text-muted)';
        } else if (checked.length <= 2) {
            const names = Array.from(checked).map(cb => {
                const label = cb.closest('.mc-checkbox-label');
                return label ? label.textContent.trim() : cb.value;
            });
            this.labelEl.textContent = names.join(', ');
            this.labelEl.style.color = '';
        } else {
            this.labelEl.textContent = checked.length + ' selected';
            this.labelEl.style.color = '';
        }
    }

    /**
     * Replace options (for AJAX-loaded content).
     */
    setOptions(html) {
        this.dropdown.innerHTML = html || '<div class="mc-multiselect-empty">No options</div>';
        this._updateLabel();
    }
}

// Auto-init
function _initMultiSelects() {
    document.querySelectorAll('[data-multiselect]').forEach(el => {
        if (!el._multiSelect) {
            el._multiSelect = new McMultiSelect(el);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', _initMultiSelects);
} else {
    _initMultiSelects();
}

window.McMultiSelect = McMultiSelect;

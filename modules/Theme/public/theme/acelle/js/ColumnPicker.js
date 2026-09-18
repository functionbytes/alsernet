/**
 * ColumnPicker — Dropdown for toggling table columns.
 *
 * Stays open while selecting checkboxes. Closes on Apply or outside click.
 * Saves column preferences via AJAX.
 *
 * Usage (declarative):
 *   <div data-column-picker data-save-url="/save-columns">
 *       <button data-column-picker-trigger class="mc-btn mc-btn-secondary mc-btn-sm">
 *           <span class="material-symbols-rounded">view_column</span> Columns
 *       </button>
 *       <div class="mc-column-picker-menu">
 *           <label class="mc-checkbox-label">
 *               <input type="checkbox" class="mc-checkbox" data-column="field_uid" checked> Field Name
 *           </label>
 *           <div class="mc-column-picker-footer">
 *               <button data-column-picker-apply class="mc-btn mc-btn-primary mc-btn-sm">Apply</button>
 *           </div>
 *       </div>
 *   </div>
 *
 * Events:
 *   - 'columns:change' dispatched on the element with detail: { columns: [...] }
 */
class McColumnPicker {
    constructor(el) {
        this.el = typeof el === 'string' ? document.querySelector(el) : el;
        if (!this.el) return;

        this.trigger = this.el.querySelector('[data-column-picker-trigger]');
        this.menu = this.el.querySelector('.mc-column-picker-menu');
        this.applyBtn = this.el.querySelector('[data-column-picker-apply]');
        this.saveUrl = this.el.dataset.saveUrl || null;

        this._bindEvents();
    }

    _bindEvents() {
        // Toggle on trigger click
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });

        // Stay open on menu click
        this.menu.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Close on outside click
        document.addEventListener('click', () => {
            this.close();
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.close();
        });

        // Apply button
        if (this.applyBtn) {
            this.applyBtn.addEventListener('click', () => {
                this.apply();
            });
        }
    }

    toggle() {
        this.el.classList.toggle('open');
    }

    open() {
        this.el.classList.add('open');
    }

    close() {
        this.el.classList.remove('open');
    }

    /**
     * Get selected column values.
     */
    getColumns() {
        return Array.from(this.el.querySelectorAll('.mc-checkbox:checked'))
            .map(cb => cb.dataset.column || cb.value);
    }

    /**
     * Apply: dispatch event, save to server, close.
     */
    apply() {
        const columns = this.getColumns();

        // Dispatch event for parent to react
        this.el.dispatchEvent(new CustomEvent('columns:change', {
            bubbles: true,
            detail: { columns }
        }));

        // Save to server if URL configured
        if (this.saveUrl) {
            fetch(this.saveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ columns })
            }).catch(err => console.error('Column save error:', err));
        }

        this.close();
    }
}

// Auto-init (handles both DOMContentLoaded and already-loaded cases)
function _initColumnPickers() {
    document.querySelectorAll('[data-column-picker]').forEach(el => {
        if (!el._columnPicker) {
            el._columnPicker = new McColumnPicker(el);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', _initColumnPickers);
} else {
    _initColumnPickers();
}

window.McColumnPicker = McColumnPicker;

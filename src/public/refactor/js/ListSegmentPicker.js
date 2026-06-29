/**
 * McListSegmentPicker — Multi-list + segment cascading selector.
 *
 * Manages addable/removable list-segment rows for campaign recipients.
 * When list changes → AJAX loads segments for that list.
 * When add clicked → AJAX fetches new row template.
 *
 * Usage (declarative):
 *   <div data-list-segment-picker
 *        data-add-url="/campaigns/{uid}/list-segment-form"
 *        data-segment-url="/segments/select-box">
 *       <div class="mc-lsp-rows" data-lsp-rows>
 *           <!-- rows rendered server-side -->
 *       </div>
 *       <button data-lsp-add>Add list</button>
 *   </div>
 *
 * Events:
 *   - 'lsp:change' — dispatched when lists/segments change
 */
class McListSegmentPicker {
    constructor(el) {
        this.el = typeof el === 'string' ? document.querySelector(el) : el;
        if (!this.el) return;

        this.rowsContainer = this.el.querySelector('[data-lsp-rows]') || this.el.querySelector('.mc-lsp-rows');
        this.addUrl = this.el.dataset.addUrl;
        this.segmentUrl = this.el.dataset.segmentUrl || '/segments/select-box';
        this.index = this.el.querySelectorAll('.mc-lsp-row').length;

        this._bindEvents();

        // Initialize RichSelect dropdowns in existing rows
        if (typeof RichSelect !== 'undefined') RichSelect.init();

        // Hide already-selected lists on init
        this._syncListOptions();
    }

    _bindEvents() {
        // Add row button
        const addBtn = this.el.querySelector('[data-lsp-add]');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.addRow());
        }

        // Before form submit: remove empty rows to avoid validation errors
        const form = this.el.closest('form');
        if (form) {
            form.addEventListener('submit', () => this._removeEmptyRows());
        }

        // Delegate: rich-select list change → load segments + sync options
        // mc-rich-select dispatches 'change' on the container [data-rich-select]
        this.el.addEventListener('change', (e) => {
            const richSelect = e.target.closest('[data-lsp-list]');
            if (richSelect) {
                this._loadSegments(richSelect);
                this._syncListOptions();
            }
        });

        // Delegate: remove row
        this.el.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('[data-lsp-remove]');
            if (removeBtn) {
                this._removeRow(removeBtn);
            }
        });
    }

    /**
     * Add a new list/segment row via AJAX.
     */
    async addRow() {
        if (!this.addUrl) return;

        try {
            const response = await fetch(`${this.addUrl}?index=${this.index}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            const html = await response.text();
            this.rowsContainer.insertAdjacentHTML('beforeend', html);
            this.index++;
            // Initialize RichSelect on the newly added row
            if (typeof RichSelect !== 'undefined') RichSelect.init();
            this._syncListOptions();
            this._dispatch('lsp:change');
        } catch (err) {
            console.error('ListSegmentPicker addRow error:', err);
        }
    }

    /**
     * Remove a row.
     */
    _removeRow(btn) {
        const row = btn.closest('.mc-lsp-row');
        if (row && this.rowsContainer.querySelectorAll('.mc-lsp-row').length > 1) {
            row.remove();
            this._syncListOptions();
            this._dispatch('lsp:change');
        }
    }

    /**
     * Load segments for a list via AJAX into the mc-multiselect dropdown.
     * @param {HTMLElement} listRichSelect - The [data-lsp-list] mc-rich-select container
     */
    async _loadSegments(listRichSelect) {
        const row = listRichSelect.closest('.mc-lsp-row');
        const multiSelect = row?.querySelector('[data-multiselect]');
        if (!multiSelect) return;

        const hiddenInput = listRichSelect.querySelector('[data-rich-select-input]');
        const listUid = hiddenInput ? hiddenInput.value : '';
        const dropdown = multiSelect.querySelector('.mc-multiselect-dropdown');

        if (!listUid) {
            dropdown.innerHTML = '<div class="mc-multiselect-empty">Select a list first</div>';
            if (multiSelect._multiSelect) multiSelect._multiSelect._updateLabel();
            return;
        }

        dropdown.innerHTML = '<div class="mc-multiselect-empty">Loading...</div>';

        try {
            const response = await fetch(`${this.segmentUrl}?list_uid=${listUid}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const optionsHtml = await response.text();

            if (optionsHtml.trim()) {
                // Convert <option> tags to mc-checkbox-label entries
                const index = row.dataset.index || 0;
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = optionsHtml;
                let checkboxHtml = '';
                tempDiv.querySelectorAll('option').forEach(opt => {
                    if (opt.value) {
                        checkboxHtml += '<label class="mc-checkbox-label">' +
                            '<input type="checkbox" class="mc-checkbox" name="lists_segments[' + index + '][segment_uids][]" value="' + opt.value + '"> ' +
                            opt.textContent +
                            '</label>';
                    }
                });
                dropdown.innerHTML = checkboxHtml || '<div class="mc-multiselect-empty">No segments — sends to all</div>';
            } else {
                dropdown.innerHTML = '<div class="mc-multiselect-empty">No segments — sends to all</div>';
            }

            // Re-init multiselect label
            if (multiSelect._multiSelect) {
                multiSelect._multiSelect._updateLabel();
            } else {
                multiSelect._multiSelect = new McMultiSelect(multiSelect);
            }

            this._dispatch('lsp:change');
        } catch (err) {
            console.error('ListSegmentPicker loadSegments error:', err);
            dropdown.innerHTML = '<div class="mc-multiselect-empty">Error loading segments</div>';
        }
    }

    /**
     * Remove rows with no list selected (before form submit).
     */
    _removeEmptyRows() {
        const rows = this.rowsContainer.querySelectorAll('.mc-lsp-row');
        rows.forEach(row => {
            const richSelect = row.querySelector('[data-lsp-list]');
            const hiddenInput = richSelect ? richSelect.querySelector('[data-rich-select-input]') : null;
            if (hiddenInput && !hiddenInput.value && rows.length > 1) {
                row.remove();
            }
        });
    }

    /**
     * Hide already-selected lists from other rows' dropdowns.
     * Each row can only pick a list not yet chosen by another row.
     */
    _syncListOptions() {
        const rows = this.rowsContainer.querySelectorAll('.mc-lsp-row');

        // Collect selected list UIDs per row
        const selectedByRow = new Map();
        rows.forEach(row => {
            const input = row.querySelector('[data-lsp-list] [data-rich-select-input]');
            if (input && input.value) {
                selectedByRow.set(row, input.value);
            }
        });

        // For each row, hide options that are selected in OTHER rows
        rows.forEach(row => {
            const otherSelected = new Set();
            selectedByRow.forEach((uid, r) => {
                if (r !== row) otherSelected.add(uid);
            });

            const options = row.querySelectorAll('[data-lsp-list] .mc-rich-select-option');
            options.forEach(opt => {
                if (otherSelected.has(opt.dataset.value)) {
                    opt.style.display = 'none';
                } else {
                    opt.style.display = '';
                }
            });
        });
    }

    _dispatch(name) {
        this.el.dispatchEvent(new CustomEvent(name, { bubbles: true }));
    }
}

// Auto-init (handles both DOMContentLoaded and already-loaded cases)
function _initListSegmentPickers() {
    document.querySelectorAll('[data-list-segment-picker]').forEach(el => {
        if (!el._listSegmentPicker) {
            el._listSegmentPicker = new McListSegmentPicker(el);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', _initListSegmentPickers);
} else {
    _initListSegmentPickers();
}

window.McListSegmentPicker = McListSegmentPicker;

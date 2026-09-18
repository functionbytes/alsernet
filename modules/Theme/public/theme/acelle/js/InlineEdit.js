/**
 * InlineEdit — Click-to-edit inline values
 *
 * Usage:
 *   <div class="mc-inline-edit" data-inline-edit
 *        data-field="company_name"
 *        data-value="Acme Inc"
 *        data-url="/api/update"
 *        data-type="text"
 *        data-placeholder="Click to edit...">
 *     <span class="mc-inline-edit-value">
 *       Acme Inc
 *       <span class="material-symbols-rounded mc-inline-edit-icon">edit</span>
 *     </span>
 *     <span class="mc-inline-edit-control">
 *       <input type="text" class="mc-form-input" value="Acme Inc">
 *       <span class="mc-inline-edit-actions">
 *         <button type="button" class="mc-inline-edit-save" title="Save">
 *           <span class="material-symbols-rounded" style="font-size:16px">check</span>
 *         </button>
 *         <button type="button" class="mc-inline-edit-cancel" title="Cancel">
 *           <span class="material-symbols-rounded" style="font-size:16px">close</span>
 *         </button>
 *       </span>
 *     </span>
 *   </div>
 *
 * Attributes:
 *   data-inline-edit     — activates the component
 *   data-field           — field name sent to server
 *   data-value           — current value (source of truth)
 *   data-url             — POST endpoint (optional; omit for client-only)
 *   data-type            — text | number | date | select | textarea (default: text)
 *   data-placeholder     — placeholder when empty
 *   data-options         — JSON array for select: [{"value":"a","label":"A"},...]
 *   data-save-on-blur    — "true" (default) or "false"
 *
 * Events dispatched on the element:
 *   mc:inline-edit:save    — { field, value, oldValue }
 *   mc:inline-edit:cancel  — { field, value }
 *   mc:inline-edit:error   — { field, error }
 */
(function () {
    'use strict';

    function McInlineEdit(el) {
        this.el = el;
        this.field = el.dataset.field || '';
        this.url = el.dataset.url || '';
        this.type = el.dataset.type || 'text';
        this.placeholder = el.dataset.placeholder || 'Click to edit...';
        this.saveOnBlur = el.dataset.saveOnBlur !== 'false';
        this.originalValue = el.dataset.value || '';

        this.valueEl = el.querySelector('.mc-inline-edit-value');
        this.controlEl = el.querySelector('.mc-inline-edit-control');
        this.input = this.controlEl
            ? this.controlEl.querySelector('input, textarea, select')
            : null;

        this._bindEvents();
        this._updateDisplay();
    }

    McInlineEdit.prototype._bindEvents = function () {
        var self = this;

        // Click value → enter edit mode
        if (this.valueEl) {
            this.valueEl.addEventListener('click', function (e) {
                e.preventDefault();
                self.open();
            });
        }

        // Save button
        var saveBtn = this.el.querySelector('.mc-inline-edit-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function (e) {
                e.preventDefault();
                self.save();
            });
        }

        // Cancel button
        var cancelBtn = this.el.querySelector('.mc-inline-edit-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function (e) {
                e.preventDefault();
                self.cancel();
            });
        }

        // Keyboard: Enter to save, Escape to cancel
        if (this.input) {
            this.input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && self.type !== 'textarea') {
                    e.preventDefault();
                    self.save();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    self.cancel();
                }
            });

            // Blur → save (unless clicking action buttons)
            if (this.saveOnBlur) {
                this.input.addEventListener('blur', function (e) {
                    // Delay to allow button clicks to register first
                    setTimeout(function () {
                        if (self.el.classList.contains('is-editing') &&
                            !self.el.contains(document.activeElement)) {
                            self.save();
                        }
                    }, 150);
                });
            }
        }
    };

    McInlineEdit.prototype.open = function () {
        if (this.el.classList.contains('is-editing')) return;
        this.el.classList.add('is-editing');
        if (this.input) {
            this.input.value = this.originalValue;
            this.input.focus();
            // Select all text for quick replacement
            if (this.input.select && this.type !== 'select') {
                this.input.select();
            }
        }
    };

    McInlineEdit.prototype.close = function () {
        this.el.classList.remove('is-editing');
        this.el.classList.remove('is-saving');
    };

    McInlineEdit.prototype.cancel = function () {
        if (this.input) this.input.value = this.originalValue;
        this.close();
        this._dispatch('mc:inline-edit:cancel', {
            field: this.field,
            value: this.originalValue
        });
    };

    McInlineEdit.prototype.save = function () {
        var newValue = this.input ? this.input.value : '';
        var oldValue = this.originalValue;

        // No change — just close
        if (newValue === oldValue) {
            this.close();
            return;
        }

        this.originalValue = newValue;
        this.el.dataset.value = newValue;
        this._updateDisplay();

        // If URL provided, POST to server
        if (this.url) {
            this._saveToServer(newValue, oldValue);
        } else {
            this.close();
            this._flashSaved();
            this._dispatch('mc:inline-edit:save', {
                field: this.field,
                value: newValue,
                oldValue: oldValue
            });
        }
    };

    McInlineEdit.prototype._saveToServer = function (newValue, oldValue) {
        var self = this;
        this.el.classList.add('is-saving');

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        var headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken.content;

        var body = {};
        body[this.field] = newValue;

        fetch(this.url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(body)
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Server error ' + response.status);
            return response.json();
        })
        .then(function () {
            self.close();
            self._flashSaved();
            self._dispatch('mc:inline-edit:save', {
                field: self.field,
                value: newValue,
                oldValue: oldValue
            });
        })
        .catch(function (err) {
            // Revert on error
            self.originalValue = oldValue;
            self.el.dataset.value = oldValue;
            self._updateDisplay();
            self.close();
            self._dispatch('mc:inline-edit:error', {
                field: self.field,
                error: err.message
            });
            if (window.McNotify) {
                McNotify.error('Could not save: ' + err.message);
            }
        });
    };

    McInlineEdit.prototype._updateDisplay = function () {
        if (!this.valueEl) return;
        var textNode = this.valueEl.childNodes[0]; // first text node
        var display = this.originalValue;

        // For select, show label not value
        if (this.type === 'select' && this.input) {
            var selected = this.input.querySelector('option[value="' + this.originalValue + '"]');
            if (selected) display = selected.textContent;
        }

        if (!display) {
            this.valueEl.classList.add('is-empty');
            display = this.placeholder;
        } else {
            this.valueEl.classList.remove('is-empty');
        }

        // Update text content (preserve the icon span)
        if (textNode && textNode.nodeType === 3) {
            textNode.textContent = display + ' ';
        } else {
            // Insert text before icon
            var icon = this.valueEl.querySelector('.mc-inline-edit-icon');
            if (icon) {
                this.valueEl.insertBefore(document.createTextNode(display + ' '), icon);
            } else {
                this.valueEl.textContent = display;
            }
        }
    };

    McInlineEdit.prototype._flashSaved = function () {
        var self = this;
        if (this.valueEl) {
            this.valueEl.classList.add('is-saved');
            setTimeout(function () {
                self.valueEl.classList.remove('is-saved');
            }, 700);
        }
    };

    McInlineEdit.prototype._dispatch = function (name, detail) {
        this.el.dispatchEvent(new CustomEvent(name, {
            bubbles: true,
            detail: detail
        }));
    };

    // ── Auto-init ──
    function initAll(root) {
        (root || document).querySelectorAll('[data-inline-edit]').forEach(function (el) {
            if (!el._mcInlineEdit) {
                el._mcInlineEdit = new McInlineEdit(el);
            }
        });
    }

    // Init on DOMContentLoaded and expose for dynamic content
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }

    window.McInlineEdit = McInlineEdit;
    window.McInlineEdit.initAll = initAll;
})();

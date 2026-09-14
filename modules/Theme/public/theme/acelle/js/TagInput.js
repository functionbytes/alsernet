/**
 * mc-tag-input — Rich tag input with pill chips
 *
 * Usage:
 *   <div class="mc-tag-input" data-mc-tag-input data-name="options[tags][]">
 *       <div class="mc-tag-input-chips" data-mc-tag-chips></div>
 *       <input type="text" class="mc-tag-input-field" data-mc-tag-field
 *              placeholder="Type and press Enter">
 *   </div>
 *
 * Options (data attributes):
 *   data-name          — name attribute for hidden inputs (default: "tags[]")
 *   data-value         — comma-separated initial values (optional)
 *   data-max           — max number of tags (optional, 0 = unlimited)
 *   data-allow-duplicates — allow duplicate tags (default: false)
 */
window.McTagInput = (function () {
    'use strict';

    var DISMISS_SVG = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2">' +
        '<path d="M3 3l6 6M9 3l-6 6"/></svg>';

    function TagInput(el) {
        this.el = el;
        this.chipsContainer = el.querySelector('[data-mc-tag-chips]');
        this.field = el.querySelector('[data-mc-tag-field]');
        this.name = el.getAttribute('data-name') || 'tags[]';
        this.max = parseInt(el.getAttribute('data-max') || '0', 10);
        this.allowDuplicates = el.hasAttribute('data-allow-duplicates');
        this.tags = [];

        this._init();
    }

    TagInput.prototype._init = function () {
        var self = this;

        // Load initial values from data-value or existing hidden inputs
        var initialValue = this.el.getAttribute('data-value');
        if (initialValue) {
            initialValue.split(',').forEach(function (t) {
                var trimmed = t.trim();
                if (trimmed) self._addTag(trimmed, true);
            });
        }

        // Click on container focuses the text field
        this.el.addEventListener('click', function () {
            self.field.focus();
        });

        // Key events on input field
        this.field.addEventListener('keydown', function (e) {
            // Enter or comma → add tag
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                var val = self.field.value.replace(/,/g, '').trim();
                if (val) {
                    self._addTag(val);
                    self.field.value = '';
                }
            }
            // Backspace on empty field → remove last tag
            else if (e.key === 'Backspace' && !self.field.value && self.tags.length > 0) {
                self._removeTagAt(self.tags.length - 1);
            }
        });

        // Handle paste with commas
        this.field.addEventListener('paste', function (e) {
            var clipboardData = e.clipboardData || window.clipboardData;
            var text = clipboardData.getData('text');
            if (text && text.indexOf(',') !== -1) {
                e.preventDefault();
                text.split(',').forEach(function (t) {
                    var trimmed = t.trim();
                    if (trimmed) self._addTag(trimmed);
                });
                self.field.value = '';
            }
        });

        // Blur → add current text as tag if non-empty
        this.field.addEventListener('blur', function () {
            var val = self.field.value.replace(/,/g, '').trim();
            if (val) {
                self._addTag(val);
                self.field.value = '';
            }
        });

        // Mark as initialized
        this.el.setAttribute('data-mc-tag-initialized', 'true');
    };

    TagInput.prototype._addTag = function (text, silent) {
        // Check max
        if (this.max > 0 && this.tags.length >= this.max) return;

        // Check duplicates
        if (!this.allowDuplicates) {
            var lower = text.toLowerCase();
            for (var i = 0; i < this.tags.length; i++) {
                if (this.tags[i].toLowerCase() === lower) return;
            }
        }

        this.tags.push(text);
        this._renderChip(text, this.tags.length - 1);
        this._syncHidden();

        if (!silent) {
            this.el.dispatchEvent(new CustomEvent('mc-tag-change', {
                detail: { tags: this.tags.slice(), action: 'add', tag: text }
            }));
        }
    };

    TagInput.prototype._removeTagAt = function (index) {
        var removed = this.tags.splice(index, 1)[0];
        this._rebuildChips();
        this._syncHidden();

        this.el.dispatchEvent(new CustomEvent('mc-tag-change', {
            detail: { tags: this.tags.slice(), action: 'remove', tag: removed }
        }));
    };

    TagInput.prototype._renderChip = function (text, index) {
        var self = this;
        var chip = document.createElement('span');
        chip.className = 'mc-tag-input-chip';
        chip.setAttribute('data-tag-index', index);

        var label = document.createElement('span');
        label.textContent = text;
        chip.appendChild(label);

        var removeBtn = document.createElement('span');
        removeBtn.className = 'mc-tag-input-chip-remove';
        removeBtn.innerHTML = DISMISS_SVG;
        removeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            // Find current index by iterating chips
            var chips = self.chipsContainer.querySelectorAll('.mc-tag-input-chip');
            for (var i = 0; i < chips.length; i++) {
                if (chips[i] === chip) {
                    self._removeTagAt(i);
                    break;
                }
            }
        });
        chip.appendChild(removeBtn);

        this.chipsContainer.appendChild(chip);
    };

    TagInput.prototype._rebuildChips = function () {
        this.chipsContainer.innerHTML = '';
        for (var i = 0; i < this.tags.length; i++) {
            this._renderChip(this.tags[i], i);
        }
    };

    TagInput.prototype._syncHidden = function () {
        // Remove existing hidden inputs
        var existing = this.el.querySelectorAll('input[type="hidden"][data-mc-tag-hidden]');
        for (var i = 0; i < existing.length; i++) {
            existing[i].remove();
        }

        // Create one hidden input per tag
        for (var j = 0; j < this.tags.length; j++) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = this.name;
            hidden.value = this.tags[j];
            hidden.setAttribute('data-mc-tag-hidden', 'true');
            this.el.appendChild(hidden);
        }
    };

    // Public API
    TagInput.prototype.getTags = function () {
        return this.tags.slice();
    };

    TagInput.prototype.setTags = function (tags) {
        this.tags = [];
        this.chipsContainer.innerHTML = '';
        var self = this;
        tags.forEach(function (t) {
            self._addTag(t, true);
        });
    };

    TagInput.prototype.clear = function () {
        this.tags = [];
        this.chipsContainer.innerHTML = '';
        this._syncHidden();
    };

    // Auto-init all [data-mc-tag-input] elements
    function initAll(container) {
        var root = container || document;
        var elements = root.querySelectorAll('[data-mc-tag-input]:not([data-mc-tag-initialized])');
        var instances = [];
        elements.forEach(function (el) {
            instances.push(new TagInput(el));
        });
        return instances;
    }

    // Auto-init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }

    return {
        TagInput: TagInput,
        initAll: initAll
    };
})();

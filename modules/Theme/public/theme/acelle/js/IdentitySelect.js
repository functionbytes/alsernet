/**
 * McIdentitySelect — Reusable AJAX autocomplete for verified sending identities.
 *
 * Fetches verified senders + domains from the server, shows a dropdown with
 * real-time keyword filtering, keyboard navigation, and validation.
 *
 * Usage (declarative):
 *   <div class="mc-identity-select" data-mc-identity-select
 *        data-url="/rui/campaigns/identity-dropbox"
 *        data-name="from_email"
 *        data-value="user@example.com"
 *        data-empty-message="No verified senders found"
 *        data-error-message="<p>This email is not verified...</p>"
 *        data-header="Verified senders">
 *       <input type="text" class="mc-form-input mc-identity-select-input"
 *              name="from_email" placeholder="you@example.com" autocomplete="off">
 *   </div>
 *
 * Options (data attributes):
 *   data-url            — AJAX endpoint returning JSON array of suggestions
 *   data-name           — input name attribute
 *   data-value          — initial value
 *   data-empty-message  — message when no identities exist at all
 *   data-error-message  — HTML message when typed value is not a verified identity
 *   data-header         — dropdown header text
 *   data-link-target    — optional, ID of another mc-identity-select to auto-fill on selection
 *
 * JSON response format (each item):
 *   { text: "Display Name", value: "email@example.com", desc: "highlighted HTML", subfix: "domain.com" }
 *
 * Events:
 *   mc-identity-change  — fired on selection/validation change, detail: { value, valid }
 */
window.McIdentitySelect = (function () {
    'use strict';

    var LOADER_SVG = '<svg class="mc-identity-select-spinner" width="20" height="20" viewBox="0 0 20 20"><circle cx="10" cy="10" r="8" stroke="var(--color-border-strong)" stroke-width="2" fill="none" stroke-dasharray="40 20" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 10 10" to="360 10 10" dur="0.8s" repeatCount="indefinite"/></circle></svg>';

    function IdentitySelect(el) {
        this.el = el;
        this.input = el.querySelector('.mc-identity-select-input');
        if (!this.input) return;

        this.url = el.getAttribute('data-url') || '';
        this.emptyMessage = el.getAttribute('data-empty-message') || 'No verified senders';
        this.errorMessage = el.getAttribute('data-error-message') || '';
        this.headerText = el.getAttribute('data-header') || '';
        this.linkTarget = el.getAttribute('data-link-target') || '';

        // Set initial value
        var initValue = el.getAttribute('data-value') || '';
        if (initValue && !this.input.value) {
            this.input.value = initValue;
        }

        this.xhr = null;
        this.currentIndex = -1;
        this.items = [];
        this.isValid = true;
        this.debounceTimer = null;

        this._buildDropdown();
        this._bindEvents();

        // Initial load to validate current value
        var self = this;
        if (this.input.value) {
            this._loadSuggestions(function () {
                self._validateValue();
            });
        }

        el.setAttribute('data-mc-identity-initialized', 'true');
    }

    // ─── DOM Construction ───────────────────────────────────────

    IdentitySelect.prototype._buildDropdown = function () {
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'mc-identity-select-dropdown';

        if (this.headerText) {
            this.dropdownHeader = document.createElement('div');
            this.dropdownHeader.className = 'mc-identity-select-dropdown-header';
            this.dropdownHeader.textContent = this.headerText;
            this.dropdown.appendChild(this.dropdownHeader);
        }

        this.list = document.createElement('ul');
        this.list.className = 'mc-identity-select-list';
        this.dropdown.appendChild(this.list);

        this.el.appendChild(this.dropdown);

        // Error container (outside dropdown)
        this.errorEl = document.createElement('div');
        this.errorEl.className = 'mc-identity-select-error';
        this.errorEl.style.display = 'none';
        this.el.appendChild(this.errorEl);
    };

    // ─── Events ─────────────────────────────────────────────────

    IdentitySelect.prototype._bindEvents = function () {
        var self = this;

        this.input.addEventListener('focus', function () {
            self._open();
            self._loadSuggestions();
        });

        this.input.addEventListener('input', function () {
            clearTimeout(self.debounceTimer);
            self.debounceTimer = setTimeout(function () {
                self._loadSuggestions();
            }, 200);
        });

        this.input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                self._moveHighlight(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                self._moveHighlight(-1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                self._selectHighlighted();
            } else if (e.key === 'Escape') {
                self._close();
            }
        });

        this.input.addEventListener('blur', function () {
            // Delay to allow click on dropdown item
            setTimeout(function () {
                self._close();
                self._validateValue();
            }, 200);
        });

        // Delegate click on list items
        this.list.addEventListener('mousedown', function (e) {
            // mousedown instead of click to fire before blur
            var li = e.target.closest('.mc-identity-select-item');
            if (li) {
                e.preventDefault();
                self._selectItem(li);
            }
        });
    };

    // ─── AJAX ───────────────────────────────────────────────────

    IdentitySelect.prototype._loadSuggestions = function (callback) {
        var self = this;

        // Show loader
        this.list.innerHTML = '<li class="mc-identity-select-loader">' + LOADER_SVG + '</li>';

        if (this.xhr && this.xhr.readyState !== 4) {
            this.xhr.abort();
        }

        this.xhr = new XMLHttpRequest();
        this.xhr.open('GET', this.url + '?keyword=' + encodeURIComponent(this.input.value.trim()), true);
        this.xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        this.xhr.onload = function () {
            if (self.xhr.status === 200) {
                try {
                    var data = JSON.parse(self.xhr.responseText);
                    self._renderItems(data);
                } catch (e) {
                    self._renderEmpty();
                }
            } else {
                self._renderEmpty();
            }
            if (typeof callback === 'function') callback();
        };

        this.xhr.onerror = function () {
            self._renderEmpty();
            if (typeof callback === 'function') callback();
        };

        this.xhr.send();
    };

    // ─── Rendering ──────────────────────────────────────────────

    IdentitySelect.prototype._renderItems = function (data) {
        this.list.innerHTML = '';
        this.items = [];
        this.currentIndex = -1;

        if (!data || !data.length) {
            this._renderEmpty();
            return;
        }

        for (var i = 0; i < data.length; i++) {
            var row = data[i];

            // Warning items (not selectable)
            if (row._warning) {
                var warnLi = document.createElement('li');
                warnLi.className = 'mc-identity-select-warning';
                warnLi.innerHTML = row._warning;
                this.list.appendChild(warnLi);
                continue;
            }

            if (typeof row.value === 'undefined' && typeof row.subfix === 'undefined') continue;

            var li = document.createElement('li');
            li.className = 'mc-identity-select-item';
            li.setAttribute('data-value', row.value || '');
            li.setAttribute('data-subfix', row.subfix || '');

            var content = '<div class="mc-identity-select-item-content">';
            content += '<span class="mc-identity-select-item-label">' + this._escapeHtml(row.text || '') + '</span>';
            if (row.desc) {
                content += '<span class="mc-identity-select-item-desc">' + row.desc + '</span>';
            }
            content += '</div>';

            li.innerHTML = content;
            this.list.appendChild(li);
            this.items.push(li);
        }

        if (this.items.length === 0) {
            this._renderEmpty();
        }
    };

    IdentitySelect.prototype._renderEmpty = function () {
        this.list.innerHTML = '<li class="mc-identity-select-empty">' + this._escapeHtml(this.emptyMessage) + '</li>';
        this.items = [];
        this.currentIndex = -1;
    };

    // ─── Selection & Navigation ─────────────────────────────────

    IdentitySelect.prototype._moveHighlight = function (direction) {
        if (!this.items.length) return;

        // Remove current highlight
        if (this.currentIndex >= 0 && this.currentIndex < this.items.length) {
            this.items[this.currentIndex].classList.remove('mc-identity-select-item--active');
        }

        this.currentIndex += direction;
        if (this.currentIndex < 0) this.currentIndex = this.items.length - 1;
        if (this.currentIndex >= this.items.length) this.currentIndex = 0;

        this.items[this.currentIndex].classList.add('mc-identity-select-item--active');
        this.items[this.currentIndex].scrollIntoView({ block: 'nearest' });
    };

    IdentitySelect.prototype._selectHighlighted = function () {
        if (this.currentIndex >= 0 && this.currentIndex < this.items.length) {
            this._selectItem(this.items[this.currentIndex]);
        }
    };

    IdentitySelect.prototype._selectItem = function (li) {
        var value = li.getAttribute('data-value');
        var subfix = li.getAttribute('data-subfix');

        if (value) {
            this.input.value = value;
        } else if (subfix) {
            // Domain-based: keep local part, replace domain
            var local = this.input.value.split('@')[0] || '';
            this.input.value = local + '@' + subfix;
        }

        this._close();
        this._validateValue();
        this._syncLinkedTarget();

        // Dispatch change event
        this.el.dispatchEvent(new CustomEvent('mc-identity-change', {
            detail: { value: this.input.value, valid: this.isValid }
        }));
    };

    // ─── Validation ─────────────────────────────────────────────

    IdentitySelect.prototype._validateValue = function () {
        var val = this.input.value.trim().toLowerCase();
        var valid = false;

        if (!val) {
            // Empty is not invalid from identity perspective (required is handled by form validation)
            valid = true;
        } else {
            for (var i = 0; i < this.items.length; i++) {
                var itemValue = (this.items[i].getAttribute('data-value') || '').trim().toLowerCase();
                var itemSubfix = (this.items[i].getAttribute('data-subfix') || '').trim().toLowerCase();

                // Exact email match
                if (itemValue && val === itemValue) {
                    valid = true;
                    break;
                }

                // Domain match: user typed something@verified-domain
                if (itemSubfix && val.indexOf('@') !== -1) {
                    var typedDomain = val.split('@')[1];
                    if (typedDomain && typedDomain === itemSubfix) {
                        valid = true;
                        break;
                    }
                }

                // Still typing (no @ yet or ends with @) — don't show error yet
                if (val.indexOf('@') === -1 || val.charAt(val.length - 1) === '@') {
                    valid = true;
                    break;
                }
            }

            // If no items at all but allowUnverified could be true, don't block
            if (this.items.length === 0 && !this.errorMessage) {
                valid = true;
            }
        }

        this.isValid = valid;

        if (!valid && this.errorMessage) {
            this.errorEl.innerHTML = this.errorMessage;
            this.errorEl.style.display = '';
            this.input.classList.add('is-invalid');
            if (this.dropdownHeader) {
                this.dropdownHeader.classList.add('mc-identity-select-dropdown-header--warning');
            }
        } else {
            this.errorEl.style.display = 'none';
            this.errorEl.innerHTML = '';
            this.input.classList.remove('is-invalid');
            if (this.dropdownHeader) {
                this.dropdownHeader.classList.remove('mc-identity-select-dropdown-header--warning');
            }
        }

        // Dispatch validation event
        this.el.dispatchEvent(new CustomEvent('mc-identity-change', {
            detail: { value: this.input.value, valid: this.isValid }
        }));
    };

    // ─── Linked Target (auto-fill reply-to from from_email) ────

    IdentitySelect.prototype._syncLinkedTarget = function () {
        if (!this.linkTarget) return;
        var targetEl = document.getElementById(this.linkTarget);
        if (!targetEl) return;

        var targetInput = targetEl.querySelector('.mc-identity-select-input');
        if (targetInput && !targetInput.value) {
            targetInput.value = this.input.value;
            // Trigger validation on the target
            targetEl.dispatchEvent(new CustomEvent('mc-identity-change', {
                detail: { value: targetInput.value, valid: true }
            }));
        }
    };

    // ─── Open / Close ───────────────────────────────────────────

    IdentitySelect.prototype._open = function () {
        this.el.classList.add('mc-identity-select--open');
    };

    IdentitySelect.prototype._close = function () {
        this.el.classList.remove('mc-identity-select--open');
        this.currentIndex = -1;
        // Remove all highlights
        for (var i = 0; i < this.items.length; i++) {
            this.items[i].classList.remove('mc-identity-select-item--active');
        }
    };

    // ─── Helpers ────────────────────────────────────────────────

    IdentitySelect.prototype._escapeHtml = function (str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    IdentitySelect.prototype.getValue = function () {
        return this.input.value;
    };

    IdentitySelect.prototype.isValidIdentity = function () {
        return this.isValid;
    };

    // ─── Auto-init ──────────────────────────────────────────────

    function initAll(container) {
        var root = container || document;
        var elements = root.querySelectorAll('[data-mc-identity-select]:not([data-mc-identity-initialized])');
        var instances = [];
        elements.forEach(function (el) {
            var instance = new IdentitySelect(el);
            el._identitySelect = instance;
            instances.push(instance);
        });
        return instances;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }

    return {
        IdentitySelect: IdentitySelect,
        initAll: initAll
    };
})();

/**
 * DynamicDropdownControl — generic, business-agnostic dropdown attached to
 * an existing <input>. Handles UI mechanics only:
 *
 *   - open/close on focus/blur (mousedown-before-blur trick so item clicks land)
 *   - fire `onKeyChange(query, ctrl)` on focus + every input event so the
 *     caller can compute (sync) or fetch (AJAX) suggestions and call
 *     `ctrl.showDropdown(items)` when ready
 *   - render items: label + optional `sub` muted text + optional `group` header
 *   - keyboard nav (Arrow Up / Down, Enter, Escape) with scrollIntoView
 *   - on pick: assign `input.value = item.value` (default), then fire `onPick(item, ctrl)`
 *
 * The library is opaque about suggestion sources, debouncing, validation,
 * placeholders, and initial values — those belong to the caller. The host
 * page also owns the <input> element; this control just binds behavior to it
 * and inserts a sibling dropdown after it.
 *
 *   const input = document.querySelector('input.my-field');
 *   const ctrl = DynamicDropdownControl.attach(input, {
 *     onKeyChange: (query, ctrl, { source }) => {
 *       // source = 'focus' (just opened) | 'input' (user typed)
 *       if (source === 'focus') {
 *         ctrl.showDropdown([], 'Type to search…');   // help message
 *         return;
 *       }
 *       // sync:
 *       ctrl.showDropdown(myLocalCompute(query));
 *       // or async:
 *       fetch('/api/search?q=' + encodeURIComponent(query))
 *         .then(r => r.json())
 *         .then(items => ctrl.showDropdown(items, 'No matches'));
 *     },
 *     onPick: (item, ctrl) => { ... },     // optional
 *   });
 *
 *   ctrl.showDropdown(items, emptyMessage?, opts?);   // populate
 *     opts.html = true → emptyMessage is rendered as HTML (caller must sanitise).
 *     Default: textContent.
 *   ctrl.hideDropdown();                              // close
 *   ctrl.destroy();                                   // unbind + remove dropdown
 *
 *   Item shape: { value: string, label: string, sub?: string, group?: string }
 *
 * Markup: dropdown is inserted as the input's immediate next sibling and
 * positioned absolutely. Wrap the input in a `position: relative` parent
 * if you want the dropdown anchored to it (the lib doesn't add wrappers).
 *
 * CSS classes used (all `.ddc-*` namespaced to avoid collision):
 *   .ddc-dropdown / .ddc-dropdown--open
 *   .ddc-list / .ddc-group-header / .ddc-item / .ddc-item--active
 *   .ddc-item-label / .ddc-item-sub / .ddc-empty
 */
(function () {
  'use strict';

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function el(tag, cls) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    return n;
  }

  function DynamicDropdownControl(input, opts) {
    if (!input || input.nodeType !== 1) {
      throw new Error('DynamicDropdownControl: input element required');
    }
    if (input.__ddc) return input.__ddc;  // idempotent

    this.input        = input;
    this.opts         = opts || {};
    this._items       = [];   // [{el, item}]
    this._currentIdx  = -1;
    this._isOpen      = false;
    this._handlers    = {};   // for cleanup in destroy()

    this._build();
    this._bind();

    input.__ddc = this;
  }

  DynamicDropdownControl.prototype._build = function () {
    this.dropdown = el('div', 'ddc-dropdown');
    this.list     = el('ul', 'ddc-list');
    this.dropdown.appendChild(this.list);
    // Insert dropdown as next sibling of input — caller controls positioning
    // by wrapping the input in a `position: relative` container if desired.
    if (this.input.nextSibling) {
      this.input.parentNode.insertBefore(this.dropdown, this.input.nextSibling);
    } else {
      this.input.parentNode.appendChild(this.dropdown);
    }
  };

  DynamicDropdownControl.prototype._bind = function () {
    var self = this;

    this._handlers.focus  = function () { self._open(); self._fireKeyChange('focus'); };
    this._handlers.input  = function () { self._open(); self._fireKeyChange('input'); };
    this._handlers.blur   = function () { setTimeout(function () { self._close(); }, 200); };
    this._handlers.key    = function (e) { self._handleKey(e); };
    this._handlers.mousedown = function (e) {
      // mousedown (not click) so it fires BEFORE the input's blur
      var li = e.target.closest('.ddc-item');
      if (!li) return;
      e.preventDefault();
      var idx = parseInt(li.getAttribute('data-idx'), 10);
      if (!isNaN(idx)) self._pickIndex(idx);
    };

    this.input.addEventListener('focus',   this._handlers.focus);
    this.input.addEventListener('input',   this._handlers.input);
    this.input.addEventListener('blur',    this._handlers.blur);
    this.input.addEventListener('keydown', this._handlers.key);
    this.list.addEventListener('mousedown', this._handlers.mousedown);
  };

  DynamicDropdownControl.prototype._handleKey = function (e) {
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        if (!this._isOpen) { this._open(); this._fireKeyChange('focus'); return; }
        this._move(1);
        break;
      case 'ArrowUp':
        e.preventDefault();
        if (!this._isOpen) return;
        this._move(-1);
        break;
      case 'Enter':
        if (!this._isOpen || this._currentIdx < 0) return;  // let form submit through
        e.preventDefault();
        this._pickIndex(this._currentIdx);
        break;
      case 'Escape':
        if (this._isOpen) { e.preventDefault(); this._close(); }
        break;
    }
  };

  DynamicDropdownControl.prototype._fireKeyChange = function (source) {
    if (typeof this.opts.onKeyChange !== 'function') return;
    try {
      // source: 'focus' (input gained focus / dropdown opened with current value)
      //       | 'input' (user typed — value changed)
      // Caller can show different content per source (e.g. help text on focus,
      // real suggestions on input).
      this.opts.onKeyChange(this.input.value, this, { source: source || 'input' });
    } catch (err) {
      // HARD RULE 1 — surface, don't swallow.
      console.error('[DynamicDropdownControl] onKeyChange threw:', err);
      this.showDropdown([], 'Error: ' + (err && err.message ? err.message : String(err)));
    }
  };

  DynamicDropdownControl.prototype._open = function () {
    if (this._isOpen) return;
    this._isOpen = true;
    this.dropdown.classList.add('ddc-dropdown--open');
  };

  DynamicDropdownControl.prototype._close = function () {
    if (!this._isOpen) return;
    this._isOpen = false;
    this.dropdown.classList.remove('ddc-dropdown--open');
    this._currentIdx = -1;
  };

  DynamicDropdownControl.prototype._move = function (delta) {
    if (!this._items.length) return;
    if (this._currentIdx >= 0) {
      this._items[this._currentIdx].el.classList.remove('ddc-item--active');
    }
    this._currentIdx = (this._currentIdx + delta + this._items.length) % this._items.length;
    var active = this._items[this._currentIdx];
    active.el.classList.add('ddc-item--active');
    active.el.scrollIntoView({ block: 'nearest' });
  };

  DynamicDropdownControl.prototype._pickIndex = function (idx) {
    var entry = this._items[idx];
    if (!entry) return;
    // Default behavior: assign canonical value to the input. Caller's onPick
    // can read input.value and override if needed.
    this.input.value = entry.item.value;
    this._close();
    if (typeof this.opts.onPick === 'function') {
      try { this.opts.onPick(entry.item, this); }
      catch (err) { console.error('[DynamicDropdownControl] onPick threw:', err); }
    }
    this.input.dispatchEvent(new CustomEvent('ddc-pick', {
      bubbles: true, detail: { item: entry.item }
    }));
  };

  // ── Public API ──

  DynamicDropdownControl.prototype.showDropdown = function (items, emptyMessage, opts) {
    items = items || [];
    this.list.innerHTML = '';
    this._items = [];
    this._currentIdx = -1;
    this._open();

    if (!items.length) {
      var em = el('li', 'ddc-empty');
      // opts.html: caller asserts the message is safe HTML (e.g. inline <kbd>
      // chips). Caller is responsible for sanitisation. Default = textContent.
      if (opts && opts.html) em.innerHTML = emptyMessage || '';
      else                   em.textContent = emptyMessage || 'No matches';
      this.list.appendChild(em);
      return;
    }

    var lastGroup = null;
    for (var i = 0; i < items.length; i++) {
      var it = items[i];
      if (it.group && it.group !== lastGroup) {
        var hdr = el('li', 'ddc-group-header');
        hdr.textContent = it.group;
        this.list.appendChild(hdr);
        lastGroup = it.group;
      }
      var li = el('li', 'ddc-item');
      li.setAttribute('data-idx', String(this._items.length));
      li.innerHTML =
        '<span class="ddc-item-label">' + escapeHtml(it.label) + '</span>' +
        (it.sub ? '<span class="ddc-item-sub">' + escapeHtml(it.sub) + '</span>' : '');
      this.list.appendChild(li);
      this._items.push({ el: li, item: it });
    }

    // Auto-highlight first item so Enter immediately picks it (no need to
    // press ArrowDown first). Standard autocomplete convention (Linear /
    // Notion / Google Calendar). Help-on-focus path (empty items) keeps
    // currentIdx = -1, so Enter does nothing there.
    if (this._items.length > 0) {
      this._currentIdx = 0;
      this._items[0].el.classList.add('ddc-item--active');
    }
  };

  DynamicDropdownControl.prototype.hideDropdown = function () {
    this._close();
  };

  DynamicDropdownControl.prototype.destroy = function () {
    this.input.removeEventListener('focus',   this._handlers.focus);
    this.input.removeEventListener('input',   this._handlers.input);
    this.input.removeEventListener('blur',    this._handlers.blur);
    this.input.removeEventListener('keydown', this._handlers.key);
    if (this.dropdown && this.dropdown.parentNode) {
      this.dropdown.parentNode.removeChild(this.dropdown);
    }
    delete this.input.__ddc;
  };

  // Static factory — clearer at call sites than `new`
  DynamicDropdownControl.attach = function (input, opts) {
    return new DynamicDropdownControl(input, opts);
  };

  window.DynamicDropdownControl = DynamicDropdownControl;
})();

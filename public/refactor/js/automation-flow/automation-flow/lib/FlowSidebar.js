/**
 * FlowSidebar — generic per-node config panel.
 *
 * Pure UI component. Doesn't know about REST or any specific node type's
 * semantics — it reads form spec from `nodeTypes[type].fields(data, ctx)`
 * and emits events when user saves/cancels.
 *
 * Field types supported: text, time, date, datetime-local, number, select,
 * multiselect, tags (comma-string), duration (preset).
 *
 * Events:
 *   • 'save'   → { nodeId, data }   (after Save button)
 *   • 'cancel' → { nodeId }
 *
 * Reactive: any field with `onChange: 'rerender'` triggers re-render with
 * fresh data, so per-kind sub-forms appear/disappear (e.g. trigger key picker).
 */
(function (global) {
  'use strict';

  const DURATION_PRESETS = [
    { value: 'PT1M',  label: '1 minute'   }, { value: 'PT5M',  label: '5 minutes'  },
    { value: 'PT15M', label: '15 minutes' }, { value: 'PT30M', label: '30 minutes' },
    { value: 'PT1H',  label: '1 hour'     }, { value: 'PT2H',  label: '2 hours'    },
    { value: 'PT4H',  label: '4 hours'    }, { value: 'PT8H',  label: '8 hours'    },
    { value: 'PT12H', label: '12 hours'   },
    { value: 'P1D',   label: '1 day'      }, { value: 'P2D',   label: '2 days'     },
    { value: 'P3D',   label: '3 days'     }, { value: 'P5D',   label: '5 days'     },
    { value: 'P1W',   label: '1 week'     }, { value: 'P2W',   label: '2 weeks'    },
    { value: 'P1M',   label: '1 month'    }, { value: 'P3M',   label: '3 months'   },
  ];

  class FlowSidebar {
    constructor(opts = {}) {
      this.nodeTypes = opts.nodeTypes || {};
      this.ctx       = opts.ctx || {};
      this.node      = null;
      this.formData  = {};
      this._listeners = {};
      this._mount();
    }

    setCtx(ctx) { this.ctx = ctx || {}; }

    _mount() {
      const aside = document.createElement('aside');
      aside.className = 'afe-sidebar';
      aside.innerHTML = `
        <div class="afe-sidebar-header">
          <div class="afe-sidebar-icon"><span class="material-symbols-rounded">tune</span></div>
          <div class="afe-sidebar-titles">
            <div class="afe-sidebar-kind">NODE</div>
            <div class="afe-sidebar-title">…</div>
          </div>
          <button class="afe-sidebar-close" type="button" aria-label="Close">
            <span class="material-symbols-rounded">close</span>
          </button>
        </div>
        <div class="afe-sidebar-body"></div>
        <div class="afe-sidebar-footer">
          <button class="afe-btn-cancel" type="button">Cancel</button>
          <button class="afe-btn-primary" type="button">Save</button>
        </div>`;
      document.body.appendChild(aside);
      this.el = aside;

      aside.querySelector('.afe-sidebar-close').addEventListener('click', () => this._handleCancel());
      aside.querySelector('.afe-btn-cancel').addEventListener('click', () => this._handleCancel());
      aside.querySelector('.afe-btn-primary').addEventListener('click', () => this._handleSave());

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isOpen()) this._handleCancel();
      });
    }

    isOpen() { return this.el.classList.contains('open'); }

    open(node) {
      this.node     = { id: node.id, type: node.type };
      this.formData = { ...(node.data || {}) };
      this._render();
      this.el.classList.add('open');
    }

    close() {
      this.el.classList.remove('open');
      this.node = null;
      this.formData = {};
      this._setSaving(false);
    }

    setSaving(v) { this._setSaving(v); }

    on(name, handler) {
      (this._listeners[name] = this._listeners[name] || []).push(handler);
      return () => this.off(name, handler);
    }
    off(name, handler) {
      const list = this._listeners[name];
      if (!list) return;
      const i = list.indexOf(handler);
      if (i !== -1) list.splice(i, 1);
    }
    _emit(name, payload) {
      (this._listeners[name] || []).forEach(h => {
        try { h(payload); } catch (e) { console.error(`[FlowSidebar] ${name}:`, e); }
      });
    }

    _handleCancel() {
      const id = this.node?.id;
      this.close();
      this._emit('cancel', { nodeId: id });
    }

    _handleSave() {
      if (!this.node || this._saving) return;
      this._collect();
      this._emit('save', { nodeId: this.node.id, data: { ...this.formData } });
      // Caller decides whether to close (await server, then close()).
    }

    _setSaving(v) {
      this._saving = !!v;
      const btn = this.el.querySelector('.afe-btn-primary');
      btn.disabled = !!v;
      btn.textContent = v ? 'Saving…' : 'Save';
    }

    _render() {
      const spec = this.nodeTypes[this.node.type];
      if (!spec) {
        this.el.querySelector('.afe-sidebar-body').textContent = `No editor for type: ${this.node.type}`;
        return;
      }
      this.el.querySelector('.afe-sidebar-icon').innerHTML =
        `<span class="material-symbols-rounded">${spec.icon || 'tune'}</span>`;
      this.el.querySelector('.afe-sidebar-kind').textContent  = (spec.label || this.node.type).toUpperCase();
      this.el.querySelector('.afe-sidebar-title').textContent = this.formData.title || spec.placeholderTitle || 'Untitled';

      const body = this.el.querySelector('.afe-sidebar-body');
      body.innerHTML = '';
      if (spec.description) {
        const p = document.createElement('p');
        p.className = 'afe-sidebar-desc';
        p.textContent = spec.description;
        body.appendChild(p);
      }
      const fields = typeof spec.fields === 'function' ? spec.fields(this.formData, this.ctx) : [];
      for (const field of fields) body.appendChild(this._buildField(field));
    }

    _collect() {
      const spec = this.nodeTypes[this.node.type];
      if (!spec || typeof spec.fields !== 'function') return;
      const body = this.el.querySelector('.afe-sidebar-body');
      const fields = spec.fields(this.formData, this.ctx);
      // Fresh — drop stale fields not in current spec (Rule 1: no cruft in data).
      const data = {};
      for (const field of fields) {
        const inputEl = body.querySelector(`.afe-field-input[data-field-name="${field.name}"]`);
        if (!inputEl) continue;
        data[field.name] = readField(field, inputEl);
      }
      this.formData = data;
    }

    _buildField(field) {
      const wrap = document.createElement('div');
      wrap.className = 'afe-field';

      const label = document.createElement('label');
      label.className = 'afe-field-label';
      label.textContent = field.label;
      wrap.appendChild(label);

      let input;
      switch (field.type) {
        case 'text': case 'time': case 'date': case 'datetime-local': case 'number':
          input = document.createElement('input');
          input.type = field.type;
          input.value = field.value ?? '';
          if (field.placeholder) input.placeholder = field.placeholder;
          break;
        case 'select':
          input = document.createElement('select');
          if (field.placeholder) {
            const o = document.createElement('option');
            o.value = ''; o.textContent = field.placeholder;
            o.disabled = true; o.selected = !field.value;
            input.appendChild(o);
          }
          for (const opt of field.options || []) {
            const o = document.createElement('option');
            o.value = opt.value; o.textContent = opt.label;
            if (String(opt.value) === String(field.value)) o.selected = true;
            input.appendChild(o);
          }
          break;
        case 'multiselect':
          input = document.createElement('div');
          input.className = 'afe-multiselect';
          for (const opt of field.options || []) {
            const lbl = document.createElement('label');
            lbl.className = 'afe-checkbox';
            const cb = document.createElement('input');
            cb.type = 'checkbox'; cb.value = opt.value;
            if ((field.value || []).map(String).includes(String(opt.value))) cb.checked = true;
            lbl.appendChild(cb);
            const sp = document.createElement('span');
            sp.textContent = opt.label;
            lbl.appendChild(sp);
            input.appendChild(lbl);
          }
          break;
        case 'tags':
          input = document.createElement('input');
          input.type = 'text';
          input.value = (field.value || []).join(', ');
          input.placeholder = field.placeholder || 'tag1, tag2, tag3';
          break;
        case 'duration':
          input = document.createElement('select');
          for (const opt of DURATION_PRESETS) {
            const o = document.createElement('option');
            o.value = opt.value; o.textContent = opt.label;
            if (opt.value === field.value) o.selected = true;
            input.appendChild(o);
          }
          break;
        default:
          throw new Error(`FlowSidebar: unsupported field type "${field.type}"`);
      }

      input.classList.add('afe-field-input');
      input.dataset.fieldName = field.name;
      input.dataset.fieldType = field.type;
      wrap.appendChild(input);

      if (field.help) {
        const help = document.createElement('div');
        help.className = 'afe-field-help';
        help.textContent = field.help;
        wrap.appendChild(help);
      }

      if (field.onChange === 'rerender') {
        input.addEventListener('change', () => { this._collect(); this._render(); });
      }
      return wrap;
    }
  }

  function readField(field, el) {
    switch (field.type) {
      case 'text': case 'time': case 'date': case 'datetime-local':
      case 'select': case 'duration':
        return el.value || null;
      case 'number':
        return el.value === '' ? null : Number(el.value);
      case 'multiselect':
        return [...el.querySelectorAll('input[type="checkbox"]:checked')].map(c => c.value);
      case 'tags':
        return el.value.split(',').map(s => s.trim()).filter(Boolean);
      default:
        throw new Error(`FlowSidebar.readField: unsupported field type "${field.type}"`);
    }
  }

  FlowSidebar.DURATION_PRESETS = DURATION_PRESETS;
  global.FlowSidebar = FlowSidebar;
})(window);

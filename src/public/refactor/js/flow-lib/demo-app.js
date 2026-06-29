/**
 * Flow Demo App — sample's "Acelle" equivalent for the demo screen.
 * Defines node types, factories, click handlers, and bootstraps Tree + Renderer.
 *
 * Library is opaque about types — this file is the ONLY place that knows
 * 'trigger / send / wait / condition / operation / webhook'.
 */
(function (global) {
  'use strict';

  const TYPES = {
    trigger:    { icon: 'alt_route',        label: 'TRIGGER',    outputs: [null]        },
    send:       { icon: 'forward_to_inbox', label: 'SEND EMAIL', outputs: [null]        },
    wait:       { icon: 'timer',            label: 'WAIT',       outputs: [null]        },
    wait_until: { icon: 'event',            label: 'WAIT UNTIL', outputs: [null]        },
    condition:  { icon: 'call_split',       label: 'CONDITION',  outputs: ['yes', 'no'] },
    operation:  { icon: 'checklist_rtl',    label: 'OPERATION',  outputs: [null]        },
    webhook:    { icon: 'webhook',          label: 'WEBHOOK',    outputs: [null]        },
  };

  const factories = {
    trigger:    (data = {}) => new Node({ type: 'trigger',    data: { title: 'Trigger', ...data } }),
    send:       (data = {}) => new Node({ type: 'send',       data: { title: 'Send email', ...data } }),
    wait:       (data = {}) => new Node({ type: 'wait',       data: { title: 'Wait', duration: 'PT1H', ...data } }),
    wait_until: (data = {}) => new Node({ type: 'wait_until', data: { title: 'Wait until', ...data } }),
    condition:  (data = {}) => new Node({ type: 'condition',  data: { title: 'Condition', kind: 'open', timeout: 'P1D', ...data } }),
    operation:  (data = {}) => new Node({ type: 'operation',  data: { title: 'Operation', kind: 'tag', ...data } }),
    webhook:    (data = {}) => new Node({ type: 'webhook',    data: { title: 'Webhook', method: 'POST', ...data } }),
  };

  function cardContent(node) {
    const cfg = TYPES[node.type] || { icon: 'circle', label: (node.type || 'NODE').toUpperCase() };
    const subtitle = summarize(node);
    const root = el('div', 'fl-card');
    root.innerHTML = `
      <div class="fl-card-icon"><span class="material-symbols-rounded">${cfg.icon}</span></div>
      <div class="fl-card-body">
        <div class="fl-card-kind">${escapeHtml(cfg.label)}</div>
        <div class="fl-card-title">${escapeHtml(node.data?.title || cfg.label)}</div>
        ${subtitle ? `<div class="fl-card-sub">${escapeHtml(subtitle)}</div>` : ''}
      </div>`;
    return root;
  }

  function outputs(node) {
    return TYPES[node.type]?.outputs || [null];
  }

  function summarize(node) {
    const d = node.data || {};
    switch (node.type) {
      case 'trigger':    return d.key || '';
      case 'send':       return d.emailSubject || d.emailUid || '';
      case 'wait':       return d.duration ? humanizeDuration(d.duration) : '';
      case 'wait_until': return d.until || '';
      case 'condition':  return (d.kind || 'check') + (d.timeout ? ' · timeout ' + humanizeDuration(d.timeout) : '');
      case 'operation':  return (d.kind || '').toUpperCase();
      case 'webhook':    return d.url || '';
    }
    return '';
  }

  function humanizeDuration(iso) {
    const m = String(iso || '').match(/^P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/);
    if (!m) return iso || '';
    const [, y, mo, w, d, h, mi, s] = m;
    const out = [];
    if (y)  out.push(y + 'y');
    if (mo) out.push(mo + 'mo');
    if (w)  out.push(w + 'w');
    if (d)  out.push(d + 'd');
    if (h)  out.push(h + 'h');
    if (mi) out.push(mi + 'm');
    if (s)  out.push(s + 's');
    return out.join(' ') || '0s';
  }

  let tree, renderer;

  function handleClickedNode(node, ev) {
    const isRoot = tree.isRoot(node.id);
    nodeMenu.open(node, ev, {
      onEdit:    () => editTitleInline(node),
      onShift:   () => isRoot ? toast("Can't shift-delete root", 'error') : (tree.remove(node.id, { mode: 'shift' }), renderer.draw(tree)),
      onCascade: () => { tree.remove(node.id, { mode: 'cascade' }); renderer.draw(tree); },
      onPath:    () => alert(tree.pathFromRoot(node.id).map(n => n.data.title || n.type).join('  →  ')),
      onCount:   () => alert(`${tree.descendants(node.id).length} descendant node(s)`),
    });
  }

  function handleClickedSlot(slot, ev) {
    addModal.open({
      onPick: (type) => {
        const node = factories[type]();
        if      (slot.kind === 'asRoot') tree.setRoot(node);
        else if (slot.kind === 'edge')   tree.splitEdge(slot.edgeId, node);
        else                              tree.addAfter(slot.source, slot.branch, node);
        renderer.draw(tree);
        toast(`Added ${type}`, 'ok');
      },
    });
  }

  function editTitleInline(node) {
    const title = prompt('Title:', node.data?.title || '');
    if (title === null) return;
    tree.update(node.id, { ...node.data, title });
    renderer.draw(tree);
  }

  const addModal = (function () {
    function open({ onPick }) {
      close();
      const overlay = el('div', 'fl-modal-overlay');
      const types = Object.entries(TYPES)
        .filter(([t]) => t !== 'trigger')
        .map(([t, cfg]) => `
          <button class="fl-modal-type" data-type="${t}">
            <span class="material-symbols-rounded">${cfg.icon}</span>
            <span>${cfg.label}</span>
          </button>`).join('');
      overlay.innerHTML = `
        <div class="fl-modal" role="dialog">
          <div class="fl-modal-title">Add node</div>
          <div class="fl-modal-grid">${types}</div>
          <div class="fl-modal-actions">
            <button class="fl-btn-cancel">Cancel</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      overlay.querySelectorAll('.fl-modal-type').forEach(btn => {
        btn.addEventListener('click', () => { close(); onPick(btn.dataset.type); });
      });
      overlay.querySelector('.fl-btn-cancel').addEventListener('click', close);
      overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
    }
    function close() { document.querySelectorAll('.fl-modal-overlay').forEach(n => n.remove()); }
    return { open, close };
  })();

  const nodeMenu = (function () {
    function open(node, ev, callbacks) {
      close();
      const m = el('div', 'fl-menu');
      m.innerHTML = `
        <div class="fl-menu-header">${escapeHtml((TYPES[node.type]?.label || node.type) + ' · ' + (node.data?.title || node.id))}</div>
        <button class="fl-menu-item" data-act="edit"><span class="material-symbols-rounded">edit</span>Edit title</button>
        <button class="fl-menu-item" data-act="shift"><span class="material-symbols-rounded">arrow_upward</span>Delete (shift)</button>
        <button class="fl-menu-item" data-act="cascade"><span class="material-symbols-rounded">delete_sweep</span>Delete (cascade)</button>
        <button class="fl-menu-item" data-act="path"><span class="material-symbols-rounded">route</span>Path from root</button>
        <button class="fl-menu-item" data-act="count"><span class="material-symbols-rounded">account_tree</span>Count descendants</button>`;
      document.body.appendChild(m);
      const r = m.getBoundingClientRect();
      const margin = 8;
      m.style.left = Math.max(margin, Math.min(ev.clientX, window.innerWidth - r.width - margin)) + 'px';
      m.style.top  = Math.max(margin, Math.min(ev.clientY + 6, window.innerHeight - r.height - margin)) + 'px';
      m.addEventListener('click', e => {
        const btn = e.target.closest('.fl-menu-item');
        if (!btn) return;
        close();
        const fn = ({ edit: callbacks.onEdit, shift: callbacks.onShift, cascade: callbacks.onCascade, path: callbacks.onPath, count: callbacks.onCount })[btn.dataset.act];
        if (fn) fn();
      });
      setTimeout(() => {
        document.addEventListener('click', dismiss, { once: true });
        document.addEventListener('keydown', escDismiss);
      }, 0);
      function dismiss() { close(); document.removeEventListener('keydown', escDismiss); }
      function escDismiss(e) { if (e.key === 'Escape') { close(); document.removeEventListener('keydown', escDismiss); } }
    }
    function close() { document.querySelectorAll('.fl-menu').forEach(n => n.remove()); }
    return { open, close };
  })();

  function toast(msg, type) {
    const t = el('div', 'fl-toast' + (type ? ' fl-toast-' + type : ''));
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 1800);
  }

  function init({ container, initialData }) {
    tree = new Tree(initialData);
    renderer = new Renderer({
      container,
      cardContent, outputs,
      onNodeClick: handleClickedNode,
      onSlotClick: handleClickedSlot,
    });
    renderer.draw(tree);
    return { tree, renderer };
  }

  function el(tag, className) { const e = document.createElement(tag); if (className) e.className = className; return e; }
  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  global.FlowDemoApp = { init, factories, TYPES };
})(window);

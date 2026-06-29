/**
 * FlowEditor — orchestrator. Wires together Flow + FlowDiagram + FlowSidebar
 * and the canvas DOM (chips, menus, modal). Pure UI: NO network I/O.
 *
 * For real-world use behind an HTTP API, attach a FlowSyncer (Acelle adapter
 * style) that subscribes to the editor's `op:*` events and persists to server.
 *
 * Events (subscribe via .on(name, handler)):
 *   • 'op:add'             { parentId, branch, type, data, insertOnEdge?, insertBefore? }
 *   • 'op:update'          { nodeId, data }
 *   • 'op:delete'          { nodeId, mode }
 *   • 'op:trigger'         { triggerKey, data }
 *
 * Public methods user code may call:
 *   • editor.flow                     — the Flow instance (read-only — use mutators below)
 *   • editor.openSidebar(nodeId)
 *   • editor.applyServerFlow(data)    — replace state from authoritative server response
 *   • editor.refresh()                — re-render after external state change
 */
(function (global) {
  'use strict';

  class FlowEditor {
    constructor(opts) {
      if (!opts || !opts.container)  throw new Error('FlowEditor: container required');
      if (!opts.nodeTypes)           throw new Error('FlowEditor: nodeTypes required');

      this.nodeTypes = opts.nodeTypes;
      this.ctx       = opts.ctx || {};
      this.flow      = new global.Flow(opts.initialFlow);
      this.diagram   = new global.FlowDiagram(opts.container, { nodeTypes: this.nodeTypes });
      this.sidebar   = new global.FlowSidebar({ nodeTypes: this.nodeTypes, ctx: this.ctx });

      this._listeners = {};
      this._menu = null;

      this.flow.on('change', () => this.refresh());
      this.sidebar.on('save',   ({ nodeId, data }) => this._onSidebarSave(nodeId, data));
      this.sidebar.on('cancel', () => {/* nothing — sidebar handles its own close */});

      this._bindCanvas();
      this.refresh({ preserveView: false });
    }

    setCtx(ctx) {
      this.ctx = ctx || {};
      this.sidebar.setCtx(this.ctx);
    }

    refresh(opts = { preserveView: true }) {
      this.diagram.render(this.flow, opts);
      this._renderInsertChips();
    }

    /** Authoritative state from server. Updates Flow via lib API (no UI knowledge of network). */
    applyServerFlow(data) {
      this.flow.replaceFromServer(data);
      // refresh() runs via flow's 'change' event listener.
    }

    openSidebar(nodeId) {
      const node = this.flow.node(nodeId);
      this.sidebar.open(node);
    }

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
        try { h(payload); } catch (e) { console.error(`[FlowEditor] ${name}:`, e); }
      });
    }

    /** Lets the syncer signal save complete / failed (controls sidebar Saving… state). */
    setSidebarSaving(v) { this.sidebar.setSaving(v); }
    closeSidebar() { this.sidebar.close(); }

    // ---------- canvas DOM events ----------

    _bindCanvas() {
      this.diagram.container.addEventListener('click', (e) => {
        const node = e.target.closest('.ad-node');
        if (node) { e.stopPropagation(); this._showNodeMenu(node, e); return; }

        const edgeChip = e.target.closest('.afe-add-on-edge');
        if (edgeChip) {
          e.stopPropagation();
          this._openAddModal({ insertOnEdge: edgeChip.dataset.edgeId });
          return;
        }
        const afterChip = e.target.closest('.afe-add-after');
        if (afterChip) {
          e.stopPropagation();
          this._openAddModal({
            insertAfter: { nodeId: afterChip.dataset.nodeId, branch: afterChip.dataset.branch || null },
          });
          return;
        }
        this._closeMenu();
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') this._closeMenu();
      });
    }

    // ---------- + chips: positioned via dagre output, NOT injected into flow data ----------

    _renderInsertChips() {
      this.diagram.world.querySelectorAll('.afe-add-on-edge, .afe-add-after').forEach(n => n.remove());
      let maxX = 0, maxY = 0;
      const track = (x, y) => { if (x > maxX) maxX = x; if (y > maxY) maxY = y; };

      // Edge midpoint chips (skip branched edges — they carry YES/NO badge).
      this.diagram._g.edges().forEach((edgeObj) => {
        const meta = this.diagram._g.edge(edgeObj);
        if (meta.branch) return;
        const id  = edgeObj.name || `${edgeObj.v}_${edgeObj.w}`;
        const mid = meta.points[Math.floor(meta.points.length / 2)];
        this._appendChip({ kind: 'edge', edgeId: id, x: mid.x, y: mid.y, title: 'Insert node on edge' });
        track(mid.x + 12, mid.y + 12);
      });

      // Per-node chips:
      //   • Leaf non-condition       → 1 chip directly below
      //   • Condition both empty     → 2 chips below (YES left, NO right)
      //   • Condition 1 branch filled→ 1 chip at the missing branch's side, aligned
      //                                with the existing child's y (so it reads as
      //                                "the missing branch lives here").
      this.flow.nodes.forEach((node) => {
        const out = this.flow.edges.filter(e => e.source === node.id);
        const layout = this.diagram.nodeLayout(node.id);
        if (!layout) return;
        const yBelow = layout.y + layout.height / 2 + 22;

        if (node.type === 'condition') {
          if (out.length === 0) {
            this._appendChip({ kind: 'after', nodeId: node.id, branch: 'yes', x: layout.x - 50, y: yBelow });
            this._appendChip({ kind: 'after', nodeId: node.id, branch: 'no',  x: layout.x + 50, y: yBelow });
            track(layout.x + 50 + 12, yBelow + 12);
          } else {
            ['yes', 'no'].forEach(b => {
              if (out.some(e => e.branch === b)) return;
              const otherEdge   = out.find(e => e.branch !== b);
              const otherLayout = otherEdge ? this.diagram.nodeLayout(otherEdge.target) : null;
              if (!otherLayout) return;
              const gap = 30, half = 12, dir = b === 'yes' ? -1 : 1;
              const x = otherLayout.x + dir * (otherLayout.width / 2 + gap + half);
              const y = otherLayout.y;
              this._appendChip({ kind: 'after', nodeId: node.id, branch: b, x, y });
              track(x + half, y + half);
            });
          }
        } else if (out.length === 0) {
          this._appendChip({ kind: 'after', nodeId: node.id, x: layout.x, y: yBelow });
          track(layout.x + 12, yBelow + 12);
        }
      });

      // Extend world bounds so off-grid chips aren't cut off by fit() / panning.
      const w = this.diagram.world;
      const curW = parseInt(w.style.width)  || 0;
      const curH = parseInt(w.style.height) || 0;
      if (maxX + 12 > curW) w.style.width  = (maxX + 24) + 'px';
      if (maxY + 12 > curH) w.style.height = (maxY + 24) + 'px';
    }

    _appendChip({ kind, nodeId, edgeId, branch, x, y, title }) {
      const chip = document.createElement('button');
      chip.className = kind === 'edge' ? 'afe-add-on-edge' : 'afe-add-after';
      if (nodeId) chip.dataset.nodeId = nodeId;
      if (edgeId) chip.dataset.edgeId = edgeId;
      if (branch) chip.dataset.branch = branch;
      chip.title = title || (branch ? `Add to ${branch.toUpperCase()} branch` : 'Add child');
      chip.innerHTML = `<span class="material-symbols-rounded">add</span>` +
        (branch ? `<span class="afe-chip-branch ${branch}">${branch.toUpperCase()}</span>` : '');
      chip.style.left = (x - 12) + 'px';
      chip.style.top  = (y - 12) + 'px';
      this.diagram.nodesLayer.appendChild(chip);
    }

    // ---------- menu ----------

    _showNodeMenu(nodeEl, ev) {
      this._closeMenu();
      const id = nodeEl.dataset.nodeId;
      const node = this.flow.node(id);
      const isTrigger = node.type === 'trigger';

      const items = [
        { icon: 'edit',         label: 'Edit',                          run: () => this.openSidebar(id) },
        { icon: 'arrow_upward', label: 'Delete (shift child up)',       disabled: isTrigger, run: () => this._requestDelete(id, 'shift') },
        { icon: 'delete_sweep', label: 'Delete (with all descendants)', disabled: isTrigger, run: () => this._requestDelete(id, 'cascade') },
        { icon: 'route',        label: 'Show hierarchy',                run: () => this._showHierarchy(id) },
        { icon: 'account_tree', label: 'Count descendants',             run: () => this._countDescendants(id) },
      ];
      if (node.type === 'condition') {
        const out = this.flow.outgoing(id);
        if (out.length > 0) {
          ['yes', 'no'].forEach(b => {
            if (out.some(e => e.branch === b)) return;
            items.push({
              icon: b === 'yes' ? 'check_circle' : 'cancel',
              label: `Add ${b.toUpperCase()} branch`,
              run: () => this._openAddModal({ insertAfter: { nodeId: id, branch: b } }),
            });
          });
        }
      }

      const cfg = this.nodeTypes[node.type] || {};
      const header = `${(cfg.label || node.type).toUpperCase()} · ${(node.data?.title) || node.id}`;
      this._openMenu(ev, items, header);
    }

    _openMenu(ev, items, header) {
      const m = document.createElement('div');
      m.className = 'afe-menu';
      m.innerHTML = `<div class="afe-menu-header">${escapeHtml(header)}</div>`;
      items.forEach((it) => {
        const btn = document.createElement('button');
        btn.className = 'afe-menu-item' + (it.disabled ? ' disabled' : '');
        btn.innerHTML = `<span class="material-symbols-rounded afe-menu-icon">${it.icon}</span><span class="afe-menu-label">${escapeHtml(it.label)}</span>`;
        if (!it.disabled) btn.addEventListener('click', () => { this._closeMenu(); it.run(); });
        m.appendChild(btn);
      });
      document.body.appendChild(m);

      const r = m.getBoundingClientRect();
      const margin = 8;
      m.style.left = Math.max(margin, Math.min(ev.clientX, window.innerWidth - r.width - margin)) + 'px';
      m.style.top  = Math.max(margin, Math.min(ev.clientY + 6, window.innerHeight - r.height - margin)) + 'px';
      this._menu = m;
      setTimeout(() => {
        this._dismiss = (e) => { if (!this._menu?.contains(e.target)) this._closeMenu(); };
        document.addEventListener('click', this._dismiss);
      }, 0);
    }

    _closeMenu() {
      if (this._menu) { this._menu.remove(); this._menu = null; }
      if (this._dismiss) { document.removeEventListener('click', this._dismiss); this._dismiss = null; }
    }

    // ---------- modal: pick type + title ----------

    _openAddModal(position) {
      this._closeMenu();
      const insertable = Object.entries(this.nodeTypes)
        .filter(([t, cfg]) => t !== 'trigger' && cfg.insertable !== false)
        .map(([t, cfg]) => ({ value: t, label: cfg.label || t.toUpperCase() }));

      const overlay = document.createElement('div');
      overlay.className = 'afe-modal-overlay';
      overlay.innerHTML = `
        <div class="afe-modal" role="dialog">
          <div class="afe-modal-title">Add node</div>
          <div class="afe-modal-body">
            <label>Type
              <select class="afe-modal-type">${insertable.map(t => `<option value="${escapeHtml(t.value)}">${escapeHtml(t.label)}</option>`).join('')}</select>
            </label>
            <label>Title
              <input class="afe-modal-title-input" placeholder="My new node">
            </label>
          </div>
          <div class="afe-modal-actions">
            <button class="afe-btn-cancel">Cancel</button>
            <button class="afe-btn-primary">Add</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);

      const typeEl  = overlay.querySelector('.afe-modal-type');
      const titleEl = overlay.querySelector('.afe-modal-title-input');
      titleEl.focus();

      const close = () => overlay.remove();
      overlay.querySelector('.afe-btn-cancel').addEventListener('click', close);
      overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
      overlay.querySelector('.afe-btn-primary').addEventListener('click', () => {
        const type  = typeEl.value;
        const title = (titleEl.value || '').trim() || `New ${type}`;
        this._emit('op:add', { ...position, type, data: { title } });
        close();
      });
    }

    // ---------- ops requested by UI (emit, syncer handles) ----------

    _requestDelete(nodeId, mode) {
      if (!confirm(`Delete this node (${mode})?`)) return;
      this._emit('op:delete', { nodeId, mode });
    }

    _onSidebarSave(nodeId, data) {
      const node = this.flow.node(nodeId);
      if (node.type === 'trigger') {
        const triggerKey = data.key;
        if (!triggerKey) {
          alert('Pick a trigger type first');
          return;
        }
        this._emit('op:trigger', { triggerKey, data });
      } else {
        this._emit('op:update', { nodeId, data });
      }
    }

    // ---------- read-only ops (don't go to server) ----------

    _showHierarchy(nodeId) {
      const path = this.flow.pathFromRoot(nodeId);
      alert(path.map(n => n.data?.title || `${n.type}#${n.id}`).join('  →  '));
    }
    _countDescendants(nodeId) {
      alert(`This node has ${this.flow.descendants(nodeId).length} descendant node(s).`);
    }
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  global.FlowEditor = FlowEditor;
})(window);

/**
 * Flow Library — Renderer (active drawer)
 *
 * Pure rendering. Owns container DOM. Reads tree, lays out via dagre,
 * builds DOM, attaches click handlers. Slot system encapsulated internally.
 *
 * Per spec: docs/automation/FLOW-LIB-API-DOC-AND-WALK-THROUGH.md
 * Exports (window): Renderer
 */
(function (global) {
  'use strict';

  const SVG_NS  = 'http://www.w3.org/2000/svg';
  const ACCENT  = '#2563EB';
  const NODE_W = 260, NODE_H = 76, SLOT_W = 28, SLOT_H = 28;
  const RANK_SEP = 64, NODE_SEP = 48, MARGIN_X = 24, MARGIN_Y = 24;
  // Missing-output chip placement: always close to parent.
  //   - vertical: parent.bottom + TIGHT_GAP
  //   - horizontal: spread by CHIP_TIGHT_DX based on the slot's index in the
  //     parent's outputs() list (yes → left, no → right; single output → center).
  // Filled siblings layout via dagre as usual; the missing-branch chip never
  // gets pushed to the sibling's rank — it stays adjacent to the parent.
  const TIGHT_GAP     = 14;
  const CHIP_TIGHT_DX = 90;
  const MIN_SCALE = 0.3, MAX_SCALE = 2;

  class Renderer {
    constructor(opts = {}) {
      this._validateOpts(opts);
      this.container = typeof opts.container === 'string'
        ? document.querySelector(opts.container)
        : opts.container;
      if (!this.container) throw new Error('Renderer: container not found');
      if (!global.dagre)   throw new Error('Renderer: window.dagre is required');

      this.cardContent = opts.cardContent;
      this.outputs     = opts.outputs;
      this.onNodeClick = opts.onNodeClick;
      this.onSlotClick = opts.onSlotClick;
      this.edgeContent = opts.edgeContent || defaultEdgeContent;

      this._tx = 0; this._ty = 0; this._scale = 1;
      this._didFirstFit = false;
      this._build();
    }

    _validateOpts(opts) {
      if (!opts.container)                          throw new Error('Renderer: container required');
      if (typeof opts.cardContent !== 'function')   throw new Error('Renderer: cardContent (fn) required');
      if (typeof opts.outputs !== 'function')       throw new Error('Renderer: outputs (fn) required');
      if (typeof opts.onNodeClick !== 'function')   throw new Error('Renderer: onNodeClick (fn) required');
      if (typeof opts.onSlotClick !== 'function')   throw new Error('Renderer: onSlotClick (fn) required');
    }

    _build() {
      this.container.classList.add('fl-root');
      this.container.innerHTML = '';
      this.viewport = el('div', 'fl-viewport');  this.container.appendChild(this.viewport);
      this.world    = el('div', 'fl-world');     this.viewport.appendChild(this.world);
      this.svg      = svg('svg', { class: 'fl-edges', xmlns: SVG_NS });
      this.world.appendChild(this.svg);
      this.nodesLayer = el('div', 'fl-nodes');   this.world.appendChild(this.nodesLayer);
      this._setupClickDelegation();
      this._setupPanZoom();
    }

    draw(tree) {
      if (!tree) throw new Error('Renderer.draw: tree required');
      this._tree = tree;
      const slots = this._computeSlots(tree);
      const g     = this._layout(tree);
      this._draw(tree, g, slots);
      if (!this._didFirstFit) { this._didFirstFit = true; this.fit(); }
    }

    fit() {
      if (!this._tree) return;
      const w = this.world.offsetWidth, h = this.world.offsetHeight;
      const cw = this.viewport.clientWidth, ch = this.viewport.clientHeight;
      if (cw === 0 || ch === 0 || w === 0 || h === 0) return;
      const scale = Math.min(1, Math.min((cw - 48) / w, (ch - 48) / h));
      this._setTransform((cw - w * scale) / 2, Math.max(20, (ch - h * scale) / 2), scale);
    }

    zoomIn()  { this._zoomBy(1.2); }
    zoomOut() { this._zoomBy(1 / 1.2); }
    _zoomBy(f) {
      const cx = this.viewport.clientWidth / 2, cy = this.viewport.clientHeight / 2;
      const s  = clamp(this._scale * f, MIN_SCALE, MAX_SCALE);
      const k  = s / this._scale;
      this._setTransform(cx - (cx - this._tx) * k, cy - (cy - this._ty) * k, s);
    }

    // Slot model — what kinds of "+" chip placements exist:
    //   { kind: 'after', source, branch, slotIndex, slotCount }  — extend from a node
    //   { kind: 'edge',  edgeId }                                — insert mid-edge
    //   { kind: 'asRoot' }                                       — empty tree
    // Every kind is positioned by a single rule in `_slotPosition`. No branching
    // strategies, no per-slot layout engines.
    _computeSlots(tree) {
      const slots = [];
      if (tree.nodes.length === 0) return [{ kind: 'asRoot' }];
      for (const node of tree.nodes) {
        const expected = this.outputs(node) || [null];
        if (expected.length === 0) continue;
        const out = tree.outgoing(node.id);
        expected.forEach((exp, i) => {
          if (out.some(e => e.branch === exp)) return;     // branch already filled
          slots.push({
            kind: 'after', source: node.id, branch: exp,
            slotIndex: i, slotCount: expected.length,
          });
        });
      }
      for (const edge of tree.edges) {
        if (edge.branch !== null) continue;
        slots.push({ kind: 'edge', edgeId: edge.id });
      }
      return slots;
    }

    _layout(tree) {
      const g = new global.dagre.graphlib.Graph({ multigraph: true });
      g.setGraph({ rankdir: 'TB', ranksep: RANK_SEP, nodesep: NODE_SEP, marginx: MARGIN_X, marginy: MARGIN_Y });
      g.setDefaultEdgeLabel(() => ({}));
      for (const node of tree.nodes) g.setNode(node.id, { width: NODE_W, height: NODE_H });
      // Insert edges in canonical branch order (yes → no → null) so dagre's
      // barycenter heuristic biases YES branches to the left and NO to the right.
      const branchOrder = (b) => (b === 'yes' ? 0 : b === 'no' ? 1 : 2);
      const sortedEdges = [...tree.edges].sort((a, b) => branchOrder(a.branch) - branchOrder(b.branch));
      for (const edge of sortedEdges) {
        g.setEdge(edge.source, edge.target, { branch: edge.branch }, edge.id);
      }
      global.dagre.layout(g);
      // Hard guarantee: enforce yes-left / no-right per source. Dagre's barycenter
      // can flip siblings under some configurations, so we deterministically
      // re-order children by branch and swap layout x where needed. Edges are
      // re-routed straight after swap (we own routing for swapped pairs only).
      this._enforceYesLeft(tree, g);
      return g;
    }

    _enforceYesLeft(tree, g) {
      for (const node of tree.nodes) {
        const out = tree.outgoing(node.id);
        if (out.length < 2) continue;
        const yes = out.find(e => e.branch === 'yes');
        const no  = out.find(e => e.branch === 'no');
        if (!yes || !no) continue;
        const yChild = g.node(yes.target), nChild = g.node(no.target);
        if (!yChild || !nChild) continue;
        if (yChild.x <= nChild.x) continue;       // already correct — no swap needed
        // Swap x coordinates of the two subtrees rooted at yes/no children.
        // Tree-shape guarantees the subtrees are disjoint and self-contained.
        const yIds = collectSubtree(tree, yes.target);
        const nIds = collectSubtree(tree, no.target);
        const dx = nChild.x - yChild.x;           // < 0 → shift yes left by |dx|, no right by |dx|
        for (const id of yIds) g.node(id).x += dx;
        for (const id of nIds) g.node(id).x -= dx;
        // Shift / re-route edges accordingly.
        for (const eObj of g.edges()) {
          const ev = eObj.v, ew = eObj.w;
          const meta = g.edge(eObj);
          const vInY = yIds.has(ev), wInY = yIds.has(ew);
          const vInN = nIds.has(ev), wInN = nIds.has(ew);
          if (vInY && wInY) {
            meta.points = meta.points.map(p => ({ x: p.x + dx, y: p.y }));
          } else if (vInN && wInN) {
            meta.points = meta.points.map(p => ({ x: p.x - dx, y: p.y }));
          } else if (wInY || wInN) {
            // Crossing edge (parent → moved subtree root) — straight re-route.
            const a = g.node(ev), b = g.node(ew);
            meta.points = [
              { x: a.x, y: a.y + NODE_H / 2 },
              { x: a.x, y: (a.y + b.y) / 2 },
              { x: b.x, y: (a.y + b.y) / 2 },
              { x: b.x, y: b.y - NODE_H / 2 },
            ];
          }
        }
      }
    }

    // Single positioning rule — chips are anchored to their parent's dagre layout.
    // Library decides where chips render; chip metadata (slot.*) feeds in, no
    // editor-side math. Re-runs from scratch on every `draw(tree)` call.
    _slotPosition(slot, g, tree) {
      if (slot.kind === 'asRoot') return { x: 100, y: 100 };
      if (slot.kind === 'edge') {
        const edge = tree.edge(slot.edgeId);
        const eObj = g.edges().find(e => (e.name || e.v + '_' + e.w) === edge.id);
        if (!eObj) return null;
        const pts = g.edge(eObj).points;
        const mid = pts[Math.floor(pts.length / 2)];
        return { x: mid.x, y: mid.y };
      }
      if (slot.kind === 'after') {
        const p = g.node(slot.source);
        if (!p) return null;
        const dx = (slot.slotIndex - (slot.slotCount - 1) / 2) * CHIP_TIGHT_DX;
        return { x: p.x + dx, y: p.y + p.height / 2 + TIGHT_GAP + SLOT_H / 2 };
      }
      return null;
    }

    _draw(tree, g, slots) {
      // Full wipe — Rule 4: no patching, no diff, no incremental DOM.
      this.nodesLayer.innerHTML = '';
      this.svg.innerHTML = '';

      // Canvas size = real-node bounds (from dagre) ∪ chip bounds (library policy).
      let { width: W, height: H } = g.graph();
      for (const slot of slots) {
        const pos = this._slotPosition(slot, g, tree);
        if (!pos) continue;
        W = Math.max(W, pos.x + SLOT_W / 2 + MARGIN_X);
        H = Math.max(H, pos.y + SLOT_H / 2 + MARGIN_Y);
      }
      this.world.style.width  = W + 'px';
      this.world.style.height = H + 'px';
      this.svg.setAttribute('width', W);
      this.svg.setAttribute('height', H);
      this.svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
      this._drawDefs();

      // Real edges
      for (const eObj of g.edges()) {
        const realEdge = tree.edge(eObj.name || eObj.v + '_' + eObj.w);
        this._renderEdge(realEdge, g.edge(eObj).points);
      }
      // Stub edges from parent → each "after" chip (visual continuity for leaves)
      for (const slot of slots) {
        if (slot.kind !== 'after') continue;
        const p   = g.node(slot.source);
        const pos = this._slotPosition(slot, g, tree);
        if (!p || !pos) continue;
        this._renderStubEdge({ x: p.x, y: p.y + p.height / 2 }, { x: pos.x, y: pos.y - SLOT_H / 2 });
      }
      // Real cards
      for (const id of g.nodes()) {
        const layout = g.node(id);
        this._renderCard(tree.node(id), layout);
      }
      // Chips
      for (const slot of slots) {
        const pos = this._slotPosition(slot, g, tree);
        if (pos) this._renderChip(slot, pos);
      }
    }

    _renderStubEdge(from, to) {
      const points = [from, { x: from.x, y: (from.y + to.y) / 2 }, { x: to.x, y: (from.y + to.y) / 2 }, to];
      const path = svg('path', {
        class: 'fl-edge fl-edge-stub',
        d: buildPath(points),
      });
      const group = svg('g', { class: 'fl-edge-group fl-edge-group-stub' });
      group.appendChild(path);
      this.svg.appendChild(group);
    }

    _drawDefs() {
      const defs = svg('defs');
      defs.innerHTML = `<marker id="fl-arrow" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto"><circle cx="5" cy="5" r="4" fill="#64748B"></circle></marker>`;
      this.svg.appendChild(defs);
    }

    _renderCard(node, layout) {
      const x = layout.x - layout.width / 2;
      const y = layout.y - layout.height / 2;
      // Convention (set by host server-side, opaque to lib): nodes flagged
      // `validation: {valid: false, ...}` get an `is-invalid` class on the
      // wrapper so visual treatment can be applied via CSS without the
      // adapter having to wrap every cardContent() call. The adapter remains
      // free to read node.validation.message itself for inline UI.
      const invalid = node.validation && node.validation.valid === false;
      const wrap = el('div', 'fl-node' + (invalid ? ' is-invalid' : ''));
      wrap.dataset.nodeId = node.id;
      wrap.style.cssText = `left:${x}px;top:${y}px;width:${layout.width}px;height:${layout.height}px;`;
      const inner = this.cardContent(node);
      if (!(inner instanceof HTMLElement)) throw new Error('cardContent must return an HTMLElement');
      wrap.appendChild(inner);
      this.nodesLayer.appendChild(wrap);
    }

    _renderEdge(edge, points) {
      const group = svg('g', { class: 'fl-edge-group' });
      group.dataset.edgeId = edge.id;
      const result = this.edgeContent(edge, points);
      if (result instanceof SVGElement || result instanceof DocumentFragment) {
        group.appendChild(result);
      } else if (result == null) {
        // ignore
      } else {
        throw new Error('edgeContent must return SVGElement or DocumentFragment or null');
      }
      this.svg.appendChild(group);
    }

    _renderChip(slot, pos) {
      const chip = el('button', 'fl-chip ' + (slot.branch ? `fl-chip-${slot.branch}` : ''));
      chip.type = 'button';
      chip.dataset.slotKind = slot.kind;
      if (slot.source) chip.dataset.slotSource = slot.source;
      if (slot.branch != null) chip.dataset.slotBranch = slot.branch;
      if (slot.edgeId) chip.dataset.slotEdgeId = slot.edgeId;
      chip.innerHTML = `<span class="material-symbols-rounded">add</span>` +
        (slot.branch ? `<span class="fl-chip-branch ${slot.branch}">${slot.branch.toUpperCase()}</span>` : '');
      chip.style.left = (pos.x - 12) + 'px';
      chip.style.top  = (pos.y - 12) + 'px';
      chip.title = slot.kind === 'asRoot' ? 'Add first node'
                : slot.kind === 'edge'    ? 'Insert on edge'
                : (slot.branch ? `Add to ${slot.branch.toUpperCase()} branch` : 'Add child');
      this.nodesLayer.appendChild(chip);
    }


    _setupClickDelegation() {
      this.viewport.addEventListener('click', (ev) => {
        const chipEl = ev.target.closest('.fl-chip');
        if (chipEl) {
          ev.stopPropagation();
          this.onSlotClick(chipDatasetToSlot(chipEl), ev);
          return;
        }
        const nodeEl = ev.target.closest('.fl-node');
        if (nodeEl && this._tree) {
          ev.stopPropagation();
          const id = nodeEl.dataset.nodeId;
          if (id && this._tree.hasNode(id)) this.onNodeClick(this._tree.node(id), ev);
        }
      });
    }

    _setupPanZoom() {
      let dragging = false, sx = 0, sy = 0, stx = 0, sty = 0;
      this.viewport.style.cursor = 'grab';
      this.viewport.addEventListener('mousedown', (e) => {
        if (e.target.closest('.fl-node, .fl-chip')) return;
        dragging = true;
        sx = e.clientX; sy = e.clientY; stx = this._tx; sty = this._ty;
        this.viewport.style.cursor = 'grabbing';
      });
      window.addEventListener('mousemove', (e) => {
        if (!dragging) return;
        this._setTransform(stx + (e.clientX - sx), sty + (e.clientY - sy), this._scale);
      });
      window.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false;
        this.viewport.style.cursor = 'grab';
      });
      this.viewport.addEventListener('wheel', (e) => {
        e.preventDefault();
        const f = e.deltaY < 0 ? 1.1 : 1 / 1.1;
        const r = this.viewport.getBoundingClientRect();
        const px = e.clientX - r.left, py = e.clientY - r.top;
        const s  = clamp(this._scale * f, MIN_SCALE, MAX_SCALE);
        const k  = s / this._scale;
        this._setTransform(px - (px - this._tx) * k, py - (py - this._ty) * k, s);
      }, { passive: false });
    }

    _setTransform(tx, ty, scale) {
      this._tx = tx; this._ty = ty; this._scale = scale;
      this.world.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
    }
  }

  function defaultEdgeContent(edge, points) {
    const frag = document.createDocumentFragment();
    const hit = svg('path', { class: 'fl-edge-hit', d: buildPath(points) });
    frag.appendChild(hit);
    const path = svg('path', { class: 'fl-edge', d: buildPath(points), 'marker-end': 'url(#fl-arrow)' });
    frag.appendChild(path);
    if (edge.branch != null) {
      const mid = points[Math.floor(points.length / 2)];
      const isYes = edge.branch === 'yes';
      const fill   = isYes ? ACCENT : 'white';
      const stroke = isYes ? 'none' : '#D1D5DB';
      const tFill  = isYes ? 'white' : '#6B7280';
      const g = svg('g', { class: `fl-branch fl-branch-${edge.branch}`, transform: `translate(${mid.x}, ${mid.y})` });
      g.appendChild(svg('rect', { x: -20, y: -11, width: 40, height: 22, rx: 11, fill, stroke, 'stroke-width': stroke === 'none' ? 0 : 1 }));
      const t = svg('text', { x: 0, y: 4, 'text-anchor': 'middle', class: 'fl-branch-label', fill: tFill });
      t.textContent = String(edge.branch).toUpperCase();
      g.appendChild(t);
      frag.appendChild(g);
    }
    return frag;
  }

  function buildPath(points) {
    if (!points || points.length < 2) return '';
    let d = `M ${points[0].x},${points[0].y}`;
    for (let i = 1; i < points.length - 1; i++) {
      const cur = points[i], next = points[i + 1];
      d += ` Q ${cur.x},${cur.y} ${(cur.x + next.x) / 2},${(cur.y + next.y) / 2}`;
    }
    const last = points[points.length - 1];
    d += ` T ${last.x},${last.y}`;
    return d;
  }

  function chipDatasetToSlot(el) {
    const ds = el.dataset;
    if (ds.slotKind === 'asRoot') return { kind: 'asRoot', asRoot: true };
    if (ds.slotKind === 'edge')   return { kind: 'edge', onEdge: ds.slotEdgeId, edgeId: ds.slotEdgeId };
    if (ds.slotKind === 'after')  return { kind: 'after', source: ds.slotSource, branch: ds.slotBranch == null ? null : ds.slotBranch };
    return null;
  }

  function el(tag, className) {
    const e = document.createElement(tag);
    if (className) e.className = className;
    return e;
  }
  function svg(tag, attrs) {
    const e = document.createElementNS(SVG_NS, tag);
    if (attrs) for (const k in attrs) e.setAttribute(k, attrs[k]);
    return e;
  }
  function clamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }

  // Collect every descendant id (including the root) reachable from `id`.
  // Used by `_enforceYesLeft` to shift a whole subtree when swapping siblings.
  function collectSubtree(tree, id) {
    const ids = new Set();
    const stack = [id];
    while (stack.length) {
      const cur = stack.pop();
      if (ids.has(cur)) continue;
      ids.add(cur);
      for (const e of tree.outgoing(cur)) stack.push(e.target);
    }
    return ids;
  }

  global.Renderer = Renderer;
})(window);

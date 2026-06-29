/**
 * FlowDiagram — pure renderer for { nodes, edges } graph.
 *
 * Independent of Acelle. Configurable per-type rendering via `nodeTypes` config.
 * Layout via dagre.js (window.dagre required).
 *
 * Rendering pipeline (NO mutation of input data, NO injection of fake nodes):
 *   1. dagre.layout(g) on real nodes + edges only
 *   2. SVG path per real edge (with optional branch label badge)
 *   3. HTML card per real node
 *   4. Pan/zoom on viewport
 *
 * Usage:
 *   const diagram = new FlowDiagram(container, { nodeTypes });
 *   diagram.render(flow);          // flow = Flow instance OR plain { nodes, edges }
 *   diagram.fit();                 // centre + scale to fit
 *   diagram.zoomIn() / .zoomOut();
 */
(function (global) {
  'use strict';

  const SVG_NS  = 'http://www.w3.org/2000/svg';
  const ACCENT  = '#2563EB';

  const DEFAULTS = {
    nodeWidth:  260,
    nodeHeight: 76,
    rankSep:    64,
    nodeSep:    48,
    minScale:   0.3,
    maxScale:   2,
  };

  class FlowDiagram {
    constructor(container, opts = {}) {
      if (typeof container === 'string') container = document.querySelector(container);
      if (!container) throw new Error('FlowDiagram: container not found');
      if (!global.dagre) throw new Error('FlowDiagram: dagre.js is required (window.dagre)');

      this.container = container;
      this.opts = Object.assign({}, DEFAULTS, opts);
      this.nodeTypes = opts.nodeTypes || {};
      this._build();
    }

    _build() {
      this.container.classList.add('ad-root');
      this.container.innerHTML = '';
      this.viewport   = el('div', 'ad-viewport');         this.container.appendChild(this.viewport);
      this.world      = el('div', 'ad-world');            this.viewport.appendChild(this.world);
      this.svg        = svg('svg', { class: 'ad-edges', xmlns: SVG_NS }); this.world.appendChild(this.svg);
      this.nodesLayer = el('div', 'ad-nodes');            this.world.appendChild(this.nodesLayer);
      this._setupPanZoom();
    }

    render(flowOrData, opts = {}) {
      const data = typeof flowOrData?.snapshot === 'function' ? flowOrData.snapshot() : flowOrData;
      if (!data || !Array.isArray(data.nodes) || !Array.isArray(data.edges)) {
        throw new Error('FlowDiagram.render: data must be { nodes, edges }');
      }
      this.data = data;
      this._layout();
      this._draw();
      if (!opts.preserveView) this.fit();
    }

    _layout() {
      const g = new global.dagre.graphlib.Graph({ multigraph: true });
      g.setGraph({
        rankdir: 'TB',
        ranksep: this.opts.rankSep,
        nodesep: this.opts.nodeSep,
        marginx: 24,
        marginy: 24,
      });
      g.setDefaultEdgeLabel(() => ({}));
      this.data.nodes.forEach(n => g.setNode(n.id, { width: this.opts.nodeWidth, height: this.opts.nodeHeight }));
      this.data.edges.forEach(e => g.setEdge(e.source, e.target, { branch: e.branch || null }, e.id));
      global.dagre.layout(g);
      this._g = g;
    }

    _draw() {
      this.nodesLayer.innerHTML = '';
      this.svg.innerHTML = '';

      const { width: w, height: h } = this._g.graph();
      this.world.style.width  = w + 'px';
      this.world.style.height = h + 'px';
      this.svg.setAttribute('width',  w);
      this.svg.setAttribute('height', h);
      this.svg.setAttribute('viewBox', `0 0 ${w} ${h}`);

      this._drawDefs();

      this._g.edges().forEach(edgeObj => {
        const meta = this._g.edge(edgeObj);
        const id   = edgeObj.name || `${edgeObj.v}_${edgeObj.w}`;
        this._drawEdge(meta.points, meta.branch, id);
      });
      this._g.nodes().forEach(id => {
        const layout = this._g.node(id);
        const node   = this.data.nodes.find(n => n.id === id);
        if (!node) return;
        this._drawNode(node, layout);
      });
    }

    nodeLayout(nodeId) { return this._g?.node(nodeId) || null; }
    edgeLayout(edgeId) {
      if (!this._g) return null;
      for (const e of this._g.edges()) {
        if ((e.name || `${e.v}_${e.w}`) === edgeId) return this._g.edge(e);
      }
      return null;
    }

    _drawDefs() {
      const defs = svg('defs');
      defs.innerHTML = `
        <marker id="ad-arrow" viewBox="0 0 10 10" refX="9" refY="5"
                markerWidth="6" markerHeight="6" orient="auto-start-reverse">
          <path d="M 0 0 L 10 5 L 0 10 z" fill="#94A3B8"></path>
        </marker>`;
      this.svg.appendChild(defs);
    }

    _drawEdge(points, branch, edgeId) {
      const group = svg('g', { class: 'ad-edge-group' });
      if (edgeId) group.setAttribute('data-edge-id', edgeId);
      group.appendChild(svg('path', { class: 'ad-edge-hit', d: this._buildPath(points) }));
      group.appendChild(svg('path', {
        class: 'ad-edge', d: this._buildPath(points), 'marker-end': 'url(#ad-arrow)',
      }));
      this.svg.appendChild(group);

      if (branch) {
        const mid   = points[Math.floor(points.length / 2)];
        const isYes = branch === 'yes';
        const text  = isYes ? 'YES' : 'NO';
        const rect  = isYes
          ? { x: -20, y: -11, width: 40, height: 22, rx: 11, fill: ACCENT }
          : { x: -20, y: -11, width: 40, height: 22, rx: 11, fill: 'white', stroke: '#D1D5DB', 'stroke-width': 1 };
        const g = svg('g', { class: `ad-branch ad-branch-${branch}`, transform: `translate(${mid.x}, ${mid.y})` });
        g.appendChild(svg('rect', rect));
        const t = svg('text', { x: 0, y: 4, 'text-anchor': 'middle', class: 'ad-branch-label' });
        t.textContent = text;
        g.appendChild(t);
        this.svg.appendChild(g);
      }
    }

    _buildPath(points) {
      if (points.length < 2) return '';
      let d = `M ${points[0].x},${points[0].y}`;
      for (let i = 1; i < points.length - 1; i++) {
        const cur = points[i], next = points[i + 1];
        d += ` Q ${cur.x},${cur.y} ${(cur.x + next.x) / 2},${(cur.y + next.y) / 2}`;
      }
      const last = points[points.length - 1];
      d += ` T ${last.x},${last.y}`;
      return d;
    }

    _drawNode(node, layout) {
      const cfg = this.nodeTypes[node.type] || { icon: 'circle', label: (node.type || 'NODE').toUpperCase() };
      const x   = layout.x - layout.width / 2;
      const y   = layout.y - layout.height / 2;
      const data     = node.data || {};
      const title    = data.title || cfg.label;
      const subtitle = typeof cfg.summarize === 'function' ? cfg.summarize(data) : '';

      const card = el('div', `ad-node ad-node-${node.type}`);
      card.setAttribute('data-node-id', node.id);
      card.style.cssText = `left:${x}px;top:${y}px;width:${layout.width}px;height:${layout.height}px;`;
      card.innerHTML = `
        <div class="ad-node-accent"></div>
        <div class="ad-node-icon"><span class="material-symbols-rounded">${escapeHtml(cfg.icon || 'circle')}</span></div>
        <div class="ad-node-body">
          <div class="ad-node-kind">${escapeHtml(cfg.label || '')}</div>
          <div class="ad-node-title">${escapeHtml(title)}</div>
          ${subtitle ? `<div class="ad-node-sub">${escapeHtml(subtitle)}</div>` : ''}
        </div>`;
      this.nodesLayer.appendChild(card);
    }

    // ---------- pan/zoom ----------

    fit() {
      if (!this._g) return;
      const { width: ww, height: wh } = this._g.graph();
      const cw = this.viewport.clientWidth;
      const ch = this.viewport.clientHeight;
      if (cw === 0 || ch === 0 || ww === 0 || wh === 0) return;
      const scale = Math.min(1, Math.min((cw - 48) / ww, (ch - 48) / wh));
      this._setTransform((cw - ww * scale) / 2, Math.max(20, (ch - wh * scale) / 2), scale);
    }
    zoomIn()  { this._zoomBy(1.2); }
    zoomOut() { this._zoomBy(1 / 1.2); }
    _zoomBy(f) {
      const cx = this.viewport.clientWidth / 2;
      const cy = this.viewport.clientHeight / 2;
      const s  = clamp(this._scale * f, this.opts.minScale, this.opts.maxScale);
      const k  = s / this._scale;
      this._setTransform(cx - (cx - this._tx) * k, cy - (cy - this._ty) * k, s);
    }

    _setupPanZoom() {
      this._tx = 0; this._ty = 0; this._scale = 1;
      let dragging = false, sx = 0, sy = 0, stx = 0, sty = 0;
      this.viewport.addEventListener('mousedown', (e) => {
        if (e.target.closest('.ad-node')) return;
        dragging = true; sx = e.clientX; sy = e.clientY; stx = this._tx; sty = this._ty;
        this.viewport.classList.add('ad-grabbing');
      });
      window.addEventListener('mousemove', (e) => {
        if (!dragging) return;
        this._setTransform(stx + (e.clientX - sx), sty + (e.clientY - sy), this._scale);
      });
      window.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false;
        this.viewport.classList.remove('ad-grabbing');
      });
      this.viewport.addEventListener('wheel', (e) => {
        e.preventDefault();
        const f = e.deltaY < 0 ? 1.1 : 1 / 1.1;
        const r = this.viewport.getBoundingClientRect();
        const px = e.clientX - r.left, py = e.clientY - r.top;
        const s  = clamp(this._scale * f, this.opts.minScale, this.opts.maxScale);
        const k  = s / this._scale;
        this._setTransform(px - (px - this._tx) * k, py - (py - this._ty) * k, s);
      }, { passive: false });
    }

    _setTransform(tx, ty, scale) {
      this._tx = tx; this._ty = ty; this._scale = scale;
      this.world.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
    }
  }

  // ---------- helpers ----------

  function el(tag, className)        { const e = document.createElement(tag); if (className) e.className = className; return e; }
  function svg(tag, attrs)           { const e = document.createElementNS(SVG_NS, tag); if (attrs) for (const k in attrs) e.setAttribute(k, attrs[k]); return e; }
  function clamp(v, lo, hi)          { return Math.max(lo, Math.min(hi, v)); }
  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  global.FlowDiagram = FlowDiagram;
})(window);

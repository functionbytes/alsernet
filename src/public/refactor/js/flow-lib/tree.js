/**
 * Flow Library — Tree (data structure)
 *
 * Pure graph data + sync mutators. No DOM, no events, no async.
 * Per spec: docs/automation/FLOW-LIB-API-DOC-AND-WALK-THROUGH.md
 *
 * Exports (window): Tree, Node, Edge, TreeError
 */
(function (global) {
  'use strict';

  class TreeError extends Error {
    constructor(msg) { super(msg); this.name = 'TreeError'; }
  }

  // ─── Node + Edge DTOs (immutable) ────────────────────────
  class Node {
    constructor({ type, data, id, validation } = {}) {
      if (!type) throw new TreeError('Node: type required');
      this.id   = id || genId('n_');
      this.type = String(type);
      this.data = { ...(data || {}) };
      // Optional pass-through metadata. The library treats this as opaque —
      // it's set by the host (server-side decorator) and consumed by the
      // renderer/adapter. Keeping it on the Node so it survives tree.load()
      // round-trips without leaking into business `data`. Convention:
      //   { valid: false, message: string }
      this.validation = validation ? { ...validation } : null;
      Object.freeze(this);
    }
    toJSON() {
      const out = { id: this.id, type: this.type, data: { ...this.data } };
      if (this.validation) out.validation = { ...this.validation };
      return out;
    }
    static fromJSON(obj) { return new Node(obj); }
  }

  class Edge {
    constructor({ source, target, branch, id } = {}) {
      if (!source) throw new TreeError('Edge: source required');
      if (!target) throw new TreeError('Edge: target required');
      this.id     = id || genId('e_');
      this.source = String(source);
      this.target = String(target);
      this.branch = branch == null ? null : String(branch);
      Object.freeze(this);
    }
    toJSON() { return { id: this.id, source: this.source, target: this.target, branch: this.branch }; }
    static fromJSON(obj) { return new Edge(obj); }
  }

  // ─── Tree ─────────────────────────────────────────────────
  class Tree {
    constructor(json) { this._reset(parseAndValidate(json)); }

    get nodes() { return this._nodes; }
    get edges() { return this._edges; }
    get root() {
      const roots = this._nodes.filter(n => !this._incomingOf(n.id));
      if (roots.length === 0) return null;
      if (roots.length > 1) throw new TreeError('Tree has multiple roots (data corrupted)');
      return roots[0];
    }

    node(id) { const n = this._nodes.find(x => x.id === id); if (!n) throw new TreeError(`Node not found: ${id}`); return n; }
    edge(id) { const e = this._edges.find(x => x.id === id); if (!e) throw new TreeError(`Edge not found: ${id}`); return e; }
    hasNode(id) { return this._nodes.some(n => n.id === id); }
    hasEdge(id) { return this._edges.some(e => e.id === id); }
    outgoing(id) { return this._edges.filter(e => e.source === id); }
    incoming(id) { return this._incomingOf(id); }

    descendants(id) {
      this.node(id);
      const visited = new Set();
      const stack = [id];
      while (stack.length) {
        const cur = stack.pop();
        for (const e of this.outgoing(cur)) {
          if (!visited.has(e.target)) { visited.add(e.target); stack.push(e.target); }
        }
      }
      return [...visited].map(nid => this.node(nid));
    }

    pathFromRoot(id) {
      const path = [];
      const seen = new Set();
      let cur = id;
      while (cur && !seen.has(cur)) {
        seen.add(cur);
        path.unshift(this.node(cur));
        const inc = this._incomingOf(cur);
        cur = inc ? inc.source : null;
      }
      return path;
    }

    isRoot(id) { return this.hasNode(id) && !this._incomingOf(id); }

    load(json) { this._reset(parseAndValidate(json)); }

    addAfter(parentId, branch, node) {
      assertNodeInstance(node);
      this.node(parentId);
      const branchVal = branch == null ? null : String(branch);
      const filled = this.outgoing(parentId).find(e => e.branch === branchVal);
      if (filled) {
        throw new TreeError(`addAfter: branch ${branchVal} already filled — use splitEdge to insert between`);
      }
      const newNodes = [...this._nodes, node];
      const newEdges = [...this._edges, new Edge({
        source: parentId, target: node.id, branch: branchVal,
      })];
      this._commit(newNodes, newEdges);
      return node;
    }

    splitEdge(edgeId, node) {
      assertNodeInstance(node);
      const edge = this.edge(edgeId);
      const oldTarget = edge.target;
      const newNodes = [...this._nodes, node];
      const newEdges = this._edges.map(e =>
        e.id === edgeId
          ? new Edge({ id: e.id, source: e.source, target: node.id, branch: e.branch })
          : e
      );
      newEdges.push(new Edge({ source: node.id, target: oldTarget, branch: null }));
      this._commit(newNodes, newEdges);
      return node;
    }

    setRoot(node) {
      assertNodeInstance(node);
      if (this._nodes.length > 0) {
        throw new TreeError('setRoot: graph already has a root, use addAfter / splitEdge');
      }
      this._commit([node], []);
      return node;
    }

    remove(id, opts = {}) {
      const mode = opts.mode;
      if (mode !== 'shift' && mode !== 'cascade') {
        throw new TreeError(`remove: mode must be 'shift' or 'cascade' (got ${mode})`);
      }
      this.node(id);

      if (mode === 'cascade') {
        const drop = new Set([id, ...this.descendants(id).map(n => n.id)]);
        const newNodes = this._nodes.filter(n => !drop.has(n.id));
        const newEdges = this._edges.filter(e => !drop.has(e.source) && !drop.has(e.target));
        this._commit(newNodes, newEdges);
        return;
      }

      if (this.isRoot(id)) {
        throw new TreeError('remove: shift not allowed on root (no parent to shift child up to)');
      }
      const out = this.outgoing(id);
      if (out.length > 1) {
        throw new TreeError('remove: shift not allowed on multi-output node — use cascade');
      }
      const incoming = this._incomingOf(id);

      let newNodes = this._nodes.filter(n => n.id !== id);
      let newEdges;

      if (out.length === 1) {
        const child = out[0].target;
        newEdges = this._edges
          .filter(e => e.id !== out[0].id)
          .map(e => e.id === incoming.id
            ? new Edge({ id: e.id, source: e.source, target: child, branch: e.branch })
            : e
          );
      } else {
        newEdges = this._edges.filter(e => e.id !== incoming.id);
      }
      this._commit(newNodes, newEdges);
    }

    update(id, data) {
      const existing = this.node(id);
      const replaced = new Node({ id: existing.id, type: existing.type, data });
      const newNodes = this._nodes.map(n => (n.id === id ? replaced : n));
      this._commit(newNodes, this._edges);
      return replaced;
    }

    snapshot() {
      return {
        version: 1,
        nodes: this._nodes.map(n => n.toJSON()),
        edges: this._edges.map(e => e.toJSON()),
      };
    }

    _reset({ nodes, edges }) { this._nodes = nodes; this._edges = edges; }
    _commit(nodes, edges) { validate(nodes, edges); this._nodes = nodes; this._edges = edges; }
    _incomingOf(id) { return this._edges.find(e => e.target === id) || null; }
  }

  // ─── Helpers ──────────────────────────────────────────────
  function assertNodeInstance(node) {
    if (!(node instanceof Node)) {
      throw new TreeError('Expected a Node instance — use new Node({type, data}) or a factory');
    }
  }

  function parseAndValidate(json) {
    if (json == null) return { nodes: [], edges: [] };
    if (typeof json !== 'object') throw new TreeError('Tree.load: expected object, got ' + typeof json);
    const rawNodes = json.nodes || [];
    const rawEdges = json.edges || [];
    if (!Array.isArray(rawNodes) || !Array.isArray(rawEdges)) {
      throw new TreeError('Tree.load: nodes and edges must be arrays');
    }
    const nodes = rawNodes.map(Node.fromJSON);
    const edges = rawEdges.map(Edge.fromJSON);
    validate(nodes, edges);
    return { nodes, edges };
  }

  function validate(nodes, edges) {
    const nodeIds = new Set();
    for (const n of nodes) {
      if (nodeIds.has(n.id)) throw new TreeError(`Duplicate node id: ${n.id}`);
      nodeIds.add(n.id);
    }
    const edgeIds = new Set();
    for (const e of edges) {
      if (edgeIds.has(e.id)) throw new TreeError(`Duplicate edge id: ${e.id}`);
      edgeIds.add(e.id);
    }
    for (const e of edges) {
      if (!nodeIds.has(e.source)) throw new TreeError(`Edge ${e.id} references missing source: ${e.source}`);
      if (!nodeIds.has(e.target)) throw new TreeError(`Edge ${e.id} references missing target: ${e.target}`);
      if (e.source === e.target) throw new TreeError(`Edge ${e.id} self-loops on ${e.source}`);
    }
    const incomingCount = new Map();
    for (const e of edges) incomingCount.set(e.target, (incomingCount.get(e.target) || 0) + 1);
    for (const [tid, count] of incomingCount) {
      if (count > 1) throw new TreeError(`Node ${tid} has multiple incoming edges`);
    }
    const sourceBranches = new Map();
    for (const e of edges) {
      const set = sourceBranches.get(e.source) || new Set();
      const key = e.branch === null ? '__null__' : e.branch;
      if (set.has(key)) throw new TreeError(`Source ${e.source} has duplicate branch: ${e.branch}`);
      set.add(key);
      sourceBranches.set(e.source, set);
    }
    if (nodes.length > 0) {
      const rootCount = nodes.filter(n => !incomingCount.has(n.id)).length;
      if (rootCount !== 1) throw new TreeError(`Expected exactly 1 root, got ${rootCount}`);
      const root = nodes.find(n => !incomingCount.has(n.id));
      const adj = new Map();
      for (const e of edges) {
        const list = adj.get(e.source) || [];
        list.push(e.target);
        adj.set(e.source, list);
      }
      const color = new Map();
      const dfs = (u) => {
        color.set(u, 1);
        for (const v of adj.get(u) || []) {
          const c = color.get(v) || 0;
          if (c === 1) throw new TreeError(`Cycle detected involving node ${v}`);
          if (c === 0) dfs(v);
        }
        color.set(u, 2);
      };
      dfs(root.id);
    }
  }

  let _idCounter = 0;
  function genId(prefix) {
    return prefix + Date.now().toString(36).slice(-3) + (_idCounter++).toString(36);
  }

  global.Tree = Tree;
  global.Node = Node;
  global.Edge = Edge;
  global.TreeError = TreeError;
})(window);

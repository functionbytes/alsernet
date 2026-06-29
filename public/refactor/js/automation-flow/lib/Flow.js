/**
 * Flow — pure JS data class for an automation graph.
 *
 * Mirror of PHP `App\Domain\Automation\Flow`. Sync API, in-memory mutations,
 * throws FlowError on invariant violations. NO network I/O. NO DOM. NO Acelle.
 *
 * Shape:
 *   { version, nodes: [{ id, type, data }], edges: [{ id, source, target, branch }] }
 *
 * Invariants (re-asserted after every mutation):
 *   • Exactly 1 trigger node, id always "trigger"
 *   • Tree-shaped (each non-trigger node has ≤ 1 incoming edge)
 *   • No cycles
 *   • Conditions: 0–2 outgoing, branches must be {yes, no} distinct
 *   • Non-conditions: 0–1 outgoing, branch must be null
 *
 * Events (subscribe via .on(name, fn)):
 *   • 'change'  → after any mutation. Payload: { reason, ...details }
 *
 * Standalone usage (no Acelle):
 *   const flow = new Flow();
 *   flow.insertAfter('trigger', null, 'send', { title: 'Welcome' });
 *   flow.snapshot()          // → plain object for serialization / rendering
 */
(function (global) {
  'use strict';

  const TRIGGER_ID = 'trigger';
  const VERSION    = 1;

  class FlowError extends Error {
    constructor(msg) { super(msg); this.name = 'FlowError'; }
  }

  class Flow {
    constructor(data) {
      this._listeners = {};
      if (data == null) {
        this._reset(emptyData());
      } else {
        this._reset(parseAndValidate(data));
      }
    }

    // ---------- read API ----------

    snapshot() {
      // Deep-cloned plain object, safe to pass to renderers.
      return {
        version: VERSION,
        nodes: this._nodes.map(n => ({ id: n.id, type: n.type, data: { ...n.data } })),
        edges: this._edges.map(e => ({ id: e.id, source: e.source, target: e.target, branch: e.branch })),
      };
    }

    get nodes() { return this._nodes; }
    get edges() { return this._edges; }
    get trigger() { return this.node(TRIGGER_ID); }
    get triggerKey() { return this.trigger?.data?.key || null; }

    node(id) {
      const n = this._nodes.find(x => x.id === id);
      if (!n) throw new FlowError(`Node not found: ${id}`);
      return n;
    }
    edge(id) {
      const e = this._edges.find(x => x.id === id);
      if (!e) throw new FlowError(`Edge not found: ${id}`);
      return e;
    }
    hasNode(id) { return this._nodes.some(n => n.id === id); }
    hasEdge(id) { return this._edges.some(e => e.id === id); }

    outgoing(nodeId) { return this._edges.filter(e => e.source === nodeId); }
    incoming(nodeId) { return this._edges.find(e => e.target === nodeId) || null; }

    descendants(nodeId) {
      const visited = new Set();
      const stack = [nodeId];
      while (stack.length) {
        const cur = stack.pop();
        for (const e of this.outgoing(cur)) {
          if (!visited.has(e.target)) { visited.add(e.target); stack.push(e.target); }
        }
      }
      return [...visited];
    }

    pathFromRoot(nodeId) {
      const path = [];
      const seen = new Set();
      let cur = nodeId;
      while (cur && !seen.has(cur)) {
        seen.add(cur);
        path.unshift(this.node(cur));
        const inc = this.incoming(cur);
        cur = inc ? inc.source : null;
      }
      return path;
    }

    // ---------- mutation API (sync, throws on violation) ----------

    insertAfter(parentId, branch, type, data) {
      const parent = this.node(parentId);
      if (parent.type === 'condition' && !branch) {
        throw new FlowError(`Inserting after condition ${parentId} requires branch (yes|no)`);
      }
      if (parent.type !== 'condition' && branch) {
        throw new FlowError(`Branch only valid on condition parents; ${parentId} is ${parent.type}`);
      }
      if (type === 'trigger') throw new FlowError('Cannot insert a second trigger');

      const newNode = { id: genNodeId(), type, data: { ...data } };
      const matching = this.outgoing(parentId).find(e => (e.branch || null) === (branch || null));

      let edges;
      if (matching) {
        const oldTarget = matching.target;
        edges = this._edges.map(e => e.id === matching.id ? { ...e, target: newNode.id } : e);
        const continuationBranch = type === 'condition' ? 'yes' : null;
        edges.push({ id: genEdgeId(), source: newNode.id, target: oldTarget, branch: continuationBranch });
      } else {
        edges = [...this._edges, { id: genEdgeId(), source: parentId, target: newNode.id, branch: branch || null }];
      }

      const nodes = [...this._nodes, newNode];
      this._commit(nodes, edges, { reason: 'insertAfter', node: newNode, parentId, branch });
      return newNode;
    }

    insertBefore(nodeId, type, data) {
      if (nodeId === TRIGGER_ID) throw new FlowError('Cannot insert above the trigger');
      if (type === 'trigger') throw new FlowError('Cannot insert a second trigger');

      const existing = this.node(nodeId);
      const incoming = this.incoming(nodeId);
      const newNode  = { id: genNodeId(), type, data: { ...data } };
      const continuationBranch = type === 'condition' ? 'yes' : null;

      let edges;
      if (incoming) {
        edges = this._edges.map(e => e.id === incoming.id ? { ...e, target: newNode.id } : e);
        edges.push({ id: genEdgeId(), source: newNode.id, target: existing.id, branch: continuationBranch });
      } else {
        edges = [...this._edges, { id: genEdgeId(), source: newNode.id, target: existing.id, branch: continuationBranch }];
      }

      this._commit([...this._nodes, newNode], edges, { reason: 'insertBefore', node: newNode, nodeId });
      return newNode;
    }

    insertOnEdge(edgeId, type, data) {
      if (type === 'trigger') throw new FlowError('Cannot insert a second trigger');
      const edge = this.edge(edgeId);
      const newNode = { id: genNodeId(), type, data: { ...data } };
      const oldTarget = edge.target;
      const continuationBranch = type === 'condition' ? 'yes' : null;

      const edges = this._edges
        .map(e => e.id === edgeId ? { ...e, target: newNode.id } : e)
        .concat({ id: genEdgeId(), source: newNode.id, target: oldTarget, branch: continuationBranch });

      this._commit([...this._nodes, newNode], edges, { reason: 'insertOnEdge', node: newNode, edgeId });
      return newNode;
    }

    replaceNode(nodeId, data) {
      const existing = this.node(nodeId);
      if (existing.type === 'trigger') {
        throw new FlowError('Use replaceTrigger() for trigger node — its options are key-bound');
      }
      const replaced = { id: existing.id, type: existing.type, data: { ...data } };
      const nodes = this._nodes.map(n => n.id === nodeId ? replaced : n);
      this._commit(nodes, this._edges, { reason: 'replaceNode', node: replaced });
      return replaced;
    }

    replaceTrigger(data) {
      const trigger = this.trigger;
      const replaced = { id: trigger.id, type: 'trigger', data: { ...data } };
      const nodes = this._nodes.map(n => n.id === TRIGGER_ID ? replaced : n);
      this._commit(nodes, this._edges, { reason: 'replaceTrigger', node: replaced });
      return replaced;
    }

    deleteShift(nodeId) {
      if (nodeId === TRIGGER_ID) throw new FlowError('Cannot delete the trigger');
      const node = this.node(nodeId);
      if (node.type === 'condition') throw new FlowError('Cannot shift-delete a condition (use cascade)');

      const incoming = this.incoming(nodeId);
      const outgoing = this.outgoing(nodeId);
      if (outgoing.length > 1) throw new FlowError(`Node ${nodeId} has multiple outbound edges (unexpected)`);

      const nodes = this._nodes.filter(n => n.id !== nodeId);
      let edges  = [...this._edges];

      if (outgoing.length === 1) {
        const childId = outgoing[0].target;
        const outId   = outgoing[0].id;
        edges = edges.filter(e => e.id !== outId);
        if (incoming) edges = edges.map(e => e.id === incoming.id ? { ...e, target: childId } : e);
      } else if (incoming) {
        edges = edges.filter(e => e.id !== incoming.id);
      }

      this._commit(nodes, edges, { reason: 'deleteShift', nodeId });
    }

    deleteCascade(nodeId) {
      if (nodeId === TRIGGER_ID) throw new FlowError('Cannot delete the trigger');
      this.node(nodeId); // verify exists

      const drop = new Set([nodeId, ...this.descendants(nodeId)]);
      const nodes = this._nodes.filter(n => !drop.has(n.id));
      const edges = this._edges.filter(e => !drop.has(e.source) && !drop.has(e.target));

      this._commit(nodes, edges, { reason: 'deleteCascade', nodeId, dropped: [...drop] });
    }

    /**
     * Replace local state with server-authoritative flow data.
     * Used by the sync layer (FlowSyncer) after a successful REST call.
     * Re-validates invariants. Emits 'change' with reason='replaceFromServer'.
     */
    replaceFromServer(data) {
      const parsed = parseAndValidate(data);
      this._reset(parsed);
      this._emit('change', { reason: 'replaceFromServer' });
    }

    // ---------- events ----------

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
        try { h(payload); } catch (e) { console.error(`[Flow] handler for "${name}" threw:`, e); }
      });
    }

    // ---------- private ----------

    _reset({ nodes, edges }) {
      this._nodes = nodes;
      this._edges = edges;
    }

    _commit(nodes, edges, payload) {
      // Re-validate before accepting. Throws on violation, leaving state unchanged.
      assertWellFormed(nodes, edges);
      this._nodes = nodes;
      this._edges = edges;
      this._emit('change', payload);
    }
  }

  // ---------- helpers ----------

  function emptyData() {
    return {
      nodes: [{ id: TRIGGER_ID, type: 'trigger', data: { title: 'Click to choose a trigger' } }],
      edges: [],
    };
  }

  function parseAndValidate(raw) {
    if (!raw || typeof raw !== 'object') throw new FlowError('Flow data must be an object');
    if (!Array.isArray(raw.nodes) || !Array.isArray(raw.edges)) {
      throw new FlowError('Flow data must have nodes[] and edges[]');
    }
    const nodes = raw.nodes.map(parseNode);
    const edges = raw.edges.map(parseEdge);
    assertWellFormed(nodes, edges);
    return { nodes, edges };
  }

  function parseNode(n) {
    if (!n || typeof n !== 'object' || !n.id || !n.type) throw new FlowError('Node missing id/type');
    return { id: String(n.id), type: String(n.type), data: { ...(n.data || {}) } };
  }
  function parseEdge(e) {
    if (!e || typeof e !== 'object' || !e.id || !e.source || !e.target) {
      throw new FlowError('Edge missing id/source/target');
    }
    let branch = e.branch == null ? null : String(e.branch);
    if (branch !== null && branch !== 'yes' && branch !== 'no') {
      throw new FlowError(`Edge branch must be yes|no|null (got "${branch}")`);
    }
    return { id: String(e.id), source: String(e.source), target: String(e.target), branch };
  }

  function assertWellFormed(nodes, edges) {
    const nodeMap = {};
    for (const n of nodes) {
      if (nodeMap[n.id]) throw new FlowError(`Duplicate node id: ${n.id}`);
      nodeMap[n.id] = n;
    }
    const triggers = nodes.filter(n => n.type === 'trigger');
    if (triggers.length !== 1) throw new FlowError('Flow must contain exactly 1 trigger');
    if (!nodeMap[TRIGGER_ID]) throw new FlowError(`Trigger node id must be "${TRIGGER_ID}"`);

    const edgeMap = {}, bySource = {}, byTarget = {};
    for (const e of edges) {
      if (edgeMap[e.id]) throw new FlowError(`Duplicate edge id: ${e.id}`);
      edgeMap[e.id] = e;
      if (!nodeMap[e.source]) throw new FlowError(`Edge ${e.id} references missing source: ${e.source}`);
      if (!nodeMap[e.target]) throw new FlowError(`Edge ${e.id} references missing target: ${e.target}`);
      if (e.source === e.target) throw new FlowError(`Edge ${e.id} self-loops on ${e.source}`);
      (bySource[e.source] = bySource[e.source] || []).push(e);
      (byTarget[e.target] = byTarget[e.target] || []).push(e);
    }

    for (const tid in byTarget) {
      if (byTarget[tid].length > 1) throw new FlowError(`Node ${tid} has multiple incoming edges`);
    }
    if (byTarget[TRIGGER_ID]) throw new FlowError('Trigger node cannot have incoming edges');

    for (const sid in bySource) {
      const list = bySource[sid];
      const node = nodeMap[sid];
      if (node.type === 'condition') {
        if (list.length > 2) throw new FlowError(`Condition ${sid} has more than 2 outbound edges`);
        const branches = {};
        for (const e of list) {
          if (e.branch === null) throw new FlowError(`Condition ${sid} edge ${e.id} missing branch label`);
          if (branches[e.branch]) throw new FlowError(`Condition ${sid} has duplicate branch: ${e.branch}`);
          branches[e.branch] = true;
        }
      } else {
        if (list.length > 1) throw new FlowError(`Node ${sid} has more than 1 outbound edge`);
        for (const e of list) {
          if (e.branch !== null) throw new FlowError(`Edge ${e.id} on non-condition source ${sid} must have null branch`);
        }
      }
    }

    // Cycle detection (DFS from trigger)
    const color = {};
    const dfs = (u) => {
      color[u] = 1;
      for (const e of bySource[u] || []) {
        const c = color[e.target] || 0;
        if (c === 1) throw new FlowError(`Cycle detected involving node ${e.target}`);
        if (c === 0) dfs(e.target);
      }
      color[u] = 2;
    };
    dfs(TRIGGER_ID);
  }

  let _idCounter = 0;
  function genNodeId() { return 'n_' + Date.now().toString(36).slice(-4) + (_idCounter++).toString(36); }
  function genEdgeId() { return 'e_' + Date.now().toString(36).slice(-4) + (_idCounter++).toString(36); }

  // ---------- export ----------

  Flow.TRIGGER_ID = TRIGGER_ID;
  Flow.VERSION    = VERSION;
  Flow.FlowError  = FlowError;

  global.Flow = Flow;
})(window);

/**
 * FlowSyncer — Acelle REST adapter for FlowEditor.
 *
 * THE ONLY file in this stack that:
 *   • does network I/O (`fetch`)
 *   • knows about Acelle URL patterns / CSRF token
 *
 * Usage:
 *   const editor = new FlowEditor({ container, nodeTypes, ctx, initialFlow });
 *   new FlowSyncer({ editor, urls, csrfToken, toast });
 *
 * Subscribes to editor's op:* events, posts to server, applies authoritative
 * response back via editor.applyServerFlow(). On error, surfaces via `toast`
 * callback (provided by Acelle blade). No DOM manipulation — UI feedback is
 * delegated to editor (sidebar saving spinner) + toast callback.
 */
(function (global) {
  'use strict';

  class FlowSyncer {
    constructor(opts) {
      if (!opts.editor)    throw new Error('FlowSyncer: editor required');
      if (!opts.urls)      throw new Error('FlowSyncer: urls required');
      if (!opts.csrfToken) throw new Error('FlowSyncer: csrfToken required');

      this.editor    = opts.editor;
      this.urls      = opts.urls;
      this.csrfToken = opts.csrfToken;
      this.toast     = opts.toast || ((msg) => console.log('[FlowSyncer]', msg));

      this.editor.on('op:add',     (p) => this._add(p));
      this.editor.on('op:update',  (p) => this._update(p));
      this.editor.on('op:delete',  (p) => this._delete(p));
      this.editor.on('op:trigger', (p) => this._trigger(p));
    }

    async _add({ type, data, insertAfter, insertBefore, insertOnEdge }) {
      const body = { data: data || {} };
      if (insertAfter)  body.insertAfter  = insertAfter;
      if (insertBefore) body.insertBefore = insertBefore;
      if (insertOnEdge) body.insertOnEdge = insertOnEdge;

      const res = await this._fetch(this.urls.createNode(type), 'POST', body);
      if (!res.ok) { this.toast(res.error || 'Add failed', 'error'); return; }
      this.editor.applyServerFlow(res.flow);
      this.toast(`Added ${type}`, 'ok');
    }

    async _update({ nodeId, data }) {
      this.editor.setSidebarSaving(true);
      try {
        const res = await this._fetch(this.urls.updateNode(nodeId), 'PATCH', { data });
        if (!res.ok) { this.toast(res.error || 'Update failed', 'error'); return; }
        this.editor.applyServerFlow(res.flow);
        this.editor.closeSidebar();
        this.toast('Updated', 'ok');
      } finally {
        this.editor.setSidebarSaving(false);
      }
    }

    async _delete({ nodeId, mode }) {
      const url = this.urls.deleteNode(nodeId) + `?mode=${encodeURIComponent(mode)}`;
      const res = await this._fetch(url, 'DELETE');
      if (!res.ok) { this.toast(res.error || 'Delete failed', 'error'); return; }
      this.editor.applyServerFlow(res.flow);
      this.toast('Deleted', 'ok');
    }

    async _trigger({ triggerKey, data }) {
      this.editor.setSidebarSaving(true);
      try {
        const res = await this._fetch(this.urls.updateTrigger, 'PATCH', { triggerKey, data });
        if (!res.ok) { this.toast(res.error || 'Update failed', 'error'); return; }
        this.editor.applyServerFlow(res.flow);
        this.editor.closeSidebar();
        this.toast('Trigger updated', 'ok');
      } finally {
        this.editor.setSidebarSaving(false);
      }
    }

    async _fetch(url, method, body) {
      const init = {
        method,
        headers: {
          'Content-Type':     'application/json',
          'Accept':           'application/json',
          'X-CSRF-TOKEN':     this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
      };
      if (body !== undefined) init.body = JSON.stringify(body);
      try {
        const r = await fetch(url, init);
        const text = await r.text();
        try { return JSON.parse(text); }
        catch { return { ok: false, error: `${r.status}: ${text.slice(0, 200)}` }; }
      } catch (e) {
        return { ok: false, error: 'Network error: ' + e.message };
      }
    }
  }

  global.FlowSyncer = FlowSyncer;
})(window);

// FileBrowser.js — headless core of the Scalable FileBrowser library.
// - Pure data + event API. No HTML strings, no DOM ownership beyond `mounts`.
// - Loads a theme (template.html) via TemplateEngine and renders on 'listing:loaded'.
// - XHR requests inject bearer auth + scope query params.
// - Errors from the backend surface to the caller — no mock fallback.

import { TemplateEngine } from './TemplateEngine.js';

const DEFAULT_MOUNTS = {
    grid: null,
    list: null,
    breadcrumbs: null,
    tray: null,
    emptyState: null,
};

export class FileBrowser {
    /**
     * @param {object}   cfg
     * @param {string}   cfg.theme                                       Path to the theme directory (contains template.html).
     * @param {string}   [cfg.endpoint]                                  Backend prefix, e.g. "/api/v1/app-directory".
     * @param {object}   [cfg.auth]                                      { type: 'bearer', token: '...' }.
     * @param {object}   [cfg.scope]                                     { type, id } — merged into every request.
     * @param {object}   [cfg.mounts]                                    { grid, list, breadcrumbs, tray, emptyState }.
     * @param {'click'|'dblclick'|false} [cfg.folderNavigation='dblclick']
     *     Which grid event enters a subfolder. Set to `false` to disable and
     *     handle navigation yourself.
     */
    constructor({ endpoint = null, theme, auth = null, scope = null, mounts = {}, folderNavigation = 'dblclick' } = {}) {
        if (!theme) throw new Error('FileBrowser: `theme` path is required');
        if (folderNavigation !== false && folderNavigation !== 'click' && folderNavigation !== 'dblclick') {
            throw new Error(`FileBrowser: folderNavigation must be 'click', 'dblclick', or false (got ${JSON.stringify(folderNavigation)})`);
        }
        this.endpoint = endpoint ? endpoint.replace(/\/+$/, '') : null;
        this.themePath = theme.replace(/\/+$/, '');
        this.auth = auth;
        this.scope = scope;
        this.folderNavigation = folderNavigation;
        this.mounts = { ...DEFAULT_MOUNTS, ...mounts };
        this.state = {
            items: [],
            breadcrumbs: [],
            path: '',
            view: 'grid',
            page: 1,
            perPage: 50,
            total: 0,
            selection: new Set(),
        };
        this._listeners = {};
        this._uploadCounter = 0;
        this.engine = new TemplateEngine();
    }

    async init() {
        await this.engine.loadFromUrl(`${this.themePath}/template.html`);
        this._wireRenderers();
        return this;
    }

    // ==========================================================================
    // Data API — pure, no DOM
    // ==========================================================================

    async list(opts = {}) {
        const params = {
            path: opts.path ?? this.state.path,
            search: opts.search ?? '',
            kind: opts.kind ?? '',
            sort: opts.sort ?? 'name',
            order: opts.order ?? 'asc',
            page: opts.page ?? 1,
            per_page: opts.perPage ?? this.state.perPage,
        };
        const result = await this._request('GET', '/list', { query: params });
        if (!result?.success) throw new Error(result?.message || 'list() failed');

        const d = result.data || {};
        this.state.items = d.items || [];
        this.state.breadcrumbs = d.breadcrumbs || [];
        this.state.path = d.currentPath ?? params.path;
        this.state.page = d.page || params.page;
        this.state.perPage = d.perPage || params.per_page;
        this.state.total = d.total ?? this.state.items.length;

        this._emit('listing:loaded', {
            items: this.state.items,
            breadcrumbs: this.state.breadcrumbs,
            path: this.state.path,
            page: this.state.page,
        });
        return this.state.items;
    }

    cd(path) { return this.list({ path }); }

    async upload(files, path = this.state.path) {
        const arr = Array.isArray(files) ? files : Array.from(files || []);
        const entries = [];
        for (const file of arr) {
            const uploadId = ++this._uploadCounter;
            this._emit('upload:queued', { uploadId, filename: file.name });
            try {
                const res = await this._uploadOne(file, path, uploadId);
                this._emit('upload:complete', { uploadId, entry: res?.data });
                if (res?.data) entries.push(res.data);
            } catch (err) {
                this._emit('upload:error', { uploadId, filename: file.name, error: err.message });
            }
        }
        // Refresh listing so the UI shows new items
        this.list({ path });
        return entries;
    }

    async delete(id) {
        const res = await this._request('DELETE', '/delete', { query: { id } });
        this._emit('item:deleted', { id });
        this.state.items = this.state.items.filter(e => e.id !== id);
        this._emit('listing:loaded', {
            items: this.state.items,
            breadcrumbs: this.state.breadcrumbs,
            path: this.state.path,
            page: this.state.page,
        });
        return res;
    }

    async rename(id, newName) {
        const res = await this._request('PATCH', '/rename', { query: { id, name: newName } });
        const entry = this.state.items.find(e => e.id === id);
        if (entry) entry.name = newName;
        this._emit('item:renamed', { id, name: newName });
        this._emit('listing:loaded', {
            items: this.state.items,
            breadcrumbs: this.state.breadcrumbs,
            path: this.state.path,
            page: this.state.page,
        });
        return res?.data;
    }

    async createFolder(path, name) {
        const res = await this._request('POST', '/create-folder', { query: { path, name } });
        this._emit('folder:created', { entry: res?.data });
        this.list({ path });
        return res?.data;
    }

    async createFile(name, path = this.state.path) {
        const res = await this._request('POST', '/create-file', { query: { name, path } });
        this._emit('file:created', { entry: res?.data });
        await this.list({ path });
        return res?.data;
    }

    view(id) {
        if (!this.endpoint) {
            const entry = this.state.items.find(e => e.id === id);
            if (entry?.url) window.open(entry.url, '_blank');
            return;
        }
        const url = this._urlFor('/view', { id });
        window.open(url, '_blank');
    }

    setView(view) {
        this.state.view = view;
        this._emit('view:changed', { view });
    }

    select(id, multi = false) {
        if (!multi) this.state.selection.clear();
        if (this.state.selection.has(id)) this.state.selection.delete(id);
        else this.state.selection.add(id);
        const selected = [...this.state.selection];
        this._emit('selection:changed', {
            selectedIds: selected,
            entries: this.state.items.filter(e => selected.includes(e.id)),
        });
    }

    // ==========================================================================
    // Event bus
    // ==========================================================================

    on(event, fn)  { (this._listeners[event] = this._listeners[event] || []).push(fn); return this; }
    off(event, fn) { if (this._listeners[event]) this._listeners[event] = this._listeners[event].filter(h => h !== fn); return this; }
    _emit(event, payload) {
        (this._listeners[event] || []).forEach(h => {
            try { h(payload, this); }
            catch (e) { console.error(`[FileBrowser] listener for "${event}" threw`, e); }
        });
    }

    // ==========================================================================
    // Renderers (subscribe each non-null mount to the relevant events)
    // ==========================================================================

    _wireRenderers() {
        if (this.mounts.grid)        this.on('listing:loaded', ({ items }) => this._renderGrid(items));
        if (this.mounts.list)        this.on('listing:loaded', ({ items }) => this._renderList(items));
        if (this.mounts.breadcrumbs) this.on('listing:loaded', ({ breadcrumbs }) => this._renderBreadcrumbs(breadcrumbs));
        if (this.mounts.emptyState)  this.on('listing:loaded', ({ items }) => this._toggleEmpty(items));
        if (this.mounts.tray) {
            this.on('upload:queued',   ({ uploadId, filename }) => this._renderUploadRow(uploadId, filename));
            this.on('upload:progress', ({ uploadId, pct })      => this._updateUploadRow(uploadId, pct, 'uploading'));
            this.on('upload:complete', ({ uploadId })            => this._updateUploadRow(uploadId, 100, 'done'));
            this.on('upload:error',    ({ uploadId })            => this._updateUploadRow(uploadId, 0, 'error'));
        }
        this._wireFolderNavigation();
    }

    _wireFolderNavigation() {
        if (!this.folderNavigation) return;
        const grid = this._el('grid');
        if (!grid) return;
        grid.addEventListener(this.folderNavigation, e => {
            // Don't treat button clicks (kebab, star, etc.) as folder navigation.
            if (e.target.closest('button')) return;
            const card = e.target.closest('[data-id]');
            if (!card) return;
            const entry = this.state.items.find(it => it.id === card.getAttribute('data-id'));
            if (entry?.isFolder) {
                this.cd(entry.path ? `${entry.path}/${entry.name}` : entry.name);
            }
        });
    }

    _el(key) {
        const ref = this.mounts[key];
        if (!ref) return null;
        return typeof ref === 'string' ? document.querySelector(ref) : ref;
    }

    _renderGrid(items) {
        const host = this._el('grid'); if (!host) return;
        host.textContent = '';
        items.forEach(item => {
            const tpl = item.isFolder ? 'folder-card' : 'grid-card';
            if (this.engine.has(tpl)) host.appendChild(this.engine.render(tpl, item));
        });
    }

    _renderList(items) {
        const host = this._el('list'); if (!host) return;
        host.textContent = '';
        if (!this.engine.has('list-row')) return;
        items.forEach(item => host.appendChild(this.engine.render('list-row', item)));
    }

    _renderBreadcrumbs(crumbs) {
        const host = this._el('breadcrumbs'); if (!host) return;
        host.textContent = '';
        if (!this.engine.has('breadcrumb-item')) return;
        crumbs.forEach(c => host.appendChild(this.engine.render('breadcrumb-item', c)));
    }

    _toggleEmpty(items) {
        const el = this._el('emptyState'); if (!el) return;
        el.hidden = (items && items.length > 0);
    }

    _renderUploadRow(uploadId, filename) {
        const host = this._el('tray'); if (!host) return;
        if (!this.engine.has('upload-progress-row')) return;
        const wrapper = document.createElement('div');
        wrapper.setAttribute('data-fb-upload-id', String(uploadId));
        wrapper.appendChild(this.engine.render('upload-progress-row', {
            uploadId, filename, pct: 0, status: 'queued',
        }));
        host.appendChild(wrapper);
    }

    _updateUploadRow(uploadId, pct, status) {
        const host = this._el('tray'); if (!host) return;
        const row = host.querySelector(`[data-fb-upload-id="${uploadId}"]`);
        if (!row) return;
        row.querySelectorAll('[data-fb-progress-bar]').forEach(bar => bar.style.width = pct + '%');
        row.querySelectorAll('[data-fb-progress-pct]').forEach(el => el.textContent = pct + '%');
        row.querySelectorAll('[data-fb-progress-status]').forEach(el => {
            el.textContent = status;
            el.setAttribute('data-status', status);
        });
        if (status === 'done') setTimeout(() => row.remove(), 1500);
    }

    // ==========================================================================
    // HTTP
    // ==========================================================================

    _urlFor(path, query) {
        if (!this.endpoint) return path;
        const base = this.endpoint + path;
        const merged = {
            ...(this.scope ? { scope_type: this.scope.type, scope_id: this.scope.id } : {}),
            ...(query || {}),
        };
        const qs = Object.entries(merged)
            .filter(([, v]) => v !== undefined && v !== null && v !== '')
            .map(([k, v]) => encodeURIComponent(k) + '=' + encodeURIComponent(v))
            .join('&');
        return qs ? `${base}?${qs}` : base;
    }

    async _request(method, path, { query, body } = {}) {
        if (!this.endpoint) { this._alertError(method, path, 'No endpoint configured'); throw new Error('No endpoint configured'); }
        const url = this._urlFor(path, query);
        const headers = { 'Accept': 'application/json' };
        if (body && !(body instanceof FormData)) headers['Content-Type'] = 'application/json';
        if (this.auth?.type === 'bearer' && this.auth.token) {
            headers['Authorization'] = 'Bearer ' + this.auth.token;
        }
        let res;
        try {
            res = await fetch(url, {
                method,
                headers,
                body: body instanceof FormData ? body : (body ? JSON.stringify(body) : undefined),
            });
        } catch (err) {
            this._alertError(method, url, `Network error — ${err.message}`);
            throw err;
        }
        if (!res.ok) {
            const detail = await this._extractErrorDetail(res);
            this._alertError(method, url, `HTTP ${res.status}${detail ? ' — ' + detail : ''}`);
            throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
    }

    async _extractErrorDetail(res) {
        try {
            const text = await res.clone().text();
            if (!text) return '';
            try {
                const j = JSON.parse(text);
                return j.message || j.error || '';
            } catch { return text.slice(0, 200); }
        } catch { return ''; }
    }

    _alertError(method, urlOrPath, message) {
        const msg = `[FileBrowser] ${method} ${urlOrPath}\n${message}`;
        console.error(msg);
        if (typeof alert === 'function') alert(msg);
    }

    _uploadOne(file, path, uploadId) {
        return new Promise((resolve, reject) => {
            if (!this.endpoint) {
                this._alertError('POST', '/upload', 'No endpoint configured');
                reject(new Error('No endpoint configured'));
                return;
            }
            const url = this._urlFor('/upload');
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url);
            if (this.auth?.type === 'bearer' && this.auth.token) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + this.auth.token);
            }
            xhr.upload.addEventListener('progress', e => {
                if (e.lengthComputable) {
                    this._emit('upload:progress', { uploadId, pct: Math.round((e.loaded / e.total) * 100) });
                }
            });
            xhr.addEventListener('load', () => {
                try {
                    const body = JSON.parse(xhr.responseText || '{}');
                    if (xhr.status >= 200 && xhr.status < 300 && body.success) return resolve(body);
                    const detail = body.message || body.error || '';
                    this._alertError('POST', url, `HTTP ${xhr.status}${detail ? ' — ' + detail : ''} (file: ${file.name})`);
                    reject(new Error(detail || `HTTP ${xhr.status}`));
                } catch (e) {
                    this._alertError('POST', url, `Invalid JSON response (file: ${file.name})`);
                    reject(e);
                }
            });
            xhr.addEventListener('error', () => {
                this._alertError('POST', url, `Network error (file: ${file.name})`);
                reject(new Error('Network error'));
            });

            const form = new FormData();
            form.append('file', file);
            form.append('path', path || '');
            if (this.scope) {
                form.append('scope_type', this.scope.type);
                if (this.scope.id != null) form.append('scope_id', String(this.scope.id));
            }
            xhr.send(form);
        });
    }

}

if (typeof window !== 'undefined') {
    window.FileBrowser = FileBrowser;
}

// TemplateEngine.js
// Minimal directive-driven renderer for the FileBrowser library.
// Zero dependency. Parses <template data-fb-tpl="..."> from an HTML file
// and renders DocumentFragments with data bindings.
//
// Directives (attached to any element inside a template):
//   data-fb-text="path[|filter]"                          -> textContent
//   data-fb-attr="name:path[|filter];name2:path2"          -> setAttribute
//   data-fb-class="static {path[|filter]} {path2}"         -> add classes
//   data-fb-class-if="path:className[;path2:className2]"   -> toggle classes
//   data-fb-if="path"                                      -> remove element if falsy
//   data-fb-if-not="path"                                  -> remove element if truthy
//   data-fb-each="path:tplName"                            -> repeat inner by rendering
//                                                             another registered template
//
// Path syntax supports dot access: "meta.childCount".
// Built-in filters: bytes, ago, icon, upper, lower. Consumers can addFilter().

export class TemplateEngine {
    constructor() {
        this._templates = new Map();
        this._filters = new Map();
        this._registerBuiltinFilters();
    }

    async loadFromUrl(url) {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`TemplateEngine: failed to fetch ${url} (${res.status})`);
        this.loadFromHtml(await res.text());
        return this;
    }

    loadFromHtml(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        doc.querySelectorAll('template[data-fb-tpl]').forEach(tpl => {
            this._templates.set(tpl.getAttribute('data-fb-tpl'), tpl);
        });
        return this;
    }

    register(name, html) {
        const tpl = document.createElement('template');
        tpl.innerHTML = html;
        this._templates.set(name, tpl);
        return this;
    }

    has(name) { return this._templates.has(name); }

    addFilter(name, fn) { this._filters.set(name, fn); return this; }

    render(name, data = {}) {
        const tpl = this._templates.get(name);
        if (!tpl) throw new Error(`TemplateEngine: template "${name}" not registered`);
        const frag = tpl.content.cloneNode(true);
        this._apply(frag, data);
        return frag;
    }

    // ---------- internals ----------

    _apply(root, data) {
        this._applyIfPass(root, data);
        this._applyEachPass(root, data);
        this._applyBindPass(root, data);
    }

    _applyIfPass(root, data) {
        Array.from(root.querySelectorAll('[data-fb-if], [data-fb-if-not]')).forEach(el => {
            if (!el.isConnected && el.parentNode == null) return;
            const ifAttr = el.getAttribute('data-fb-if');
            const ifNot = el.getAttribute('data-fb-if-not');
            if (ifAttr !== null && !this._truthy(this._resolve(data, ifAttr))) {
                el.remove(); return;
            }
            if (ifNot !== null && this._truthy(this._resolve(data, ifNot))) {
                el.remove(); return;
            }
            if (ifAttr !== null) el.removeAttribute('data-fb-if');
            if (ifNot !== null) el.removeAttribute('data-fb-if-not');
        });
    }

    _applyEachPass(root, data) {
        // Only top-level each per render pass; nested templates each-render themselves.
        Array.from(root.querySelectorAll('[data-fb-each]')).forEach(el => {
            if (!el.parentNode) return;
            const raw = el.getAttribute('data-fb-each');
            el.removeAttribute('data-fb-each');
            const [collPath, tplName] = raw.split(':').map(s => s.trim());
            const coll = this._resolve(data, collPath) || [];
            el.textContent = '';
            if (!tplName || !this.has(tplName)) return;
            for (const item of coll) el.appendChild(this.render(tplName, item));
        });
    }

    _applyBindPass(root, data) {
        root.querySelectorAll('*').forEach(el => this._applyOne(el, data));
    }

    _applyOne(el, data) {
        const txt = el.getAttribute('data-fb-text');
        if (txt !== null) {
            el.textContent = this._pipe(data, txt);
            el.removeAttribute('data-fb-text');
        }

        const attr = el.getAttribute('data-fb-attr');
        if (attr !== null) {
            attr.split(';').forEach(pair => {
                const idx = pair.indexOf(':');
                if (idx < 0) return;
                const name = pair.slice(0, idx).trim();
                const expr = pair.slice(idx + 1).trim();
                if (!name || !expr) return;
                const val = this._pipe(data, expr);
                if (val == null || val === false || val === '') el.removeAttribute(name);
                else el.setAttribute(name, val);
            });
            el.removeAttribute('data-fb-attr');
        }

        const cls = el.getAttribute('data-fb-class');
        if (cls !== null) {
            const resolved = cls.replace(/\{([^}]+)\}/g, (_, expr) => {
                const v = this._pipe(data, expr.trim());
                return v == null ? '' : String(v);
            });
            resolved.split(/\s+/).filter(Boolean).forEach(c => el.classList.add(c));
            el.removeAttribute('data-fb-class');
        }

        const ci = el.getAttribute('data-fb-class-if');
        if (ci !== null) {
            ci.split(';').forEach(pair => {
                const idx = pair.indexOf(':');
                if (idx < 0) return;
                const path = pair.slice(0, idx).trim();
                const klass = pair.slice(idx + 1).trim();
                if (path && klass && this._truthy(this._resolve(data, path))) el.classList.add(klass);
            });
            el.removeAttribute('data-fb-class-if');
        }
    }

    _pipe(data, expr) {
        const [path, ...filters] = expr.split('|').map(s => s.trim());
        let val = this._resolve(data, path);
        for (const f of filters) {
            const fn = this._filters.get(f);
            if (fn) val = fn(val, data);
        }
        return val == null ? '' : val;
    }

    _resolve(data, path) {
        if (!path || path === '.') return data;
        return path.split('.').reduce((obj, key) => (obj == null ? obj : obj[key]), data);
    }

    _truthy(v) {
        if (v == null || v === false || v === '' || v === 0) return false;
        if (Array.isArray(v) && v.length === 0) return false;
        return true;
    }

    _registerBuiltinFilters() {
        this.addFilter('bytes', filterBytes);
        this.addFilter('ago', filterAgo);
        this.addFilter('icon', filterIcon);
        this.addFilter('upper', v => String(v ?? '').toUpperCase());
        this.addFilter('lower', v => String(v ?? '').toLowerCase());
    }
}

function filterBytes(n) {
    if (n == null || isNaN(Number(n))) return '';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let x = Number(n), i = 0;
    while (x >= 1024 && i < units.length - 1) { x /= 1024; i++; }
    return (i === 0 ? x : x.toFixed(1)) + ' ' + units[i];
}

function filterAgo(t) {
    if (!t) return '';
    const sec = Math.floor(Date.now() / 1000 - Number(t));
    if (sec < 60) return 'just now';
    if (sec < 3600) return Math.floor(sec / 60) + 'm ago';
    if (sec < 86400) return Math.floor(sec / 3600) + 'h ago';
    if (sec < 2592000) return Math.floor(sec / 86400) + 'd ago';
    return new Date(Number(t) * 1000).toLocaleDateString();
}

function filterIcon(kind) {
    const map = {
        folder: 'folder',
        image: 'image',
        pdf: 'picture_as_pdf',
        doc: 'description',
        sheet: 'table_view',
        video: 'movie',
        audio: 'audiotrack',
        archive: 'folder_zip',
        code: 'code',
        text: 'article',
    };
    return map[kind] || 'insert_drive_file';
}

if (typeof window !== 'undefined') {
    window.TemplateEngine = TemplateEngine;
}

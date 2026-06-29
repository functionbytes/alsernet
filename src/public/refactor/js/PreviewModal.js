/**
 * PreviewModal — Full-chrome template/content preview modal with device toggle.
 *
 * Distinct from McPopup: renders an iframe stage wrapped in either a macOS-style
 * browser chrome (desktop / tablet) or a phone chassis (mobile). Used for
 * template previews that would otherwise open in a new tab.
 *
 * Usage (API):
 *   McPreviewModal.open({
 *       url: '/rui/admin/email-templates/abc/preview',
 *       title: 'Preview: Welcome email',
 *       subtitle: 'Base template',
 *       cta: { label: 'Create email', href: '/campaigns/create?tpl=abc' },
 *       devices: ['desktop', 'tablet', 'mobile'],
 *       initialDevice: 'desktop',
 *       onClose: () => {}
 *   });
 *
 * Usage (data attributes — auto-wired):
 *   <a data-preview="/url"
 *      data-preview-title="Preview: X"
 *      data-preview-subtitle="Base template"
 *      data-preview-cta-label="Use template"
 *      data-preview-cta-href="/url/use"
 *      data-preview-edit-href="/url/edit"
 *      data-preview-edit-label="Edit">Preview</a>
 *
 * Multi-action API (programmatic):
 *   McPreviewModal.open({
 *       url: '/preview',
 *       actions: [
 *           { label: 'Edit', href: '/edit', icon: 'edit', variant: 'secondary' },
 *           { label: 'Use template', href: '/use', variant: 'primary' },
 *       ],
 *   });
 */
class PreviewModal {
    constructor() {
        this.backdrop = null;
        this.modal = null;
        this.iframe = null;
        this.currentDevice = 'desktop';
        this.triggerElement = null;
        this.currentOptions = null;
        this._keyHandler = null;
    }

    init() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-preview]');
            if (!trigger) return;
            const url = trigger.dataset.preview;
            if (!url || url === 'true' || url === '1') return;
            // Stop other document-level delegates (e.g. SidePanel attached to
            // the same parent card) from also firing for this click. Without
            // this, clicking an eye-icon inside a card with data-side-panel
            // would open BOTH the modal and the side panel.
            e.preventDefault();
            e.stopImmediatePropagation();
            this.triggerElement = trigger;
            const actions = [];
            if (trigger.dataset.previewEditLabel || trigger.dataset.previewEditHref) {
                actions.push({
                    label: trigger.dataset.previewEditLabel || 'Edit',
                    href: trigger.dataset.previewEditHref || '#',
                    icon: 'edit',
                    variant: 'secondary',
                });
            }
            if (trigger.dataset.previewCtaLabel) {
                actions.push({
                    label: trigger.dataset.previewCtaLabel,
                    href: trigger.dataset.previewCtaHref || '#',
                    variant: 'primary',
                });
            }
            const chassis = trigger.dataset.previewChassis || 'browser';
            const openOpts = {
                url,
                title: trigger.dataset.previewTitle || '',
                subtitle: trigger.dataset.previewSubtitle || '',
                actions: actions.length ? actions : null,
                chassis,
            };
            // Popup chassis = single viewport (popup frames are responsive
            // internally). Browser chassis keeps the 3-device toggle.
            if (chassis === 'popup') openOpts.devices = ['desktop'];
            this.open(openOpts);
        });
    }

    open(options = {}) {
        if (!options.url) throw new Error('McPreviewModal.open: url required');
        if (this.modal) this._destroy();

        this.currentOptions = Object.assign({
            devices: ['desktop', 'tablet', 'mobile'],
            initialDevice: 'desktop',
            title: 'Preview',
            subtitle: '',
            cta: null,
            actions: null,
            chassis: 'browser', // 'browser' | 'popup'
            onClose: null,
        }, options);

        // Normalize: allow legacy `cta` → actions[]
        if (!this.currentOptions.actions && this.currentOptions.cta) {
            this.currentOptions.actions = [Object.assign(
                { variant: 'primary' },
                this.currentOptions.cta
            )];
        }

        // Popup chassis defaults to a single viewport (form previews don't have
        // device variants). Caller can still pass devices: [...] explicitly to
        // override.
        if (this.currentOptions.chassis === 'popup' && !options.devices) {
            this.currentOptions.devices = ['desktop'];
        }

        this.currentDevice = this.currentOptions.initialDevice;
        this._build();
        this._attachEvents();

        document.body.style.overflow = 'hidden';

        requestAnimationFrame(() => {
            this.backdrop.classList.add('active');
            this.modal.classList.add('active');
            const closeBtn = this.modal.querySelector('.mc-preview-modal-close');
            if (closeBtn) closeBtn.focus();
        });
    }

    close() {
        if (!this.modal) return;
        const cb = this.currentOptions && this.currentOptions.onClose;
        const trigger = this.triggerElement;

        this.backdrop.classList.remove('active');
        this.modal.classList.remove('active');

        setTimeout(() => {
            this._destroy();
            if (trigger && typeof trigger.focus === 'function') {
                try { trigger.focus(); } catch (e) {}
            }
            if (typeof cb === 'function') cb();
        }, 260);
    }

    setDevice(device) {
        if (!this.modal) return;
        if (['desktop', 'tablet', 'mobile'].indexOf(device) === -1) return;
        this.currentDevice = device;
        const frame = this.modal.querySelector('.mc-preview-modal-frame');
        if (frame) {
            frame.setAttribute('data-device', device);
        }
        this.modal.querySelectorAll('.mc-preview-modal-device-btn').forEach(btn => {
            const active = btn.dataset.device === device;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    _deviceIcon(device) {
        if (device === 'desktop') {
            return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>`;
        }
        if (device === 'tablet') {
            return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="2" width="16" height="20" rx="2"/>
                <line x1="12" y1="18" x2="12.01" y2="18"/>
            </svg>`;
        }
        // mobile
        return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="6" y="2" width="12" height="20" rx="2"/>
            <line x1="12" y1="18" x2="12.01" y2="18"/>
        </svg>`;
    }

    _deviceLabel(device) {
        return ({ desktop: 'Desktop preview', tablet: 'Tablet preview', mobile: 'Mobile preview' })[device] || device;
    }

    _actionHtml(action) {
        const variant = action.variant === 'primary' ? 'mc-btn-primary' : 'mc-btn-secondary';
        const icon = action.icon ? this._actionIcon(action.icon) : '';
        return `<a class="mc-btn ${variant} mc-btn-sm mc-preview-modal-action"
                   data-variant="${this._escAttr(action.variant || 'secondary')}"
                   href="${this._escAttr(action.href || '#')}">
                ${icon}${this._esc(action.label)}
            </a>`;
    }

    _actionIcon(name) {
        const icons = {
            edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>',
            copy: '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
            external: '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        };
        const path = icons[name];
        if (!path) return '';
        return `<svg class="mc-preview-modal-action-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
    }

    _build() {
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'mc-preview-modal-backdrop';

        this.modal = document.createElement('div');
        this.modal.className = 'mc-preview-modal';
        this.modal.setAttribute('role', 'dialog');
        this.modal.setAttribute('aria-modal', 'true');
        this.modal.setAttribute('aria-label', this.currentOptions.title || 'Preview');

        const actions = this.currentOptions.actions || [];
        const actionsHtml = actions.length ? `
            <div class="mc-preview-modal-actions">
                ${actions.map((a) => this._actionHtml(a)).join('')}
            </div>` : '';

        const deviceBtns = this.currentOptions.devices.map(device => `
            <button type="button"
                    class="mc-preview-modal-device-btn ${this.currentDevice === device ? 'is-active' : ''}"
                    data-device="${device}"
                    aria-pressed="${this.currentDevice === device ? 'true' : 'false'}"
                    aria-label="${this._escAttr(this._deviceLabel(device))}">
                ${this._deviceIcon(device)}
            </button>
        `).join('');

        this.modal.innerHTML = `
            <div class="mc-preview-modal-header">
                <div class="mc-preview-modal-titlebox">
                    <h2 class="mc-preview-modal-title">${this._esc(this.currentOptions.title)}</h2>
                    ${this.currentOptions.subtitle ? `<div class="mc-preview-modal-subtitle">${this._esc(this.currentOptions.subtitle)}</div>` : ''}
                </div>
                <button type="button" class="mc-preview-modal-close" aria-label="Close preview">
                    <span class="mc-preview-modal-close-label">Close</span>
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M5 5l10 10M15 5L5 15"/>
                    </svg>
                </button>
            </div>
            <div class="mc-preview-modal-toolbar">
                <div class="mc-preview-modal-devices" role="group" aria-label="Preview device">
                    ${deviceBtns}
                </div>
                ${actionsHtml}
            </div>
            <div class="mc-preview-modal-stage">
                <div class="mc-preview-modal-frame" data-device="${this.currentDevice}" data-chassis="${this._escAttr(this.currentOptions.chassis)}">
                    <!-- Browser chrome (desktop + tablet) — dots only, macOS-style -->
                    <div class="mc-preview-modal-browser-chrome" aria-hidden="true">
                        <div class="mc-preview-modal-browser-dots">
                            <span class="mc-preview-modal-browser-dot"></span>
                            <span class="mc-preview-modal-browser-dot"></span>
                            <span class="mc-preview-modal-browser-dot"></span>
                        </div>
                    </div>
                    <!-- Phone speaker bar (mobile only, on black chassis) -->
                    <div class="mc-preview-modal-phone-speaker" aria-hidden="true"></div>
                    <div class="mc-preview-modal-iframe-wrap">
                        <div class="mc-preview-modal-loading">
                            <div class="mc-spinner mc-spinner-lg"></div>
                        </div>
                        <iframe class="mc-preview-modal-iframe"
                                src="${this._escAttr(this.currentOptions.url)}"
                                sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-forms"
                                scrolling="yes"
                                title="${this._escAttr(this.currentOptions.title)}"></iframe>
                    </div>
                    <!-- Phone home indicator (mobile only) -->
                    <div class="mc-preview-modal-phone-home" aria-hidden="true"></div>
                    <!-- Corner close (popup chassis only) -->
                    <button type="button" class="mc-preview-modal-corner-close"
                            aria-label="Close preview"
                            aria-hidden="${this.currentOptions.chassis === 'popup' ? 'false' : 'true'}">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M5 5l10 10M15 5L5 15"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(this.backdrop);
        document.body.appendChild(this.modal);

        this.iframe = this.modal.querySelector('.mc-preview-modal-iframe');
        const loading = this.modal.querySelector('.mc-preview-modal-loading');
        if (this.iframe && loading) {
            this.iframe.addEventListener('load', () => {
                loading.classList.add('is-hidden');
                this._injectIframeScrollbarStyles();
            }, { once: true });
        }
    }

    /**
     * Inject visible-scrollbar styles into the iframe's document so the preview
     * frame feels like a real browser/device. macOS auto-hides iframe scrollbars
     * by default, which made users think scroll was broken. Same-origin only —
     * gracefully skipped for cross-origin previews (they keep native behavior).
     */
    _injectIframeScrollbarStyles() {
        if (!this.iframe) return;
        try {
            const doc = this.iframe.contentDocument;
            if (!doc) return;
            if (doc.getElementById('mc-preview-modal-scrollbar-style')) return;

            const style = doc.createElement('style');
            style.id = 'mc-preview-modal-scrollbar-style';
            // !important on every rule so template CSS can't override.
            // Template authors sometimes style their own ::-webkit-scrollbar to
            // match brand colors, which would leak into the preview and look
            // broken. We force a neutral, subtle scrollbar every time.
            // macOS/Chrome default to "overlay" scrollbars that auto-hide unless
            // the user is actively scrolling — which makes preview feel broken.
            // -webkit-appearance: none forces a classic, always-visible scrollbar
            // (like a real browser chrome) regardless of OS setting. `overflow: auto`
            // on html is redundant safety for templates that set `html { overflow: hidden }`.
            style.textContent = `
                html { scrollbar-width: thin !important; scrollbar-color: rgba(0,0,0,.4) rgba(0,0,0,.06) !important; overflow-y: auto !important; }
                body { overflow-y: visible !important; }
                ::-webkit-scrollbar {
                    -webkit-appearance: none !important;
                    width: 12px !important;
                    height: 12px !important;
                    background: rgba(0,0,0,.04) !important;
                }
                ::-webkit-scrollbar-track {
                    background: rgba(0,0,0,.04) !important;
                    border: none !important;
                    border-left: 1px solid rgba(0,0,0,.06) !important;
                }
                ::-webkit-scrollbar-thumb {
                    background-color: rgba(0,0,0,.38) !important;
                    border-radius: 6px !important;
                    border: 2px solid transparent !important;
                    background-clip: padding-box !important;
                    min-height: 40px !important;
                }
                ::-webkit-scrollbar-thumb:hover { background-color: rgba(0,0,0,.55) !important; }
                ::-webkit-scrollbar-corner { background: transparent !important; }
            `;
            (doc.head || doc.documentElement).appendChild(style);
        } catch (e) {
            // cross-origin — skip silently
        }
    }

    _attachEvents() {
        this.backdrop.addEventListener('click', () => this.close());

        this.modal.querySelector('.mc-preview-modal-close')
            .addEventListener('click', () => this.close());

        const cornerClose = this.modal.querySelector('.mc-preview-modal-corner-close');
        if (cornerClose) cornerClose.addEventListener('click', () => this.close());

        this.modal.querySelectorAll('.mc-preview-modal-device-btn').forEach(btn => {
            btn.addEventListener('click', () => this.setDevice(btn.dataset.device));
        });

        this._keyHandler = (e) => {
            if (e.key === 'Escape' && this.modal) {
                this.close();
            }
        };
        document.addEventListener('keydown', this._keyHandler);
    }

    _destroy() {
        if (this._keyHandler) {
            document.removeEventListener('keydown', this._keyHandler);
            this._keyHandler = null;
        }
        if (this.backdrop && this.backdrop.parentNode) this.backdrop.remove();
        if (this.modal && this.modal.parentNode) this.modal.remove();
        document.body.style.overflow = '';
        this.backdrop = null;
        this.modal = null;
        this.iframe = null;
        this.currentOptions = null;
        this.triggerElement = null;
    }

    _esc(text) {
        if (text == null) return '';
        const d = document.createElement('div');
        d.textContent = String(text);
        return d.innerHTML;
    }
    _escAttr(text) {
        return this._esc(text).replace(/"/g, '&quot;');
    }
}

window.McPreviewModal = new PreviewModal();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.McPreviewModal.init());
} else {
    window.McPreviewModal.init();
}

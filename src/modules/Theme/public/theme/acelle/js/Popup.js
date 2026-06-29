/**
 * Popup — Modal/popup management with AJAX content loading.
 *
 * Usage:
 *   Popup.open({ url: '/some/page', title: 'Edit Item', size: 'lg' });
 *   Popup.open({ content: '<p>Hello</p>', title: 'Static' });
 *   Popup.close();
 */
class Popup {
    constructor() {
        this.backdrop = null;
        this.modal = null;
        this.stack = [];
        this._history = [];
    }

    /**
     * Initialize popup system.
     */
    init() {
        // Handle [data-popup] links
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-popup]');
            if (trigger) {
                e.preventDefault();
                const popupAttr = trigger.dataset.popup;
                const url = (popupAttr && popupAttr !== 'true' && popupAttr !== '1') ? popupAttr : trigger.href;
                const title = trigger.dataset.popupTitle || '';
                const size = trigger.dataset.popupSize || '';
                this.open({ url, title, size });
            }
        });

        // Escape key closes
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal) {
                this.close();
            }
        });
    }

    /**
     * Open a modal popup.
     * @param {Object} options - { url, content, title, size, onClose }
     */
    async open(options = {}) {
        this._createElements(options);

        if (options.url) {
            await this._loadContent(options.url);
        } else if (options.content) {
            this.modal.querySelector('.mc-modal-body').innerHTML = options.content;
        }

        // Animate in
        requestAnimationFrame(() => {
            this.backdrop.classList.add('active');
            this.modal.classList.add('active');
        });

        document.body.style.overflow = 'hidden';
        this.stack.push({ options, modal: this.modal, backdrop: this.backdrop });
    }

    /**
     * Close the current modal.
     */
    close() {
        if (!this.modal) return;

        const current = this.stack.pop();
        if (!current) return;

        current.modal.classList.remove('active');
        current.backdrop.classList.remove('active');

        // Clear history for this popup
        this._history = [];

        setTimeout(() => {
            current.modal.remove();
            current.backdrop.remove();

            if (this.stack.length > 0) {
                const prev = this.stack[this.stack.length - 1];
                this.modal = prev.modal;
                this.backdrop = prev.backdrop;
            } else {
                this.modal = null;
                this.backdrop = null;
                document.body.style.overflow = '';
            }

            if (current.options.onClose) {
                current.options.onClose();
            }
        }, 250);
    }

    /**
     * Navigate to a new URL within the current popup (replaces body content).
     * If no popup is open, opens a new one.
     * @param {string} url - URL to load
     * @param {function} callback - Optional callback after load
     */
    async navigate(url, callback) {
        if (!this.modal) {
            return this.open({ url });
        }

        // Push current body to history for goBack()
        var body = this.modal.querySelector('.mc-modal-body');
        this._history.push(body.innerHTML);

        // Show loading
        body.innerHTML = '<div class="mc-popup-loading"><div class="mc-spinner mc-spinner-lg"></div></div>';

        // Load new content
        await this._loadContent(url);

        if (callback) callback();
    }

    /**
     * Go back to previous content within the popup.
     * If no history, closes the popup.
     */
    goBack() {
        if (this._history.length > 0) {
            var prev = this._history.pop();
            var body = this.modal.querySelector('.mc-modal-body');
            body.innerHTML = prev;
            this._initLoadedContent();
        } else {
            this.close();
        }
    }

    /**
     * Show loading state in current popup body.
     */
    loading() {
        if (!this.modal) return;
        var body = this.modal.querySelector('.mc-modal-body');
        if (body) {
            body.innerHTML = '<div class="mc-popup-loading"><div class="mc-spinner mc-spinner-lg"></div></div>';
        }
    }

    /**
     * Show mask overlay on current popup (for form submissions).
     */
    mask(text) {
        if (!this.modal) return;
        var existing = this.modal.querySelector('.mc-popup-mask');
        if (existing) return;
        var mask = document.createElement('div');
        mask.className = 'mc-popup-mask';
        mask.innerHTML = '<div class="mc-spinner mc-spinner-lg"></div>' + (text ? '<div class="mc-popup-mask-text">' + text + '</div>' : '');
        this.modal.appendChild(mask);
    }

    /**
     * Remove mask overlay from current popup.
     */
    unmask() {
        if (!this.modal) return;
        var mask = this.modal.querySelector('.mc-popup-mask');
        if (mask) mask.remove();
    }

    /**
     * Set popup body content from raw HTML string.
     * @param {string} html
     */
    setContent(html) {
        if (!this.modal) return;
        var body = this.modal.querySelector('.mc-modal-body');
        if (body) {
            body.innerHTML = html;
            this._initLoadedContent();
        }
    }

    /**
     * Create modal DOM elements.
     */
    _createElements(options) {
        // Backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'mc-modal-backdrop';
        this.backdrop.addEventListener('click', () => this.close());

        // Modal
        const sizeClass = options.size ? `mc-modal-${options.size}` : '';
        this.modal = document.createElement('div');
        this.modal.className = `mc-modal ${sizeClass}`;
        this.modal.innerHTML = `
            ${options.title ? `
                <div class="mc-modal-header">
                    <h3 class="mc-modal-title">${this._escapeHtml(options.title)}</h3>
                    <button class="mc-modal-close" aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M5 5l10 10M15 5L5 15"/>
                        </svg>
                    </button>
                </div>
            ` : ''}
            <div class="mc-modal-body">
                <div class="mc-popup-loading">
                    <div class="mc-spinner mc-spinner-lg"></div>
                </div>
            </div>
        `;

        // Close button
        const closeBtn = this.modal.querySelector('.mc-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        document.body.appendChild(this.backdrop);
        document.body.appendChild(this.modal);
    }

    /**
     * Load content via AJAX.
     */
    async _loadContent(url) {
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            if (this.modal) {
                this.modal.querySelector('.mc-modal-body').innerHTML = html;
                this._initLoadedContent();
            }
        } catch (error) {
            console.error('Popup load error:', error);
            if (this.modal) {
                this.modal.querySelector('.mc-modal-body').innerHTML =
                    '<div class="mc-alert mc-alert-danger"><div class="mc-alert-content">Failed to load content</div></div>';
            }
        }
    }

    /**
     * Initialize interactive elements in loaded popup content.
     * - McForm for AJAX form submission
     * - data-popup-close for cancel buttons
     */
    _initLoadedContent() {
        if (!this.modal) return;

        // Execute <script> tags loaded via innerHTML (innerHTML ignores them)
        this.modal.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            oldScript.replaceWith(newScript);
        });

        // Init AJAX forms inside popup
        this.modal.querySelectorAll('[data-mc-form]').forEach(form => {
            if (!window.McForm) return;
            new window.McForm(form, {
                onSuccess: (data) => {
                    this.close();
                    if (data.message && window.McNotify) window.McNotify.success(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        document.dispatchEvent(new CustomEvent('list:reload'));
                    }
                }
            });
        });

        // Cancel/close buttons
        this.modal.querySelectorAll('[data-popup-close]').forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });

        // Back buttons
        this.modal.querySelectorAll('[data-popup-back]').forEach(btn => {
            btn.addEventListener('click', () => this.goBack());
        });

        // Init RichSelect in loaded content
        if (window.McRichSelect) {
            this.modal.querySelectorAll('[data-rich-select]').forEach(el => {
                if (!el._richSelect) el._richSelect = new McRichSelect(el);
            });
        }

        // Init TagInput in loaded content
        if (window.McTagInput && window.McTagInput.init) {
            this.modal.querySelectorAll('[data-mc-tag-input]').forEach(el => {
                if (!el.getAttribute('data-mc-tag-initialized')) new McTagInput(el);
            });
        }

        // Init IdentitySelect in loaded content
        if (window.McIdentitySelect && window.McIdentitySelect.IdentitySelect) {
            this.modal.querySelectorAll('[data-mc-identity-select]').forEach(el => {
                if (!el._identitySelect) el._identitySelect = new McIdentitySelect.IdentitySelect(el);
            });
        }

        // Init FileUpload in loaded content
        if (window.McFileUpload) {
            this.modal.querySelectorAll('.mc-file-upload[data-upload-url]').forEach(el => {
                if (!el._mcFileUpload) el._mcFileUpload = new McFileUpload(el);
            });
        }
    }

    /**
     * Escape HTML.
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export singleton
window.McPopup = new Popup();

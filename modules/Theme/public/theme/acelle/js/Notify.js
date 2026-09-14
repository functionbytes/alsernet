/**
 * Notify — Toast notification system.
 *
 * Usage:
 *   Notify.success('Saved!');
 *   Notify.error('Something went wrong');
 *   Notify.info('Processing...');
 *   Notify.warning('Check your input');
 */
class Notify {
    constructor() {
        this.container = null;
        this.defaultDuration = 4000;
    }

    /**
     * Initialize the notification container.
     */
    init() {
        if (this.container) return;

        this.container = document.createElement('div');
        this.container.className = 'mc-toast-container';
        document.body.appendChild(this.container);
    }

    /**
     * Show a toast notification.
     * @param {string} message - The message to display
     * @param {string} type - 'success' | 'error' | 'warning' | 'info'
     * @param {number} duration - Auto-dismiss in ms (0 = manual close only)
     */
    show(message, type = 'info', duration = this.defaultDuration) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `mc-toast ${type}`;

        const icon = this._getIcon(type);

        toast.innerHTML = `
            ${icon}
            <span class="mc-toast-message">${this._escapeHtml(message)}</span>
            <button class="mc-toast-close" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4l8 8M12 4l-8 8"/>
                </svg>
            </button>
        `;

        // Close button handler
        toast.querySelector('.mc-toast-close').addEventListener('click', () => {
            this._remove(toast);
        });

        this.container.appendChild(toast);

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => this._remove(toast), duration);
        }

        return toast;
    }

    /**
     * Show success toast.
     */
    success(message, duration) {
        return this.show(message, 'success', duration);
    }

    /**
     * Show error toast.
     */
    error(message, duration) {
        return this.show(message, 'error', duration);
    }

    /**
     * Show warning toast.
     */
    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    /**
     * Show info toast.
     */
    info(message, duration) {
        return this.show(message, 'info', duration);
    }

    /**
     * Remove a toast with animation.
     * @param {HTMLElement} toast
     */
    _remove(toast) {
        if (!toast || !toast.parentElement) return;
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 200);
    }

    /**
     * Get SVG icon for toast type.
     * @param {string} type
     * @returns {string}
     */
    _getIcon(type) {
        const svgs = {
            success: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7.5l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            error:   '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 3l8 8M11 3L3 11" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>',
            warning: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1.5L13 12H1L7 1.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7 5.5v3M7 10v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
            info:    '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 6v5M7 3.5v.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>',
        };
        const svg = svgs[type] || svgs.info;
        return `<span class="mc-toast-icon mc-toast-icon--${type}">${svg}</span>`;
    }

    /**
     * Escape HTML to prevent XSS in toast messages.
     * @param {string} text
     * @returns {string}
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export singleton
window.McNotify = new Notify();

/**
 * McDialog — Confirm and alert dialogs.
 *
 * Usage:
 *   McDialog.confirm({
 *       title: 'Delete item?',
 *       message: 'This action cannot be undone.',
 *       type: 'danger',           // 'danger', 'warning', 'info'
 *       confirmText: 'Delete',
 *       cancelText: 'Cancel',
 *       onConfirm: () => { ... },
 *       onCancel: () => { ... },
 *   });
 *
 *   McDialog.alert({
 *       title: 'Success!',
 *       message: 'Item has been saved.',
 *       type: 'info',
 *   });
 */
class Dialog {
    constructor() {
        // noop
    }

    /**
     * Show a confirm dialog.
     */
    confirm(options = {}) {
        const {
            title = 'Are you sure?',
            message = '',
            type = 'warning',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            onConfirm = null,
            onCancel = null,
        } = options;

        const icons = {
            danger: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 8l8 8M16 8l-8 8"/></svg>',
            warning: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l10.39 18H1.61L12 2z" stroke-linejoin="round"/><path d="M12 10v4M12 17v1" stroke-linecap="round"/></svg>',
            info: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 11v5M12 7v1" stroke-linecap="round"/></svg>',
            success: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        };

        const backdrop = document.createElement('div');
        backdrop.className = 'mc-modal-backdrop';

        const modal = document.createElement('div');
        modal.className = 'mc-modal mc-modal-sm';

        var cancelBtn = cancelText
            ? '<button class="mc-btn mc-btn-secondary" data-dialog-cancel>' + this._escapeHtml(cancelText) + '</button>'
            : '';

        modal.innerHTML =
            '<div class="mc-confirm-body">' +
                '<div class="mc-confirm-icon ' + type + '">' +
                    (icons[type] || icons.warning) +
                '</div>' +
                '<div class="mc-confirm-title">' + this._escapeHtml(title) + '</div>' +
                (message ? '<div class="mc-confirm-text">' + this._escapeHtml(message) + '</div>' : '') +
                '<div class="mc-confirm-actions">' +
                    cancelBtn +
                    '<button class="mc-btn ' + (type === 'danger' ? 'mc-btn-danger' : 'mc-btn-primary') + '" data-dialog-confirm>' + this._escapeHtml(confirmText) + '</button>' +
                '</div>' +
            '</div>';

        const close = () => {
            modal.classList.remove('active');
            backdrop.classList.remove('active');
            setTimeout(() => {
                modal.remove();
                backdrop.remove();
            }, 250);
        };

        modal.querySelector('[data-dialog-confirm]').addEventListener('click', () => {
            close();
            if (onConfirm) onConfirm();
        });

        var cancelEl = modal.querySelector('[data-dialog-cancel]');
        if (cancelEl) {
            cancelEl.addEventListener('click', () => {
                close();
                if (onCancel) onCancel();
            });
        }

        backdrop.addEventListener('click', () => {
            close();
            if (onCancel) onCancel();
        });

        document.body.appendChild(backdrop);
        document.body.appendChild(modal);

        // Focus confirm button for keyboard accessibility
        requestAnimationFrame(() => {
            backdrop.classList.add('active');
            modal.classList.add('active');
            modal.querySelector('[data-dialog-confirm]').focus();
        });

        // Escape key closes
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                close();
                if (onCancel) onCancel();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }

    /**
     * Show an alert dialog (single OK button).
     */
    alert(options = {}) {
        const {
            title = 'Notice',
            message = '',
            type = 'info',
            okText = 'OK',
            onOk = null,
        } = options;

        this.confirm({
            title,
            message,
            type,
            confirmText: okText,
            cancelText: '',
            onConfirm: onOk,
        });
    }

    /**
     * Escape HTML to prevent XSS.
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export singleton
window.McDialog = new Dialog();

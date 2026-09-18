/**
 * Form — Form validation and AJAX submission with loading states.
 *
 * Usage:
 *   new McForm('#my-form', {
 *       onSuccess: (response) => { ... },
 *       onError: (errors) => { ... },
 *   });
 *
 * Or declarative:
 *   <form data-mc-form data-redirect="/success">
 */
class McForm {
    constructor(formEl, options = {}) {
        this.form = typeof formEl === 'string' ? document.querySelector(formEl) : formEl;
        if (!this.form) return;

        this.options = {
            ajax: options.ajax !== false, // default true
            redirect: options.redirect || this.form.dataset.redirect || null,
            onSuccess: options.onSuccess || null,
            onError: options.onError || null,
            onBeforeSubmit: options.onBeforeSubmit || null,
        };

        this.submitBtn = this.form.querySelector('[type="submit"]');
        this.isSubmitting = false;

        if (this.options.ajax) {
            this.form.addEventListener('submit', (e) => this._handleSubmit(e));
        }
    }

    /**
     * Initialize all [data-mc-form] forms on the page.
     */
    static init() {
        document.querySelectorAll('[data-mc-form]').forEach(form => {
            new McForm(form);
        });
    }

    /**
     * Handle form submission.
     */
    async _handleSubmit(e) {
        e.preventDefault();

        if (this.isSubmitting) return;

        // Clear previous errors
        this._clearErrors();

        // Before submit callback
        if (this.options.onBeforeSubmit) {
            const proceed = this.options.onBeforeSubmit(this.form);
            if (proceed === false) return;
        }

        this.isSubmitting = true;
        this._setLoading(true);

        const formData = new FormData(this.form);
        const method = this.form.method.toUpperCase();
        const action = this.form.action;

        try {
            const response = await fetch(action, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const data = await response.json();

            if (response.ok) {
                if (this.options.onSuccess) {
                    this.options.onSuccess(data);
                } else if (this.options.redirect) {
                    window.location.href = this.options.redirect;
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.message && window.McNotify) {
                    McNotify.success(data.message);
                }
            } else if (response.status === 422) {
                // Validation errors
                if (data.errors && Object.keys(data.errors).length > 0) {
                    this._showErrors(data.errors);
                    if (this.options.onError) {
                        this.options.onError(data.errors);
                    }
                } else if (data.message && window.McNotify) {
                    McNotify.error(data.message);
                }
            } else {
                throw new Error(data.message || `HTTP ${response.status}`);
            }
        } catch (error) {
            console.error('McForm submit error:', error);
            if (window.McNotify) {
                McNotify.error(error.message || 'Something went wrong');
            }
        } finally {
            this.isSubmitting = false;
            this._setLoading(false);
        }
    }

    /**
     * Set loading state on submit button.
     */
    _setLoading(loading) {
        if (!this.submitBtn) return;

        if (loading) {
            this.submitBtn.dataset.originalText = this.submitBtn.innerHTML;
            this.submitBtn.innerHTML = '<div class="mc-spinner mc-spinner-sm" style="border-top-color:currentColor"></div>';
            this.submitBtn.disabled = true;
        } else {
            this.submitBtn.innerHTML = this.submitBtn.dataset.originalText || this.submitBtn.innerHTML;
            this.submitBtn.disabled = false;
        }
    }

    /**
     * Show validation errors on form fields.
     */
    _showErrors(errors) {
        Object.entries(errors).forEach(([field, messages]) => {
            const input = this.form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');

                // Create error element
                const errorEl = document.createElement('div');
                errorEl.className = 'mc-form-error';
                errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
                input.parentElement.appendChild(errorEl);
            }
        });

        // Scroll to first error
        const firstError = this.form.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }

    /**
     * Clear all validation errors.
     */
    _clearErrors() {
        this.form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        this.form.querySelectorAll('.mc-form-error').forEach(el => {
            el.remove();
        });
    }
}

// Export
window.McForm = McForm;

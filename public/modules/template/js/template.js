/**
 * Template Module - AJAX Interactions
 * Patrón: Mercosan Theme (activate/remove templates)
 */

(function () {
    'use strict';

    class TemplateManagement {
        constructor() {
            this.init();
        }

        /**
         * Inicializar event listeners
         */
        init() {
            this.bindActivateTemplate();
            this.bindRemoveTemplate();
            this.bindConfirmRemove();
        }

        /**
         * Event: Click en botón "Activar Template"
         * Envía POST request a /settings/templates/activate
         */
        bindActivateTemplate() {
            $(document).on('click', '.btn-trigger-activate-template', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const templateSlug = $btn.data('template');
                const url = $btn.data('url');

                Botble.showButtonLoading($btn);

                $.ajax({
                    type: 'POST',
                    url: url,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json',
                    },
                    data: JSON.stringify({
                        template: templateSlug,
                    }),
                    success: (response) => {
                        Botble.showSuccess(response.message || 'Template activated successfully');
                        Botble.hideButtonLoading($btn);

                        // Recargar página después de 1 segundo
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    },
                    error: (xhr) => {
                        const response = xhr.responseJSON || {};
                        Botble.showError(response.message || 'Error activating template');
                        Botble.hideButtonLoading($btn);
                    },
                });
            });
        }

        /**
         * Event: Click en botón "Eliminar Template"
         * Abre modal de confirmación
         */
        bindRemoveTemplate() {
            $(document).on('click', '.btn-trigger-remove-template', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const templateSlug = $btn.data('template');
                const templateName = $btn.data('name') || templateSlug;
                const url = $btn.data('url');

                // Actualizar datos en el botón de confirmación
                $('#confirm-remove-template-button')
                    .data('template', templateSlug)
                    .data('url', url)
                    .data('name', templateName);

                // Actualizar mensaje del modal
                $('#remove-template-modal-text').text(
                    `¿Estás seguro de que deseas eliminar la plantilla "${templateName}"? Esta acción no se puede deshacer.`
                );

                // Mostrar modal
                $('#remove-template-modal').modal('show');
            });
        }

        /**
         * Event: Click en botón "Confirmar eliminación" del modal
         * Envía POST request a /settings/templates/remove
         */
        bindConfirmRemove() {
            $(document).on('click', '#confirm-remove-template-button', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const templateSlug = $btn.data('template');
                const url = $btn.data('url');

                Botble.showButtonLoading($btn);

                $.ajax({
                    type: 'POST',
                    url: url,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json',
                    },
                    data: JSON.stringify({
                        template: templateSlug,
                    }),
                    success: (response) => {
                        Botble.showSuccess(response.message || 'Template removed successfully');
                        Botble.hideButtonLoading($btn);
                        $('#remove-template-modal').modal('hide');

                        // Recargar página después de 1 segundo
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    },
                    error: (xhr) => {
                        const response = xhr.responseJSON || {};
                        Botble.showError(response.message || 'Error removing template');
                        Botble.hideButtonLoading($btn);
                    },
                });
            });
        }
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        new TemplateManagement();
    });
})();

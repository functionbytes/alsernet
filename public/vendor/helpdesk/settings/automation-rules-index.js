/**
 * Helpdesk · Settings → Reglas de automatizacion (listado). Especifico de
 * esta pantalla: activar/desactivar una regla via AJAX sin recargar.
 * Select2 y el flash de sesion los cubre settings-common.js; el borrado usa
 * el boton .btn-delete generico (data-url + data-name) que abre el modal
 * compartido #delete-modal / #delete-form.
 */
(function ($) {
    'use strict';

    $(function () {
        $(document).on('click', '.toggle-rule', function () {
            const $btn = $(this);

            $.ajax({
                url: $btn.data('url'),
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-HTTP-Method-Override': 'PATCH',
                },
                success: function (response) {
                    if (response.is_active) {
                        $btn.removeClass('btn-secondary').addClass('btn-success').text('Activa');
                    } else {
                        $btn.removeClass('btn-success').addClass('btn-secondary').text('Inactiva');
                    }
                },
                error: function () {
                    toastr.error('No se pudo cambiar el estado.', 'Error');
                },
            });
        });
    });
})(jQuery);

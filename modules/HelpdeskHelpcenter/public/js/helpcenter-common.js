/**
 * Comportamiento repetido en varias vistas del Centro de Ayuda: boton
 * ".delete-btn" que rellena el modal generico core::components.delete, y
 * los toastr de exito/error tras una redirección con sesion flash.
 *
 * Se incluye desde partials/common-scripts.blade.php — esa vista siembra
 * window.HelpcenterFlash con la sesion flash de la request actual (lo unico
 * que este fichero no puede resolver por su cuenta).
 */
(function () {
    'use strict';

    $(document).ready(function () {
        $('.delete-btn').on('click', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        var flash = window.HelpcenterFlash || {};
        if (flash.success) { toastr.success(flash.success, 'Éxito'); }
        if (flash.error) { toastr.error(flash.error, 'Error'); }
    });
})();

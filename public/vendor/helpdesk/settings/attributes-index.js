/**
 * Helpdesk · Settings → Atributos personalizados (listado).
 * Especifico de esta pantalla: los selects de filtro se auto-envian al
 * cambiar, y el toggle activo/inactivo de cada atributo se guarda via AJAX
 * sin recargar la pagina. Select2 y el flash de sesion los cubre
 * settings-common.js.
 */
(function ($) {
    'use strict';

    $(function () {
        $('#filterForm select').on('change', function () {
            $('#filterForm').trigger('submit');
        });

        $(document).on('change', '.attribute-toggle', function () {
            const $toggle = $(this);
            const url = $toggle.data('url');

            $toggle.prop('disabled', true);

            $.ajax({
                url: url,
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    toastr.success(response.message || 'Estado actualizado', 'Éxito');
                },
                error: function (xhr) {
                    // Revierte el estado visual si la peticion falla.
                    $toggle.prop('checked', !$toggle.prop('checked'));

                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Error al actualizar el estado';

                    toastr.error(msg, 'Error');
                },
                complete: function () {
                    $toggle.prop('disabled', false);
                },
            });
        });
    });
})(jQuery);

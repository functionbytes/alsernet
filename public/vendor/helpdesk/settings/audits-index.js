/**
 * Helpdesk · Settings → Auditoria (listado). Especifico de esta pantalla:
 * el modal "Ver propiedades" que vuelca el JSON de propiedades del evento
 * seleccionado. Select2 y el flash de sesion los cubre settings-common.js.
 */
(function ($) {
    'use strict';

    $(function () {
        $(document).on('click', '.btn-view-properties', function () {
            const raw = $(this).data('properties');
            const description = $(this).data('description');

            $('#propertiesDescription').text(description);
            $('#propertiesContent').text(JSON.stringify(raw, null, 2));
            $('#propertiesModal').modal('show');
        });
    });
})(jQuery);

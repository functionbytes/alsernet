/**
 * Helpdesk · Settings → Marcas (editar). Especifico de esta pantalla: copiar
 * el widget token al portapapeles. No hay select2 ni borrado en esta
 * pagina; se carga junto a settings-common.js solo para mantener el mismo
 * patron de publicacion en toda la carpeta settings/.
 */
(function ($) {
    'use strict';

    $(function () {
        $('.btn-copy-token').on('click', function () {
            const token = $(this).closest('.input-group').find('input').val();
            navigator.clipboard.writeText(token).then(function () {});
        });
    });
})(jQuery);

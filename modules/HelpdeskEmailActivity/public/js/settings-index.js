/**
 * Actividad de correo — Configuracion (settings/index.blade.php).
 * Extraido del <script> inline de esa vista: copiar la URL del webhook.
 */
(function () {
    'use strict';

    $(function () {
        $('[data-copy-webhook-url]').on('click', function () {
            var $input = $(this).closest('.input-group').find('input');
            $input.select();
            navigator.clipboard?.writeText($input.val());
        });
    });
})();

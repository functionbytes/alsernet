/**
 * Listado de reglas automaticas (managers/social-rules/index.blade.php).
 * Extraido del <script> inline de esa vista.
 *
 * Depende de window.SocialRulesFlash (sembrado por un bootstrap inline
 * minimo en la propia vista con la sesion flash).
 */
(function () {
    'use strict';

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        var title = $(this).data('title') || 'Confirmar eliminación';
        $('#delete-form').attr('action', url);
        $('#delete-modal .modal-title').text(title);
        new bootstrap.Modal(document.getElementById('delete-modal')).show();
    });

    var flash = window.SocialRulesFlash || {};
    if (flash.success) { toastr.success(flash.success, 'Éxito'); }
    if (flash.error) { toastr.error(flash.error, 'Error'); }
})();

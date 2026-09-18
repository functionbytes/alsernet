/**
 * Listado de formularios pre-chat (settings/pre-chat-forms/index.blade.php).
 * Extraido del <script> inline de esa vista.
 *
 * Depende de window.PreChatFormsFlash (sembrado por un bootstrap inline
 * minimo en la propia vista con la sesion flash).
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var flash = window.PreChatFormsFlash || {};
        if (flash.success) { toastr.success(flash.success, 'Exito'); }
        if (flash.error) { toastr.error(flash.error, 'Error'); }

        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });
    });
})();

/**
 * ai-agent-flows-index.js — HelpdeskAgents module
 *
 * Listado de flujos de IA (managers/ai-agent/flows/index.blade.php).
 * Muestra los mensajes flash de sesión y arma el modal de borrado
 * compartido con el flujo seleccionado.
 *
 * Depende de: jQuery, toastr (globales) y window.HelpdeskAgentsFlowsIndex
 * (mensajes flash, inyectados por la propia vista).
 */
(function ($) {
    'use strict';

    var flash = window.HelpdeskAgentsFlowsIndex || {};

    $(document).ready(function () {
        if (flash.success) {
            toastr.success(flash.success, 'Éxito');
        }
        if (flash.error) {
            toastr.error(flash.error, 'Error');
        }

        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });
    });
})(jQuery);

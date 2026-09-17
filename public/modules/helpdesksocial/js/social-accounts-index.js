/**
 * Cuentas sociales (managers/social-accounts/index.blade.php). Extraido
 * del <script> inline de esa vista.
 *
 * OJO — cuatro bugs reales encontrados y arreglados de paso al probar el
 * módulo en vivo (estuvo deshabilitado desde su creación, nadie los vio):
 *   1) @section('scripts')/@endsection en vez de @push('scripts')/@endpush
 *      — este bloque nunca se inyectaba.
 *   2) Al `window.__confirm(...)` le faltaba el `});` de cierre — habría
 *      sido SyntaxError franco si el bloque hubiese llegado a ejecutarse.
 *   3) Llamaba a POST .../accounts/{id}/crisis-mode, una ruta que no
 *      existe — el backend real son DOS endpoints separados,
 *      crisis-mode/enter y crisis-mode/exit.
 *   4) enter exige un motivo (EnterCrisisModeRequest), que window.__confirm()
 *      no puede recoger (no acepta texto libre) — se usa el modal
 *      #crisisModeModal solo para entrar; salir no necesita motivo.
 */
(function ($) {
    'use strict';

    var pendingAccountId = null;

    function callCrisisEndpoint(accountId, action, extraData) {
        $.ajax({
            url: '/api/helpdesk/social/accounts/' + accountId + '/crisis-mode/' + action,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: extraData || {},
            success: function (response) {
                if (window.toastr) {
                    toastr.success(response.message || 'Modo crisis actualizado.');
                }
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo actualizar el modo crisis.';
                if (window.toastr) { toastr.error(msg); }
            }
        });
    }

    function toggleCrisisMode(accountId, isActive) {
        if (isActive) {
            window.__confirm('¿Desactivar el modo crisis de esta cuenta? Las respuestas automáticas se reanudarán.', function () {
                callCrisisEndpoint(accountId, 'exit');
            });
            return;
        }

        pendingAccountId = accountId;
        $('#crisisModeReason').val('');
        $('#crisisModeModal').modal('show');
    }

    $('#crisisModeForm').on('submit', function (e) {
        e.preventDefault();
        var reason = $.trim($('#crisisModeReason').val());
        if (!reason || !pendingAccountId) { return; }
        callCrisisEndpoint(pendingAccountId, 'enter', { reason: reason });
    });

    window.toggleCrisisMode = toggleCrisisMode;
})(jQuery);

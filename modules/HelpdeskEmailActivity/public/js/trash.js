/**
 * Actividad de correo — Papelera (emails/trash.blade.php). Extraido del
 * <script> inline de esa vista: confirmaciones + restaurar/eliminar
 * definitivamente (fila individual y en bloque).
 *
 * Depende de window.EmailActivityTrash = { flash, i18n }, sembrado por un
 * bootstrap inline minimo en la propia vista (sesion flash + cadenas
 * traducidas — lo unico que este fichero no puede resolver por su cuenta).
 */
(function () {
    'use strict';

    $(function () {
        var data = window.EmailActivityTrash || { flash: {}, i18n: {} };
        var flash = data.flash || {};
        var i18n = data.i18n || {};

        if (flash.success) { toastr.success(flash.success); }
        if (flash.error) { toastr.error(flash.error); }

        var csrf = $('meta[name="csrf-token"]').attr('content');
        var $confirmModal = $('#trash-confirm-modal');
        var confirmModal = new bootstrap.Modal($confirmModal[0]);
        var pendingAccept = null;

        function askConfirm(opts) {
            $('#trash-confirm-title').text(opts.title);
            $('#trash-confirm-message').text(opts.message);
            pendingAccept = opts.onAccept;
            confirmModal.show();
        }

        $('#trash-confirm-accept').on('click', function () {
            var fn = pendingAccept;
            pendingAccept = null;
            confirmModal.hide();
            if (typeof fn === 'function') fn();
        });

        // Restaurar (fila individual)
        $(document).on('click', '.js-trash-restore', function () {
            var url = $(this).data('url');
            askConfirm({
                title: i18n.restoreConfirmTitle,
                message: i18n.restoreConfirm,
                onAccept: function () {
                    $.ajax({ url: url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                        .done(function () { location.reload(); })
                        .fail(function (xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error'); });
                },
            });
        });

        // Eliminar definitivamente (fila individual) — irreversible, ver
        // EmailLogController::forceDestroy().
        $(document).on('click', '.js-trash-force-delete', function () {
            var url = $(this).data('url');
            askConfirm({
                title: i18n.forceDeleteConfirmTitle,
                message: i18n.forceDeleteConfirm,
                onAccept: function () {
                    $.ajax({ url: url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                        .done(function () { location.reload(); })
                        .fail(function (xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error'); });
                },
            });
        });

        // Restauración masiva
        window.BulkActions.init({ checkbox: '.trash-bulk-checkbox', selectAll: '#trash-select-all', toolbar: '#trash-bulk-toolbar' });

        $('#trash-bulk-restore').on('click', function () {
            var uids = $('.trash-bulk-checkbox:checked').map(function () { return this.value; }).get();
            if (!uids.length) { toastr.warning(i18n.noneSelected); return; }
            var url = $(this).data('url');
            askConfirm({
                title: i18n.bulkRestoreConfirmTitle,
                message: (i18n.bulkRestoreConfirm || '').replace(':count', uids.length),
                onAccept: function () {
                    $.ajax({ url: url, method: 'POST', data: { uids: uids }, headers: { 'X-CSRF-TOKEN': csrf } })
                        .done(function () { location.reload(); })
                        .fail(function (xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error'); });
                },
            });
        });
    });
})();

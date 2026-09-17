/**
 * Listado de canales de correo (managers/settings/email-channels/index.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Depende de
 * window.BulkActions (core/js/bulk.js, no se toca), jQuery, select2, toastr
 * y window.hdtEmailChannelsIndexConfig (que publica el propio Blade como
 * datos, no como lógica).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtEmailChannelsIndexConfig || {};

        if (cfg.successMessage) { toastr.success(cfg.successMessage, 'Exito'); }
        if (cfg.errorMessage) { toastr.error(cfg.errorMessage, 'Error'); }

        // Delete modal
        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        // Sincronizar un canal concreto
        $(document).on('click', '.btn-sync-channel', function (e) {
            e.preventDefault();
            var $link = $(this);
            var original = $link.text();
            $link.text('Sincronizando...');

            $.ajax({
                url: cfg.syncUrlBase + '/' + $link.data('id') + '/sync',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 1000);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error inesperado al sincronizar el canal.');
                    $link.text(original);
                },
            });
        });

        // Bulk actions
        var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });
        $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-action-select').val('').trigger('change');
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            bulk.reset();
        });

        $('#bulk-apply-btn').on('click', function () {
            var action = $('#bulk-action-select').val();
            var ids = bulk.getIds();
            if (!action) { toastr.warning('Selecciona una accion.'); return; }
            if (!ids.length) { toastr.warning('Selecciona al menos un canal.'); return; }
            if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' canal(es) seleccionados?')) { return; }

            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');
            $.ajax({
                url: cfg.bulkActionUrl,
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 800);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
        });
    });
})();

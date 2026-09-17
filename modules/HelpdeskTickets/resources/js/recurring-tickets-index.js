/**
 * Listado de tickets recurrentes (managers/recurring-tickets/index.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Depende de
 * window.BulkActions / window.FilterShell (core, no se tocan), jQuery,
 * select2, toastr y window.hdtRecurringTicketsIndexConfig (que publica el
 * propio Blade como datos, no como lógica).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtRecurringTicketsIndexConfig || {};

        if (cfg.successMessage) { toastr.success(cfg.successMessage, 'Exito'); }
        if (cfg.errorMessage) { toastr.error(cfg.errorMessage, 'Error'); }

        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        // ── Filtros avanzados ────────────────────────────────────────────
        $('.select2-filter-modal').select2({
            dropdownParent: window.FilterShell.el('rt-filter-modal'),
            width: '100%',
        });

        $('#rt-filter-apply-btn').on('click', function () {
            $('#rt-filter-frequency').val($('#modal-frequency').val());
            $('#rt-filter-category').val($('#modal-category').val());
            $('#rt-filter-status').val($('#modal-status').val());
            window.FilterShell.close('rt-filter-modal');
            $('#rt-filter-form').submit();
        });

        $('#rt-filter-clear-btn').on('click', function () {
            $('#modal-frequency, #modal-category, #modal-status').val(null).trigger('change');
        });

        // ── Acciones masivas ─────────────────────────────────────────────────
        if (document.querySelector('.bulk-checkbox')) {
            $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

            var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

            $('#bulk-modal').on('hide.bs.modal', function () {
                $('#bulk-action-select').val('').trigger('change');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                bulk.reset();
            });

            $('#bulk-apply-btn').on('click', function () {
                var action = $('#bulk-action-select').val();
                var ids = bulk.getIds();

                if (! action) { toastr.warning('Selecciona una accion.'); return; }
                if (! ids.length) { toastr.warning('Selecciona al menos un ticket recurrente.'); return; }
                if (action === 'delete' && ! confirm('¿Eliminar los ' + ids.length + ' ticket(s) recurrente(s) seleccionado(s)? No se puede deshacer.')) return;

                $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

                $.ajax({
                    url: cfg.bulkActionUrl,
                    method: 'POST',
                    data: JSON.stringify({ action: action, ids: ids }),
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
        }
    });
})();

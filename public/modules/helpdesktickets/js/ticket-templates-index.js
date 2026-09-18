/**
 * Listado de plantillas de ticket (managers/ticket-templates/index.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Depende de
 * window.BulkActions (core/js/bulk.js, no se toca), jQuery, select2, toastr
 * y window.hdtTicketTemplatesIndexConfig (que publica el propio Blade como
 * datos, no como lógica).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtTicketTemplatesIndexConfig || {};

        if (cfg.successMessage) { toastr.success(cfg.successMessage, 'Exito'); }
        if (cfg.errorMessage) { toastr.error(cfg.errorMessage, 'Error'); }

        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        // ── Filtros avanzados ────────────────────────────────────────────
        $('.select2-filter-modal').select2({ dropdownParent: $('#tt-filter-modal'), width: '100%' });

        $('#tt-filter-apply-btn').on('click', function () {
            $('#tt-filter-category').val($('#tt-modal-category').val());
            $('#tt-filter-priority').val($('#tt-modal-priority').val());
            $('#tt-filter-status').val($('#tt-modal-status').val());
            $('#tt-filter-modal').modal('hide');
            $('#tt-filter-form').submit();
        });

        $('#tt-filter-clear-btn').on('click', function () {
            $('#tt-modal-category, #tt-modal-priority, #tt-modal-status').val(null).trigger('change');
        });

        // ── Bulk: un BulkActions.init() por pestaña (Generales / Mis plantillas) ──
        ['general', 'mine'].forEach(function (group) {
            $('#bulk-' + group + '-action-select').select2({ dropdownParent: $('#bulk-' + group + '-modal'), width: '100%' });

            var bulk = window.BulkActions.init({
                checkbox: '.bulk-checkbox-' + group,
                toolbar: '#bulk-toolbar-' + group,
                selectAll: '#select-all-' + group,
            });

            $('#bulk-' + group + '-modal').on('hide.bs.modal', function () {
                $('#bulk-' + group + '-action-select').val('').trigger('change');
                $('#bulk-' + group + '-apply-btn').prop('disabled', false).text('Aplicar');
                bulk.reset();
            });

            $('#bulk-' + group + '-apply-btn').on('click', function () {
                var action = $('#bulk-' + group + '-action-select').val();
                var ids = bulk.getIds();

                if (! action) { toastr.warning('Selecciona una acción.'); return; }
                if (! ids.length) { toastr.warning('Selecciona al menos una plantilla.'); return; }
                if (action === 'delete' && ! confirm('¿Eliminar las ' + ids.length + ' plantilla(s) seleccionada(s)? No se puede deshacer.')) return;

                $('#bulk-' + group + '-apply-btn').prop('disabled', true).text('Procesando...');

                $.ajax({
                    url: cfg.bulkActionUrl,
                    method: 'POST',
                    data: JSON.stringify({ action: action, ids: ids }),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        $('#bulk-' + group + '-modal').modal('hide');
                        toastr.success(res.message);
                        setTimeout(function () { location.reload(); }, 800);
                    },
                    error: function (xhr) {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar.');
                        $('#bulk-' + group + '-apply-btn').prop('disabled', false).text('Aplicar');
                    },
                });
            });
        });
    });
})();

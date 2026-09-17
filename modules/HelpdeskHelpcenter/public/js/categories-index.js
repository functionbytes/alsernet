/**
 * Listado de categorias del Centro de Ayuda (helpcenter/categories/index.blade.php).
 * Extraido del <script> inline de esa vista: filtros (modal/panel lateral
 * segun window.FilterShell) + acciones masivas.
 *
 * Depende de que #categories-filter-form lleve data-bulk-url con la URL de
 * route('manager.helpcenter.categories.bulk-action') — lo unico que este
 * fichero no puede resolver por su cuenta.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var bulkUrl = $('#categories-filter-form').data('bulk-url');

        // El contenedor de filtros es modal o panel lateral segun el .env.
        $('.select2-filter-modal').select2({
            dropdownParent: window.FilterShell.el('categories-filter-modal'),
            width: '100%',
        });

        $('#categories-filter-apply-btn').on('click', function () {
            $('#filter-role').val($('#modal-role').val());
            $('#filter-content').val($('#modal-content').val());
            window.FilterShell.close('categories-filter-modal');
            $('#categories-filter-form').submit();
        });

        $('#categories-filter-clear-btn').on('click', function () {
            $('#modal-role, #modal-content').val(null).trigger('change');
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
                if (! ids.length) { toastr.warning('Selecciona al menos una categoria.'); return; }

                $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

                $.ajax({
                    url: bulkUrl,
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

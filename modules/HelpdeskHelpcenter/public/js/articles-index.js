/**
 * Listado de articulos del Centro de Ayuda (helpcenter/articles/index.blade.php).
 * Extraido del <script> inline de esa vista: filtros (modal/panel lateral
 * segun window.FilterShell) + acciones masivas.
 *
 * Depende de que #articles-filter-form lleve data-bulk-url con la URL de
 * route('manager.helpcenter.articles.bulk-action') — lo unico que este
 * fichero no puede resolver por su cuenta.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var bulkUrl = $('#articles-filter-form').data('bulk-url');

        // El contenedor de filtros es modal o panel lateral segun el .env.
        $('.select2-filter-modal').select2({
            dropdownParent: window.FilterShell.el('articles-filter-modal'),
            width: '100%',
        });

        $('#articles-filter-apply-btn').on('click', function () {
            $('#filter-draft').val($('#modal-draft').val());
            $('#filter-category').val($('#modal-category').val());
            $('#filter-author').val($('#modal-author').val());
            window.FilterShell.close('articles-filter-modal');
            $('#articles-filter-form').submit();
        });

        $('#articles-filter-clear-btn').on('click', function () {
            $('#modal-draft, #modal-category, #modal-author').val(null).trigger('change');
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
                if (! ids.length) { toastr.warning('Selecciona al menos un articulo.'); return; }

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

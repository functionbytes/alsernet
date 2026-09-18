/**
 * Listado de contactos (contacts/index.blade.php) — filtros, paginación,
 * acciones masivas (toolbar flotante + modal) y filtros avanzados. Extraído
 * de los <script> inline de esa vista.
 *
 * Depende de:
 *   - window.BulkActions (public/core/js/bulk.js, cargado en el layout)
 *   - send-hsm-modal.js (flujo bulk "send-hsm" reusado vía
 *     [data-bulk-action="send-hsm"], sin duplicar esa lógica)
 */
(function () {
    'use strict';

    $(function () {
        var $filterForm = $('#contactsFilterForm');

        $filterForm.find('.select2').select2({ width: '100%' });

        var bulkUrl = $filterForm.data('bulk-url');

        // Helper global (public/core/js/bulk.js, cargado en el layout) — mismo
        // patrón que settings/users: toolbar flotante + contador delegado.
        var bulk = window.BulkActions.init({ checkbox: '.contact-check' });

        $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-action-select').val('').trigger('change');
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            bulk.reset();
        });

        $('#per-page-select').on('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });

        $('#bulk-apply-btn').on('click', function () {
            var action = $('#bulk-action-select').val();
            var ids = bulk.getIds();

            if (!action) { toastr.warning('Selecciona una acción antes de continuar.'); return; }
            if (!ids.length) { toastr.warning('Selecciona al menos un contacto.'); return; }
            if (action === 'delete' && !confirm('¿Eliminar ' + ids.length + ' contactos? Esta acción no se puede deshacer.')) { return; }

            if (action === 'send-hsm') {
                $('#bulk-modal').modal('hide');
                // send-hsm no pasa por bulkUrl: reusa el flujo bulk ya construido
                // en send-hsm-modal.js (mismo selector, sin duplicar esa lógica).
                $('[data-bulk-action="send-hsm"]').trigger('click');
                return;
            }

            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

            $.ajax({
                url: bulkUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                contentType: 'application/json',
                data: JSON.stringify({ action: action, ids: ids }),
            }).done(function (resp) {
                $('#bulk-modal').modal('hide');
                toastr.success(resp.message || 'Acción aplicada');
                setTimeout(function () { location.reload(); }, 800);
            }).fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al ejecutar la acción');
            }).always(function () {
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            });
        });

        $('.delete-btn').on('click', function (e) {
            e.preventDefault();
            $('#delete-form').attr('action', $(this).data('url'));
            $('#delete-modal').modal('show');
        });

        // El modal solo rellena los hidden del formulario de busqueda: asi el
        // filtro viaja por GET y la URL sigue siendo compartible.
        $('.select2-filter-modal').select2({ dropdownParent: $('#ct-filter-modal'), width: '100%' });

        $('#ct-filter-apply-btn').on('click', function () {
            $('#ct-filter-channel').val($('#ct-modal-channel').val());
            $('#ct-filter-verified').val($('#ct-modal-verified').val());
            $('#ct-filter-banned').val($('#ct-modal-banned').val());
            $('#ct-filter-modal').modal('hide');
            $filterForm.submit();
        });

        $('#ct-filter-clear-btn').on('click', function () {
            window.location = $filterForm.attr('action');
        });
    });
})();

/**
 * Listado de contactos (contacts/index.blade.php) — filtros, paginación,
 * acciones masivas (barra de selección + modal) y fila clicable. Extraído de
 * los <script> inline de esa vista.
 *
 * Depende de:
 *   - window.BulkActions (public/core/js/bulk.js, cargado en el layout)
 *   - send-hsm-modal.js ("Enviar plantilla" de la barra lleva
 *     data-bulk-action="send-hsm" y ese script abre su flujo masivo)
 *   - export-modal.js ("Exportar selección" lleva data-export-selection)
 */
(function () {
    'use strict';

    // Acciones que exigen escribir el número de contactos afectados.
    var CONFIRM_ACTIONS = ['ban', 'delete'];

    $(function () {
        var $filterForm = $('#contactsFilterForm');
        var bulkUrl = $filterForm.data('bulk-url');

        // Helper global (public/core/js/bulk.js, cargado en el layout) — mismo
        // patrón que settings/users: barra de selección + contador delegado.
        var bulk = window.BulkActions.init({ checkbox: '.contact-check' });

        var $bulkModal = $('#bulk-modal');
        var $apply = $('#bulk-apply-btn');
        var $confirmInput = $('#bulk-confirm-input');

        function currentAction() {
            return $bulkModal.find('input[name="bulk-action"]:checked').val();
        }

        function refreshApplyLabel() {
            var n = bulk.getIds().length;
            $apply.prop('disabled', false).text('Aplicar a ' + n + ' contacto' + (n === 1 ? '' : 's'));
        }

        function syncFields() {
            var action = currentAction();
            $bulkModal.find('[data-bulk-field="tag"]').toggleClass('d-none', action !== 'tag');
            $bulkModal.find('[data-bulk-field="assign"]').toggleClass('d-none', action !== 'assign');
            $bulkModal.find('[data-bulk-field="confirm"]').toggleClass('d-none', CONFIRM_ACTIONS.indexOf(action) === -1);
            $confirmInput.val('');
        }

        $bulkModal.on('change', 'input[name="bulk-action"]', syncFields);

        $bulkModal.on('show.bs.modal', function () {
            refreshApplyLabel();
            syncFields();
        });

        // La selección se conserva al cancelar: solo se limpia al recargar
        // tras aplicar la acción, o con "Quitar".
        $bulkModal.on('hidden.bs.modal', function () {
            $bulkModal.find('input[name="bulk-action"][value="tag"]').prop('checked', true);
            $('#bulk-tag-input').val('');
            $('#bulk-owner-select').val('');
            syncFields();
        });

        $('#per-page-select').on('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });

        $apply.on('click', function () {
            var action = currentAction();
            var ids = bulk.getIds();
            var payload = { action: action, ids: ids };

            if (!ids.length) { toastr.warning('Selecciona al menos un contacto.'); return; }

            if (action === 'tag') {
                var tag = $.trim($('#bulk-tag-input').val());
                if (!tag) {
                    toastr.warning('Indica la etiqueta que quieres añadir.');
                    $('#bulk-tag-input').trigger('focus');
                    return;
                }
                payload.tag = tag;
            } else if (action === 'assign') {
                var ownerId = $('#bulk-owner-select').val();
                // Sin responsable = null: el backend quita el responsable actual.
                payload.owner_id = ownerId === '' ? null : parseInt(ownerId, 10);
            }

            if (CONFIRM_ACTIONS.indexOf(action) !== -1 && $.trim($confirmInput.val()) !== String(ids.length)) {
                toastr.warning('Escribe ' + ids.length + ' para confirmar la acción.');
                $confirmInput.trigger('focus');
                return;
            }

            $apply.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: bulkUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                contentType: 'application/json',
                data: JSON.stringify(payload),
            }).done(function (resp) {
                $bulkModal.modal('hide');
                toastr.success(resp.message || 'Acción aplicada');
                setTimeout(function () { location.reload(); }, 800);
            }).fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al ejecutar la acción');
                refreshApplyLabel();
            });
        });

        // El menú "···" de cada fila vive dentro de .ctl-table-scroll (overflow
        // para pantallas estrechas), que recortaría el menú desplegable —
        // sobre todo en las últimas filas—. Popper en modo "fixed" lo saca del
        // flujo y no depende del overflow del contenedor.
        $('.ctl-row [data-bs-toggle="dropdown"]').each(function () {
            new window.bootstrap.Dropdown(this, {
                popperConfig: function (config) {
                    return $.extend({}, config, { strategy: 'fixed' });
                },
            });
        });

        $('.delete-btn').on('click', function (e) {
            e.preventDefault();
            $('#delete-form').attr('action', $(this).data('url'));
            $('#delete-modal').modal('show');
        });

        // Filtros de la barra de vistas (canal, origen, etiqueta, responsable):
        // selects fuera del <form>, asociados por atributo form="contactsFilterForm".
        // Cambiarlos envía el filtro por GET igual que el resto (URL compartible).
        $('#ctl-channel-filter, #ctl-origin-filter, #ctl-tag-filter, #ctl-owner-filter').on('change', function () {
            $filterForm.trigger('submit');
        });

        $('#bulk-clear-btn').on('click', function () {
            bulk.reset();
        });

        // Fila clicable (data-ctl-href): navega a la ficha 360 salvo que el
        // click venga de un control interactivo (checkbox, botón, enlace,
        // dropdown) que ya tiene su propio comportamiento.
        $(document).on('click', '.ctl-row[data-ctl-href]', function (e) {
            if ($(e.target).closest('input, button, a, .dropdown-menu').length) {
                return;
            }
            window.location = $(this).data('ctl-href');
        });
    });
})();

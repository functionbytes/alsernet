/**
 * Validación + envío común de las barras de "acciones masivas" de los
 * listados de HelpdeskTickets (macros, canales de correo, tickets
 * recurrentes, plantillas de ticket, automatizaciones). Mismo bloque
 * duplicado en los 5 *-index.js: validar acción/ids elegidos, confirmar el
 * borrado, deshabilitar el botón mientras viaja el POST y reponerlo si
 * falla.
 *
 * Se carga ANTES que cada *-index.js (ver los @push('scripts') de sus
 * blades). Depende de jQuery, toastr y de una instancia ya creada con
 * window.BulkActions.init() (core/js/bulk.js, no se toca).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    /**
     * @param {Object} opts
     * @param {jQuery} opts.$modal    modal Bootstrap que envuelve el select + botón
     * @param {jQuery} opts.$select   select de la acción elegida
     * @param {Object} opts.bulk      instancia de window.BulkActions.init()
     * @param {jQuery} opts.$applyBtn botón "Aplicar"
     * @param {string} opts.url       endpoint bulk-action
     * @param {string} opts.emptyActionMessage texto si no se eligió acción
     * @param {string} opts.emptyIdsMessage    texto si no se seleccionó ningún registro
     * @param {string} opts.errorMessage       texto de error genérico (fallback si el servidor no manda 'message')
     * @param {string} [opts.applyLabel] texto del botón en reposo (por defecto 'Aplicar')
     * @param {string} [opts.busyLabel]  texto del botón mientras viaja el POST (por defecto 'Procesando...')
     * @param {function(number): (string|false)} [opts.confirmDeleteMessage]
     *        texto de confirm() cuando action === 'delete'; sin esta opción no se confirma
     */
    window.HdtBulkListActions = {
        run: function (opts) {
            var applyLabel = opts.applyLabel || 'Aplicar';
            var busyLabel = opts.busyLabel || 'Procesando...';

            opts.$modal.on('hide.bs.modal', function () {
                opts.$select.val('').trigger('change');
                opts.$applyBtn.prop('disabled', false).text(applyLabel);
                opts.bulk.reset();
            });

            opts.$applyBtn.on('click', function () {
                var action = opts.$select.val();
                var ids = opts.bulk.getIds();

                if (!action) { toastr.warning(opts.emptyActionMessage); return; }
                if (!ids.length) { toastr.warning(opts.emptyIdsMessage); return; }
                if (action === 'delete' && opts.confirmDeleteMessage && !confirm(opts.confirmDeleteMessage(ids.length))) return;

                opts.$applyBtn.prop('disabled', true).text(busyLabel);

                $.ajax({
                    url: opts.url,
                    method: 'POST',
                    data: JSON.stringify({ action: action, ids: ids }),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        opts.$modal.modal('hide');
                        toastr.success(res.message);
                        setTimeout(function () { location.reload(); }, 800);
                    },
                    error: function (xhr) {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || opts.errorMessage);
                        opts.$applyBtn.prop('disabled', false).text(applyLabel);
                    },
                });
            });
        },
    };
})();

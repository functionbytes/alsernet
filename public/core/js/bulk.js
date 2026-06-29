/**
 * BulkActions - Plugin reutilizable para acciones masivas en tablas.
 *
 * Uso mínimo en cualquier vista:
 *
 *   const bulk = window.BulkActions.init({
 *       checkbox  : '.my-checkbox',   // clase de checkboxes individuales
 *       selectAll : '#select-all',    // checkbox cabecera
 *       toolbar   : '#bulk-toolbar',  // barra flotante (contiene data-bulk-count)
 *   });
 *
 *   bulk.getIds()   → array de IDs seleccionados (parseInt)
 *   bulk.getCount() → número de ítems seleccionados
 *   bulk.reset()    → deselecciona todo y oculta la barra
 */
window.BulkActions = (function ($) {

    function init(options) {
        const cfg = $.extend({
            checkbox  : '.bulk-checkbox',
            selectAll : '#select-all',
            toolbar   : '#bulk-toolbar',
        }, options);

        const $selectAll = $(cfg.selectAll);
        const $toolbar   = $(cfg.toolbar);

        /* ── Helpers ─────────────────────────────────────────────────────── */
        function sync() {
            const total   = $(cfg.checkbox).length;
            const checked = $(cfg.checkbox + ':checked').length;

            $selectAll.prop('indeterminate', checked > 0 && checked < total);
            $selectAll.prop('checked', total > 0 && checked === total);

            $toolbar.find('[data-bulk-count]').text(checked);
            $toolbar.toggleClass('d-none', checked === 0);
        }

        /* ── Eventos ─────────────────────────────────────────────────────── */
        $selectAll.off('change.bulk').on('change.bulk', function () {
            $(cfg.checkbox).prop('checked', this.checked);
            sync();
        });

        $(document).off('change.bulk', cfg.checkbox)
                   .on('change.bulk', cfg.checkbox, sync);

        /* ── API pública ─────────────────────────────────────────────────── */
        return {
            sync,
            reset() {
                $(cfg.checkbox).prop('checked', false);
                $selectAll.prop({ checked: false, indeterminate: false });
                $toolbar.addClass('d-none').find('[data-bulk-count]').text(0);
            },
            getIds() {
                return $(cfg.checkbox + ':checked')
                    .map(function () { return parseInt(this.value, 10); })
                    .get();
            },
            getCount() {
                return $(cfg.checkbox + ':checked').length;
            },
        };
    }

    return { init };

})(jQuery);

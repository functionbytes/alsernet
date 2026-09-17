/**
 * Helpdesk · Misc — formulario de edicion de agente (agents/edit.blade.php):
 * muestra/oculta el bloque de horario semanal segun la disponibilidad
 * elegida y habilita/deshabilita los campos de hora de cada dia.
 *
 * No requiere config: toda la logica es puramente de DOM.
 */
(function ($) {
    'use strict';

    function init() {
        const $sel = $('#accepts_conversations');
        const $block = $('#schedule-block');

        if (!$sel.length) {
            return;
        }

        function toggleSchedule() {
            $block.toggle($sel.val() === 'working_hours');
        }

        function toggleDayInputs($row, enabled) {
            $row.find('.day-time').prop('disabled', !enabled);
        }

        // Bind day toggle switches
        $(document).on('change', '.day-toggle', function () {
            toggleDayInputs($(this).closest('.row'), this.checked);
        });

        toggleSchedule();
        $sel.on('change', toggleSchedule);
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(jQuery);

/**
 * Formulario de ticket recurrente (managers/recurring-tickets/form.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Sin datos dinámicos de
 * servidor: los ids son fijos y siempre los mismos en esta pantalla.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });

        $('#frequency').on('change', function () {
            $('#cron-expression-group').toggle(this.value === 'custom');
        });
    });
})();

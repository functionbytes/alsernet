/**
 * Formulario "Nuevo ticket" del panel de agente (agents/tickets/create.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Solo depende de
 * jQuery, select2 y window.hdtAgentTicketCreateConfig (templates), que
 * publica el propio Blade como datos, no como lógica.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtAgentTicketCreateConfig || {};
        var TEMPLATES = cfg.templates || {};

        $('.select2').select2({ width: '100%' });

        // Usar plantilla: autorrellena el formulario, no lo bloquea — el agente
        // puede seguir editando cualquier campo despues de aplicarla.
        $('#templateSelect').on('change', function () {
            var id = $(this).val();
            if (!id || !TEMPLATES[id]) return;

            var tpl = TEMPLATES[id];

            $('input[name="subject"]').val(tpl.subject);
            $('textarea[name="description"]').val(tpl.body);

            if (tpl.category_id) {
                $('#categorySelect').val(String(tpl.category_id)).trigger('change');
            }

            if (tpl.priority) {
                $('#prioritySelect').val(tpl.priority).trigger('change');
            }
        });
    });
})();

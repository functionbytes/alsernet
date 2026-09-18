/**
 * Formulario de plantilla de ticket (managers/ticket-templates/form.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Sin datos dinámicos de
 * servidor: los valores de ejemplo de la vista previa son fijos.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });

        // Vista previa con datos de ejemplo — puramente en el navegador, no llama
        // al servidor ni al ERP; el mismo texto de ejemplo para todas las
        // variables listadas en el panel de la derecha (TicketVariableInterpolator::availableVariables()).
        var SAMPLE_VALUES = {
            '{{ticket_number}}': 'TCK-2026-00123',
            '{{ticket_subject}}': 'Asunto de ejemplo',
            '{{ticket_status}}': 'Abierto',
            '{{ticket_priority}}': 'Media',
            '{{ticket_category}}': 'Soporte técnico',
            '{{customer_name}}': 'Ana Pérez',
            '{{customer_email}}': 'ana.perez@ejemplo.com',
            '{{customer_phone}}': '600 111 222',
            '{{agent_name}}': 'Tu nombre',
            '{{assignee_name}}': 'Tu nombre',
            '{{fecha}}': new Date().toLocaleDateString('es-ES'),
            '{{erp_id_cliente}}': '4521',
            '{{erp_nif}}': 'B12345678',
            '{{erp_ciudad}}': 'Madrid',
            '{{erp_saldo_pendiente}}': '150.00',
            '{{erp_limite_credito}}': '5000',
            '{{erp_ultimo_pedido_numero}}': 'PED-000987',
            '{{erp_ultimo_pedido_fecha}}': '15/08/2026',
        };

        function applySample(text) {
            Object.keys(SAMPLE_VALUES).forEach(function (key) {
                text = text.split(key).join(SAMPLE_VALUES[key]);
            });

            return text;
        }

        $('#previewTemplateBtn').on('click', function () {
            var subject = $('#templateSubjectInput').val() || '';
            var body = $('#templateBodyInput').val() || '';

            $('#templatePreviewSubject').text(applySample(subject));
            $('#templatePreviewBody').text(applySample(body));
            $('#templatePreviewBox').prop('hidden', false);
        });
    });
})();

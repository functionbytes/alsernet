/**
 * Formulario "Editar ticket" (managers/tickets/edit.blade.php) — propiedad
 * de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Solo depende de
 * jQuery, select2 y window.hdtTicketEditConfig (customFields), que publica
 * el propio Blade como datos, no como lógica.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtTicketEditConfig || {};
        var existingCustomFields = cfg.customFields || {};

        $('.select2').select2({ width: '100%' });

        var $categorySelect = $('#categorySelect');
        var $customFieldsContainer = $('#customFieldsContainer');

        function renderCustomFields() {
            if (!$categorySelect.length) return;

            var $selected = $categorySelect.find(':selected');
            var fields = JSON.parse($selected.attr('data-fields') || '[]');
            var required = JSON.parse($selected.attr('data-required') || '[]');

            $customFieldsContainer.empty();

            if (fields.length === 0) return;

            $customFieldsContainer.append(
                '<div class="col-12"><h6 class="fw-semibold mb-1">Campos personalizados</h6></div>'
            );

            fields.forEach(function (field) {
                var isRequired = required.includes(field.name);
                var fieldName = 'custom_fields[' + field.name + ']';
                var currentValue = existingCustomFields[field.name] || '';
                var $input;

                if (field.type === 'text') {
                    $input = $('<input type="text" class="form-control">')
                        .attr({ name: fieldName, placeholder: field.placeholder || '' })
                        .val(currentValue);
                } else if (field.type === 'textarea') {
                    $input = $('<textarea class="form-control" rows="3">').attr('name', fieldName).val(currentValue);
                } else if (field.type === 'select') {
                    $input = $('<select class="form-select select2">').attr('name', fieldName);
                    $input.append($('<option value="">').text('Seleccione...'));
                    (field.options || []).forEach(function (opt) {
                        $input.append($('<option>').val(opt).text(opt).prop('selected', currentValue === opt));
                    });
                } else if (field.type === 'date') {
                    $input = $('<input type="date" class="form-control">').attr('name', fieldName).val(currentValue);
                } else {
                    return;
                }

                if (isRequired) {
                    $input.prop('required', true);
                }

                var $label = $('<label class="form-label">').text(field.label || field.name);
                if (isRequired) {
                    $label.append($('<span class="text-danger">').text('*'));
                }

                var $col = $('<div class="col-12">').append($label, $input);

                if (field.help_text) {
                    $col.append($('<small class="text-muted">').text(field.help_text));
                }

                $customFieldsContainer.append($col);

                // El <select> del campo personalizado se crea después del init
                // genérico de arriba: necesita su propia llamada a select2().
                if ($input.is('select')) {
                    $input.select2({ width: '100%' });
                }
            });
        }

        $categorySelect.on('change', renderCustomFields);
        renderCustomFields();
    });
})();

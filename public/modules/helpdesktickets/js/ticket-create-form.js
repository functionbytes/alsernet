/**
 * Formulario "Crear ticket" del panel de manager (managers/tickets/create.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Solo depende de
 * jQuery, select2 y window.hdtTicketCreateConfig (templates), que publica el
 * propio Blade como datos, no como lógica.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtTicketCreateConfig || {};
        var TEMPLATES = cfg.templates || {};

        // customerSelect/assigneeSelect ya tienen su propio select2 con placeholder
        // más abajo — el resto de selects planos usa la config genérica.
        $('.select2').not('#customerSelect, #assigneeSelect').select2({ width: '100%' });

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
                $('select[name="priority"]').val(tpl.priority).trigger('change');
            }
        });

        // Dynamic custom fields based on category selection
        var $categorySelect = $('#categorySelect');
        var $customFieldsContainer = $('#customFieldsContainer');

        $categorySelect.on('change', function () {
            var $selected = $(this).find(':selected');
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
                var $input;

                if (field.type === 'text') {
                    $input = $('<input type="text" class="form-control">')
                        .attr({ name: fieldName, placeholder: field.placeholder || '' });
                } else if (field.type === 'textarea') {
                    $input = $('<textarea class="form-control" rows="3">').attr('name', fieldName);
                } else if (field.type === 'select') {
                    $input = $('<select class="form-select select2">').attr('name', fieldName);
                    $input.append($('<option value="">').text('Seleccione...'));
                    (field.options || []).forEach(function (opt) {
                        $input.append($('<option>').val(opt).text(opt));
                    });
                } else if (field.type === 'date') {
                    $input = $('<input type="date" class="form-control">').attr('name', fieldName);
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
        });

        // Trigger on page load if category is pre-selected
        if ($categorySelect.val()) {
            $categorySelect.trigger('change');
        }

        // Select2 for customer and assignee
        if ($.fn.select2) {
            $('#customerSelect').select2({ placeholder: 'Buscar cliente por nombre o email...', allowClear: true, width: '100%' });
            $('#assigneeSelect').select2({ placeholder: 'Seleccionar agente...', allowClear: true, width: '100%' });
        }

        // Disable submit while sending
        $('#ticketForm').on('submit', function () {
            var $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creando ticket...');
        });

        // Knowledge base suggestions on subject input
        var kbTimer;
        $(document).on('input', 'input[name="subject"]', function () {
            clearTimeout(kbTimer);
            var q = $(this).val().trim();
            if (q.length < 3) {
                $('#kb-suggestions').empty();
                return;
            }
            kbTimer = setTimeout(function () {
                $.get('/helpcenter/search', { q: q }).done(function (res) {
                    var $container = $('#kb-suggestions');
                    $container.empty();

                    if (!res.articles || !res.articles.length) return;

                    var $list = $('<div class="list-group mt-2">');
                    res.articles.forEach(function (a) {
                        $list.append(
                            $('<a target="_blank" class="list-group-item list-group-item-action small py-2">')
                                .attr('href', '/helpcenter/articles/' + encodeURIComponent(a.slug))
                                .append($('<i class="fas fa-book me-2 text-muted">'))
                                .append(document.createTextNode(a.title))
                        );
                    });

                    var $alert = $('<div class="alert alert-info p-2 mb-0">').append(
                        $('<strong class="small">').append(
                            $('<i class="fas fa-lightbulb me-1">'),
                            document.createTextNode(' ¿Esto podría resolverlo?')
                        ),
                        $list
                    );

                    $container.append($alert);
                });
            }, 500);
        });
    });
})();

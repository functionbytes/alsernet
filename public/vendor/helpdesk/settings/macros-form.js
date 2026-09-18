/**
 * Parcial settings/macros/_form.blade.php (crear/editar macro) — constructor
 * dinamico de acciones del macro. window.HdMacroFormConfig = { lookupUrls:
 * {agents, groups, tags}, initialRowIndex } lo imprime el Blade.
 */
$(function () {
    var cfg = window.HdMacroFormConfig || {};
    var lookupUrls = cfg.lookupUrls || {};
    var lookupCache = { agents: null, groups: null, tags: null };

    function fetchLookup(key) {
        if (lookupCache[key]) {
            return $.Deferred().resolve(lookupCache[key]).promise();
        }
        return $.getJSON(lookupUrls[key]).then(function (res) {
            lookupCache[key] = res.data || res;
            return lookupCache[key];
        });
    }

    // Sustituye el placeholder e inicializa select2 en el <select> resultante
    // (si lo hay) -- centralizado aqui porque renderInput() lo llama tanto en
    // sincrono (change_status/change_priority) como dentro de un .then()
    // asincrono (assign_agent/assign_group/add_tag/remove_tag), y las dos
    // rutas necesitan el mismo re-init tras insertar el <select> en el DOM.
    function setPlaceholderHtml($placeholder, html) {
        $placeholder.html(html);
        $placeholder.find('select').select2({ width: '100%', dropdownParent: $placeholder.closest('.modal, body') });
    }

    function renderInput(actionType, currentValue, $placeholder, idx) {
        var name = 'actions[' + idx + '][value]';

        switch (actionType) {
            case 'assign_agent':
                fetchLookup('agents').then(function (agents) {
                    var opts = '<option value="">— Sin asignar —</option>';
                    $.each(agents, function (i, a) {
                        opts += '<option value="' + a.id + '"' + (String(a.id) === String(currentValue) ? ' selected' : '') + '>'
                            + $('<div>').text(a.name).html() + ' (' + $('<div>').text(a.email).html() + ')</option>';
                    });
                    setPlaceholderHtml($placeholder, '<select name="' + name + '" class="form-select form-select-sm">' + opts + '</select>');
                });
                return;

            case 'assign_group':
                fetchLookup('groups').then(function (groups) {
                    var opts = '<option value="">— Sin grupo —</option>';
                    $.each(groups, function (i, g) {
                        opts += '<option value="' + g.id + '"' + (String(g.id) === String(currentValue) ? ' selected' : '') + '>'
                            + $('<div>').text(g.name).html() + '</option>';
                    });
                    setPlaceholderHtml($placeholder, '<select name="' + name + '" class="form-select form-select-sm">' + opts + '</select>');
                });
                return;

            case 'add_tag':
            case 'remove_tag':
                fetchLookup('tags').then(function (tags) {
                    var opts = '<option value="">— Selecciona etiqueta —</option>';
                    $.each(tags, function (i, t) {
                        opts += '<option value="' + t.id + '"' + (String(t.id) === String(currentValue) ? ' selected' : '') + '>'
                            + $('<div>').text(t.name).html() + '</option>';
                    });
                    setPlaceholderHtml($placeholder, '<select name="' + name + '" class="form-select form-select-sm">' + opts + '</select>');
                });
                return;

            case 'change_status':
                setPlaceholderHtml($placeholder,
                    '<select name="' + name + '" class="form-select form-select-sm">'
                    + '<option value="open"' + (currentValue === 'open' ? ' selected' : '') + '>Abierta</option>'
                    + '<option value="pending"' + (currentValue === 'pending' ? ' selected' : '') + '>Pendiente</option>'
                    + '<option value="resolved"' + (currentValue === 'resolved' ? ' selected' : '') + '>Resuelta</option>'
                    + '</select>'
                );
                return;

            case 'change_priority':
                setPlaceholderHtml($placeholder,
                    '<select name="' + name + '" class="form-select form-select-sm">'
                    + '<option value="low"' + (currentValue === 'low' ? ' selected' : '') + '>Baja</option>'
                    + '<option value="medium"' + (currentValue === 'medium' ? ' selected' : '') + '>Media</option>'
                    + '<option value="high"' + (currentValue === 'high' ? ' selected' : '') + '>Alta</option>'
                    + '<option value="urgent"' + (currentValue === 'urgent' ? ' selected' : '') + '>Urgente</option>'
                    + '</select>'
                );
                return;

            case 'send_reply':
                $placeholder.html(
                    '<textarea name="' + name + '" class="form-control form-control-sm" rows="3"'
                    + ' placeholder="Hola {{ contact.name }}, gracias por contactarnos...">'
                    + $('<div>').text(currentValue).html()
                    + '</textarea>'
                    + '<div class="form-text">Variables disponibles: {{ contact.name }}, {{ inbox.name }}, {{ agent.name }}</div>'
                );
                return;

            case 'add_note':
                $placeholder.html(
                    '<textarea name="' + name + '" class="form-control form-control-sm" rows="2"'
                    + ' placeholder="Nota interna para el equipo...">'
                    + $('<div>').text(currentValue).html()
                    + '</textarea>'
                );
                return;

            case 'resolve_conversation':
            case 'close_conversation':
                $placeholder.html(
                    '<input type="hidden" name="' + name + '" value="">'
                    + '<div class="form-control form-control-sm bg-light text-muted fst-italic">Sin parametros — la accion se ejecuta tal cual</div>'
                );
                return;

            default:
                $placeholder.html('<input type="text" class="form-control form-control-sm" disabled placeholder="Selecciona una accion primero">');
        }
    }

    // When action type changes, replace the input area
    $(document).on('change', '.js-action-type', function () {
        var $row = $(this).closest('.action-row');
        var idx = $row.data('index');
        var $placeholder = $row.find('.js-action-input-placeholder');
        renderInput($(this).val(), '', $placeholder, idx);
    });

    // Selects estaticos del formulario + el "Accion" de cada fila ya
    // renderizada en el servidor (los selects de "Valor" se inicializan aparte,
    // en setPlaceholderHtml(), porque se crean despues por JS).
    window.HdSettingsCommon.initFormSelect2('select[name="visibility"], select[name="language"], .js-action-type');

    // Init existing rows (edit mode — values already embedded as data attrs)
    $('#actionsContainer .action-row').each(function () {
        var $row = $(this);
        var actionType = $row.find('.js-action-type').val();
        if (actionType) {
            var currentValue = $row.find('.js-initial-value').val() || '';
            renderInput(actionType, currentValue, $row.find('.js-action-input-placeholder'), $row.data('index'));
        }
    });

    // Remove action row
    $(document).on('click', '.js-remove-action', function () {
        $(this).closest('.action-row').remove();
    });

    // Add new action row
    var rowIdx = cfg.initialRowIndex || 1;
    $('#btnAddAction').on('click', function () {
        var html = $('#action-row-template').html().replace(/__INDEX__/g, rowIdx++);
        var $newRow = $(html).appendTo('#actionsContainer');
        $newRow.find('.js-action-type').select2({ width: '100%' });
    });
});

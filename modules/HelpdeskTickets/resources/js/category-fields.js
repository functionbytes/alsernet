/**
 * Campos personalizados de una categoría de tickets (24-sep-2026).
 * Interfaz sobre TicketCategoryFieldsController (API que existía sin UI):
 * lista, alta y baja. Los campos se rellenan en el formulario público y el
 * agente los edita desde el panel del ticket.
 */
(function ($) {
    'use strict';

    var $root = $('#category-fields');
    if (!$root.length) return;

    var urls = {
        index: $root.data('index-url'),
        store: $root.data('store-url'),
        destroy: $root.data('destroy-url'),
        update: $root.data('update-url'),
        reorder: $root.data('reorder-url'),
    };
    var current = [];
    var editingId = null;
    var TYPE_LABELS = {
        text: 'Texto', textarea: 'Texto largo', email: 'Email', phone: 'Teléfono', number: 'Número',
        select: 'Desplegable', radio: 'Opción única', checkbox: 'Varias opciones', date: 'Fecha', file: 'Archivo',
    };
    var WITH_OPTIONS = ['select', 'radio', 'checkbox'];

    function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }

    function notify(ok, msg) {
        if (window.toastr) toastr[ok ? 'success' : 'error'](msg); else if (!ok) window.alert(msg);
    }

    function render(fields) {
        var $list = $root.find('[data-fields-list]');
        if (!fields.length) {
            $list.html('<p class="text-muted small mb-0">Esta categoría todavía no tiene campos.</p>');
            return;
        }
        $list.html('<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr>' +
            '<th>Etiqueta</th><th>Clave</th><th>Tipo</th><th>Obligatorio</th><th><span class="visually-hidden">Acciones</span></th>' +
            '</tr></thead><tbody>' +
            fields.map(function (f, i) {
                return '<tr><td>' + esc(f.label) + '</td><td><code>' + esc(f.key) + '</code></td><td>' + esc(TYPE_LABELS[f.type] || f.type) + '</td>' +
                    '<td>' + (f.is_required ? 'Sí' : 'No') + '</td>' +
                    '<td class="text-end text-nowrap">' +
                        '<button type="button" class="btn btn-sm btn-light" data-move-field="' + f.id + '" data-dir="-1"' + (i === 0 ? ' disabled' : '') + ' aria-label="Subir ' + esc(f.label) + '">Subir</button> ' +
                        '<button type="button" class="btn btn-sm btn-light" data-move-field="' + f.id + '" data-dir="1"' + (i === fields.length - 1 ? ' disabled' : '') + ' aria-label="Bajar ' + esc(f.label) + '">Bajar</button> ' +
                        '<button type="button" class="btn btn-sm btn-light" data-edit-field="' + f.id + '">Editar</button> ' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary" data-delete-field="' + f.id + '" aria-label="Eliminar el campo ' + esc(f.label) + '">Eliminar</button>' +
                    '</td></tr>';
            }).join('') + '</tbody></table></div>');
    }

    function load() {
        $.getJSON(urls.index).done(function (resp) {
            current = (resp && resp.data) || [];
            render(current);
        });
    }

    function resetForm($form) {
        $form[0].reset();
        editingId = null;
        $form.find('button[type=submit]').text('Añadir campo');
        $form.find('[data-cancel-edit]').prop('hidden', true);
        $root.find('[data-options-row]').prop('hidden', true);
    }

    $root.on('change', '[name="type"]', function () {
        $root.find('[data-options-row]').prop('hidden', WITH_OPTIONS.indexOf(this.value) === -1);
    });

    $root.on('submit', '[data-field-form]', function (ev) {
        ev.preventDefault();
        var $form = $(this);
        var type = $form.find('[name="type"]').val();
        var payload = {
            label: $.trim($form.find('[name="label"]').val()),
            type: type,
            is_required: $form.find('[name="is_required"]').is(':checked') ? 1 : 0,
            is_visible: 1,
            width: 'full',
        };
        if (WITH_OPTIONS.indexOf(type) !== -1) {
            payload.options = $.trim($form.find('[name="options"]').val()).split('\n').map($.trim).filter(Boolean)
                .map(function (line) {
                    var parts = line.split('|');
                    return { value: $.trim(parts[0]), label: $.trim(parts[1] || parts[0]) };
                });
        }
        var editing = editingId !== null;
        if (editing) {
            var original = current.find(function (f) { return String(f.id) === String(editingId); }) || {};
            payload.key = original.key;
            payload.is_visible = original.is_visible ? 1 : 0;
            payload.width = original.width || 'full';
        }
        $.ajax({
            url: editing ? String(urls.update).replace('__FIELD__', editingId) : urls.store,
            method: editing ? 'PATCH' : 'POST',
            data: payload,
            headers: { Accept: 'application/json' },
        })
            .done(function () { resetForm($form); notify(true, editing ? 'Campo actualizado' : 'Campo añadido'); load(); })
            .fail(function (xhr) {
                var j = xhr.responseJSON || {};
                notify(false, (j.errors && Object.values(j.errors)[0][0]) || j.message || 'No se pudo añadir el campo');
            });
    });

    $root.on('click', '[data-edit-field]', function () {
        var f = current.find(function (x) { return String(x.id) === String($(this).data('edit-field')); }.bind(this));
        if (!f) return;
        var $form = $root.find('[data-field-form]');
        editingId = f.id;
        $form.find('[name="label"]').val(f.label).trigger('focus');
        $form.find('[name="type"]').val(f.type).trigger('change');
        $form.find('[name="is_required"]').prop('checked', !!f.is_required);
        $form.find('[name="options"]').val((f.options || []).map(function (o) { return o.value === o.label ? o.label : o.value + '|' + o.label; }).join('\n'));
        $form.find('button[type=submit]').text('Guardar cambios');
        $form.find('[data-cancel-edit]').prop('hidden', false);
    });

    $root.on('click', '[data-cancel-edit]', function () { resetForm($root.find('[data-field-form]')); });

    $root.on('click', '[data-move-field]', function () {
        var id = $(this).data('move-field');
        var dir = Number($(this).data('dir'));
        var idx = current.findIndex(function (f) { return String(f.id) === String(id); });
        var to = idx + dir;
        if (idx < 0 || to < 0 || to >= current.length) return;
        var moved = current.splice(idx, 1)[0];
        current.splice(to, 0, moved);
        render(current);
        $.ajax({
            url: urls.reorder, method: 'POST', headers: { Accept: 'application/json' },
            data: { items: current.map(function (f, i) { return { id: f.id, sort_order: i + 1 }; }) },
        }).fail(function () { notify(false, 'No se pudo guardar el orden'); load(); });
    });

    $root.on('click', '[data-delete-field]', function () {
        var url = String(urls.destroy).replace('__FIELD__', $(this).data('delete-field'));
        $.ajax({ url: url, method: 'DELETE', headers: { Accept: 'application/json' } })
            .done(function () { notify(true, 'Campo eliminado'); load(); })
            .fail(function () { notify(false, 'No se pudo eliminar el campo'); });
    });

    load();
})(jQuery);

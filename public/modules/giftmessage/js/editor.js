(function ($) {
    var FONT_STACKS = {
        helvetica: 'Helvetica, Arial, sans-serif',
        times: '"Times New Roman", Times, serif',
        courier: '"Courier New", Courier, monospace',
        dejavusans: '"DejaVu Sans", Arial, sans-serif',
        dejavuserif: '"DejaVu Serif", Georgia, serif',
    };

    var dirty = { envelope: false, card: false };
    var originalOrdersHtml = '';

    function fontStack(family) {
        return FONT_STACKS[family] || FONT_STACKS.helvetica;
    }

    function ptToPx(pt) {
        return (parseFloat(pt) || 0) * (96 / 72);
    }

    function scopeFor($el) {
        return $el.closest('.giftmessage-canvas').attr('id') === 'canvas-card' ? 'card' : 'envelope';
    }

    function markDirty($el) {
        dirty[scopeFor($el)] = true;
    }

    function dragMoveListener(event) {
        var $el = $(event.target);
        var left = (parseFloat($el.css('left')) || 0) + event.dx;
        var top = (parseFloat($el.css('top')) || 0) + event.dy;

        $el.css({ left: left + 'px', top: top + 'px' });
        markDirty($el);
    }

    function initInteractions() {
        if (typeof interact !== 'function') {
            return;
        }

        interact('.giftmessage-drag').draggable({
            modifiers: [interact.modifiers.restrictRect({ restriction: 'parent', endOnly: true })],
            listeners: { move: dragMoveListener },
        });
    }

    function initCanvas(canvasId) {
        var $canvas = $('#' + canvasId);
        if (!$canvas.length) {
            return;
        }

        $canvas.css('background-image', 'url(' + $canvas.data('bg') + ')');

        $canvas.find('.giftmessage-drag').each(function () {
            var $el = $(this);
            $el.css({ left: $el.data('x') + '%', top: $el.data('y') + '%' });
        });
    }

    function applyFontStyle(scope, slot, font, size) {
        $('#canvas-' + scope + ' [data-slot="' + slot + '"]').css({
            fontFamily: fontStack(font),
            fontSize: ptToPx(size) + 'px',
        });
    }

    function initFontPreview(fonts) {
        Object.keys(fonts).forEach(function (scope) {
            Object.keys(fonts[scope]).forEach(function (slot) {
                var style = fonts[scope][slot];
                applyFontStyle(scope, slot, style.font, style.size);
            });
        });
    }

    function bindFontInputs() {
        [
            ['env_t1', 'envelope', 't1'],
            ['env_t2', 'envelope', 't2'],
            ['card_t1', 'card', 't1'],
            ['card_t2', 'card', 't2'],
        ].forEach(function (mapping) {
            var prefix = mapping[0];
            var scope = mapping[1];
            var slot = mapping[2];

            $('[name="' + prefix + '_font"]').on('change', function () {
                applyFontStyle(scope, slot, $(this).val(), $('[name="' + prefix + '_size"]').val());
            });
            $('[name="' + prefix + '_size"]').on('input', function () {
                applyFontStyle(scope, slot, $('[name="' + prefix + '_font"]').val(), $(this).val());
            });
        });
    }

    function collectPercent($el) {
        var $canvas = $el.closest('.giftmessage-canvas');
        var left = parseFloat($el.css('left')) || 0;
        var top = parseFloat($el.css('top')) || 0;

        return {
            x: Math.round((left / $canvas.width()) * 10000) / 100,
            y: Math.round((top / $canvas.height()) * 10000) / 100,
        };
    }

    function savePositions(scope, $btn) {
        var config = window.GIFTMESSAGE_EDITOR;
        var $canvas = $('#canvas-' + scope);
        var t1 = collectPercent($canvas.find('[data-slot="t1"]'));
        var t2 = collectPercent($canvas.find('[data-slot="t2"]'));
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: config.urls.savePositions,
            method: 'POST',
            data: { scope: scope, t1_x: t1.x, t1_y: t1.y, t2_x: t2.x, t2_y: t2.y },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Posiciones guardadas correctamente.');
                dirty[scope] = false;
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar las posiciones.');
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            },
        });
    }

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    // Data attached via jQuery .data() rather than string-concatenated HTML
    // attributes, so a gift message containing quotes can't break the markup.
    function renderOrderRow(order) {
        var name = ((order.firstname || '') + ' ' + (order.lastname || '')).trim();
        var idGestionCell = order.id_gestion ? escapeHtml(order.id_gestion) : '<span class="text-muted">&mdash;</span>';

        var $checkbox = $('<input>', { type: 'checkbox', 'class': 'form-check-input order-checkbox' }).data({
            idOrder: order.id_order,
            giftMessage: order.gift_message || '',
            firstname: order.firstname || '',
            lastname: order.lastname || '',
            idGestion: order.id_gestion || '',
        });

        var $row = $('<tr>');
        $('<td>').append($checkbox).appendTo($row);
        $('<td>').text(order.id_order).appendTo($row);
        $('<td>').html(idGestionCell).appendTo($row);
        $('<td>').text(name).appendTo($row);
        $('<td>').text(order.gift_message).appendTo($row);

        return $row;
    }

    function renderOrders(rows) {
        var $tbody = $('#orders-table tbody').empty();

        if (!rows.length) {
            $tbody.html('<tr><td colspan="5" class="text-center text-muted py-3">No se encontraron pedidos para esos numeros de gestion.</td></tr>');
            toggleGenerateActions();

            return;
        }

        rows.forEach(function (order) {
            $tbody.append(renderOrderRow(order));
        });
        toggleGenerateActions();
    }

    function toggleGenerateActions() {
        $('#generate-actions').toggleClass('d-none', $('.order-checkbox:checked').length === 0);
    }

    function selectedRows() {
        return $('.order-checkbox:checked').map(function () {
            var $checkbox = $(this);

            return {
                id_order: $checkbox.data('idOrder'),
                gift_message: $checkbox.data('giftMessage'),
                firstname: $checkbox.data('firstname'),
                lastname: $checkbox.data('lastname'),
                id_gestion: $checkbox.data('idGestion'),
            };
        }).get();
    }

    // El backend ya no vuelve a resolver los pedidos por id: el frontend envia
    // las filas completas que el usuario ya vio en pantalla (listado o busqueda
    // por gestion), asi que basta con serializarlas como inputs ocultos.
    function buildHiddenRows() {
        var $container = $('#generate-hidden-rows').empty();

        selectedRows().forEach(function (row, index) {
            Object.keys(row).forEach(function (key) {
                $('<input>', {
                    type: 'hidden',
                    name: 'rows[' + index + '][' + key + ']',
                    value: row[key] == null ? '' : row[key],
                }).appendTo($container);
            });
        });
    }

    function searchOrders() {
        var config = window.GIFTMESSAGE_EDITOR;
        var gestionIds = $('#gestion-search').val().trim();

        if (!gestionIds) {
            toastr.warning('Indica al menos un numero de gestion.');

            return;
        }

        $.ajax({
            url: config.urls.ordersSearch,
            method: 'POST',
            data: { gestion_ids: gestionIds },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                renderOrders(response.rows || []);
                toastr.success('Busqueda completada.');
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al buscar los pedidos.');
            },
        });
    }

    function resetOrders() {
        $('#orders-table tbody').html(originalOrdersHtml);
        $('#gestion-search').val('');
        toggleGenerateActions();
        toastr.success('Listado restaurado.');
    }

    $(document).ready(function () {
        var config = window.GIFTMESSAGE_EDITOR;
        if (!config) {
            return;
        }

        originalOrdersHtml = $('#orders-table tbody').html();

        initCanvas('canvas-envelope');
        initCanvas('canvas-card');
        initInteractions();
        initFontPreview(config.fonts || {});
        bindFontInputs();
        toggleGenerateActions();

        $('#save-positions-envelope').on('click', function () {
            savePositions('envelope', $(this));
        });
        $('#save-positions-card').on('click', function () {
            savePositions('card', $(this));
        });

        $('#gestion-search-btn').on('click', searchOrders);
        $('#gestion-search').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                searchOrders();
            }
        });
        $('#gestion-reset-btn').on('click', resetOrders);

        $(document).on('change', '.order-checkbox', toggleGenerateActions);

        $('#generate-form button[type="submit"]').on('click', function () {
            $('#generate-type').val($(this).data('type'));
        });

        $('#generate-form').on('submit', function (e) {
            if (selectedRows().length === 0) {
                e.preventDefault();
                toastr.warning('Selecciona al menos un pedido.');

                return;
            }

            buildHiddenRows();
        });

        window.addEventListener('beforeunload', function (e) {
            if (dirty.envelope || dirty.card) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
})(jQuery);

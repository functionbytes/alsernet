(function ($) {
    var FALLBACK_STACK = 'Helvetica, Arial, sans-serif';

    var dirty = { envelope: false, card: false };
    var fontsDirty = { envelope: false, card: false };

    // Los stacks los manda el servidor porque incluyen las fuentes subidas, que
    // no se pueden conocer desde el JS.
    function fontStack(family) {
        var stacks = (window.GIFTMESSAGE_SETTINGS || {}).stacks || {};

        return stacks[family] || FALLBACK_STACK;
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
        syncFineTuneInputs($el);
    }

    function resizeMoveListener(event) {
        var $el = $(event.target);
        var left = (parseFloat($el.css('left')) || 0) + event.deltaRect.left;
        var top = (parseFloat($el.css('top')) || 0) + event.deltaRect.top;

        $el.css({
            width: event.rect.width + 'px',
            height: event.rect.height + 'px',
            left: left + 'px',
            top: top + 'px',
        });
        markDirty($el);
        syncFineTuneInputs($el);
    }

    function initInteractions() {
        if (typeof interact !== 'function') {
            return;
        }

        interact('.giftmessage-drag')
            .draggable({
                modifiers: [interact.modifiers.restrictRect({ restriction: 'parent', endOnly: true })],
                listeners: { move: dragMoveListener },
            })
            .resizable({
                edges: { left: true, right: true, top: true, bottom: true },
                modifiers: [
                    interact.modifiers.restrictEdges({ outer: 'parent' }),
                    // Con un minimo mas pequeno los bordes de redimensionar
                    // (que reaccionan a unos px de margen) ocupan practicamente
                    // toda la caja y no queda centro libre para arrastrarla con
                    // el raton; 56x24 deja siempre un centro agarrable.
                    interact.modifiers.restrictSize({ min: { width: 56, height: 24 } }),
                ],
                listeners: { move: resizeMoveListener },
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
            $el.css({
                left: $el.data('x') + '%',
                top: $el.data('y') + '%',
                width: $el.data('w') + '%',
                height: $el.data('h') + '%',
            });
        });
    }

    var SLOTS = [
        { prefix: 'env_t1', scope: 'envelope', slot: 't1' },
        { prefix: 'env_t2', scope: 'envelope', slot: 't2' },
        { prefix: 'card_t1', scope: 'card', slot: 't1' },
        { prefix: 'card_t2', scope: 'card', slot: 't2' },
    ];

    function rgba(hex, opacityPercent) {
        var clean = /^#?[0-9A-Fa-f]{6}$/.test(hex || '') ? String(hex).replace('#', '') : '000000';
        var alpha = Math.max(0, Math.min(100, parseInt(opacityPercent, 10) || 0)) / 100;

        return 'rgba(' + parseInt(clean.slice(0, 2), 16) + ', ' + parseInt(clean.slice(2, 4), 16) +
            ', ' + parseInt(clean.slice(4, 6), 16) + ', ' + alpha + ')';
    }

    // La opacidad se pinta sobre el color del texto y no como `opacity` de la
    // caja: la caja lleva borde y fondo propios para poder arrastrarla, y deben
    // seguir visibles aunque el texto se configure casi transparente.
    function applyFontStyle(scope, slot, style) {
        $('#canvas-' + scope + ' [data-slot="' + slot + '"]').css({
            fontFamily: fontStack(style.font),
            fontSize: ptToPx(style.size) + 'px',
            color: rgba(style.color, style.opacity),
        });
    }

    function readSlotStyle(prefix) {
        return {
            font: $('[name="' + prefix + '_font"]').val(),
            size: $('[name="' + prefix + '_size"]').val(),
            color: $('[name="' + prefix + '_color"]').val(),
            opacity: $('[name="' + prefix + '_opacity"]').val(),
        };
    }

    function initFontPreview(fonts) {
        Object.keys(fonts).forEach(function (scope) {
            Object.keys(fonts[scope]).forEach(function (slot) {
                applyFontStyle(scope, slot, fonts[scope][slot]);
            });
        });
    }

    function bindFontInputs() {
        SLOTS.forEach(function (mapping) {
            var refresh = function () {
                applyFontStyle(mapping.scope, mapping.slot, readSlotStyle(mapping.prefix));
            };

            $('[name="' + mapping.prefix + '_font"]').on('change', refresh);
            $('[name="' + mapping.prefix + '_size"]').on('input', refresh);
            $('[name="' + mapping.prefix + '_color"]').on('input change', refresh);
            $('[name="' + mapping.prefix + '_opacity"]').on('input', refresh);
        });
    }

    function percentOf(pixels, total) {
        return Math.round((pixels / total) * 10000) / 100;
    }

    function collectBox($el) {
        var $canvas = $el.closest('.giftmessage-canvas');
        var canvasWidth = $canvas.width();
        var canvasHeight = $canvas.height();

        return {
            x: percentOf(parseFloat($el.css('left')) || 0, canvasWidth),
            y: percentOf(parseFloat($el.css('top')) || 0, canvasHeight),
            w: percentOf($el.outerWidth(), canvasWidth),
            h: percentOf($el.outerHeight(), canvasHeight),
        };
    }

    // ─── Ajuste fino por numeros (alternativa a arrastrar con el raton) ─────
    // Sin esto, una caja redimensionada muy pequena es practicamente
    // imposible de mover: los bordes de resize ocupan todo el centro.
    function syncFineTuneInputs($el) {
        var canvas = scopeFor($el);
        var slot = $el.data('slot');
        var box = collectBox($el);

        ['x', 'y', 'w', 'h'].forEach(function (axis) {
            $('.giftmessage-pos-input[data-canvas="' + canvas + '"][data-slot="' + slot + '"][data-axis="' + axis + '"]')
                .val(box[axis]);
        });
    }

    function bindFineTuneInputs() {
        var cssProp = { x: 'left', y: 'top', w: 'width', h: 'height' };

        $('.giftmessage-pos-input').on('input change', function () {
            var $input = $(this);
            var value = parseFloat($input.val());

            if (isNaN(value)) {
                return;
            }

            var $box = $('#canvas-' + $input.data('canvas') + ' [data-slot="' + $input.data('slot') + '"]');

            $box.css(cssProp[$input.data('axis')], value + '%');
            markDirty($box);
        });
    }

    // ─── Vista previa con texto de muestra ─────────────────────────────────
    // Refleja en las cajas T1/T2 de AMBOS lienzos (el mensaje y el n. de
    // pedido son los mismos para sobre y tarjeta) el texto que se escriba en
    // la barra de arriba, para detectar antes de generar un PDF real si un
    // mensaje largo o con emoji se recorta o rompe mal la linea.
    function applySampleText() {
        var message = $('#preview-message').val().trim();
        var order = $('#preview-order').val().trim();

        $('.giftmessage-drag[data-slot="t1"]').text(message || 'T1 · Mensaje');
        $('.giftmessage-drag[data-slot="t2"]').text(order || 'T2 · Gestion');
    }

    // ─── Color + hex sincronizados ──────────────────────────────────────────
    function bindColorHexPairs() {
        $('.giftmessage-color-hex').each(function () {
            var $hex = $(this);
            var $color = $('#' + $hex.data('colorTarget'));

            $hex.on('input', function () {
                var value = $hex.val().trim();

                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    $color.val(value).trigger('input');
                }
            });

            $color.on('input', function () {
                $hex.val($color.val());
            });
        });
    }

    // ─── Miniatura: previsualiza el archivo elegido antes de guardarlo ──────
    function bindImagePreviews() {
        $('.giftmessage-image-input').each(function () {
            var $input = $(this);
            var $preview = $('#' + $input.data('previewTarget'));

            $input.on('change', function () {
                var file = this.files && this.files[0];

                if (!file) {
                    return;
                }

                $preview.attr('src', URL.createObjectURL(file)).removeClass('d-none');
                $preview.closest('.giftmessage-image-preview-wrap')
                    .find('[data-preview-label]')
                    .text('Nueva imagen seleccionada (todavia sin guardar)');
            });
        });
    }

    // ─── Copiar posicion + tipografia del sobre a la tarjeta ────────────────
    // No guarda nada por si solo: rellena los campos de la tarjeta con los
    // valores actuales en pantalla del sobre para que el usuario revise y
    // pulse "Guardar" el mismo, igual que si los hubiera escrito a mano.
    function copyEnvelopeToCard() {
        ['t1', 't2'].forEach(function (slot) {
            ['font', 'size', 'color', 'opacity'].forEach(function (field) {
                var value = $('[name="env_' + slot + '_' + field + '"]').val();
                var $target = $('[name="card_' + slot + '_' + field + '"]');

                $target.val(value);

                if (field === 'font') {
                    $target.trigger('change'); // refresca el select2
                }
            });

            $('.giftmessage-color-hex[data-color-target="card_' + slot + '_color"]')
                .val($('#card_' + slot + '_color').val());

            var $envBox = $('#canvas-envelope [data-slot="' + slot + '"]');
            var $cardBox = $('#canvas-card [data-slot="' + slot + '"]');

            if ($envBox.length && $cardBox.length) {
                $cardBox.css({
                    left: $envBox.css('left'),
                    top: $envBox.css('top'),
                    width: $envBox.css('width'),
                    height: $envBox.css('height'),
                });
                markDirty($cardBox);
            }

            applyFontStyle('card', slot, readSlotStyle('card_' + slot));
        });

        fontsDirty.card = true;
        toastr.info('Configuracion del sobre copiada. Revisa y pulsa "Guardar" en cada seccion de la tarjeta.');
    }

    function saveFontsScope(scope, $btn) {
        var config = window.GIFTMESSAGE_SETTINGS;
        var prefixes = scope === 'card' ? ['card_t1', 'card_t2'] : ['env_t1', 'env_t2'];
        var data = {};

        prefixes.forEach(function (prefix) {
            ['font', 'size', 'color', 'opacity'].forEach(function (field) {
                data[prefix + '_' + field] = $('[name="' + prefix + '_' + field + '"]').val();
            });
        });

        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: config.urls.saveFonts,
            method: 'POST',
            data: data,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Tipografia guardada correctamente.');
                fontsDirty[scope] = false;
            },
            error: function (xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                var message = errors
                    ? Object.values(errors).flat().join(' ')
                    : ((xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar la tipografia.');
                toastr.error(message);
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            },
        });
    }

    function savePositions(scope, $btn) {
        var config = window.GIFTMESSAGE_SETTINGS;
        var $canvas = $('#canvas-' + scope);
        var t1 = collectBox($canvas.find('[data-slot="t1"]'));
        var t2 = collectBox($canvas.find('[data-slot="t2"]'));
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: config.urls.savePositions,
            method: 'POST',
            data: {
                scope: scope,
                t1_x: t1.x, t1_y: t1.y, t1_w: t1.w, t1_h: t1.h,
                t2_x: t2.x, t2_y: t2.y, t2_w: t2.w, t2_h: t2.h,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Posiciones y tamanos guardados correctamente.');
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

    $(document).ready(function () {
        var config = window.GIFTMESSAGE_SETTINGS;
        if (!config) {
            return;
        }

        initCanvas('canvas-envelope');
        initCanvas('canvas-card');
        initInteractions();
        bindFineTuneInputs();
        initFontPreview(config.fonts || {});
        bindFontInputs();
        bindColorHexPairs();
        bindImagePreviews();
        applySampleText();

        $('#preview-message, #preview-order').on('input', applySampleText);

        $('#save-positions-envelope').on('click', function () {
            savePositions('envelope', $(this));
        });
        $('#save-positions-card').on('click', function () {
            savePositions('card', $(this));
        });

        $('#save-fonts-envelope').on('click', function () {
            saveFontsScope('envelope', $(this));
        });
        $('#save-fonts-card').on('click', function () {
            saveFontsScope('card', $(this));
        });

        $('#copy-to-card').on('click', copyEnvelopeToCard);

        // Marca "sin guardar" la tipografia en cuanto se toca cualquiera de
        // sus campos, igual que ya hace savePositions() con las cajas.
        $('[name$="_font"], [name$="_size"], [name$="_color"], [name$="_opacity"]').on('input change', function () {
            fontsDirty[$(this).attr('name').indexOf('card_') === 0 ? 'card' : 'envelope'] = true;
        });

        window.addEventListener('beforeunload', function (e) {
            if (dirty.envelope || dirty.card || fontsDirty.envelope || fontsDirty.card) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
})(jQuery);

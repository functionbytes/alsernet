(function ($) {
    var FALLBACK_STACK = 'Helvetica, Arial, sans-serif';

    // Mismo suelo que GiftMessagePdfService::MIN_FONT_SIZE.
    var MIN_FONT_PT = 5;

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

    // Milimetros reales de cada pieza, para pasar los puntos del PDF a pixeles
    // del lienzo. El lienzo mide 600x270 px para 200x90 mm (y 660x330 para
    // 220x110), o sea 3 px/mm, no los 3.78 px/mm que asume ptToPx: convertir
    // como si fuera una pantalla a 96 dpi pintaba la letra un 26% mas grande de
    // lo que sale impresa.
    var PIECE_WIDTH_MM = { envelope: 220, card: 200 };

    function ptToCanvasPx(pt, scope) {
        var $canvas = $('#canvas-' + scope);
        var widthMm = PIECE_WIDTH_MM[scope];

        if (!$canvas.length || !widthMm) {
            return ptToPx(pt);
        }

        // 1 pt = 0.352778 mm
        return (parseFloat(pt) || 0) * 0.352778 * ($canvas.width() / widthMm);
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
        shrinkToFit($el);
        refreshPreviewMetrics();
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
        var $box = $('#canvas-' + scope + ' [data-slot="' + slot + '"]');
        var px = ptToCanvasPx(style.size, scope);

        $box.css({
            fontFamily: fontStack(style.font),
            fontSize: px + 'px',
            color: rgba(style.color, style.opacity),
        }).data('maxFontPx', px);

        shrinkToFit($box);
    }

    // ─── Vista previa fiel al PDF ───────────────────────────────────────────
    // El tamano y la fuente los decide el servidor con el mismo codigo que usa
    // el PDF (GiftMessagePdfService::previewMetrics): encoger por CSS aqui no
    // basta, porque el PDF fuerza DejaVu Sans cuando hay emojis —mas ancha y un
    // 25% mas alta por linea que Helvetica— y mide con las metricas de la
    // fuente. Se manda ademas el tamano actual de las cajas, que puede no estar
    // guardado todavia.
    var metricsTimer = null;

    function currentBoxes() {
        var boxes = { envelope: {}, card: {} };

        $('.giftmessage-drag').each(function () {
            var $box = $(this);
            var $canvas = $box.closest('.giftmessage-canvas');
            var scope = $canvas.attr('id') === 'canvas-card' ? 'card' : 'envelope';

            boxes[scope][$box.data('slot')] = {
                w: percentOf($box.outerWidth(), $canvas.width()),
                h: percentOf($box.outerHeight(), $canvas.height()),
            };
        });

        return boxes;
    }

    function refreshPreviewMetrics() {
        var config = window.GIFTMESSAGE_SETTINGS;

        if (!config || !config.urls.previewMetrics) {
            return;
        }

        clearTimeout(metricsTimer);
        metricsTimer = setTimeout(function () {
            $.ajax({
                url: config.urls.previewMetrics,
                method: 'POST',
                data: JSON.stringify({
                    message: $('#preview-message').val(),
                    order: $('#preview-order').val(),
                    boxes: currentBoxes(),
                }),
                contentType: 'application/json',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Object.keys(response).forEach(function (scope) {
                        Object.keys(response[scope]).forEach(function (slot) {
                            var metrics = response[scope][slot];

                            $('#canvas-' + scope + ' [data-slot="' + slot + '"]').css({
                                fontFamily: metrics.font_family,
                                fontSize: ptToCanvasPx(metrics.font_size, scope) + 'px',
                                lineHeight: metrics.line_height || 1.2,
                            });
                        });

                        showFitNote(scope, response[scope].t1);
                    });
                },
                error: function () {
                    // Si el servidor no responde, al menos que no se salga del
                    // recuadro: se encoge en el navegador como aproximacion.
                    $('.giftmessage-drag').each(function () { shrinkToFit($(this)); });
                },
            });
        }, 350);
    }

    // Cartel con el tamano real de impresion: sin esto el ajuste de las cajas se
    // hacia a ojo y no habia forma de saber si el mensaje iba a salir legible.
    function showFitNote(scope, metrics) {
        var $note = $('[data-fit-note="' + scope + '"]');

        if (!$note.length || !metrics) {
            return;
        }

        var texto = 'El mensaje se imprimira a ' + metrics.font_size + ' pt';
        var alerta = false;

        if (!metrics.fits) {
            texto = 'El mensaje NO cabe ni a ' + metrics.min_font_size + ' pt: se recortara al imprimir. Amplia la caja o acorta el texto.';
            alerta = true;
        } else if (metrics.font_size <= metrics.min_font_size) {
            texto += ' (el minimo configurado). Un mensaje mas largo ya no cabria.';
            alerta = true;
        } else if (metrics.font_size < metrics.configured_size) {
            texto += ', reducido desde los ' + metrics.configured_size + ' pt configurados porque no cabia.';
        } else {
            texto += ', el tamano configurado.';
        }

        $note.text(texto).toggleClass('giftmessage-fit-note-alert', alerta);
    }

    // Encogido local, solo como respaldo mientras llega la respuesta del
    // servidor o si esta falla.
    function shrinkToFit($box) {
        var maxPx = $box.data('maxFontPx');

        if (!maxPx || !$box.length) {
            return;
        }

        var minPx = 4;
        var size = maxPx;

        $box.css('fontSize', size + 'px');

        while (size > minPx && $box[0].scrollHeight > $box[0].clientHeight) {
            size -= 1;
            $box.css('fontSize', size + 'px');
        }
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

            $('[name="' + mapping.prefix + '_font"]').on('change', refresh).on('change', refreshPreviewMetrics);
            $('[name="' + mapping.prefix + '_size"]').on('input', refresh).on('input', refreshPreviewMetrics);
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
            shrinkToFit($box);
            refreshPreviewMetrics();
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

        $('.giftmessage-drag').each(function () { shrinkToFit($(this)); });
        refreshPreviewMetrics();
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

    // ─── Imagen de fondo: zona de arrastrar y soltar ────────────────────────
    // La zona es a la vez vista previa y destino: la imagen guardada se pinta
    // como fondo suyo. Sube en cuanto se suelta el archivo y repinta el fondo,
    // la etiqueta y el lienzo de posicionamiento sin recargar. Solo se recarga
    // cuando antes NO habia imagen, porque en ese caso el lienzo ni siquiera
    // esta renderizado (la vista muestra el aviso de "sube primero la imagen").
    function paintDropzone($zone, url) {
        $zone.css('background-image', url ? 'url("' + url + '")' : 'none')
            .toggleClass('giftmessage-dropzone-filled', !!url);
    }

    function initImageDropzones() {
        var config = window.GIFTMESSAGE_SETTINGS;

        if (typeof Dropzone === 'undefined' || !config) {
            return;
        }

        Dropzone.autoDiscover = false;

        $('.giftmessage-dropzone').each(function () {
            var element = this;
            var $zone = $(element);
            var scope = $zone.data('scope');
            var field = $zone.data('field');
            var $block = $zone.closest('.col-12');
            var hadImage = !!$zone.attr('data-image');

            // El fondo se aplica aqui y no en el HTML para no dejar un style=""
            // con la URL incrustada en la plantilla.
            paintDropzone($zone, $zone.attr('data-image'));

            new Dropzone(element, {
                url: config.urls.uploadImage,
                paramName: field,
                acceptedFiles: 'image/*',
                maxFilesize: 5,
                maxFiles: 1,
                uploadMultiple: false,
                parallelUploads: 1,
                createImageThumbnails: false,
                addRemoveLinks: false,
                timeout: 60000,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                dictInvalidFileType: 'Ese archivo no es una imagen.',
                dictFileTooBig: 'La imagen no puede superar los 5 MB.',
                init: function () {
                    var dropzone = this;

                    // maxFiles: 1 por pieza — al soltar otra se descarta la anterior
                    // para que la zona no acumule intentos previos.
                    dropzone.on('addedfile', function () {
                        if (dropzone.files.length > 1) {
                            dropzone.removeFile(dropzone.files[0]);
                        }
                    });

                    dropzone.on('sending', function () {
                        $zone.addClass('is-uploading')
                            .find('[data-dropzone-title]').text('Subiendo imagen...');
                    });

                    dropzone.on('success', function (file, response) {
                        var url = (response.images || {})[scope];
                        var name = (response.names || {})[scope];

                        if (!hadImage) {
                            toastr.success('Imagen guardada. Recargando para abrir el editor de posiciones...');
                            setTimeout(function () { location.reload(); }, 800);

                            return;
                        }

                        // Cache-busting: el nombre del fichero puede repetirse y el
                        // navegador serviria la imagen vieja.
                        var fresh = url + (url.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now();

                        $zone.attr('data-image', url);
                        paintDropzone($zone, fresh);
                        $block.find('[data-preview-label]').text(name || '');
                        $('#canvas-' + scope)
                            .attr('data-bg', url)
                            .css('background-image', 'url("' + fresh + '")');

                        toastr.success('Imagen actualizada correctamente.');
                    });

                    dropzone.on('error', function (file, message) {
                        var text = typeof message === 'string'
                            ? message
                            : (message && message.message) || 'No se pudo subir la imagen.';

                        toastr.error(text);
                    });

                    dropzone.on('complete', function (file) {
                        $zone.removeClass('is-uploading')
                            .find('[data-dropzone-title]')
                            .text($zone.attr('data-image')
                                ? 'Arrastra otra imagen para reemplazarla'
                                : 'Arrastra la imagen aqui');
                        dropzone.removeFile(file);
                    });
                },
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
        initImageDropzones();
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

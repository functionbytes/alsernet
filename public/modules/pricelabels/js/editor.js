(function ($) {
    var FIELDS = [];
    var FIELD_LABELS = {};
    var CANVAS_DIM = { v: { w: 700, h: 990 }, h: { w: 1133, h: 720 } };
    var SAMPLE_TEXT = {
        referencia: '312553',
        descripcion: 'Tripode Primos Trigger Stick',
        pvprp: '169,99€',
        pvp: '149,99€',
    };
    var SAMPLE_ROW = null;
    var BARCODE_PREVIEWS = {};

    function sampleTextFor(key) {
        if (SAMPLE_ROW && SAMPLE_ROW[key]) {
            return SAMPLE_ROW[key];
        }

        return SAMPLE_TEXT[key] || FIELD_LABELS[key] || key;
    }
    // Se sobrescribe en $(document).ready con los stacks del servidor, que
    // incluyen las fuentes personalizadas subidas en settings.
    var FONT_STACKS = {
        helvetica: 'Helvetica, Arial, sans-serif',
        times: '"Times New Roman", Times, serif',
        courier: '"Courier New", Courier, monospace',
    };
    var SNAP_RANGE = 6;

    var zoomState = { v: 1, h: 1 };
    var positionsDirty = { v: false, h: false };

    function markDirty(canvasKey) {
        positionsDirty[canvasKey] = true;
    }

    function canvasKeyFor(el) {
        var canvasId = $(el).closest('.pricelabels-canvas').attr('id');

        return canvasId === 'canvas-h' ? 'h' : 'v';
    }

    function fontStack(family) {
        return FONT_STACKS[family] || FONT_STACKS.helvetica;
    }

    function ptToPx(pt) {
        return (parseFloat(pt) || 0) * (96 / 72);
    }

    function fieldStyle(fieldsConfig, key) {
        return (fieldsConfig && fieldsConfig[key]) || {};
    }

    function applyStyle($el, key, isHorizontal, fieldsConfig) {
        var style = fieldStyle(fieldsConfig, key);
        var family = isHorizontal ? (style.font_family_h || 'helvetica') : (style.font_family || 'helvetica');
        var size = isHorizontal ? (style.font_size_h || 12) : (style.font_size || 12);

        $el.css({
            color: style.color || '#000000',
            fontFamily: fontStack(family),
            fontSize: ptToPx(size) + 'px',
            fontWeight: style.bold ? 'bold' : 'normal',
            fontStyle: style.italic ? 'italic' : 'normal',
            textAlign: style.align || 'center',
            width: (style.box_w || 150) + 'px',
            height: (style.box_h || 30) + 'px',
        });
    }

    function currentZoomFor($el) {
        var canvasId = $el.closest('.pricelabels-canvas').attr('id');

        return zoomState[canvasId === 'canvas-h' ? 'h' : 'v'] || 1;
    }

    function drawGuides(canvasId, x, y) {
        var $canvas = $('#' + canvasId);
        $canvas.find('.snap-guide').remove();

        if (x !== null) {
            $('<div class="snap-guide snap-guide-v"></div>').css({ left: x + 'px' }).appendTo($canvas);
        }
        if (y !== null) {
            $('<div class="snap-guide snap-guide-h"></div>').css({ top: y + 'px' }).appendTo($canvas);
        }
    }

    function applySnap($el, canvasId, x, y) {
        var snappedX = x;
        var snappedY = y;
        var guideX = null;
        var guideY = null;

        $('#' + canvasId + ' .pricelabels-drag').not($el).each(function () {
            var $other = $(this);
            var ox = parseFloat($other.css('left')) || 0;
            var oy = parseFloat($other.css('top')) || 0;

            if (guideX === null && Math.abs(x - ox) <= SNAP_RANGE) {
                snappedX = ox;
                guideX = ox;
            }
            if (guideY === null && Math.abs(y - oy) <= SNAP_RANGE) {
                snappedY = oy;
                guideY = oy;
            }
        });

        drawGuides(canvasId, guideX, guideY);

        return { x: snappedX, y: snappedY };
    }

    function dragMoveListener(event) {
        var $el = $(event.target);
        var canvasId = $el.closest('.pricelabels-canvas').attr('id');
        var zoom = currentZoomFor($el);

        var left = (parseFloat($el.css('left')) || 0) + event.dx / zoom;
        var top = (parseFloat($el.css('top')) || 0) + event.dy / zoom;

        var snapped = applySnap($el, canvasId, left, top);
        $el.css({ left: snapped.x + 'px', top: snapped.y + 'px' });
        markDirty(canvasId === 'canvas-h' ? 'h' : 'v');
    }

    function dragEndListener(event) {
        $(event.target).closest('.pricelabels-canvas').find('.snap-guide').remove();
    }

    function resizeMoveListener(event) {
        var $el = $(event.target);
        var key = $el.data('key');
        var zoom = currentZoomFor($el);

        var width = (parseFloat($el.css('width')) || 0) + event.deltaRect.width / zoom;
        var height = (parseFloat($el.css('height')) || 0) + event.deltaRect.height / zoom;
        var left = (parseFloat($el.css('left')) || 0) + event.deltaRect.left / zoom;
        var top = (parseFloat($el.css('top')) || 0) + event.deltaRect.top / zoom;

        // box_w/box_h son por campo (no por slot) y se comparten entre ambos lienzos.
        $('[data-key="' + key + '"]').css({ width: width + 'px', height: height + 'px' });
        $el.css({ left: left + 'px', top: top + 'px' });

        $('#field-' + key + '-box-w').val(Math.round(width));
        $('#field-' + key + '-box-h').val(Math.round(height));
        markDirty(canvasKeyFor($el));
    }

    function initInteractions() {
        if (typeof interact !== 'function') {
            return;
        }

        interact('.pricelabels-drag')
            .draggable({
                modifiers: [
                    interact.modifiers.restrictRect({ restriction: 'parent', endOnly: true }),
                ],
                listeners: { move: dragMoveListener, end: dragEndListener },
            })
            .resizable({
                edges: { left: true, right: true, bottom: true, top: true },
                modifiers: [
                    interact.modifiers.restrictSize({ min: { width: 20, height: 12 } }),
                    interact.modifiers.restrictEdges({ outer: 'parent' }),
                ],
                listeners: { move: resizeMoveListener },
            });
    }

    function initPreview(opts) {
        var $canvas = $('#' + opts.canvasId);
        if (!$canvas.length) {
            return;
        }

        var dim = CANVAS_DIM[opts.mode];
        var $outer = $('#' + opts.outerId);
        var positions = opts.positions || {};
        var isHorizontal = opts.mode === 'h';

        $canvas.css({
            width: dim.w + 'px',
            height: dim.h + 'px',
            backgroundImage: 'url(' + $canvas.data('bg') + ')',
            backgroundSize: '100% 100%',
            backgroundRepeat: 'no-repeat',
        });
        $outer.css({ width: dim.w + 'px', height: dim.h + 'px' });

        for (var slot = 1; slot <= opts.slots; slot++) {
            FIELDS.forEach(function (key) {
                var pos = (positions[key] && positions[key][slot]) ? positions[key][slot] : { x: 50, y: 50 };
                var text = key === 'label' ? opts.labelText : sampleTextFor(key);

                var $el = $('<div class="pricelabels-drag"></div>')
                    .attr('data-key', key)
                    .attr('data-slot', slot)
                    .attr('tabindex', '0')
                    .css({ left: pos.x + 'px', top: pos.y + 'px' });

                // Los campos de codigo de barras/QR se dibujan como imagen para
                // que el lienzo refleje lo que saldra impreso.
                if (BARCODE_PREVIEWS[key]) {
                    $el.addClass('is-barcode').append(
                        $('<img alt="">').attr('src', BARCODE_PREVIEWS[key])
                    );
                } else {
                    $el.text(text + ' #' + slot);
                }

                applyStyle($el, key, isHorizontal, opts.fields);
                $canvas.append($el);
            });
        }
    }

    function initZoom(sliderId, valueId, outerId, canvasId, mode, key) {
        var $slider = $('#' + sliderId);
        if (!$slider.length) {
            return;
        }

        var dim = CANVAS_DIM[mode];

        $slider.on('input', function () {
            var percent = parseInt($slider.val(), 10);
            var zoom = percent / 100;
            zoomState[key] = zoom;

            $('#' + valueId).text(percent + '%');
            $('#' + outerId).css({ width: (dim.w * zoom) + 'px', height: (dim.h * zoom) + 'px' });
            $('#' + canvasId).css({ transform: 'scale(' + zoom + ')', transformOrigin: 'top left' });
        });
    }

    function copyPositions(toHorizontal, fromSlots, toSlots) {
        var fromCanvas = toHorizontal ? 'canvas-v' : 'canvas-h';
        var toCanvas = toHorizontal ? 'canvas-h' : 'canvas-v';
        var slots = Math.min(fromSlots, toSlots);

        FIELDS.forEach(function (key) {
            for (var slot = 1; slot <= slots; slot++) {
                var $from = $('#' + fromCanvas + ' [data-key="' + key + '"][data-slot="' + slot + '"]');
                var left = parseFloat($from.css('left')) || 0;
                var top = parseFloat($from.css('top')) || 0;

                $('#' + toCanvas + ' [data-key="' + key + '"][data-slot="' + slot + '"]').css({ left: left + 'px', top: top + 'px' });
            }
        });

        markDirty(toHorizontal ? 'h' : 'v');

        if (fromSlots !== toSlots) {
            toastr.warning('El origen tiene ' + fromSlots + ' etiqueta(s) y el destino ' + toSlots + '. Solo se copiaron los primeros ' + slots + ' slot(s); revisa el resto manualmente.');
        } else {
            toastr.success('Posiciones copiadas (slot a slot). Revisa el resultado y pulsa "Guardar posiciones".');
        }
    }

    function pagesLine(rowsCount, slotsV, slotsH) {
        var parts = [];

        if (slotsV && $('#save-positions-v').length) {
            parts.push('Vertical: ' + Math.ceil(rowsCount / slotsV) + ' pagina(s) (' + slotsV + ' por hoja)');
        }
        if (slotsH && $('#save-positions-h').length) {
            parts.push('Horizontal: ' + Math.ceil(rowsCount / slotsH) + ' pagina(s) (' + slotsH + ' por hoja)');
        }

        return parts.join(' &middot; ');
    }

    function renderExcelPreview($preview, data, slotsV, slotsH) {
        window.PriceLabelsShared.renderExcelPreview($preview, data, FIELD_LABELS);

        var pages = pagesLine(data.rows_count, slotsV, slotsH);
        if (pages) {
            $preview.find('span.text-success').first().after('<div class="text-muted">' + pages + '</div>');
        }
    }

    function bindExcelPreview(slotsV, slotsH) {
        var $input = $('#generate-excel');
        var $preview = $('#generate-excel-preview');
        if (!$input.length) {
            return;
        }

        $input.on('change', function () {
            var file = this.files[0];
            var url = $input.closest('form').data('preview-url');

            if (!file || !url) {
                $preview.addClass('d-none').empty();

                return;
            }

            var fd = new FormData();
            fd.append('excel_file', file);

            $preview.removeClass('d-none').html('<span class="text-muted">Analizando archivo...</span>');

            $.ajax({
                url: url,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) { renderExcelPreview($preview, res, slotsV, slotsH); },
                error: function (xhr) {
                    $preview.removeClass('d-none').html('<span class="text-danger">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo leer el archivo.') + '</span>');
                },
            });
        });
    }

    function bindGenerateForm() {
        var $form = $('#generate-form');
        if (!$form.length) {
            return;
        }

        var $status = $('#generate-status');
        var $buttons = $form.find('button[type="submit"]');
        var clickedType = null;
        var isSubmitting = false;

        $buttons.on('click', function () {
            clickedType = $(this).val();
        });

        $form.on('submit', function (e) {
            e.preventDefault();

            if (isSubmitting) {
                return;
            }
            isSubmitting = true;

            var originalTexts = {};
            $buttons.each(function () {
                var $btn = $(this);
                originalTexts[$btn.attr('name') + $btn.val()] = $btn.text();
            });

            $buttons.prop('disabled', true).each(function () {
                $(this).text($(this).val() === clickedType ? 'Generando...' : $(this).text());
            });

            $status.html('<span class="text-muted">Enviando archivo...</span>');

            var fd = new FormData(this);
            fd.append('type', clickedType);

            function restoreButtons() {
                isSubmitting = false;
                $buttons.each(function () {
                    var $btn = $(this);
                    $btn.prop('disabled', false).text(originalTexts[$btn.attr('name') + $btn.val()] || $btn.text());
                });
            }

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $status.html('<span class="text-muted">Procesando etiquetas en segundo plano...</span>');
                    window.PriceLabelsShared.pollGenerationStatus({
                        statusUrl: res.generations[0].status_url,
                        $status: $status,
                        onDone: restoreButtons,
                    });
                },
                error: function (xhr) {
                    var message = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'Error al enviar el archivo.';
                    $status.html('<span class="text-danger">' + message + '</span>');
                    toastr.error(message);
                    restoreButtons();
                },
            });
        });
    }

    function collectPositions(canvasId, slots) {
        var positions = {};

        FIELDS.forEach(function (key) {
            positions[key] = {};
        });

        for (var slot = 1; slot <= slots; slot++) {
            FIELDS.forEach(function (key) {
                var $el = $('#' + canvasId + ' [data-key="' + key + '"][data-slot="' + slot + '"]');
                positions[key][slot] = {
                    x: parseInt($el.css('left'), 10) || 0,
                    y: parseInt($el.css('top'), 10) || 0,
                };
            });
        }

        return positions;
    }

    function collectFields() {
        var props = ['color', 'font-family', 'font-size', 'font-family-h', 'font-size-h', 'align', 'box-w', 'box-h'];
        var checkboxProps = ['bold', 'italic'];
        var fields = {};

        FIELDS.forEach(function (key) {
            var $anyInput = $('#field-' + key + '-color');
            if (!$anyInput.length) {
                return;
            }

            var style = {};

            props.forEach(function (prop) {
                var $input = $('#field-' + key + '-' + prop);
                if ($input.length) {
                    style[prop.replace(/-/g, '_')] = $input.val();
                }
            });

            checkboxProps.forEach(function (prop) {
                var $input = $('#field-' + key + '-' + prop);
                if ($input.length) {
                    style[prop] = $input.is(':checked') ? 1 : 0;
                }
            });

            fields[key] = style;
        });

        return fields;
    }

    function savePositions(orientation, canvasId, slots, $btn) {
        var config = window.PRICE_LABELS_EDITOR;
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Guardando...');

        var payload = {
            orientation: orientation,
            positions: collectPositions(canvasId, slots),
        };

        if (config.saveIncludesStyle) {
            payload.fields = collectFields();
        }

        $.ajax({
            url: config.urls.savePositions,
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Posiciones guardadas correctamente.');
                positionsDirty[orientation === 'horizontal' ? 'h' : 'v'] = false;
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar las posiciones.');
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            },
        });
    }

    function bindPreviewPdf(slotsV, slotsH) {
        var config = window.PRICE_LABELS_EDITOR;
        var previewUrl = config.urls && config.urls.previewPdf;

        if (!previewUrl) {
            return;
        }

        var objectUrl = null;

        $('.preview-pdf-btn').on('click', function () {
            var $btn = $(this);
            var orientation = $btn.data('orientation');
            var canvasId = orientation === 'horizontal' ? 'canvas-h' : 'canvas-v';
            var slots = orientation === 'horizontal' ? slotsH : slotsV;
            var originalText = $btn.text();

            $btn.prop('disabled', true).text('Generando...');
            $('#preview-pdf-status').html('<span class="text-muted">Generando la hoja de prueba...</span>');
            $('#preview-pdf-modal').modal('show');

            $.ajax({
                url: previewUrl,
                method: 'POST',
                data: {
                    orientation: orientation,
                    positions: collectPositions(canvasId, slots),
                    fields: collectFields(),
                },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                xhrFields: { responseType: 'blob' },
                success: function (blob) {
                    if (objectUrl) {
                        URL.revokeObjectURL(objectUrl);
                    }

                    objectUrl = URL.createObjectURL(blob);
                    $('#preview-pdf-frame').attr('src', objectUrl);
                    $('#preview-pdf-open').attr('href', objectUrl);
                    $('#preview-pdf-status').empty();
                },
                error: function (xhr) {
                    // La respuesta de error tambien llega como blob por responseType.
                    var showError = function (message) {
                        $('#preview-pdf-status').html('<span class="text-danger">' + message + '</span>');
                        toastr.error(message);
                    };

                    if (xhr.response instanceof Blob) {
                        xhr.response.text().then(function (text) {
                            var message = 'No se pudo generar la previsualizacion.';
                            try {
                                var parsed = JSON.parse(text);
                                message = parsed.message || (parsed.errors && Object.values(parsed.errors)[0][0]) || message;
                            } catch (e) { /* respuesta no JSON */ }
                            showError(message);
                        });

                        return;
                    }

                    showError('No se pudo generar la previsualizacion.');
                },
                complete: function () {
                    $btn.prop('disabled', false).text(originalText);
                },
            });
        });
    }

    function initZoomReset(buttonId, sliderId) {
        $('#' + buttonId).on('click', function () {
            $('#' + sliderId).val(100).trigger('input');
        });
    }

    function bindRowCanvasHighlight() {
        $('tr[data-field-key]').on('mouseenter', function () {
            $('[data-key="' + $(this).data('field-key') + '"]').addClass('is-highlighted');
        }).on('mouseleave', function () {
            $('[data-key="' + $(this).data('field-key') + '"]').removeClass('is-highlighted');
        });

        $(document).on('mouseenter', '.pricelabels-drag', function () {
            $(this).addClass('is-highlighted');
            $('tr[data-field-key="' + $(this).data('key') + '"]').addClass('pricelabels-row-highlight');
        }).on('mouseleave', '.pricelabels-drag', function () {
            $(this).removeClass('is-highlighted');
            $('tr[data-field-key="' + $(this).data('key') + '"]').removeClass('pricelabels-row-highlight');
        });

        $('tr[data-field-key]').on('click', function (e) {
            if ($(e.target).is('input, select, button, a, i')) {
                return;
            }

            var $row = $(this);
            var wasPinned = $row.hasClass('is-pinned');

            $('tr[data-field-key]').removeClass('is-pinned');
            $('.pricelabels-drag').removeClass('is-pinned');

            if (!wasPinned) {
                $row.addClass('is-pinned');
                $('[data-key="' + $row.data('field-key') + '"]').addClass('is-pinned');
            }
        });
    }

    function highlightNewField(key) {
        if (!key) {
            return;
        }

        var $row = $('tr[data-field-key="' + key + '"]');
        if ($row.length) {
            $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $row.addClass('pricelabels-row-highlight');
            setTimeout(function () { $row.removeClass('pricelabels-row-highlight'); }, 4000);
        }

        $('[data-key="' + key + '"]').addClass('is-new');
        setTimeout(function () { $('[data-key="' + key + '"]').removeClass('is-new'); }, 4500);
    }

    function bindKeyboardNudge() {
        $(document).on('keydown', '.pricelabels-drag', function (e) {
            var step = e.shiftKey ? 10 : 1;
            var $el = $(this);
            var left = parseFloat($el.css('left')) || 0;
            var top = parseFloat($el.css('top')) || 0;
            var moved = true;

            if (e.key === 'ArrowLeft') {
                left -= step;
            } else if (e.key === 'ArrowRight') {
                left += step;
            } else if (e.key === 'ArrowUp') {
                top -= step;
            } else if (e.key === 'ArrowDown') {
                top += step;
            } else {
                moved = false;
            }

            if (moved) {
                e.preventDefault();
                $el.css({ left: left + 'px', top: top + 'px' });
                markDirty(canvasKeyFor($el));
            }
        });
    }

    function bindApplyStyleToAll() {
        var $button = $('#apply-style-all');
        if (!$button.length) {
            return;
        }

        var props = ['color', 'font-family', 'font-size', 'font-family-h', 'font-size-h', 'align'];
        var checkboxProps = ['bold', 'italic'];

        $button.on('click', function () {
            var source = $('#apply-style-source').val();
            if (!source) {
                return;
            }

            FIELDS.forEach(function (key) {
                if (key === source) {
                    return;
                }

                props.forEach(function (prop) {
                    var $src = $('#field-' + source + '-' + prop);
                    var $dst = $('#field-' + key + '-' + prop);
                    if ($src.length && $dst.length) {
                        $dst.val($src.val()).trigger($dst.is('select') ? 'change' : 'input');
                    }
                });

                checkboxProps.forEach(function (prop) {
                    var $src = $('#field-' + source + '-' + prop);
                    var $dst = $('#field-' + key + '-' + prop);
                    if ($src.length && $dst.length) {
                        $dst.prop('checked', $src.is(':checked')).trigger('change');
                    }
                });
            });

            toastr.success('Estilo aplicado a todos los campos. No olvides pulsar "Guardar cambios".');
        });
    }

    function bindStyleInputs() {
        FIELDS.forEach(function (key) {
            $('#field-' + key + '-color').on('input', function () {
                $('[data-key="' + key + '"]').css('color', $(this).val());
            });
            $('#field-' + key + '-font-family').on('change', function () {
                $('#canvas-v [data-key="' + key + '"]').css('font-family', fontStack($(this).val()));
            });
            $('#field-' + key + '-font-family-h').on('change', function () {
                $('#canvas-h [data-key="' + key + '"]').css('font-family', fontStack($(this).val()));
            });
            $('#field-' + key + '-font-size').on('input', function () {
                $('#canvas-v [data-key="' + key + '"]').css('font-size', ptToPx($(this).val()) + 'px');
            });
            $('#field-' + key + '-font-size-h').on('input', function () {
                $('#canvas-h [data-key="' + key + '"]').css('font-size', ptToPx($(this).val()) + 'px');
            });
            $('#field-' + key + '-bold').on('change', function () {
                $('[data-key="' + key + '"]').css('font-weight', $(this).is(':checked') ? 'bold' : 'normal');
            });
            $('#field-' + key + '-italic').on('change', function () {
                $('[data-key="' + key + '"]').css('font-style', $(this).is(':checked') ? 'italic' : 'normal');
            });
            $('#field-' + key + '-align').on('change', function () {
                $('[data-key="' + key + '"]').css('text-align', $(this).val());
            });
            $('#field-' + key + '-box-w').on('input', function () {
                $('[data-key="' + key + '"]').css('width', $(this).val() + 'px');
            });
            $('#field-' + key + '-box-h').on('input', function () {
                $('[data-key="' + key + '"]').css('height', $(this).val() + 'px');
            });
        });
    }

    $(document).ready(function () {
        var config = window.PRICE_LABELS_EDITOR;
        if (!config) {
            return;
        }

        FIELDS = config.fieldKeys || [];

        if (config.fontStacks) {
            FONT_STACKS = config.fontStacks;
        }

        BARCODE_PREVIEWS = config.barcodePreviews || {};

        FIELD_LABELS = config.fieldLabels || {};
        SAMPLE_ROW = config.sampleRow || null;

        var grid = config.grid || {};
        var slotsV = ((grid.vertical && grid.vertical.rows) || 2) * ((grid.vertical && grid.vertical.columns) || 2);
        var slotsH = ((grid.horizontal && grid.horizontal.rows) || 2) * ((grid.horizontal && grid.horizontal.columns) || 4);

        initPreview({
            mode: 'v',
            canvasId: 'canvas-v',
            outerId: 'canvas-outer-v',
            slots: slotsV,
            positions: config.positions.vertical,
            fields: config.fields,
            labelText: config.labelText,
        });

        initPreview({
            mode: 'h',
            canvasId: 'canvas-h',
            outerId: 'canvas-outer-h',
            slots: slotsH,
            positions: config.positions.horizontal,
            fields: config.fields,
            labelText: config.labelText,
        });

        initInteractions();
        bindStyleInputs();
        bindRowCanvasHighlight();
        bindKeyboardNudge();
        bindApplyStyleToAll();
        highlightNewField(config.newFieldKey);

        initZoom('zoom-v', 'zoom-v-value', 'canvas-outer-v', 'canvas-v', 'v', 'v');
        initZoom('zoom-h', 'zoom-h-value', 'canvas-outer-h', 'canvas-h', 'h', 'h');
        initZoomReset('zoom-v-reset', 'zoom-v');
        initZoomReset('zoom-h-reset', 'zoom-h');

        window.addEventListener('beforeunload', function (e) {
            if (positionsDirty.v || positionsDirty.h) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        $('#save-positions-v').on('click', function () {
            savePositions('vertical', 'canvas-v', slotsV, $(this));
        });

        $('#save-positions-h').on('click', function () {
            savePositions('horizontal', 'canvas-h', slotsH, $(this));
        });

        $('#copy-positions-to-h').on('click', function () {
            copyPositions(true, slotsV, slotsH);
        });

        $('#copy-positions-to-v').on('click', function () {
            copyPositions(false, slotsH, slotsV);
        });

        bindPreviewPdf(slotsV, slotsH);
        bindExcelPreview(slotsV, slotsH);
        bindGenerateForm();
    });
})(jQuery);

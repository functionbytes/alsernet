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

    function sampleTextFor(key) {
        return SAMPLE_TEXT[key] || FIELD_LABELS[key] || key;
    }
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
                    .text(text + ' #' + slot)
                    .css({ left: pos.x + 'px', top: pos.y + 'px' });

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

    function renderExcelPreview($preview, data) {
        var html = '<span class="text-success">Se detectaron ' + data.rows_count + ' etiqueta(s).</span>';

        if (data.sample && data.sample.length) {
            html += '<table class="table table-sm table-borderless mt-1 mb-0">';
            data.sample.forEach(function (row) {
                html += '<tr>' + Object.keys(row).map(function (key) {
                    return '<td class="p-0 pe-2 text-muted">' + row[key] + '</td>';
                }).join('') + '</tr>';
            });
            html += '</table>';
        }

        $preview.removeClass('d-none').html(html);
    }

    function bindExcelPreview() {
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
                success: function (res) { renderExcelPreview($preview, res); },
                error: function (xhr) {
                    $preview.removeClass('d-none').html('<span class="text-danger">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo leer el archivo.') + '</span>');
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

    function savePositions(orientation, canvasId, slots, $btn) {
        var config = window.PRICE_LABELS_EDITOR;
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: config.urls.savePositions,
            method: 'POST',
            data: {
                orientation: orientation,
                positions: collectPositions(canvasId, slots),
            },
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
            $('tr[data-field-key="' + $(this).data('key') + '"]').addClass('pricelabels-row-highlight');
        }).on('mouseleave', '.pricelabels-drag', function () {
            $('tr[data-field-key="' + $(this).data('key') + '"]').removeClass('pricelabels-row-highlight');
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
        FIELD_LABELS = config.fieldLabels || {};

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

        bindExcelPreview();
    });
})(jQuery);

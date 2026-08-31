(function ($) {
    var FIELDS = [];
    var FIELD_LABELS = {};
    var CANVAS_DIM = { v: { w: 700, h: 990 }, h: { w: 1133, h: 720 } };
    // Tamano fisico de cada hoja (mm), igual que CANVAS_DIM en
    // PriceLabelPdfService::CANVAS_DIM: sirve para el indicador de
    // posicion/tamano en vivo al arrastrar/redimensionar.
    var PAGE_MM = { v: { w: 210, h: 297 }, h: { w: 340, h: 216 } };
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
    var $floatTb = null;
    var $selected = null;
    // Filas/columnas de cada rejilla, rellenadas en $(document).ready(); las
    // necesita canvasContextFor() para recalcular la cuadricula por campo.
    var gridDims = { v: { rows: 2, columns: 2 }, h: { rows: 2, columns: 4 } };

    function canvasContextFor($el) {
        var mode = canvasKeyFor($el);

        return {
            canvasId: mode === 'h' ? 'canvas-h' : 'canvas-v',
            mode: mode,
            rows: gridDims[mode].rows,
            columns: gridDims[mode].columns,
        };
    }

    // Rectangulo (en px del lienzo) de la celda de cuadricula a la que
    // pertenece el slot de $el; misma division en filas/columnas que
    // PriceLabelTemplateService::defaultPositions().
    function slotCellFor($el) {
        var ctx = canvasContextFor($el);
        var dim = CANVAS_DIM[ctx.mode];
        var slot = parseInt($el.data('slot'), 10) || 1;
        var col = (slot - 1) % ctx.columns;
        var row = Math.floor((slot - 1) / ctx.columns);
        var cellW = dim.w / ctx.columns;
        var cellH = dim.h / ctx.rows;

        return { x0: col * cellW, y0: row * cellH, w: cellW, h: cellH };
    }

    function toMm(px, mode, axis) {
        var dim = CANVAS_DIM[mode];
        var page = PAGE_MM[mode];
        var factor = axis === 'x' ? (page.w / dim.w) : (page.h / dim.h);

        return (px * factor).toFixed(1);
    }

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

    // Un punto tipografico en px DEL LIENZO. Ojo: no es la conversion de
    // pantalla 96/72, porque el lienzo no esta a 96 DPI: son 700px para
    // representar 210mm (vertical) o 1133px para 340mm (horizontal), es
    // decir ~84.7 DPI. Con 96/72 el editor pintaba el texto un 13.4% mas
    // ancho que el PDF, y por eso lo que se veia pegado en el lienzo salia
    // separado al imprimir (y al reves).
    function ptToPx(pt, mode) {
        var dim = CANVAS_DIM[mode] || CANVAS_DIM.v;
        var page = PAGE_MM[mode] || PAGE_MM.v;

        return (parseFloat(pt) || 0) * (dim.w / page.w) * (25.4 / 72);
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
            fontSize: ptToPx(size, isHorizontal ? 'h' : 'v') + 'px',
            fontWeight: style.bold ? 'bold' : 'normal',
            fontStyle: style.italic ? 'italic' : 'normal',
            textAlign: style.align || 'center',
            // La caja es por orientacion (box_w_h/box_h_h en horizontal); las
            // plantillas antiguas solo tienen box_w/box_h y caen en ellos.
            width: (isHorizontal ? (style.box_w_h || style.box_w || 150) : (style.box_w || 150)) + 'px',
            height: (isHorizontal ? (style.box_h_h || style.box_h || 30) : (style.box_h || 30)) + 'px',
        });
    }

    function currentZoomFor($el) {
        var canvasId = $el.closest('.pricelabels-canvas').attr('id');

        return zoomState[canvasId === 'canvas-h' ? 'h' : 'v'] || 1;
    }

    function drawGuides(canvasId, x, y, centeredX, centeredY) {
        var $canvas = $('#' + canvasId);
        $canvas.find('.snap-guide, .snap-center-badge').remove();

        if (x !== null) {
            $('<div class="snap-guide snap-guide-v' + (centeredX ? ' is-center' : '') + '"></div>')
                .css({ left: x + 'px' }).appendTo($canvas);
        }
        if (y !== null) {
            $('<div class="snap-guide snap-guide-h' + (centeredY ? ' is-center' : '') + '"></div>')
                .css({ top: y + 'px' }).appendTo($canvas);
        }

        // Aviso tipo Photoshop: cuando el centro del campo coincide con el
        // centro de su celda de la cuadricula, no solo se ve la guia verde,
        // tambien se avisa con texto por si la guia queda tapada por el campo.
        if (centeredX || centeredY) {
            var label = (centeredX && centeredY) ? 'Centrado' : (centeredX ? 'Centrado horizontal' : 'Centrado vertical');

            $('<span class="snap-center-badge"></span>').text(label)
                .css({ left: (x !== null ? x : 10) + 6 + 'px', top: (y !== null ? y : 10) - 20 + 'px' })
                .appendTo($canvas);
        }
    }

    function applySnap($el, canvasId, x, y) {
        var width = parseFloat($el.css('width')) || 0;
        var height = parseFloat($el.css('height')) || 0;
        var centerX = x + width / 2;
        var centerY = y + height / 2;

        var snappedX = x;
        var snappedY = y;
        var guideX = null;
        var guideY = null;
        var centeredX = false;
        var centeredY = false;

        // 1) Guia de centrado (estilo Photoshop): centro del campo vs.
        // centro de la celda de cuadricula a la que pertenece su slot. Tiene
        // prioridad sobre el resto porque es la referencia mas util al
        // colocar etiquetas repetidas.
        var cell = slotCellFor($el);
        var cellCenterX = cell.x0 + cell.w / 2;
        var cellCenterY = cell.y0 + cell.h / 2;

        if (Math.abs(centerX - cellCenterX) <= SNAP_RANGE) {
            snappedX = cellCenterX - width / 2;
            guideX = cellCenterX;
            centeredX = true;
        }
        if (Math.abs(centerY - cellCenterY) <= SNAP_RANGE) {
            snappedY = cellCenterY - height / 2;
            guideY = cellCenterY;
            centeredY = true;
        }

        // 2) Alineacion de bordes con otros campos (comportamiento previo).
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

        drawGuides(canvasId, guideX, guideY, centeredX, centeredY);

        return { x: snappedX, y: snappedY };
    }

    // Etiqueta flotante tipo Photoshop con la posicion/tamano en vivo (px y
    // mm reales de la hoja impresa) mientras se arrastra o redimensiona.
    function showInfoBadge(canvasId, $el, text) {
        var $canvas = $('#' + canvasId);
        var $badge = $canvas.find('.pricelabels-info-badge');

        if (!$badge.length) {
            $badge = $('<div class="pricelabels-info-badge"></div>').appendTo($canvas);
        }

        var left = parseFloat($el.css('left')) || 0;
        var top = parseFloat($el.css('top')) || 0;

        $badge.text(text).css({ left: left + 'px', top: (top - 22) + 'px' }).addClass('is-visible');
    }

    function hideInfoBadge(canvasId) {
        $('#' + canvasId + ' .pricelabels-info-badge').removeClass('is-visible');
    }

    // Limita value al rango [min, max]; si el campo es mas ancho/alto que la
    // propia celda (min > max), lo ancla al borde inicial en vez de crashear.
    function clampRange(value, min, max) {
        if (max < min) {
            return min;
        }

        return Math.min(Math.max(value, min), max);
    }

    function rectsOverlap(a, b) {
        return a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;
    }

    function rectFor($el) {
        return {
            x: parseFloat($el.css('left')) || 0,
            y: parseFloat($el.css('top')) || 0,
            w: parseFloat($el.css('width')) || 0,
            h: parseFloat($el.css('height')) || 0,
        };
    }

    // Otros campos del MISMO slot (misma etiqueta repetida), que es donde
    // tiene sentido comprobar solapes: campos de slots distintos son
    // fisicamente otra etiqueta impresa, nunca se pisan entre si.
    function siblingsInSameSlot($el, canvasId) {
        var slot = $el.data('slot');

        return $('#' + canvasId + ' .pricelabels-drag[data-slot="' + slot + '"]').not($el);
    }

    // Al empezar a arrastrar/redimensionar, recuerda con que campos ya
    // solapaba: a esos se les deja seguir tocandose durante todo el gesto
    // (para poder separar solapes ya existentes en plantillas guardadas
    // antes de esta funcion), pero no se puede invadir a ningun campo nuevo.
    function rememberOverlapIgnoreList($el) {
        var canvasId = $el.closest('.pricelabels-canvas').attr('id');
        var rect = rectFor($el);
        var ignored = [];

        siblingsInSameSlot($el, canvasId).each(function () {
            if (rectsOverlap(rect, rectFor($(this)))) {
                ignored.push(this);
            }
        });

        $el.data('pl-overlap-ignore', ignored);
    }

    function collidesWithSiblings($el, canvasId, candidateRect) {
        var ignored = $el.data('pl-overlap-ignore') || [];
        var collides = false;

        siblingsInSameSlot($el, canvasId).each(function () {
            if (ignored.indexOf(this) !== -1) {
                return;
            }
            if (rectsOverlap(candidateRect, rectFor($(this)))) {
                collides = true;
                return false;
            }
        });

        return collides;
    }

    function dragStartListener(event) {
        rememberOverlapIgnoreList($(event.target));
    }

    function dragMoveListener(event) {
        var $el = $(event.target);
        var canvasId = $el.closest('.pricelabels-canvas').attr('id');
        var mode = canvasId === 'canvas-h' ? 'h' : 'v';
        var zoom = currentZoomFor($el);

        var prevLeft = parseFloat($el.css('left')) || 0;
        var prevTop = parseFloat($el.css('top')) || 0;
        var left = prevLeft + event.dx / zoom;
        var top = prevTop + event.dy / zoom;

        var snapped = applySnap($el, canvasId, left, top);

        // No se puede cruzar al slot vecino: el campo queda anclado dentro
        // de los limites de SU propia celda de la cuadricula, para que con
        // varias etiquetas repetidas no se termine invadiendo la imagen de
        // al lado.
        var width = parseFloat($el.css('width')) || 0;
        var height = parseFloat($el.css('height')) || 0;
        var cell = slotCellFor($el);
        var clampedX = clampRange(snapped.x, cell.x0, cell.x0 + cell.w - width);
        var clampedY = clampRange(snapped.y, cell.y0, cell.y0 + cell.h - height);

        // Tampoco se puede pisar a otro campo del mismo slot: se resuelve
        // eje por eje (como deslizar contra una pared) para poder seguir
        // moviendose en la direccion libre aunque la otra quede bloqueada.
        // Los campos con los que ya se solapaba al empezar el arrastre se
        // ignoran, para no dejar "congelado" un solape que ya traia la
        // plantilla de antes de esta funcion.
        var finalX = collidesWithSiblings($el, canvasId, { x: clampedX, y: prevTop, w: width, h: height })
            ? prevLeft
            : clampedX;
        var finalY = collidesWithSiblings($el, canvasId, { x: finalX, y: clampedY, w: width, h: height })
            ? prevTop
            : clampedY;

        $el.css({ left: finalX + 'px', top: finalY + 'px' });
        markDirty(mode);

        showInfoBadge(canvasId, $el,
            'X: ' + Math.round(finalX) + 'px (' + toMm(finalX, mode, 'x') + 'mm)  ·  ' +
            'Y: ' + Math.round(finalY) + 'px (' + toMm(finalY, mode, 'y') + 'mm)');

        if ($selected && $selected.is($el)) {
            positionFloatToolbar($el);
        }
    }

    function dragEndListener(event) {
        var canvasId = $(event.target).closest('.pricelabels-canvas').attr('id');
        $('#' + canvasId + ' .snap-guide, #' + canvasId + ' .snap-center-badge').remove();
        hideInfoBadge(canvasId);
    }

    function resizeStartListener(event) {
        rememberOverlapIgnoreList($(event.target));
    }

    // Igual que en el drag: retrocede 1px cada vez hasta dejar de solapar a
    // un campo del mismo slot con el que no se solapaba ya al empezar (esos
    // se ignoran, ver rememberOverlapIgnoreList). Los pasos de redimensionar
    // son pequenos entre eventos, asi que el bucle es corto en la practica.
    function resizeGrowLimit($el, canvasId, left, top, width, height) {
        function collides(w, h) {
            return collidesWithSiblings($el, canvasId, { x: left, y: top, w: w, h: h });
        }

        while (width > 20 && collides(width, height)) {
            width -= 1;
        }
        while (height > 12 && collides(width, height)) {
            height -= 1;
        }

        return { width: width, height: height };
    }

    function resizeMoveListener(event) {
        var $el = $(event.target);
        var key = $el.data('key');
        var canvasId = $el.closest('.pricelabels-canvas').attr('id');
        var mode = canvasId === 'canvas-h' ? 'h' : 'v';
        var zoom = currentZoomFor($el);

        var width = (parseFloat($el.css('width')) || 0) + event.deltaRect.width / zoom;
        var height = (parseFloat($el.css('height')) || 0) + event.deltaRect.height / zoom;
        var left = (parseFloat($el.css('left')) || 0) + event.deltaRect.left / zoom;
        var top = (parseFloat($el.css('top')) || 0) + event.deltaRect.top / zoom;

        // El redimensionado solo crece hacia la derecha/abajo (edges limitados
        // arriba en initInteractions): tampoco puede pasar del borde de su
        // propia celda de la cuadricula, ni montarse encima de otro campo
        // nuevo del mismo slot.
        var cell = slotCellFor($el);
        width = Math.min(width, cell.x0 + cell.w - left);
        height = Math.min(height, cell.y0 + cell.h - top);

        var limited = resizeGrowLimit($el, canvasId, left, top, width, height);
        width = limited.width;
        height = limited.height;

        // La caja es por campo Y por orientacion: se replica en los slots del
        // MISMO lienzo, nunca en el de la otra orientacion (la hoja vertical y
        // la horizontal tienen proporciones distintas y se ajustan aparte).
        $('#' + canvasId + ' [data-key="' + key + '"]').css({ width: width + 'px', height: height + 'px' });
        $el.css({ left: left + 'px', top: top + 'px' });

        $(boxInputId(key, mode, 'w')).val(Math.round(width));
        $(boxInputId(key, mode, 'h')).val(Math.round(height));
        markDirty(mode);

        showInfoBadge(canvasId, $el,
            'W: ' + Math.round(width) + 'px (' + toMm(width, mode, 'x') + 'mm)  ·  ' +
            'H: ' + Math.round(height) + 'px (' + toMm(height, mode, 'y') + 'mm)');

        if ($selected && $selected.is($el)) {
            positionFloatToolbar($el);
        }
    }

    function resizeEndListener(event) {
        var canvasId = $(event.target).closest('.pricelabels-canvas').attr('id');
        hideInfoBadge(canvasId);
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
                listeners: { start: dragStartListener, move: dragMoveListener, end: dragEndListener },
            })
            .resizable({
                // Solo la esquina inferior-derecha (coincide con el tirador
                // visual ::after). Con los 4 bordes activos y el margen de
                // deteccion por defecto de interact.js (20px), CUALQUIER
                // arrastre en un campo mas bajo/estrecho que ~40px (p.ej.
                // "Precio recomendado", 23px de alto) se interpretaba
                // siempre como redimensionar y nunca como mover.
                edges: { right: true, bottom: true },
                margin: 8,
                modifiers: [
                    interact.modifiers.restrictSize({ min: { width: 20, height: 12 } }),
                    interact.modifiers.restrictEdges({ outer: 'parent' }),
                ],
                listeners: { start: resizeStartListener, move: resizeMoveListener, end: resizeEndListener },
            })
            // tap/doubletap de interact.js solo disparan si el puntero NO se
            // movio (pointerWasMoved === false), asi que nunca compiten con
            // arrastrar/redimensionar: mover el campo sigue funcionando igual.
            .on('tap', function (event) {
                selectField($(event.target));
            })
            .on('doubletap', function (event) {
                var $el = $(event.target);
                selectField($el);
                focusFieldSettings($el.data('key'), canvasKeyFor($el));
            });
    }

    // Lineas divisorias entre las celdas de la cuadricula (una por cada
    // borde interno de fila/columna), para que con varias etiquetas
    // repetidas sea evidente donde termina una y empieza la siguiente.
    function drawGridOverlay(canvasId, mode) {
        var $canvas = $('#' + canvasId);
        $canvas.find('.pricelabels-grid-line').remove();

        var dim = CANVAS_DIM[mode];
        var rows = gridDims[mode].rows;
        var columns = gridDims[mode].columns;
        var cellW = dim.w / columns;
        var cellH = dim.h / rows;

        for (var c = 1; c < columns; c++) {
            $('<div class="pricelabels-grid-line pricelabels-grid-line-v"></div>')
                .css({ left: (c * cellW) + 'px' }).appendTo($canvas);
        }
        for (var r = 1; r < rows; r++) {
            $('<div class="pricelabels-grid-line pricelabels-grid-line-h"></div>')
                .css({ top: (r * cellH) + 'px' }).appendTo($canvas);
        }
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
                    // El "#slot" ya no hace falta para distinguir una
                    // etiqueta repetida de otra: las lineas de la cuadricula
                    // (drawGridOverlay) marcan el limite de cada celda.
                    $el.text(text);
                }

                applyStyle($el, key, isHorizontal, opts.fields);
                $canvas.append($el);
            });
        }

        drawGridOverlay(opts.canvasId, opts.mode);
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

    // Toma la posicion actual del slot 1 de cada campo (o de un unico campo,
    // via onlyKey) y la replica en el resto de slots de la cuadricula,
    // recalculando el offset segun la columna/fila de cada slot (misma
    // logica que defaultPositions() en PriceLabelTemplateService, pero
    // partiendo del ajuste manual del slot 1).
    function applyGridFromFirstSlot(canvasId, mode, rows, columns, onlyKey) {
        var dim = CANVAS_DIM[mode];
        var cellW = dim.w / columns;
        var cellH = dim.h / rows;
        var slots = rows * columns;
        var keys = onlyKey ? [onlyKey] : FIELDS;

        keys.forEach(function (key) {
            var $base = $('#' + canvasId + ' [data-key="' + key + '"][data-slot="1"]');
            if (!$base.length) {
                return;
            }

            var baseX = parseFloat($base.css('left')) || 0;
            var baseY = parseFloat($base.css('top')) || 0;

            for (var slot = 2; slot <= slots; slot++) {
                var row = Math.floor((slot - 1) / columns);
                var col = (slot - 1) % columns;

                $('#' + canvasId + ' [data-key="' + key + '"][data-slot="' + slot + '"]').css({
                    left: (baseX + col * cellW) + 'px',
                    top: (baseY + row * cellH) + 'px',
                });
            }
        });

        markDirty(canvasId === 'canvas-h' ? 'h' : 'v');

        var scopeMsg = onlyKey ? ('del campo "' + (FIELD_LABELS[onlyKey] || onlyKey) + '"') : 'de todos los campos';
        toastr.success('Posiciones ' + scopeMsg + ' recalculadas desde la etiqueta #1 en toda la cuadricula. Revisa el resultado y pulsa "Guardar posiciones".');
    }

    // ── Seleccion de campo + toolbar flotante ──────────────────────────
    // Al pulsar (tap, sin arrastrar) un campo del lienzo se muestra una
    // pequena barra de acciones rapidas justo encima, que reusa los mismos
    // inputs de la tabla de estilo lateral (una sola fuente de verdad: lo
    // que envia collectFields()/collectPositions() al guardar).

    function repositionSelectedToolbar() {
        if ($selected && $selected.length) {
            positionFloatToolbar($selected);
        }
    }

    function positionFloatToolbar($el) {
        if (!$floatTb || !$floatTb.length) {
            return;
        }

        var rect = $el[0].getBoundingClientRect();
        var tbHeight = $floatTb.outerHeight() || 36;
        var tbWidth = $floatTb.outerWidth() || 260;
        var top = rect.top - tbHeight - 8;

        if (top < 4) {
            top = rect.bottom + 8;
        }

        // El campo puede quedar pegado al borde del viewport (p.ej. tras usar
        // el boton de ajustes, que hace scroll a la tabla lateral): sujeta el
        // toolbar dentro del area visible en vez de dejarlo cortado.
        top = Math.max(4, Math.min(top, window.innerHeight - tbHeight - 4));
        var left = Math.max(4, Math.min(rect.left, window.innerWidth - tbWidth - 4));

        $floatTb.css({ top: top + 'px', left: left + 'px' });
    }

    // La fuente y el tamano son por orientacion (sufijo "-h" en horizontal),
    // el resto de props del campo (color, negrita, cursiva, align) se
    // comparten entre ambos lienzos.
    function fontFamilyInputId(key, canvasKey) {
        return '#field-' + key + '-font-family' + (canvasKey === 'h' ? '-h' : '');
    }

    function fontSizeInputId(key, canvasKey) {
        return '#field-' + key + '-font-size' + (canvasKey === 'h' ? '-h' : '');
    }

    // dim: 'w' (ancho) o 'h' (alto). En horizontal el input lleva el sufijo
    // "-h" extra: field-<key>-box-w-h / field-<key>-box-h-h.
    function boxInputId(key, canvasKey, dim) {
        return '#field-' + key + '-box-' + dim + (canvasKey === 'h' ? '-h' : '');
    }

    function refreshFloatToolbarState($el) {
        if (!$floatTb || !$floatTb.length) {
            return;
        }

        var key = $el.data('key');
        var canvasKey = canvasKeyFor($el);
        var isBold = $('#field-' + key + '-bold').is(':checked');
        var isItalic = $('#field-' + key + '-italic').is(':checked');
        var align = $('#field-' + key + '-align').val() || 'center';
        var color = $('#field-' + key + '-color').val() || '#000000';
        var $srcFamily = $(fontFamilyInputId(key, canvasKey));
        var $tbFamily = $floatTb.find('[data-action="font-family"]');

        // El select de fuente clona las mismas opciones que el de la tabla
        // lateral (incluye las fuentes personalizadas subidas en ajustes):
        // una unica fuente de verdad para la lista de fuentes disponibles.
        if ($srcFamily.length) {
            $tbFamily.html($srcFamily.html()).val($srcFamily.val());
        }
        $floatTb.find('[data-action="font-size"]').val($(fontSizeInputId(key, canvasKey)).val());

        $floatTb.find('[data-action="bold"]').toggleClass('is-on', isBold);
        $floatTb.find('[data-action="italic"]').toggleClass('is-on', isItalic);
        $floatTb.find('[data-action="front"]').toggleClass('is-on', $el.hasClass('is-pinned'));
        $floatTb.find('[data-align]').toggleClass('is-on', false)
            .filter('[data-align="' + align + '"]').toggleClass('is-on', true);
        $floatTb.find('.pricelabels-ftb-color-swatch').css('background', color);
    }

    // Centra el campo (en X, en Y, o en ambos ejes) dentro de la celda de su
    // slot: equivalente de un clic a lo que las guias de arrastre ya avisan.
    function centerFieldInCell($el, axis) {
        var cell = slotCellFor($el);
        var width = parseFloat($el.css('width')) || 0;
        var height = parseFloat($el.css('height')) || 0;

        if (axis === 'x') {
            $el.css('left', (cell.x0 + cell.w / 2 - width / 2) + 'px');
        } else {
            $el.css('top', (cell.y0 + cell.h / 2 - height / 2) + 'px');
        }

        markDirty(canvasKeyFor($el));

        if ($selected && $selected.is($el)) {
            positionFloatToolbar($el);
        }
    }

    function selectField($el) {
        $('.pricelabels-drag').removeClass('is-selected');
        $el.addClass('is-selected');
        $selected = $el;
        refreshFloatToolbarState($el);
        positionFloatToolbar($el);
        if ($floatTb) {
            $floatTb.addClass('is-visible');
        }
    }

    function deselectField() {
        if ($selected && $selected.length) {
            $selected.removeClass('is-selected');
        }
        $selected = null;
        if ($floatTb) {
            $floatTb.removeClass('is-visible');
        }
    }

    // Hace scroll hasta la fila de la tabla lateral de ese campo y enfoca el
    // ancho de caja: lo unico que la toolbar rapida no cubre (fuente, tamano,
    // negrita, cursiva, alinear y color ya se editan directo en el lienzo).
    function focusFieldSettings(key, canvasKey) {
        var $row = $('tr[data-field-key="' + key + '"]');
        if (!$row.length) {
            return;
        }

        $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $row.addClass('pricelabels-row-highlight');
        setTimeout(function () { $row.removeClass('pricelabels-row-highlight'); }, 2000);

        // El ancho de la orientacion desde la que se abrio, no siempre el vertical.
        $(boxInputId(key, canvasKey || 'v', 'w')).trigger('focus');
    }

    function bindFloatToolbar() {
        $floatTb = $('#pricelabels-float-tb');
        if (!$floatTb.length) {
            return;
        }

        $floatTb.on('click', '.pricelabels-ftb-btn', function (e) {
            e.stopPropagation();

            if (!$selected || !$selected.length) {
                return;
            }

            var action = $(this).data('action');
            var key = $selected.data('key');

            var align = $(this).data('align');

            if (action === 'bold') {
                var $bold = $('#field-' + key + '-bold');
                $bold.prop('checked', !$bold.is(':checked')).trigger('change');
            } else if (action === 'italic') {
                var $italic = $('#field-' + key + '-italic');
                $italic.prop('checked', !$italic.is(':checked')).trigger('change');
            } else if (action === 'align') {
                $('#field-' + key + '-align').val(align).trigger('change');
            } else if (action === 'color') {
                // El id de la tabla lo lleva el hex; quien abre la paleta del
                // sistema es la muestra del componente de color.
                $('#field-' + key + '-color-picker').trigger('click');
            } else if (action === 'front') {
                $('tr[data-field-key="' + key + '"]').trigger('click');
            } else if (action === 'center-x') {
                centerFieldInCell($selected, 'x');
            } else if (action === 'center-y') {
                centerFieldInCell($selected, 'y');
            } else if (action === 'grid') {
                var ctx = canvasContextFor($selected);
                applyGridFromFirstSlot(ctx.canvasId, ctx.mode, ctx.rows, ctx.columns, key);
            } else if (action === 'settings') {
                focusFieldSettings(key, canvasKeyFor($selected));
            } else if (action === 'close') {
                deselectField();
                return;
            }

            refreshFloatToolbarState($selected);
        });

        $floatTb.on('change', '[data-action="font-family"]', function () {
            if (!$selected || !$selected.length) {
                return;
            }

            var key = $selected.data('key');
            var canvasKey = canvasKeyFor($selected);
            $(fontFamilyInputId(key, canvasKey)).val($(this).val()).trigger('change');
        });

        $floatTb.on('input', '[data-action="font-size"]', function () {
            if (!$selected || !$selected.length) {
                return;
            }

            var key = $selected.data('key');
            var canvasKey = canvasKeyFor($selected);
            $(fontSizeInputId(key, canvasKey)).val($(this).val()).trigger('input');
        });

        // Clic en el fondo del lienzo (fuera de cualquier campo) deselecciona.
        $('.pricelabels-canvas').on('click', function (e) {
            if (e.target === this) {
                deselectField();
            }
        });

        // Clic en cualquier otra parte de la pagina (fuera del campo
        // seleccionado y fuera de la propia toolbar) tambien deselecciona,
        // no solo el fondo del lienzo.
        $(document).on('click', function (e) {
            if ($selected && !$(e.target).closest('.pricelabels-drag, #pricelabels-float-tb').length) {
                deselectField();
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $selected) {
                deselectField();
            }
        });

        $(window).on('scroll resize', repositionSelectedToolbar);
        $('.pricelabels-canvas-outer').on('scroll', repositionSelectedToolbar);
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
        var props = ['color', 'font-family', 'font-size', 'font-family-h', 'font-size-h', 'align', 'box-w', 'box-h', 'box-w-h', 'box-h-h'];
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
                $('#canvas-v [data-key="' + key + '"]').css('font-size', ptToPx($(this).val(), 'v') + 'px');
            });
            $('#field-' + key + '-font-size-h').on('input', function () {
                $('#canvas-h [data-key="' + key + '"]').css('font-size', ptToPx($(this).val(), 'h') + 'px');
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
            // Cada input de caja afecta solo a SU lienzo (la caja es por
            // orientacion, como la fuente y el tamano).
            $('#field-' + key + '-box-w').on('input', function () {
                $('#canvas-v [data-key="' + key + '"]').css('width', $(this).val() + 'px');
            });
            $('#field-' + key + '-box-h').on('input', function () {
                $('#canvas-v [data-key="' + key + '"]').css('height', $(this).val() + 'px');
            });
            $('#field-' + key + '-box-w-h').on('input', function () {
                $('#canvas-h [data-key="' + key + '"]').css('width', $(this).val() + 'px');
            });
            $('#field-' + key + '-box-h-h').on('input', function () {
                $('#canvas-h [data-key="' + key + '"]').css('height', $(this).val() + 'px');
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
        var rowsV = (grid.vertical && grid.vertical.rows) || 2;
        var columnsV = (grid.vertical && grid.vertical.columns) || 2;
        var rowsH = (grid.horizontal && grid.horizontal.rows) || 2;
        var columnsH = (grid.horizontal && grid.horizontal.columns) || 4;
        var slotsV = rowsV * columnsV;
        var slotsH = rowsH * columnsH;
        gridDims.v = { rows: rowsV, columns: columnsV };
        gridDims.h = { rows: rowsH, columns: columnsH };

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
        bindFloatToolbar();
        highlightNewField(config.newFieldKey);

        initZoom('zoom-v', 'zoom-v-value', 'canvas-outer-v', 'canvas-v', 'v', 'v');
        initZoom('zoom-h', 'zoom-h-value', 'canvas-outer-h', 'canvas-h', 'h', 'h');
        initZoomReset('zoom-v-reset', 'zoom-v');
        initZoomReset('zoom-h-reset', 'zoom-h');

        $('#grid-overlay-v').on('change', function () {
            $('#canvas-v').toggleClass('is-grid-hidden', !$(this).is(':checked'));
        });
        $('#grid-overlay-h').on('change', function () {
            $('#canvas-h').toggleClass('is-grid-hidden', !$(this).is(':checked'));
        });

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

        $('#apply-grid-v').on('click', function () {
            applyGridFromFirstSlot('canvas-v', 'v', rowsV, columnsV);
        });

        $('#apply-grid-h').on('click', function () {
            applyGridFromFirstSlot('canvas-h', 'h', rowsH, columnsH);
        });

        bindPreviewPdf(slotsV, slotsH);
        bindExcelPreview(slotsV, slotsH);
        bindGenerateForm();
    });
})(jQuery);

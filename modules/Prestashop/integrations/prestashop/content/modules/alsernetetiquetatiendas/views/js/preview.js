(function () {
    // Claves de elementos que dibujamos
    var KEYS = ['label', 'referencia', 'descripcion', 'pvprp', 'pvp'];

    // Tamaños base REALES (px) del lienzo:
    // Vertical: 700x990, Horizontal: 1133x720
    var CANVAS_DIM = {
        v: { w: 700, h: 990 },
        h: { w: 1133, h: 720 }
    };

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $all(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
    function int(v, d) { var n = parseInt(v, 10); return isNaN(n) ? (d || 0) : n; }

    // 1pt = 96/72 px
    function ptToPx(pt) { return (parseFloat(pt) || 0) * (96 / 72); }

    /** ------------ PREVIEW ENGINE (reutilizable para V y H) ------------ */
    function initPreview(opts) {
        var mode = opts.mode;            // 'v' | 'h'
        var canvasId = opts.canvasId;        // 'canvas'  | 'canvasH'
        var outerId = opts.outerId;         // 'canvas-outer' | 'canvasH-outer'
        var slots = opts.slots;           // 4  | 8
        var cfg = opts.cfg;             // window.ALSER_URLS  | window.ALSER_URLS_H
        var saveBtnId = opts.saveBtnId;       // 'save-positions-v' | 'save-positions-h'
        var rootExists = !!$('#' + outerId);     // si no existe el DOM, no inicializamos

        if (!rootExists) return;

        var canvas = $('#' + canvasId);
        var outer = $('#' + outerId);
        if (!canvas || !outer) return;

        var DIM = CANVAS_DIM[mode] || CANVAS_DIM.v;

        function applyCanvasSize() {
            // Tamaño REAL del lienzo (no escalamos)
            canvas.style.width = DIM.w + 'px';
            canvas.style.height = DIM.h + 'px';

            // Sin transformaciones
            canvas.style.transform = 'none';
            canvas.style.transformOrigin = 'top left';

            // El contenedor toma el mismo tamaño
            outer.style.width = DIM.w + 'px';
            outer.style.height = DIM.h + 'px';
        }

        function applyFontAndColor(el) {
            var key = el.dataset.key;

            // Color
            try {
                if (cfg.colors && cfg.colors[key]) el.style.color = cfg.colors[key];
            } catch (e) { }

            // Fuente (px convertidos desde pt)
            try {
                if (cfg.fonts && cfg.fonts[key]) {
                    var f = cfg.fonts[key];
                    if (f.family) el.style.fontFamily = f.family;
                    if (f.size) el.style.fontSize = ptToPx(f.size) + 'px';
                    el.style.fontWeight = 'bold';
                    el.style.textShadow = '0 1px 1px rgba(0,0,0,0.15)';
                }
            } catch (e) { }
        }

        function applyDragLook(el) {


            // Para que se vean un poco transparentes y manejables
            if (el.dataset.key === 'pvp' || el.dataset.key === 'pvprp' || el.dataset.key === 'descripcion') {
                // precios sin fondo
                el.style.position = 'absolute';
                el.style.cursor = 'move';
                el.style.padding = '2px 6px';
                el.style.borderRadius = '4px';
                el.style.userSelect = 'none';
                // el.style.boxShadow = '0 1px 2px rgba(0,0,0,.12)';
            } else {
                el.style.position = 'absolute';
                el.style.cursor = 'move';
                el.style.padding = '2px 6px';
                el.style.borderRadius = '4px';
                el.style.userSelect = 'none';
                el.style.boxShadow = '0 1px 2px rgba(0,0,0,.12)';
                el.style.background = 'rgba(255,255,255,0.6)';
                el.style.border = '1px dashed #aaa';
            }
        }

        // Tamaños FIJOS PEQUEÑOS de las cajas en px
        var BOX_SIZES = {
            referencia: { w: 75, h: 22 },
            descripcion: { w: 220, h: 36 },
            pvprp: { w: 110, h: 26 },
            pvp: { w: 150, h: 40 },
            label: { w: 120, h: 18 }
        };

        function applyRealSize(el) {
            var key = el.dataset.key;
            var def = BOX_SIZES[key] || { w: 150, h: 24 };

            el.style.width = def.w + 'px';
            el.style.height = def.h + 'px';

            if (key === 'descripcion') {
                el.style.textAlign = 'center';
                el.style.whiteSpace = 'normal';
                el.style.lineHeight = '1.2';
            } else {
                el.style.whiteSpace = 'nowrap';
            }
        }

        // Posiciones desde la config -> directamente en px (mismo sistema de coords que el PDF)
        function setInitialPos(el) {
            var key = el.dataset.key;
            var slot = el.dataset.slot;
            var x = 50, y = 50;
            try {
                if (cfg.pos && cfg.pos[key] && cfg.pos[key][slot]) {
                    x = int(cfg.pos[key][slot].x, 50);
                    y = int(cfg.pos[key][slot].y, 50);
                }
            } catch (e) { }
            el.style.left = x + 'px';
            el.style.top = y + 'px';
        }

        function initDrag(el) {
            function clamp(val, min, max) { return Math.max(min, Math.min(max, val)); }
            el.addEventListener('mousedown', function (e) {
                e.preventDefault();
                var startX = e.clientX, startY = e.clientY;
                var sx = int(el.style.left, 0), sy = int(el.style.top, 0);

                function onMove(ev) {
                    var dx = (ev.clientX - startX);
                    var dy = (ev.clientY - startY);

                    var nx = sx + dx;
                    var ny = sy + dy;

                    var eW = el.offsetWidth, eH = el.offsetHeight;
                    var maxX = DIM.w - eW;
                    var maxY = DIM.h - eH;

                    nx = clamp(nx, 0, Math.max(0, maxX));
                    ny = clamp(ny, 0, Math.max(0, maxY));

                    el.style.left = nx + 'px';
                    el.style.top = ny + 'px';
                }
                function onUp() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                }
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
        }

        // Recoge posiciones (px) tal cual para backend
        function collectPositions() {
            var data = {};
            for (var slot = 1; slot <= slots; slot++) {
                KEYS.forEach(function (k) {
                    var selector = (mode === 'h')
                        ? '.drag[data-key="' + k + '"][data-slot="' + slot + '"][data-orient="h"]'
                        : '.drag[data-key="' + k + '"][data-slot="' + slot + '"]:not([data-orient])';
                    var el = document.querySelector(selector);
                    var st = el ? el.style : null;
                    var x = st ? int(st.left, 0) : 0;
                    var y = st ? int(st.top, 0) : 0;

                    if (mode === 'h') {
                        // Horizontal: pos_<key>_hx<slot>_{x|y}
                        data['pos_' + k + '_hx' + slot + '_x'] = x;
                        data['pos_' + k + '_hx' + slot + '_y'] = y;

                        if (!cfg.pos[k]) cfg.pos[k] = {};
                        if (!cfg.pos[k][slot]) cfg.pos[k][slot] = {};
                        cfg.pos[k][slot].x = x;
                        cfg.pos[k][slot].y = y;
                    } else {
                        // Vertical: pos_<key>_<slot>_{x|y}
                        data['pos_' + k + '_' + slot + '_x'] = x;
                        data['pos_' + k + '_' + slot + '_y'] = y;

                        if (!cfg.pos[k]) cfg.pos[k] = {};
                        if (!cfg.pos[k][slot]) cfg.pos[k][slot] = {};
                        cfg.pos[k][slot].x = x;
                        cfg.pos[k][slot].y = y;
                    }
                });
            }
            return data;
        }

        function savePositions() {
            var url = (cfg && cfg.save) ? cfg.save : null;
            if (!url) { alert('No se encontró la URL de guardado.'); return; }

            var payload = collectPositions();
            var fd = new FormData();
            Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });

            fetch(url, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(j => {
                    if (j && j.success) {
                        var btn = $('#' + saveBtnId);
                        if (btn) {
                            btn.classList.remove('btn-default'); btn.classList.add('btn-success');
                            btn.textContent = 'Posiciones guardadas';
                            setTimeout(function () {
                                btn.classList.remove('btn-success'); btn.classList.add('btn-default');
                                btn.textContent = (mode === 'h' ? 'Guardar posiciones (horizontal)' : 'Guardar posiciones (vertical)');
                            }, 1500);
                        }
                    } else {
                        alert((j && j.error) ? j.error : 'Error guardando posiciones');
                    }
                })
                .catch(() => alert('Error de red al guardar posiciones'));
        }

        function init() {
            applyCanvasSize();

            // vertical: elementos SIN data-orient (o data-orient!="h")
            // horizontal: elementos con data-orient="h"
            var selector = (mode === 'h')
                ? '#' + canvasId + ' .drag[data-orient="h"]'
                : '#' + canvasId + ' .drag:not([data-orient])';

            $all(selector).forEach(function (el) {
                applyFontAndColor(el);
                applyDragLook(el);
                applyRealSize(el);    // <-- tamaño pequeño de cada "botón"
                setInitialPos(el);
                initDrag(el);
            });

            var btn = $('#' + saveBtnId);
            if (btn) btn.addEventListener('click', savePositions);
        }

        init();
    }

    /** ---------- GENERAR PDFs (usa ALSER_URLS.gen) ---------- */
    function initGenerate() {
        var genBtn = $('#generate');
        if (!genBtn) return;
        genBtn.addEventListener('click', function () {
            var form = $('#excel-form');
            if (!form) return;

            var fd = new FormData(form);
            var s = $('#gen-status');
            if (s) s.textContent = 'Generando...';

            var url = (window.ALSER_URLS && window.ALSER_URLS.gen) ? window.ALSER_URLS.gen : null;
            if (!url) { if (s) s.textContent = 'Falta URL de generación'; return; }

            fetch(url, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(j => {
                    if (j && j.success) {
                        var out = '';
                        if (j.pdf_vertical) out += '<a class="btn btn-success" target="_blank" href="' + j.pdf_vertical + '">Descargar PDF Vertical</a> ';
                        if (j.pdf_horizontal) out += '<a class="btn btn-info" target="_blank" href="' + j.pdf_horizontal + '">Descargar PDF Horizontal</a>';
                        if (!out) out = 'Generado pero sin archivos disponibles.';
                        if (s) s.innerHTML = out;
                    } else {
                        if (s) s.textContent = (j && j.error) ? j.error : 'Error generando PDF';
                    }
                })
                .catch(e => {
                    console.error(e);
                    if (s) s.textContent = 'Error de red';
                });
        });
    }

    /** ---------- BOOT ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        // Vertical (si existe configuración y DOM)
        if (window.ALSER_URLS && $('#canvas-outer') && $('#canvas')) {
            initPreview({
                mode: 'v',
                canvasId: 'canvas',
                outerId: 'canvas-outer',
                slots: 4,
                cfg: window.ALSER_URLS,
                saveBtnId: 'save-positions-v'
            });
        }

        // Horizontal (si existe configuración y DOM)
        if (window.ALSER_URLS_H && $('#canvasH-outer') && $('#canvasH')) {
            initPreview({
                mode: 'h',
                canvasId: 'canvasH',
                outerId: 'canvasH-outer',
                slots: 8,
                cfg: window.ALSER_URLS_H,
                saveBtnId: 'save-positions-h'
            });
        }

        initGenerate();
    });
})();

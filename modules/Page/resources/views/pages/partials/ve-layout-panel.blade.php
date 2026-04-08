<div id="ve-layout-panel" style="height:100%; display:flex; flex-direction:column; overflow:hidden;">

    {{-- Header --}}
    <div style="padding:10px 12px; border-bottom:1px solid #e9ecef; background:#f8f9fa; flex-shrink:0; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <div style="font-size:10px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.5px;">LAYOUT</div>
            <span style="font-size:12px; color:#555;">Grid Bootstrap</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-layout"
                title="Actualizar" style="font-size:11px; padding:2px 8px;">
            <i class="fa-duotone fa-solid fa-sync fa-xs"></i>
        </button>
    </div>

    {{-- Quick insert --}}
    <div style="padding:10px 12px; border-bottom:1px solid #e9ecef; flex-shrink:0;">
        <div style="font-size:10px; font-weight:600; color:#888; margin-bottom:6px; text-transform:uppercase;">Insertar estructura</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            <button type="button" class="btn btn-sm btn-outline-secondary ve-insert-layout"
                    data-cols="1" style="font-size:11px; padding:4px;">
                <i class="fa-duotone fa-solid fa-square me-1"></i>1 Col
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ve-insert-layout"
                    data-cols="2" style="font-size:11px; padding:4px;">
                <i class="fa-duotone fa-solid fa-columns me-1"></i>2 Cols
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ve-insert-layout"
                    data-cols="3" style="font-size:11px; padding:4px;">
                <i class="fa-duotone fa-solid fa-grip-lines-vertical me-1"></i>3 Cols
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ve-insert-layout"
                    data-cols="4" style="font-size:11px; padding:4px;">
                <i class="fa-duotone fa-solid fa-th me-1"></i>4 Cols
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ve-insert-layout"
                    data-type="1-2" style="font-size:11px; padding:4px;">
                <span style="font-size:10px;">1/3 · 2/3</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ve-insert-layout"
                    data-type="2-1" style="font-size:11px; padding:4px;">
                <span style="font-size:10px;">2/3 · 1/3</span>
            </button>
        </div>
    </div>

    {{-- Empty state --}}
    <div id="ve-layout-empty" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#aaa; text-align:center; padding:20px;">
        <i class="fa-duotone fa-solid fa-th" style="font-size:28px; margin-bottom:10px; opacity:.3;"></i>
        <div style="font-size:12px; line-height:1.5;">Haz clic en "Actualizar"<br>para ver la estructura de la página</div>
    </div>

    {{-- Structure tree --}}
    <div id="ve-layout-tree" style="flex:1; overflow-y:auto; display:none; padding:8px;"></div>

    <div style="padding:8px 12px; border-top:1px solid #e9ecef; background:#f8f9fa; flex-shrink:0;">
        <small style="font-size:10px; color:#999;">
            <i class="fa-duotone fa-solid fa-info-circle me-1"></i>Haz clic en un elemento para seleccionarlo en el preview
        </small>
    </div>

</div>

<script>
(function ($) {
    'use strict';

    /* ── Build the column HTML for a preset ─────────────────────────── */
    function makeColHtml(spans) {
        return spans.map(function (span) {
            return '<div class="col-md-' + span + '">\n  <p>Contenido de la columna</p>\n</div>';
        }).join('\n');
    }

    const PRESETS = {
        1:   [12],
        2:   [6, 6],
        3:   [4, 4, 4],
        4:   [3, 3, 3, 3],
        '1-2': [4, 8],
        '2-1': [8, 4],
    };

    /* ── Insert layout structure into CKEditor ───────────────────────── */
    $(document).on('click', '.ve-insert-layout', function () {
        const cols = $(this).data('cols') || $(this).data('type');
        const spans = PRESETS[cols];
        if (!spans || !window.veEditor) return;

        const html = '<section class="py-5">\n<div class="container">\n<div class="row g-4">\n' +
            makeColHtml(spans) + '\n</div>\n</div>\n</section>';

        window.veEditor.model.change(function () {
            const view  = window.veEditor.data.processor.toView(html);
            const model = window.veEditor.data.toModel(view);
            window.veEditor.model.insertContent(model);
        });

        if (window.vePushHistory) window.vePushHistory('Insertar layout ' + cols + ' col(s)', window.veEditor.getData());

        // Visual feedback
        const $btn = $(this);
        $btn.removeClass('btn-outline-secondary').addClass('btn-success');
        setTimeout(function () {
            $btn.removeClass('btn-success').addClass('btn-outline-secondary');
        }, 1000);
    });

    /* ── Scan iframe for Bootstrap grid structure ────────────────────── */
    function scanLayout() {
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({ type: 've-scan-layout' }, '*');
    }

    /* ── Handle scan response ────────────────────────────────────────── */
    window.veHandleLayoutScan = function (data) {
        const $tree  = $('#ve-layout-tree');
        const $empty = $('#ve-layout-empty');

        if (!data || !data.length) {
            $tree.hide(); $empty.show(); return;
        }
        $empty.hide();
        $tree.empty().show();

        data.forEach(function (container) {
            const $cont = buildContainerNode(container);
            $tree.append($cont);
        });
    };

    function buildContainerNode(container) {
        const $wrap = $('<div>').css({ marginBottom: '8px' });

        const $header = $('<div>').css({
            display: 'flex', alignItems: 'center', gap: '6px',
            padding: '5px 8px', background: '#f5f5f5',
            borderRadius: '5px', cursor: 'pointer',
            border: '1px solid #dee2e6', marginBottom: '4px',
        })
        .html(
            '<i class="fa-duotone fa-solid fa-square" style="color:#555;font-size:10px;"></i>' +
            '<span style="font-size:11px;font-weight:600;color:#333;flex:1;">' + escHtml(container.tag) + '</span>' +
            '<span class="badge" style="background:#555;font-size:9px;">' + (container.rows || 0) + ' filas</span>'
        )
        .on('click', function () {
            if (container.nodeId) {
                const frame = document.getElementById('ve-preview-frame');
                if (frame && frame.contentWindow) {
                    frame.contentWindow.postMessage({ type: 've-select-by-id', nodeId: container.nodeId }, '*');
                }
            }
        });

        $wrap.append($header);

        if (container.rows && container.rows.length) {
            container.rows.forEach(function (row, ri) {
                const $row = $('<div>').css({ marginLeft: '12px', marginBottom: '4px' });

                const $rowHead = $('<div>').css({
                    display: 'flex', alignItems: 'center', gap: '5px',
                    padding: '4px 8px', background: '#f0f0f0',
                    borderRadius: '4px', cursor: 'pointer',
                    border: '1px solid #dee2e6', marginBottom: '3px',
                })
                .html(
                    '<i class="fa-duotone fa-solid fa-grip-lines" style="color:#666;font-size:10px;"></i>' +
                    '<span style="font-size:11px;color:#333;flex:1;">Fila ' + (ri + 1) + '</span>' +
                    '<span class="badge" style="background:#666;font-size:9px;">' + (row.cols ? row.cols.length : 0) + ' cols</span>'
                )
                .on('click', function () {
                    if (row.nodeId) {
                        const frame = document.getElementById('ve-preview-frame');
                        if (frame && frame.contentWindow) {
                            frame.contentWindow.postMessage({ type: 've-select-by-id', nodeId: row.nodeId }, '*');
                        }
                    }
                });

                $row.append($rowHead);

                if (row.cols && row.cols.length) {
                    const $cols = $('<div>').css({
                        display: 'flex', gap: '3px', marginLeft: '12px', flexWrap: 'wrap',
                    });
                    row.cols.forEach(function (col) {
                        const $col = $('<div>').css({
                            flex: col.span || 1, minWidth: '24px',
                            padding: '3px 6px', background: '#eee',
                            border: '1px solid #dee2e6', borderRadius: '4px',
                            fontSize: '10px', textAlign: 'center', cursor: 'pointer',
                            color: '#555',
                        })
                        .text('col-' + (col.span || '?'))
                        .on('click', function () {
                            if (col.nodeId) {
                                const frame = document.getElementById('ve-preview-frame');
                                if (frame && frame.contentWindow) {
                                    frame.contentWindow.postMessage({ type: 've-select-by-id', nodeId: col.nodeId }, '*');
                                }
                            }
                        });
                        $cols.append($col);
                    });
                    $row.append($cols);
                }

                $wrap.append($row);
            });
        }

        return $wrap;
    }

    function escHtml(str) { return $('<span>').text(str).html(); }

    /* ── Refresh button ─────────────────────────────────────────────── */
    $('#btn-refresh-layout').on('click', scanLayout);

    window.veRefreshLayout = scanLayout;

})(jQuery);
</script>

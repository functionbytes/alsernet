<div id="ve-layout-panel" class="ve-panel-root">

    {{-- Header --}}
    <div class="ve-lp-header">
        <div>
            <div class="ve-lp-title">LAYOUT</div>
            <div class="ve-lp-subtitle">Grid Bootstrap</div>
        </div>
        <button type="button" class="ve-lp-refresh" id="btn-refresh-layout" title="Actualizar estructura">
            <i class="fas fa-rotate-right"></i>
        </button>
    </div>

    {{-- Quick insert --}}
    <div class="ve-lp-section">
        <div class="ve-lp-section-label">Insertar estructura</div>
        <div class="ve-lp-presets">
            <button type="button" class="ve-lp-preset ve-insert-layout" data-cols="1" title="1 columna">
                <span class="ve-lp-preset-vis">
                    <span class="ve-lp-col ve-lp-col--f12"></span>
                </span>
                <span class="ve-lp-preset-label">1 Col</span>
            </button>
            <button type="button" class="ve-lp-preset ve-insert-layout" data-cols="2" title="2 columnas iguales">
                <span class="ve-lp-preset-vis">
                    <span class="ve-lp-col ve-lp-col--f6"></span>
                    <span class="ve-lp-col ve-lp-col--f6"></span>
                </span>
                <span class="ve-lp-preset-label">2 Cols</span>
            </button>
            <button type="button" class="ve-lp-preset ve-insert-layout" data-cols="3" title="3 columnas iguales">
                <span class="ve-lp-preset-vis">
                    <span class="ve-lp-col ve-lp-col--f4"></span>
                    <span class="ve-lp-col ve-lp-col--f4"></span>
                    <span class="ve-lp-col ve-lp-col--f4"></span>
                </span>
                <span class="ve-lp-preset-label">3 Cols</span>
            </button>
            <button type="button" class="ve-lp-preset ve-insert-layout" data-cols="4" title="4 columnas iguales">
                <span class="ve-lp-preset-vis">
                    <span class="ve-lp-col ve-lp-col--f3"></span>
                    <span class="ve-lp-col ve-lp-col--f3"></span>
                    <span class="ve-lp-col ve-lp-col--f3"></span>
                    <span class="ve-lp-col ve-lp-col--f3"></span>
                </span>
                <span class="ve-lp-preset-label">4 Cols</span>
            </button>
            <button type="button" class="ve-lp-preset ve-insert-layout" data-type="1-2" title="1/3 · 2/3">
                <span class="ve-lp-preset-vis">
                    <span class="ve-lp-col ve-lp-col--f4"></span>
                    <span class="ve-lp-col ve-lp-col--wide ve-lp-col--f8"></span>
                </span>
                <span class="ve-lp-preset-label">1/3 · 2/3</span>
            </button>
            <button type="button" class="ve-lp-preset ve-insert-layout" data-type="2-1" title="2/3 · 1/3">
                <span class="ve-lp-preset-vis">
                    <span class="ve-lp-col ve-lp-col--wide ve-lp-col--f8"></span>
                    <span class="ve-lp-col ve-lp-col--f4"></span>
                </span>
                <span class="ve-lp-preset-label">2/3 · 1/3</span>
            </button>
        </div>
    </div>

    {{-- Empty state --}}
    <div id="ve-layout-empty" class="ve-lp-empty">
        <i class="fas fa-table-cells-large ve-lp-empty-icon"></i>
        <div class="ve-lp-empty-text">Haz clic en "Actualizar"<br>para ver la estructura de la página</div>
    </div>

    {{-- Structure tree --}}
    <div id="ve-layout-tree" class="ve-scrollable-area ve-hidden"></div>

    {{-- Footer hint --}}
    <div class="ve-lp-footer">
        <i class="fas fa-circle-info me-1"></i>Haz clic en un elemento para seleccionarlo
    </div>

</div>

<style>
/* ── Layout panel ──────────────────────────────────────────────── */
.ve-lp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border-bottom: 1px solid var(--ve-border);
    flex-shrink: 0;
    background: var(--ve-bg);
}
.ve-lp-title {
    font-size: 10px;
    font-weight: 600;
    color: var(--ve-text-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    line-height: 1;
    margin-bottom: 2px;
}
.ve-lp-subtitle {
    font-size: 13px;
    font-weight: 700;
    color: var(--ve-text);
}
.ve-lp-refresh {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    color: #737c7f;
    font-size: 12px;
    cursor: pointer;
    transition: all .15s;
    flex-shrink: 0;
}
.ve-lp-refresh:hover {
    background: #f4f6f8;
    border-color: #dee2e6;
    color: #1a1c1e;
}

/* ── Section label ─────────────────────────────────────────────── */
.ve-lp-section {
    padding: 10px 14px;
    border-bottom: 1px solid #e9ecef;
    flex-shrink: 0;
}
.ve-lp-section-label {
    font-size: 9px;
    font-weight: 700;
    color: #abb3b7;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 8px;
}

/* ── Presets grid ──────────────────────────────────────────────── */
.ve-lp-presets {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}
.ve-lp-preset {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 4px;
    padding: 7px 8px 6px;
    background: #f4f6f8;
    border: 1px solid #eaeff1;
    border-radius: 7px;
    cursor: pointer;
    transition: all .15s;
    text-align: center;
}
.ve-lp-preset:hover {
    background: #eaeff1;
    border-color: #dee2e6;
}
.ve-lp-preset.ve-insert-active {
    border-color: #b10100;
    background: #fff0f0;
}
.ve-lp-preset-vis {
    display: flex;
    gap: 2px;
    height: 18px;
    border-radius: 3px;
    overflow: hidden;
}
.ve-lp-col {
    background: #dee2e6;
    border-radius: 2px;
    transition: background .15s;
}
.ve-lp-col--wide {
    background: #c5cdd2;
}
/* Flex presets for column width visualisation */
.ve-lp-col--f3  { flex: 3; }
.ve-lp-col--f4  { flex: 4; }
.ve-lp-col--f6  { flex: 6; }
.ve-lp-col--f8  { flex: 8; }
.ve-lp-col--f12 { flex: 12; }
/* Chevron icon inside layout tree nodes */
.ve-lp-chevron-icon { font-size: 8px; transition: transform .15s; }
.ve-lp-preset:hover .ve-lp-col  { background: #c5cdd2; }
.ve-lp-preset:hover .ve-lp-col--wide { background: #adb5bd; }
.ve-lp-preset-label {
    font-size: 10px;
    font-weight: 500;
    color: #586064;
    white-space: nowrap;
}

/* ── Empty state ───────────────────────────────────────────────── */
.ve-lp-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 24px 20px;
    color: #abb3b7;
}
.ve-lp-empty-icon {
    font-size: 30px;
    margin-bottom: 12px;
    opacity: .4;
}
.ve-lp-empty-text {
    font-size: 12px;
    line-height: 1.6;
    color: #abb3b7;
}

/* ── Tree nodes ────────────────────────────────────────────────── */
.ve-lp-container {
    margin-bottom: 6px;
    border: 1px solid var(--ve-border);
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}
.ve-lp-node {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    background: #f4f6f8;
    border: none;
    border-left: 3px solid #b10100;
    cursor: pointer;
    transition: all .12s;
}
.ve-lp-node:hover {
    background: #eef0f2;
}
.ve-lp-node-icon {
    font-size: 10px;
    color: var(--ve-text-muted);
    flex-shrink: 0;
}
.ve-lp-node-tag {
    font-size: 11px;
    font-weight: 700;
    color: var(--ve-text);
    font-family: 'SF Mono', 'Fira Code', monospace;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ve-lp-badge {
    font-size: 9px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    flex-shrink: 0;
    line-height: 1.4;
}
.ve-lp-badge--rows {
    background: var(--ve-bg-muted);
    color: var(--ve-text-soft);
}
.ve-lp-badge--cols {
    background: var(--ve-bg-muted);
    color: var(--ve-text-muted);
}
/* Container inner content */
.ve-lp-container-body {
    padding: 4px 6px 6px;
}

/* Row node */
.ve-lp-row {
    margin-left: 6px;
    margin-bottom: 3px;
    border-left: 2px solid var(--ve-border);
    padding-left: 6px;
}
.ve-lp-row-node {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    background: var(--ve-bg-subtle);
    border: 1px solid var(--ve-border);
    border-radius: 5px;
    cursor: pointer;
    transition: all .12s;
    margin-bottom: 3px;
}
.ve-lp-row-node:hover {
    background: #f0f0f0;
    border-color: #ddd;
}
.ve-lp-row-label {
    font-size: 10px;
    font-weight: 600;
    color: #666;
    flex: 1;
}

/* Column chips */
.ve-lp-cols-row {
    display: flex;
    gap: 3px;
    margin-left: 8px;
    flex-wrap: wrap;
    margin-bottom: 2px;
    padding-left: 6px;
}
.ve-lp-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 3px 8px;
    background: var(--ve-bg-muted);
    border: 1px solid var(--ve-border);
    border-radius: 4px;
    font-size: 9px;
    font-weight: 600;
    font-family: 'SF Mono', 'Fira Code', monospace;
    color: #666;
    cursor: pointer;
    transition: all .12s;
    white-space: nowrap;
}
.ve-lp-chip:hover {
    background: #b10100;
    border-color: #b10100;
    color: #fff;
}

/* ── Footer ────────────────────────────────────────────────────── */
#ve-layout-tree { padding: 10px; }

.ve-lp-footer {
    padding: 7px 14px;
    border-top: 1px solid #e9ecef;
    background: #f4f6f8;
    flex-shrink: 0;
    font-size: 10px;
    color: #abb3b7;
}
</style>

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
        $btn.addClass('ve-insert-active');
        setTimeout(function () { $btn.removeClass('ve-insert-active'); }, 800);
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
            $tree.addClass('ve-hidden'); $empty.removeClass('ve-hidden'); return;
        }
        $empty.addClass('ve-hidden');
        $tree.empty().removeClass('ve-hidden');

        data.forEach(function (container) {
            $tree.append(buildContainerNode(container));
        });
    };

    function selectNode(nodeId) {
        const frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow && nodeId) {
            frame.contentWindow.postMessage({ type: 've-select-by-id', nodeId: nodeId }, '*');
        }
    }

    function buildContainerNode(container) {
        const rowCount = container.rows ? container.rows.length : 0;

        const $wrap = $('<div class="ve-lp-container">');

        const $chevron = rowCount > 0
            ? '<i class="fas fa-chevron-down ve-lp-node-icon ve-lp-chevron-icon"></i>'
            : '<i class="fas fa-square ve-lp-node-icon"></i>';

        const $header = $('<div class="ve-lp-node">')
            .html(
                $chevron +
                '<span class="ve-lp-node-tag">' + escHtml(container.tag) + '</span>' +
                '<span class="ve-lp-badge ve-lp-badge--rows">' + rowCount + ' fila' + (rowCount !== 1 ? 's' : '') + '</span>'
            );

        // Click on tag selects in preview, click on chevron toggles
        $header.on('click', function (e) {
            if (rowCount > 0 && $(e.target).hasClass('fa-chevron-down') || $(e.target).hasClass('fa-chevron-up')) {
                var $body = $wrap.find('.ve-lp-container-body');
                var $ico = $header.find('.fa-chevron-down, .fa-chevron-up');
                $body.slideToggle(150);
                $ico.toggleClass('fa-chevron-down fa-chevron-up');
            } else {
                selectNode(container.nodeId);
            }
        });

        $wrap.append($header);

        if (container.rows && container.rows.length) {
            const $body = $('<div class="ve-lp-container-body">');

            container.rows.forEach(function (row, ri) {
                const colCount = row.cols ? row.cols.length : 0;
                const $rowWrap = $('<div class="ve-lp-row">');

                const $rowHead = $('<div class="ve-lp-row-node">')
                    .html(
                        '<i class="fas fa-grip-lines ve-lp-node-icon"></i>' +
                        '<span class="ve-lp-row-label">Fila ' + (ri + 1) + '</span>' +
                        '<span class="ve-lp-badge ve-lp-badge--cols">' + colCount + ' col' + (colCount !== 1 ? 's' : '') + '</span>'
                    )
                    .on('click', function () { selectNode(row.nodeId); });

                $rowWrap.append($rowHead);

                if (row.cols && row.cols.length) {
                    const $chipsRow = $('<div class="ve-lp-cols-row">');
                    row.cols.forEach(function (col) {
                        $('<span class="ve-lp-chip">')
                            .text('col-' + (col.span || '?'))
                            .on('click', function () { selectNode(col.nodeId); })
                            .appendTo($chipsRow);
                    });
                    $rowWrap.append($chipsRow);
                }

                $body.append($rowWrap);
            });

            $wrap.append($body);
        }

        return $wrap;
    }

    function escHtml(str) { return $('<span>').text(str).html(); }

    /* ── Refresh button ─────────────────────────────────────────────── */
    $('#btn-refresh-layout').on('click', scanLayout);

    window.veRefreshLayout = scanLayout;

})(jQuery);
</script>

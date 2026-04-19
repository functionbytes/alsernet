<div id="ve-history-panel-wrap" class="ve-panel-root">

    <div class="ve-panel-header">
        <div>
            <div class="ve-panel-label">Versiones</div>
            <span class="ve-panel-title">Historial</span>
        </div>
        <div class="ve-panel-actions">
            <span class="ve-hist-counter" id="ve-hist-counter">0 / 60</span>
            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="btn-save-snapshot" title="Crear snapshot">
                <i class="fa-solid fa-camera"></i>
            </button>
        </div>
    </div>

    <div id="ve-history-empty" class="ve-empty-state ve-hidden">
        <div class="ve-es-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="ve-es-title">Sin historial todavía</div>
        <div class="ve-es-desc">Los cambios que hagas aparecerán aquí. Haz clic para deshacer.</div>
    </div>

    <div id="ve-history-list" class="ve-scrollable-area"></div>

    {{-- Snapshots section --}}
    <div class="ve-snapshot-section">
        <div class="ve-snapshot-header">
            <span class="ve-panel-label">
                <i class="fa-solid fa-camera me-1"></i>Snapshots
            </span>
            <button type="button" class="btn btn-outline-danger ve-snapshot-clear" id="btn-clear-snapshots">Limpiar</button>
        </div>
        <div id="ve-snapshots-list" class="ve-snapshot-list">
            <div id="ve-snapshots-empty" class="ve-snapshot-empty">
                Sin snapshots guardados
            </div>
        </div>
    </div>

    {{-- Server versions section --}}
    <div class="ve-snapshot-section" id="ve-server-versions-section">
        <div class="ve-snapshot-header">
            <span class="ve-panel-label">
                <i class="fa-solid fa-code-branch me-1"></i>Versiones
            </span>
            <button type="button" class="btn btn-outline-secondary ve-snapshot-clear" id="btn-load-versions" title="Cargar versiones">
                <i class="fa-solid fa-rotate-right"></i>
            </button>
        </div>
        <div id="ve-versions-list" class="ve-snapshot-list">
            <div id="ve-versions-empty" class="ve-snapshot-empty">
                Haz clic en <i class="fa-solid fa-rotate-right"></i> para cargar
            </div>
        </div>
    </div>

    <div class="ve-panel-footer">
        <small><i class="fa-solid fa-info-circle me-1"></i>Haz clic en cualquier entrada para restaurar ese estado</small>
    </div>

</div>

<style>
/* History panel classes (no inline styles) */
.ve-panel-actions { display:flex; gap:4px; }
.ve-hidden { display:none !important; }
.ve-empty-text { font-size:12px; line-height:1.5; }
.ve-scrollable-area { flex:1; overflow-y:auto; }
.ve-snapshot-section { border-top:2px solid var(--ve-border, #eee); flex-shrink:0; }
.ve-snapshot-header { padding:6px 12px; display:flex; align-items:center; justify-content:space-between; }
.ve-snapshot-clear { font-size:9px !important; padding:1px 6px !important; }
.ve-snapshot-list { max-height:160px; overflow-y:auto; }
.ve-snapshot-empty { text-align:center; color:var(--ve-text-muted); font-size:11px; padding:12px; }
/* Snapshot items (rendered via JS) */
.ve-snapshot-item { display:flex; align-items:center; gap:6px; padding:5px 10px; border-bottom:1px solid var(--ve-border); font-size:11px; }
.ve-snapshot-item-icon { font-size:10px; }
.ve-snapshot-item-date { flex:1; color:var(--ve-text-soft); }
.ve-snapshot-item-btn { font-size:10px; padding:1px 6px; }
/* Version items */
.ve-version-item { display:flex; align-items:center; gap:6px; padding:5px 10px; border-bottom:1px solid var(--ve-border); font-size:11px; }
.ve-version-badge { background:#e8f4fd; color:#1a6fa0; border-radius:3px; padding:0 5px; font-size:10px; font-weight:600; flex-shrink:0; }
.ve-version-info { flex:1; min-width:0; }
.ve-version-title { color:var(--ve-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:100px; }
.ve-version-meta { color:var(--ve-text-muted); font-size:10px; }
</style>

<script>
(function ($) {
    'use strict';

    // Wire the history-panel undo/redo buttons to the global functions
    $('#btn-undo-hist').on('click', function () { $('#btn-undo').trigger('click'); });
    $('#btn-redo-hist').on('click', function () { $('#btn-redo').trigger('click'); });

    /* ── Snapshots (Feature 2) ───────────────────────────────────────── */
    var PAGE_ID = '{{ $page->id }}';
    var SNAP_KEY = 've-snapshots-' + PAGE_ID;

    function getSnapshots() {
        try {
            return JSON.parse(localStorage.getItem(SNAP_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function saveSnapshots(snaps) {
        localStorage.setItem(SNAP_KEY, JSON.stringify(snaps));
    }

    function renderSnapshots() {
        var snaps = getSnapshots();
        var $list  = $('#ve-snapshots-list');
        var $empty = $('#ve-snapshots-empty');
        if (!snaps.length) {
            $empty.show();
            $list.find('.ve-snapshot-item').remove();
            return;
        }
        $empty.hide();
        $list.find('.ve-snapshot-item').remove();
        snaps.slice().reverse().forEach(function (snap, i) {
            var realIdx = snaps.length - 1 - i;
            var date = new Date(snap.timestamp).toLocaleString('es', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' });
            var $item = $([
                '<div class="ve-snapshot-item">',
                '<i class="fa-solid fa-camera text-muted ve-snapshot-item-icon"></i>',
                '<span class="ve-snapshot-item-date" title="' + (snap.label || 'Snapshot') + '">' + date + '</span>',
                '<button class="btn btn-sm btn-outline-secondary ve-snap-restore ve-snapshot-item-btn" data-idx="' + realIdx + '">Restaurar</button>',
                '<button class="btn btn-sm btn-outline-danger ve-snap-delete ve-snapshot-item-btn" data-idx="' + realIdx + '"><i class="fa-solid fa-times"></i></button>',
                '</div>',
            ].join(''));
            $list.append($item);
        });
    }

    $('#btn-save-snapshot').on('click', function () {
        var p = window.parent || window;
        var html = '';
        if (p.veEditor) {
            html = p.veEditor.getData();
        }
        var label = prompt('Nombre del snapshot (opcional):', '');
        if (label === null) return; // cancelled
        var snaps = getSnapshots();
        snaps.push({
            timestamp: Date.now(),
            label:     label || 'Snapshot',
            html:      html
        });
        // Keep max 20 snapshots
        if (snaps.length > 20) snaps = snaps.slice(-20);
        saveSnapshots(snaps);
        renderSnapshots();
        if (p.showToast) p.showToast('<i class="fa-solid fa-camera me-1"></i>Snapshot guardado');
    });

    $(document).on('click', '.ve-snap-restore', function () {
        var idx   = parseInt($(this).data('idx'), 10);
        var snaps = getSnapshots();
        var snap  = snaps[idx];
        if (!snap) return;
        var p = window.parent || window;
        if (!confirm('¿Restaurar este snapshot?')) return;
        if (p.veEditor) {
            p.veEditor.setData(snap.html || '');
        }
        if (p.vePushHistory) p.vePushHistory('Snapshot restaurado', snap.html || '');
        if (p.showToast) p.showToast('<i class="fa-solid fa-camera me-1"></i>Snapshot restaurado');
    });

    $(document).on('click', '.ve-snap-delete', function () {
        var idx   = parseInt($(this).data('idx'), 10);
        var snaps = getSnapshots();
        snaps.splice(idx, 1);
        saveSnapshots(snaps);
        renderSnapshots();
    });

    $('#btn-clear-snapshots').on('click', function () {
        if (!confirm('¿Eliminar todos los snapshots?')) return;
        localStorage.removeItem(SNAP_KEY);
        renderSnapshots();
    });

    // Initial render
    renderSnapshots();

    /* ── Server versions (Feature 3) ───────────────────────────────── */
    var p = window.parent || window;
    var versionsLoaded = false;

    function renderVersions(versions) {
        var $list  = $('#ve-versions-list');
        var $empty = $('#ve-versions-empty');
        $list.find('.ve-version-item').remove();

        if (!versions.length) {
            $empty.text('Sin versiones guardadas').show();
            return;
        }

        $empty.hide();
        versions.forEach(function (v) {
            var $item = $([
                '<div class="ve-version-item">',
                '<span class="ve-version-badge">v' + v.version_number + '</span>',
                '<div class="ve-version-info">',
                '<div class="ve-version-title" title="' + $('<div>').text(v.title || '').html() + '">' + $('<div>').text(v.title || 'Sin título').html() + '</div>',
                '<div class="ve-version-meta">' + v.created_at_human + ' · ' + $('<div>').text(v.user).html() + '</div>',
                '</div>',
                '<button class="btn btn-sm btn-outline-secondary ve-version-load ve-snapshot-item-btn" data-id="' + v.id + '">Cargar</button>',
                '</div>',
            ].join(''));
            $list.append($item);
        });
    }

    $('#btn-load-versions').on('click', function () {
        var $btn = $(this);
        var versionsUrl = p.EDITOR_VERSIONS_URL;
        if (!versionsUrl) return;

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
        $('#ve-versions-empty').text('Cargando...').show();

        $.get(versionsUrl)
            .done(function (res) {
                if (res.success) {
                    renderVersions(res.data);
                    versionsLoaded = true;
                }
            })
            .fail(function () {
                $('#ve-versions-empty').text('Error al cargar versiones').show();
            })
            .always(function () {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-rotate-right"></i>');
            });
    });

    $(document).on('click', '.ve-version-load', function () {
        var versionId = $(this).data('id');
        var versionUrl = p.EDITOR_VERSION_URL;
        if (!versionUrl) return;

        var $btn = $(this).prop('disabled', true).text('...');

        $.get(versionUrl + '/' + versionId)
            .done(function (res) {
                if (!res.success || !res.data) return;
                if (!confirm('¿Cargar la versión ' + res.data.version_number + ' en el editor? Los cambios no guardados quedarán en el historial.')) return;
                if (p.veEditor) {
                    var html = res.data.content || '';
                    if (p.vePushHistory) p.vePushHistory('Antes de cargar v' + res.data.version_number, p.veEditor.getData());
                    p.veEditor.setData(html);
                    if (p.showToast) p.showToast('<i class="fa-solid fa-code-branch me-1"></i>Versión ' + res.data.version_number + ' cargada');
                }
            })
            .fail(function () {
                if (p.showToast) p.showToast('<i class="fa-solid fa-triangle-exclamation me-1"></i>Error al cargar la versión', 'error');
            })
            .always(function () {
                $btn.prop('disabled', false).text('Cargar');
            });
    });

})(jQuery);
</script>

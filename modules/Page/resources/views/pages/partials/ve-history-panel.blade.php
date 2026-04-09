<div id="ve-history-panel-wrap" class="ve-panel-root">

    <div class="ve-panel-header">
        <div>
            <div class="ve-panel-label">Historial</div>
            <span class="ve-panel-title">Cambios recientes</span>
        </div>
        <div class="ve-panel-actions">
            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="btn-undo-hist" title="Deshacer">
                <i class="fa-duotone fa-solid fa-undo"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="btn-redo-hist" title="Rehacer">
                <i class="fa-duotone fa-solid fa-redo"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="btn-save-snapshot" title="Guardar snapshot">
                <i class="fa-duotone fa-solid fa-camera"></i>
            </button>
        </div>
    </div>

    <div id="ve-history-empty" class="ve-empty-state ve-hidden">
        <i class="fa-duotone fa-solid fa-history ve-empty-icon"></i>
        <div class="ve-empty-text">El historial aparecerá<br>cuando hagas cambios</div>
    </div>

    <div id="ve-history-list" class="ve-scrollable-area"></div>

    {{-- Snapshots section --}}
    <div class="ve-snapshot-section">
        <div class="ve-snapshot-header">
            <span class="ve-panel-label">
                <i class="fa-duotone fa-solid fa-camera me-1"></i>Snapshots
            </span>
            <button type="button" class="btn btn-outline-danger ve-snapshot-clear" id="btn-clear-snapshots">Limpiar</button>
        </div>
        <div id="ve-snapshots-list" class="ve-snapshot-list">
            <div id="ve-snapshots-empty" class="ve-snapshot-empty">
                Sin snapshots guardados
            </div>
        </div>
    </div>

    <div class="ve-panel-footer">
        <small><i class="fa-duotone fa-solid fa-info-circle me-1"></i>Haz clic en cualquier entrada para restaurar ese estado</small>
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
.ve-snapshot-empty { text-align:center; color:#aaa; font-size:11px; padding:12px; }
/* Snapshot items (rendered via JS) */
.ve-snapshot-item { display:flex; align-items:center; gap:6px; padding:5px 10px; border-bottom:1px solid #f0f0f0; font-size:11px; }
.ve-snapshot-item-icon { font-size:10px; }
.ve-snapshot-item-date { flex:1; color:#555; }
.ve-snapshot-item-btn { font-size:10px; padding:1px 6px; }
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
                '<i class="fa-duotone fa-solid fa-camera text-muted ve-snapshot-item-icon"></i>',
                '<span class="ve-snapshot-item-date" title="' + (snap.label || 'Snapshot') + '">' + date + '</span>',
                '<button class="btn btn-xs btn-outline-secondary ve-snap-restore ve-snapshot-item-btn" data-idx="' + realIdx + '">Restaurar</button>',
                '<button class="btn btn-xs btn-outline-danger ve-snap-delete ve-snapshot-item-btn" data-idx="' + realIdx + '"><i class="fa-duotone fa-solid fa-times"></i></button>',
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
        if (p.showToast) p.showToast('<i class="fa-duotone fa-solid fa-camera me-1"></i>Snapshot guardado');
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
        if (p.showToast) p.showToast('<i class="fa-duotone fa-solid fa-camera me-1"></i>Snapshot restaurado');
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

})(jQuery);
</script>

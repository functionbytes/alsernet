<div id="ve-history-panel-wrap" style="height:100%; display:flex; flex-direction:column; overflow:hidden;">

    <div style="padding:10px 12px; border-bottom:1px solid #e9ecef; background:#f8f9fa; flex-shrink:0; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <div style="font-size:10px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.5px;">HISTORIAL</div>
            <span style="font-size:12px; color:#555;">Cambios recientes</span>
        </div>
        <div style="display:flex; gap:4px;">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-undo-hist"
                    title="Deshacer" style="font-size:11px; padding:2px 8px;">
                <i class="fa-duotone fa-solid fa-undo"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-redo-hist"
                    title="Rehacer" style="font-size:11px; padding:2px 8px;">
                <i class="fa-duotone fa-solid fa-redo"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-save-snapshot"
                    title="Guardar snapshot" style="font-size:11px; padding:2px 8px;">
                <i class="fa-duotone fa-solid fa-camera"></i>
            </button>
        </div>
    </div>

    <div id="ve-history-empty" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#aaa; text-align:center; padding:24px; display:none;">
        <i class="fa-duotone fa-solid fa-history" style="font-size:28px; margin-bottom:10px; opacity:.3;"></i>
        <div style="font-size:12px; line-height:1.5;">El historial aparecerá<br>cuando hagas cambios</div>
    </div>

    <div id="ve-history-list" style="flex:1; overflow-y:auto;"></div>

    {{-- Snapshots section --}}
    <div style="border-top:2px solid #e9ecef; flex-shrink:0;">
        <div style="padding:6px 12px; background:#f8f9fa; display:flex; align-items:center; justify-content:space-between;">
            <span style="font-size:10px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.5px;">
                <i class="fa-duotone fa-solid fa-camera me-1"></i>SNAPSHOTS
            </span>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-snapshots"
                    style="font-size:10px; padding:1px 6px;">Limpiar</button>
        </div>
        <div id="ve-snapshots-list" style="max-height:160px; overflow-y:auto;">
            <div id="ve-snapshots-empty" style="text-align:center; color:#aaa; font-size:11px; padding:12px;">
                Sin snapshots guardados
            </div>
        </div>
    </div>

    <div style="padding:8px 12px; border-top:1px solid #e9ecef; background:#f8f9fa; flex-shrink:0;">
        <small style="font-size:10px; color:#999;">
            <i class="fa-duotone fa-solid fa-info-circle me-1"></i>Haz clic en cualquier entrada para restaurar ese estado
        </small>
    </div>

</div>

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
                '<div class="ve-snapshot-item" style="display:flex;align-items:center;gap:6px;padding:5px 10px;border-bottom:1px solid #f0f0f0;font-size:11px;">',
                '<i class="fa-duotone fa-solid fa-camera text-muted" style="font-size:10px;"></i>',
                '<span style="flex:1;color:#555;" title="' + (snap.label || 'Snapshot') + '">' + date + '</span>',
                '<button class="btn btn-xs btn-outline-secondary ve-snap-restore" data-idx="' + realIdx + '"',
                ' style="font-size:10px;padding:1px 6px;">Restaurar</button>',
                '<button class="btn btn-xs btn-outline-danger ve-snap-delete" data-idx="' + realIdx + '"',
                ' style="font-size:10px;padding:1px 6px;"><i class="fa-duotone fa-solid fa-times"></i></button>',
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

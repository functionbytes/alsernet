<div id="ve-sections-panel" class="ve-panel-root">

    {{-- Header --}}
    <div class="ve-panel-header">
        <div>
            <div class="ve-panel-label">Capas</div>
            <span class="ve-panel-title">Estructura de la página</span>
        </div>
        <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="btn-refresh-sections" title="Actualizar">
            <i class="fa-solid fa-sync"></i>
        </button>
    </div>

    {{-- Tabs --}}
    <div class="ve-tabs-bar">
        <button class="ve-tab active" data-tab="sections">Secciones</button>
        <button class="ve-tab" data-tab="navigator">Navegador</button>
    </div>

    {{-- Sections view --}}
    <div id="ve-sections-view" class="ve-tab-content active">
        <div id="ve-sections-empty" class="ve-empty-state ve-initially-hidden">
            <small>No hay secciones detectadas.<br>Añade bloques desde el panel de Bloques.</small>
        </div>
        <div id="ve-sections-list" class="ve-sections-list-area"></div>
    </div>

    {{-- Navigator view --}}
    <div id="ve-navigator-view" class="ve-tab-content">
        <div id="ve-navigator-empty" class="ve-empty-state">
            <i class="fa-solid fa-sitemap ve-empty-icon"></i>
            <div>Haz clic en "Actualizar" para ver el árbol DOM</div>
        </div>
        <div id="ve-navigator-tree" class="ve-navigator-tree-area"></div>
    </div>

    {{-- Footer --}}
    <div class="ve-panel-footer">
        <small>Arrastra para reordenar · Click para seleccionar</small>
    </div>
</div>

<style>
.ve-panel-root { height:100%; display:flex; flex-direction:column; }
.ve-initially-hidden { display: none; }
.ve-panel-header { padding:10px 12px; border-bottom:1px solid var(--ve-border, #eee); flex-shrink:0; display:flex; align-items:center; justify-content:space-between; }
.ve-panel-label { font-size:10px; font-weight:600; color:#999; text-transform:uppercase; letter-spacing:.5px; }
.ve-panel-title { font-size:13px; font-weight:700; color:#333; }
.ve-panel-action-btn { font-size:14px !important; width:34px !important; height:34px !important; padding:0 !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; border-radius:8px !important; }
.ve-panel-footer { padding:6px 12px; border-top:1px solid var(--ve-border, #eee); flex-shrink:0; font-size:10px; color:#bbb; }

/* Tabs */
.ve-tabs-bar { display:flex; border-bottom:1px solid var(--ve-border, #eee); flex-shrink:0; }
.ve-tab { flex:1; padding:8px 0; background:transparent; border:none; border-bottom:2px solid transparent; font-size:11px; font-weight:600; color:#999; cursor:pointer; transition:all .15s; }
.ve-tab:hover { color:#666; }
.ve-tab.active { color:#90bb13; border-bottom-color:#90bb13; }

/* Tab content */
.ve-tab-content { display:none; flex:1 1 0; overflow-y:auto; min-height:0; }
.ve-tab-content.active { display:block; }

/* Empty state */
.ve-empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px; text-align:center; color:#bbb; font-size:12px; }
.ve-empty-icon { font-size:28px; margin-bottom:10px; opacity:.3; }

/* Sections list */
.ve-sections-list-area { flex:1; overflow-y:auto; padding:8px; }

/* Navigator tree */
.ve-navigator-tree-area { padding:8px; display:none; }
.ve-nav-node { margin-left:10px; margin-bottom:2px; }
.ve-nav-node:first-child { margin-left:0; }
.ve-nav-node-header {
    display:flex; align-items:center; gap:6px;
    padding:6px 8px; border:1px solid var(--ve-border, #eee);
    border-radius:6px; cursor:pointer; font-size:11px;
    color:#555; background:#fff; transition:all .15s;
    margin-bottom:2px;
}
.ve-nav-node-header:hover { border-color:#ccc; box-shadow:0 1px 4px rgba(0,0,0,.05); }
.ve-nav-node-header.ve-nav-selected { border-color:#90bb13; background:#fdf2f2; color:#90bb13; }
.ve-nav-toggle { width:16px; text-align:center; font-size:9px; color:#bbb; cursor:pointer; flex-shrink:0; transition:transform .15s; }
.ve-nav-toggle.empty { visibility:hidden; }
.ve-nav-toggle.open { transform:rotate(90deg); }
.ve-nav-tag-badge {
    font-family:'SF Mono',monospace; font-size:9px; font-weight:600;
    background:#f4f6f8; color:#999; padding:1px 5px;
    border-radius:3px; flex-shrink:0;
}
.ve-nav-label { font-size:11px; color:#333; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:500; }
.ve-nav-text { font-size:9px; color:#bbb; max-width:80px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-left:auto; }
.ve-nav-children { display:none; padding-left:4px; border-left:1px solid #eee; margin-left:8px; }
.ve-nav-children.open { display:block; }
</style>

<script>
(function($){
    // Tab switching
    $(document).on('click', '#ve-sections-panel .ve-tab', function(){
        var tab = $(this).data('tab');
        $('#ve-sections-panel .ve-tab').removeClass('active');
        $(this).addClass('active');
        $('#ve-sections-panel .ve-tab-content').removeClass('active');
        if (tab === 'sections') {
            $('#ve-sections-view').addClass('active');
        } else {
            $('#ve-navigator-view').addClass('active');
            refreshNavigator();
        }
    });

    // Navigator scan
    function refreshNavigator() {
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-scan-navigator' }, '*');
        }
    }

    window.veHandleNavigatorScan = function(data) {
        var $tree = $('#ve-navigator-tree');
        var $empty = $('#ve-navigator-empty');
        if (!data || !data.length) { $tree.hide(); $empty.show(); return; }
        $empty.hide();
        $tree.empty().show();
        data.forEach(function(node) {
            $tree.append(buildNavNode(node, 0));
        });
    };

    function buildNavNode(node, depth) {
        var $wrap = $('<div class="ve-nav-node">');
        var hasChildren = node.children && node.children.length > 0;
        var toggleIcon = hasChildren ? '&#9654;' : '';
        var toggleClass = hasChildren ? 've-nav-toggle' : 've-nav-toggle empty';

        // Extract just the tag name for the badge
        var tagOnly = node.tag || '?';
        var displayLabel = node.label || tagOnly;
        // Truncate label
        if (displayLabel.length > 25) displayLabel = displayLabel.substring(0, 25) + '...';

        var $header = $('<div class="ve-nav-node-header">')
            .html(
                '<span class="' + toggleClass + '">' + toggleIcon + '</span>' +
                '<span class="ve-nav-tag-badge">' + escHtml(tagOnly) + '</span>' +
                '<span class="ve-nav-label">' + escHtml(displayLabel) + '</span>' +
                (node.text ? '<span class="ve-nav-text">' + escHtml(node.text) + '</span>' : '')
            );

        $header.on('click', function(e) {
            var $target = $(e.target);
            if ($target.hasClass('ve-nav-toggle') || $target.closest('.ve-nav-toggle').length) {
                var $kids = $wrap.find('> .ve-nav-children');
                $kids.toggleClass('open');
                var $tog = $header.find('.ve-nav-toggle');
                $tog.toggleClass('open');
                $tog.html($kids.hasClass('open') ? '&#9660;' : '&#9654;');
            } else {
                $('.ve-nav-node-header').removeClass('ve-nav-selected');
                $header.addClass('ve-nav-selected');
                var frame = document.getElementById('ve-preview-frame');
                if (frame && frame.contentWindow && node.nodeId) {
                    frame.contentWindow.postMessage({ type: 've-select-by-id', nodeId: node.nodeId }, '*');
                }
            }
        });

        $wrap.append($header);

        if (hasChildren) {
            var $children = $('<div class="ve-nav-children">');
            node.children.forEach(function(child) {
                $children.append(buildNavNode(child, depth + 1));
            });
            $wrap.append($children);
        }

        return $wrap;
    }

    function escHtml(s) { return $('<span>').text(s).html(); }

    // Also refresh when clicking refresh button
    $('#btn-refresh-sections').on('click', function() {
        var activeTab = $('#ve-sections-panel .ve-tab.active').data('tab');
        if (activeTab === 'navigator') refreshNavigator();
    });

    // Listen for navigator data
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 've-navigator-scan') {
            window.veHandleNavigatorScan(e.data.data);
        }
    });
})(jQuery);
</script>

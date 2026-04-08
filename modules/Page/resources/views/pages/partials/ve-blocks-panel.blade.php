<div id="ve-blocks-panel" style="height:100%; display:flex; flex-direction:column; overflow:hidden;">

    {{-- Search --}}
    <div style="padding:8px; border-bottom:1px solid #e9ecef; flex-shrink:0;">
        <div style="position:relative;">
            <i class="fa-duotone fa-solid fa-search" style="position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#aaa; font-size:12px; pointer-events:none;"></i>
            <input type="text" id="ve-block-search" class="form-control form-control-sm"
                   placeholder="Buscar bloque..."
                   style="padding-left:28px; font-size:12px;">
        </div>
    </div>

    {{-- Drag hint --}}
    <div style="padding:5px 10px; background:#f5f5f5; border-bottom:1px solid #dee2e6; flex-shrink:0; font-size:11px; color:#888;">
        <i class="fa-duotone fa-solid fa-hand-pointer me-1"></i>
        Arrastra al preview o haz clic para insertar
    </div>

    {{-- Blocks list (grouped by category) --}}
    <div id="ve-blocks-list" style="flex:1; overflow-y:auto;">
        <div id="ve-blocks-accordion"></div>
    </div>

</div>

<style>
.ve-blocks-category { border-bottom: 1px solid #f0f0f0; }
.ve-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 9px 12px;
    background: transparent;
    border: none;
    border-bottom: 1px solid #f0f0f0;
    font-size: 12px;
    font-weight: 600;
    color: #444;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.ve-category-header:hover { background: #f8f9fa; }
.ve-category-header[aria-expanded="true"],
.ve-category-header:not(.collapsed) {
    background: rgba(99, 102, 241, 0.08);
}
.ve-category-header i.ve-cat-chevron { font-size: 10px; color: #aaa; transition: transform .2s; }
.ve-category-header.collapsed i.ve-cat-chevron { transform: rotate(-90deg); }
.ve-blocks-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 10px 10px;
}
.ve-block-item {
    cursor: grab;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 8px;
    text-align: center;
    transition: border-color .15s, box-shadow .15s;
    user-select: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    background: #fff;
}
.ve-block-item .ve-block-icon { font-size: 20px; color: #6c757d; line-height: 1; }
.ve-block-item .ve-block-icon.ve-block-icon-custom { color: #1a1a1a; }
.ve-block-item .ve-block-name { font-size: 11px; color: #333; font-weight: 500; line-height: 1.3; }
.ve-block-item:hover { border-color: #1a1a1a; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
.ve-block-item:active { cursor: grabbing; }
.ve-block-item.dragging { opacity: .5; transform: scale(.97); }
.ve-block-item-custom {
    background: #fafff0;
    position: relative;
}
.ve-block-item-custom .ve-delete-custom-block {
    position: absolute;
    top: 4px;
    right: 4px;
    font-size: 9px;
    padding: 1px 4px;
    border-radius: 3px;
    line-height: 1.4;
}
</style>

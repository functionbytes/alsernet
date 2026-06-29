<div id="ve-blocks-panel" class="ve-blocks-panel-root">

    {{-- Search --}}
    <div class="ve-blocks-search-wrap">
        <div class="position-relative">
            <i class="fa-solid fa-search ve-search-icon"></i>
            <input type="text" id="ve-block-search" class="form-control form-control-sm ve-search-input"
                   placeholder="Buscar bloque...">
        </div>
    </div>

    {{-- Drag hint --}}
    <div class="ve-blocks-drag-hint">
        <i class="fa-solid fa-hand-pointer me-1"></i>
        Arrastra al preview o haz clic para insertar
    </div>

    {{-- Blocks list (grouped by category) --}}
    <div id="ve-blocks-list" class="ve-blocks-list-scroll">
        <div id="ve-blocks-accordion"></div>
    </div>

</div>

<style>
.ve-blocks-category { border-bottom: 1px solid var(--ve-border); }
.ve-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 9px 12px;
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--ve-border);
    font-size: 12px;
    font-weight: 600;
    color: var(--ve-text);
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.ve-category-header:hover { background: var(--ve-bg-muted); }
.ve-category-header[aria-expanded="true"],
.ve-category-header:not(.collapsed) {
    background: var(--ve-hover);
}
.ve-category-header i.ve-cat-chevron { font-size: 10px; color: var(--ve-text-muted); transition: transform .2s; }
.ve-category-header.collapsed i.ve-cat-chevron { transform: rotate(-90deg); }
.ve-blocks-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 10px 10px;
}
.ve-block-item {
    cursor: grab;
    border: 1px solid var(--ve-border);
    border-radius: 8px;
    padding: 12px 8px;
    text-align: center;
    transition: border-color .15s, box-shadow .15s;
    user-select: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    background: var(--ve-bg);
}
.ve-block-item .ve-block-icon { font-size: 20px; color: var(--ve-text-soft); line-height: 1; }
.ve-block-item .ve-block-icon.ve-block-icon-custom { color: var(--ve-text); }
.ve-block-item .ve-block-name { font-size: 11px; color: var(--ve-text); font-weight: 500; line-height: 1.3; }
.ve-block-item:hover { border-color: var(--ve-text); box-shadow: 0 2px 8px rgba(0,0,0,.15); }
.ve-block-item:active { cursor: grabbing; }
.ve-block-item.dragging { opacity: .5; transform: scale(.97); }
.ve-block-item-custom {
    background: var(--ve-bg-subtle);
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

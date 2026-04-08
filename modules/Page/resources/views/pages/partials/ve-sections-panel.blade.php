<div id="ve-sections-panel" style="height:100%; display:flex; flex-direction:column;">

    <div class="p-2 border-bottom bg-light d-flex align-items-center justify-content-between">
        <small class="text-muted fw-semibold" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px;">
            Secciones de la página
        </small>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-sections" title="Actualizar lista">
            <i class="fa-duotone fa-solid fa-sync fa-xs"></i>
        </button>
    </div>

    <div id="ve-sections-empty" class="text-center py-4 text-muted px-3" style="display:none;">
        <small>No hay secciones detectadas.<br>Añade bloques desde el panel de Bloques.</small>
    </div>

    <div id="ve-sections-list" style="flex:1; overflow-y:auto; padding:8px;">
        {{-- Se llena con JS --}}
    </div>

    <div class="p-2 border-top bg-light">
        <small class="text-muted" style="font-size:10px;">
            Arrastra para reordenar · Click para seleccionar
        </small>
    </div>
</div>

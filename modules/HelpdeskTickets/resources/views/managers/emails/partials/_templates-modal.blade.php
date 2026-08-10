{{-- Plantillas y macros — reusa el catálogo de Macro (mismo usado por el
     command palette de tickets), filtrado a las que tienen una acción
     'reply'. Insertar reemplaza el cuerpo del compose modal. --}}
<div class="bv-modal" data-bv-modal-name="eml-templates">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Emails · Plantillas</span>
                <div class="bv-modal-title">Insertar plantilla</div>
            </div>
            <button type="button" class="bv-modal-close" data-htk-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div id="eml-templates-list" style="display:flex;flex-direction:column;gap:4px"></div>
        </div>
        <div class="bv-modal-foot">
            <button type="button" class="btn-secondary" data-htk-close>Cerrar</button>
        </div>
    </div>
</div>

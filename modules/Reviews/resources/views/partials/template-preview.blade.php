<div class="offcanvas offcanvas-end" id="templatePreviewPanel" tabindex="-1" style="width: 450px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">
            <i class="fas fa-eye me-2 text-primary"></i>Vista previa del template
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="templatePreviewLoading" class="text-center py-5 d-none">
            <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
            <p class="text-muted">Cargando template...</p>
        </div>

        <div id="templatePreviewContent" class="flex-grow-1">
            <div class="mb-3">
                <label class="form-label fw-bold text-muted text-uppercase">Nombre</label>
                <p id="previewTemplateName" class="mb-0 fw-semibold">—</p>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-muted text-uppercase">Categoria</label>
                <p id="previewTemplateCategory" class="mb-0">—</p>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-muted text-uppercase">Texto del template</label>
                <div id="previewTemplateText" class="border rounded p-3 bg-light text-break" style="white-space: pre-wrap; min-height: 80px;"></div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold text-muted text-uppercase">Vista previa personalizada</label>
                <small class="text-muted d-block mb-2">Con datos del revisor actual</small>
                <div id="previewTemplateRendered" class="border rounded p-3 bg-white text-break" style="white-space: pre-wrap; min-height: 80px;"></div>
            </div>
        </div>

        <div class="mt-auto pt-3 border-top">
            <button id="applyTemplateBtn" class="btn btn-primary w-100" disabled>
                <i class="fas fa-check me-2"></i>Aplicar template
            </button>
        </div>
    </div>
</div>

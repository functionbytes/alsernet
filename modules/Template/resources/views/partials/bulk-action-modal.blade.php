<div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acción masiva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> {{ $entityPlural }}</strong>.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Acción</label>
                    <select id="bulk-action-select" class="form-select">
                        <option value="">Seleccionar acción...</option>
                        <option value="activate">Activar</option>
                        <option value="deactivate">Desactivar</option>
                        <option value="delete">Eliminar</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

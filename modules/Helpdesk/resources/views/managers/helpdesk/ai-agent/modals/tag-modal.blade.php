<!-- Tag Modal -->
<div class="modal fade" id="tagModal" tabindex="-1" aria-labelledby="tagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="tagModalLabel">
                    <i class="ti ti-tag me-2"></i><span id="tagModalTitle">Nuevo Tag</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="tagForm">
                <div class="modal-body">
                    <input type="hidden" id="tag_id" name="id">

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tag_name" name="name" required placeholder="Ej: Urgente, Soporte Técnico">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Color <span class="text-danger">*</span></label>
                            <input type="color" class="form-control form-control-color" id="tag_color" name="color" value="#90bb13">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" id="tag_description" name="description" rows="2" placeholder="Breve descripción del propósito de este tag"></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Icono (Tabler)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-icon" id="tag_icon_preview"></i></span>
                                <input type="text" class="form-control" id="tag_icon" name="icon" placeholder="ti-star">
                            </div>
                            <small class="text-muted">Ej: ti-star, ti-flag, ti-alert-circle</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Prioridad</label>
                            <input type="number" class="form-control" id="tag_priority" name="priority" value="0" min="0" max="100">
                            <small class="text-muted">Mayor prioridad = más relevante</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Instrucción Adicional para el System Prompt</label>
                            <textarea class="form-control" id="tag_system_prompt_addition" name="system_prompt_addition" rows="3" placeholder="Instrucciones adicionales que se añadirán al system prompt cuando este tag esté activo..."></textarea>
                            <small class="text-muted">Este texto se agregará al prompt del agente cuando una conversación tenga este tag</small>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="tag_is_active" name="is_active" checked>
                                <label class="form-check-label" for="tag_is_active">
                                    <strong>Tag Activo</strong>
                                    <small class="d-block text-muted">Los tags inactivos no se mostrarán en las opciones</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="saveTagBtn">
                        <i class="ti ti-check me-2"></i>Guardar Tag
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Update icon preview
document.getElementById('tag_icon')?.addEventListener('input', function() {
    const preview = document.getElementById('tag_icon_preview');
    preview.className = 'ti ' + this.value;
});
</script>
@endpush

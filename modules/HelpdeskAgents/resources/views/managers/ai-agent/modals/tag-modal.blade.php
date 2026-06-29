{{-- Tag modal: shared create/edit --}}
<div class="modal fade" id="tagModal" tabindex="-1" aria-labelledby="tagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tagModalLabel">Nuevo tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="tagForm">
                <div class="modal-body">
                    <input type="hidden" id="tag_id" name="id">

                    {{-- Informacion basica --}}
                    <div class="mb-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre, descripcion y color identificador</p>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="tag_name">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tag_name" name="name"
                                       placeholder="Ej: Urgente, Soporte tecnico" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="tag_color">Color <span class="text-danger">*</span></label>
                                <input type="color" class="form-control form-control-color w-100" id="tag_color" name="color" value="#90bb13">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tag_description">Descripcion</label>
                                <textarea class="form-control" id="tag_description" name="description" rows="2"
                                          placeholder="Breve descripcion del proposito de este tag"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tag_icon">Icono (Font Awesome)</label>
                                <input type="text" class="form-control" id="tag_icon" name="icon"
                                       placeholder="fas fa-star">
                                <div class="form-text">Ej: fas fa-star, fas fa-flag, fas fa-bolt</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Configuracion --}}
                    <div>
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Configuracion</h6>
                        <p class="text-muted small mb-3">Prioridad de uso y disponibilidad</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tag_priority">Prioridad</label>
                                <input type="number" class="form-control" id="tag_priority" name="priority"
                                       value="0" min="0" max="100">
                                <div class="form-text">Mayor prioridad = mas relevante</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tag_is_active">Estado</label>
                                <select class="form-select" id="tag_is_active" name="is_active">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tag_system_prompt_addition">Instruccion adicional para el system prompt</label>
                                <textarea class="form-control" id="tag_system_prompt_addition" name="system_prompt_addition" rows="3"
                                          placeholder="Instrucciones que se añadiran al prompt cuando este tag este activo..."></textarea>
                                <div class="form-text">Este texto se agrega al prompt del agente cuando una conversacion tenga este tag</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar tag</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

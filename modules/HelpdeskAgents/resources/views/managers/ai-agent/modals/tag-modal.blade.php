{{-- Tag modal: shared create/edit --}}
<div class="modal fade" id="tagModal" tabindex="-1" aria-labelledby="tagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tagModalLabel">Nueva etiqueta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="tagForm">
                <div class="modal-body">
                    <input type="hidden" id="tag_id" name="id">

                    {{-- Información básica --}}
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Información básica</h6>
                        <p class="text-muted small mb-3">Nombre, descripción y color identificador</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="tag_name">Nombre <span class="ais-required">· obligatorio</span></label>
                                <input type="text" class="form-control" id="tag_name" name="name"
                                       placeholder="Ej: Urgente, Soporte técnico" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tag_color">Color <span class="ais-required">· obligatorio</span></label>
                                @include('core::components.color-field', [
                                    'name' => 'color',
                                    'id' => 'tag_color',
                                    'value' => '#90bb13',
                                    'preview' => 'Etiqueta',
                                    'previewFrom' => '#tag_name',
                                ])
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tag_description">Descripción</label>
                                <textarea class="form-control" id="tag_description" name="description" rows="2"
                                          placeholder="Breve descripción del propósito de este tag"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tag_icon">Icono (Font Awesome)</label>
                                <input type="text" class="form-control" id="tag_icon" name="icon"
                                       placeholder="fas fa-star">
                                <div class="form-text">Ej: fas fa-star, fas fa-flag, fas fa-bolt</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Configuración --}}
                    <div>
                        <h6 class="fw-bold mb-3">Configuración</h6>
                        <p class="text-muted small mb-3">Prioridad de uso y disponibilidad</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tag_priority">Prioridad</label>
                                <input type="number" class="form-control" id="tag_priority" name="priority"
                                       value="0" min="0" max="100">
                                <div class="form-text">Mayor prioridad = más relevante</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tag_is_active">Estado</label>
                                <select class="form-select" id="tag_is_active" name="is_active">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tag_system_prompt_addition">Instrucción adicional para el system prompt</label>
                                <textarea class="form-control" id="tag_system_prompt_addition" name="system_prompt_addition" rows="3"
                                          placeholder="Instrucciones que se añadirán al prompt cuando este tag esté activo..."></textarea>
                                <div class="form-text">Este texto se agrega al prompt del agente cuando una conversación tenga este tag</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar etiqueta</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

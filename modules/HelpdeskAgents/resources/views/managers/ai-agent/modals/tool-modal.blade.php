{{-- Tool modal: shared create/edit --}}
<div class="modal fade" id="toolModal" tabindex="-1" aria-labelledby="toolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toolModalLabel">Nueva herramienta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="toolForm">
                <div class="modal-body">
                    <input type="hidden" id="tool_id" name="id">

                    {{-- Informacion basica --}}
                    <div class="mb-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre y descripcion del tool</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tool_name">Nombre de la funcion <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tool_name" name="name"
                                       placeholder="get_weather, search_database" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tool_type">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" id="tool_type" name="type" required>
                                    <option value="function">Funcion</option>
                                    <option value="api">API externa</option>
                                    <option value="database">Consulta base de datos</option>
                                    <option value="custom">Personalizado</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tool_description">Descripcion <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="tool_description" name="description" rows="2"
                                          placeholder="Describe que hace esta herramienta..." required></textarea>
                                <div class="form-text">Esta descripcion ayudara al LLM a decidir cuando usar esta herramienta</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Parametros --}}
                    <div class="mb-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Parametros</h6>
                        <p class="text-muted small mb-3">Configuracion del tool</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="tool_parameters">Parametros (JSON Schema)</label>
                                <textarea class="form-control font-monospace" id="tool_parameters" name="parameters" rows="6"
                                          placeholder='{"type":"object","properties":{"city":{"type":"string","description":"The city name"}},"required":["city"]}'></textarea>
                                <div class="form-text">Define los parametros que acepta esta funcion en formato JSON Schema</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tool_implementation">Implementacion (codigo/endpoint)</label>
                                <textarea class="form-control font-monospace" id="tool_implementation" name="implementation" rows="3"
                                          placeholder="URL del endpoint, codigo PHP, o referencia a clase/metodo"></textarea>
                                <div class="form-text">URL de API, codigo ejecutable, o ruta a metodo PHP</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tool_auth_config">Configuracion de autenticacion (JSON)</label>
                                <textarea class="form-control font-monospace" id="tool_auth_config" name="auth_config" rows="3"
                                          placeholder='{"type":"bearer","token":"xxx"}'></textarea>
                                <div class="form-text">API keys, tokens u otras credenciales necesarias</div>
                            </div>
                        </div>
                    </div>

                    {{-- Configuracion --}}
                    <div>
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Configuracion</h6>
                        <p class="text-muted small mb-3">Disponibilidad</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tool_requires_approval">Requiere aprobacion</label>
                                <select class="form-select" id="tool_requires_approval" name="requires_approval">
                                    <option value="0">No requiere aprobacion</option>
                                    <option value="1">Requiere aprobacion</option>
                                </select>
                                <div class="form-text">El agente pedira permiso antes de ejecutar esta herramienta</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tool_is_active">Estado</label>
                                <select class="form-select" id="tool_is_active" name="is_active">
                                    <option value="1">Activa</option>
                                    <option value="0">Inactiva</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar herramienta</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

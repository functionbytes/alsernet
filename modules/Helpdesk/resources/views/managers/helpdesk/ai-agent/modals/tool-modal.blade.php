<!-- Tool Modal -->
<div class="modal fade" id="toolModal" tabindex="-1" aria-labelledby="toolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="toolModalLabel">
                    <i class="ti ti-tool me-2"></i><span id="toolModalTitle">Nueva Herramienta</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="toolForm">
                <div class="modal-body">
                    <input type="hidden" id="tool_id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Nombre de la Función <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tool_name" name="name" required placeholder="get_weather, search_database">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" id="tool_type" name="type" required>
                                <option value="function">Función</option>
                                <option value="api">API Externa</option>
                                <option value="database">Consulta Base de Datos</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="tool_description" name="description" rows="2" required placeholder="Describe qué hace esta herramienta..."></textarea>
                            <small class="text-muted">Esta descripción ayudará al LLM a decidir cuándo usar esta herramienta</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Parámetros (JSON Schema)</label>
                            <textarea class="form-control font-monospace" id="tool_parameters" name="parameters" rows="6" placeholder='{
  "type": "object",
  "properties": {
    "city": {
      "type": "string",
      "description": "The city name"
    }
  },
  "required": ["city"]
}'></textarea>
                            <small class="text-muted">Define los parámetros que acepta esta función en formato JSON Schema</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Implementación (Código/Endpoint)</label>
                            <textarea class="form-control font-monospace" id="tool_implementation" name="implementation" rows="4" placeholder="URL del endpoint, código PHP, o referencia a clase/método"></textarea>
                            <small class="text-muted">URL de API, código ejecutable, o ruta a método PHP</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Configuración de Autenticación (JSON)</label>
                            <textarea class="form-control font-monospace" id="tool_auth_config" name="auth_config" rows="3" placeholder='{
  "type": "bearer",
  "token": "xxx"
}'></textarea>
                            <small class="text-muted">API keys, tokens u otras credenciales necesarias</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="tool_requires_approval" name="requires_approval">
                                <label class="form-check-label" for="tool_requires_approval">
                                    <strong>Requiere Aprobación</strong>
                                    <small class="d-block text-muted">El agente pedirá permiso antes de ejecutar esta herramienta</small>
                                </label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="tool_is_active" name="is_active" checked>
                                <label class="form-check-label" for="tool_is_active">
                                    <strong>Herramienta Activa</strong>
                                    <small class="d-block text-muted">Solo las herramientas activas estarán disponibles para el agente</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="saveToolBtn">
                        <i class="ti ti-check me-2"></i>Guardar Herramienta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Knowledge Base Modal -->
<div class="modal fade" id="knowledgeModal" tabindex="-1" aria-labelledby="knowledgeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="knowledgeModalLabel">
                    <i class="ti ti-book me-2"></i><span id="knowledgeModalTitle">Nuevo Documento</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="knowledgeForm">
                <div class="modal-body">
                    <input type="hidden" id="knowledge_id" name="id">

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="knowledge_title" name="title" required placeholder="Título del documento o artículo">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" id="knowledge_type" name="type" required>
                                <option value="document">Documento</option>
                                <option value="faq">FAQ</option>
                                <option value="article">Artículo</option>
                                <option value="manual">Manual</option>
                                <option value="url">URL</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Contenido <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="knowledge_content" name="content" rows="10" required placeholder="Contenido del documento que el agente usará para generar respuestas..."></textarea>
                            <small class="text-muted">Este contenido será indexado y usado por el agente para responder preguntas</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">URL de Origen</label>
                            <input type="url" class="form-control" id="knowledge_source_url" name="source_url" placeholder="https://ejemplo.com/articulo">
                            <small class="text-muted">Enlace al documento original (opcional)</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tipo de Fuente</label>
                            <select class="form-select" id="knowledge_source_type" name="source_type">
                                <option value="">Sin especificar</option>
                                <option value="manual">Entrada Manual</option>
                                <option value="import">Importación</option>
                                <option value="scrape">Web Scraping</option>
                                <option value="help_center">Centro de Ayuda</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Tags (separados por comas)</label>
                            <input type="text" class="form-control" id="knowledge_tags" name="tags" placeholder="soporte, tutorial, api, configuración">
                            <small class="text-muted">Ayuda a categorizar y encontrar este contenido</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Resumen</label>
                            <textarea class="form-control" id="knowledge_summary" name="summary" rows="2" placeholder="Resumen breve del contenido (se genera automáticamente si se deja vacío)"></textarea>
                            <small class="text-muted">Un resumen corto para búsquedas rápidas</small>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="knowledge_is_active" name="is_active" checked>
                                <label class="form-check-label" for="knowledge_is_active">
                                    <strong>Documento Activo</strong>
                                    <small class="d-block text-muted">Solo los documentos activos serán usados por el agente</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" id="generateEmbeddingBtn">
                        <i class="ti ti-vector me-2"></i>Generar Embedding
                    </button>
                    <button type="submit" class="btn btn-warning" id="saveKnowledgeBtn">
                        <i class="ti ti-check me-2"></i>Guardar Documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

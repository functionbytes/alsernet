{{-- Knowledge modal: shared create/edit --}}
<div class="modal fade" id="knowledgeModal" tabindex="-1" aria-labelledby="knowledgeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="knowledgeModalLabel">Nuevo documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="knowledgeForm">
                <div class="modal-body">
                    <input type="hidden" id="knowledge_id" name="id">

                    {{-- Información básica --}}
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Información básica</h6>
                        <p class="text-muted small mb-3">Título y contenido</p>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="knowledge_title">Título <span class="ais-required">· obligatorio</span></label>
                                <input type="text" class="form-control" id="knowledge_title" name="title"
                                       placeholder="Título del documento o artículo" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="knowledge_type">Tipo <span class="ais-required">· obligatorio</span></label>
                                <select class="form-select" id="knowledge_type" name="type" required>
                                    <option value="document">Documento</option>
                                    <option value="faq">FAQ</option>
                                    <option value="article">Artículo</option>
                                    <option value="manual">Manual</option>
                                    <option value="url">URL</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="knowledge_content">Contenido <span class="ais-required">· obligatorio</span></label>
                                <textarea class="form-control" id="knowledge_content" name="content" rows="10"
                                          placeholder="Contenido del documento que el agente usará para generar respuestas..." required></textarea>
                                <div class="form-text">Este contenido será indexado y usado por el agente para responder preguntas</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Metadata --}}
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Metadata</h6>
                        <p class="text-muted small mb-3">Tags, categorías y fuente</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="knowledge_source_url">URL de origen</label>
                                <input type="url" class="form-control" id="knowledge_source_url" name="source_url"
                                       placeholder="https://ejemplo.com/artículo">
                                <div class="form-text">Enlace al documento original (opcional)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="knowledge_source_type">Tipo de fuente</label>
                                <select class="form-select" id="knowledge_source_type" name="source_type">
                                    <option value="">Sin especificar</option>
                                    <option value="manual">Entrada manual</option>
                                    <option value="import">Importación</option>
                                    <option value="scrape">Web scraping</option>
                                    <option value="help_center">Centro de ayuda</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="knowledge_tags">Tags (separados por comas)</label>
                                <input type="text" class="form-control" id="knowledge_tags" name="tags"
                                       placeholder="soporte, tutorial, api, configuración">
                                <div class="form-text">Ayuda a categorizar y encontrar este contenido</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="knowledge_summary">Resumen</label>
                                <textarea class="form-control" id="knowledge_summary" name="summary" rows="2"
                                          placeholder="Resumen breve del contenido (se genera automáticamente si se deja vacío)"></textarea>
                                <div class="form-text">Un resumen corto para búsquedas rápidas</div>
                            </div>
                        </div>
                    </div>

                    {{-- Configuración --}}
                    <div>
                        <h6 class="fw-bold mb-3">Configuración</h6>
                        <p class="text-muted small mb-3">Visibilidad</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="knowledge_is_active">Estado</label>
                                <select class="form-select" id="knowledge_is_active" name="is_active">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                <div class="form-text">Solo los documentos activos serán usados por el agente</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar documento</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

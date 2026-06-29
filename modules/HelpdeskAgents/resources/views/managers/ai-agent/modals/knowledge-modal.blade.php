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

                    {{-- Informacion basica --}}
                    <div class="mb-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Informacion basica</h6>
                        <p class="text-muted small mb-3">Titulo y contenido</p>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="knowledge_title">Titulo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="knowledge_title" name="title"
                                       placeholder="Titulo del documento o articulo" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="knowledge_type">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" id="knowledge_type" name="type" required>
                                    <option value="document">Documento</option>
                                    <option value="faq">FAQ</option>
                                    <option value="article">Articulo</option>
                                    <option value="manual">Manual</option>
                                    <option value="url">URL</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="knowledge_content">Contenido <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="knowledge_content" name="content" rows="10"
                                          placeholder="Contenido del documento que el agente usara para generar respuestas..." required></textarea>
                                <div class="form-text">Este contenido sera indexado y usado por el agente para responder preguntas</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Metadata --}}
                    <div class="mb-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Metadata</h6>
                        <p class="text-muted small mb-3">Tags, categorias y fuente</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="knowledge_source_url">URL de origen</label>
                                <input type="url" class="form-control" id="knowledge_source_url" name="source_url"
                                       placeholder="https://ejemplo.com/articulo">
                                <div class="form-text">Enlace al documento original (opcional)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="knowledge_source_type">Tipo de fuente</label>
                                <select class="form-select" id="knowledge_source_type" name="source_type">
                                    <option value="">Sin especificar</option>
                                    <option value="manual">Entrada manual</option>
                                    <option value="import">Importacion</option>
                                    <option value="scrape">Web scraping</option>
                                    <option value="help_center">Centro de ayuda</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="knowledge_tags">Tags (separados por comas)</label>
                                <input type="text" class="form-control" id="knowledge_tags" name="tags"
                                       placeholder="soporte, tutorial, api, configuracion">
                                <div class="form-text">Ayuda a categorizar y encontrar este contenido</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="knowledge_summary">Resumen</label>
                                <textarea class="form-control" id="knowledge_summary" name="summary" rows="2"
                                          placeholder="Resumen breve del contenido (se genera automaticamente si se deja vacio)"></textarea>
                                <div class="form-text">Un resumen corto para busquedas rapidas</div>
                            </div>
                        </div>
                    </div>

                    {{-- Configuracion --}}
                    <div>
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Configuracion</h6>
                        <p class="text-muted small mb-3">Visibilidad</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="knowledge_is_active">Estado</label>
                                <select class="form-select" id="knowledge_is_active" name="is_active">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                <div class="form-text">Solo los documentos activos seran usados por el agente</div>
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

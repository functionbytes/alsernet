{{-- Gestor de medios - Modal global (usado por window.MediaPicker) --}}
<div id="mp-modal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="d-flex flex-grow-1 overflow-hidden">

                {{-- Sidebar --}}
                <div class="mp-sidebar d-flex flex-column p-3">

                    {{-- Brand --}}
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <i class="fas fa-photo-video mp-sidebar-brand-icon"></i>
                        <span class="mp-sidebar-brand">Media Manager</span>
                    </div>

                    {{-- Add folder --}}
                    <button id="mp-new-folder-btn" type="button" class="mp-add-folder-btn mb-4">
                        <i class="fas fa-plus me-1"></i>Nueva carpeta
                    </button>

                    {{-- Nav --}}
                    <p class="mp-section-label mb-2">Explorar</p>
                    <nav class="d-flex flex-column gap-1 mb-4" id="mp-sidebar-tabs">
                        <button class="mp-nav-btn mp-tab-btn active" data-view="all_media">
                            <i class="fas fa-folder-open mp-nav-icon"></i>Biblioteca
                        </button>
                        <button class="mp-nav-btn mp-tab-btn" data-view="recent">
                            <i class="fas fa-clock mp-nav-icon"></i>Recientes
                        </button>
                    </nav>

                    {{-- Disco --}}
                    <p class="mp-section-label mb-2">Almacenamiento</p>
                    <select id="mp-disk-select" class="form-select form-select-sm">
                        @foreach(array_filter(config('filesystems.disks', []), fn($d) => ($d['driver'] ?? '') !== 'local' || ($d['root'] ?? '') === public_path('media'), ARRAY_FILTER_USE_BOTH) as $disk => $config)
                            <option value="{{ $disk }}">{{ ucfirst($disk) }} ({{ $config['driver'] ?? 'local' }})</option>
                        @endforeach
                    </select>

                </div>

                {{-- Main --}}
                <div class="mp-main d-flex flex-column flex-grow-1">

                    {{-- Top bar --}}
                    <div class="mp-topbar px-4 py-3">
                        <div class="d-flex align-items-center gap-3 mb-2">

                            {{-- Search --}}
                            <div class="position-relative flex-grow-1">
                                <i class="fas fa-search mp-search-icon"></i>
                                <input id="mp-search" type="text" autocomplete="off"
                                       class="form-control mp-search-input w-100"
                                       placeholder="Buscar archivos...">
                                <button id="mp-search-clear" type="button" aria-label="Limpiar">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            {{-- Type filter tabs --}}
                            <nav class="d-flex mp-type-tabs flex-shrink-0" id="mp-type-tabs">
                                <button class="mp-type-tab active" data-type="all">Todos</button>
                                <button class="mp-type-tab" data-type="image">Imágenes</button>
                                <button class="mp-type-tab" data-type="video">Videos</button>
                                <button class="mp-type-tab" data-type="document">Documentos</button>
                            </nav>

                            {{-- Upload --}}
                            <button id="mp-upload-btn" type="button" class="btn btn-sm mp-btn-primary px-3 flex-shrink-0">
                                <i class="fas fa-cloud-upload-alt me-1"></i>Subir
                            </button>

                        </div>

                        {{-- Breadcrumb --}}
                        <nav id="mp-breadcrumb">
                            <ol class="breadcrumb mb-0 small"></ol>
                        </nav>
                    </div>

                    {{-- Upload zone --}}
                    <div id="mp-dropzone" class="d-none px-4 py-3">
                        <div class="mp-drop-area">
                            <i class="fas fa-cloud-upload-alt fa-2x mb-2 mp-upload-icon"></i>
                            <h6 class="fw-bold mb-1">Arrastra y suelta archivos aquí</h6>
                            <p class="text-muted small mb-3">
                                Destino: <strong id="mp-upload-context">Raíz</strong> &mdash;
                                Máximo <strong>100 MB</strong> por archivo.
                            </p>
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                <label class="btn btn-sm mp-btn-primary mb-0">
                                    <i class="fas fa-upload me-1"></i>Seleccionar archivos
                                    <input id="mp-file-input" type="file" class="d-none" multiple>
                                </label>
                                <button id="mp-import-url-btn" type="button" class="btn btn-sm mp-btn-outline">
                                    <i class="fas fa-link me-1"></i>Desde URL
                                </button>
                            </div>
                            <div id="mp-import-url-form" class="d-none mt-3 mx-auto mp-import-form">
                                <div class="input-group input-group-sm">
                                    <input id="mp-import-url-input" type="url" class="form-control"
                                           placeholder="https://ejemplo.com/imagen.jpg">
                                    <button id="mp-import-url-submit" class="btn mp-btn-primary" type="button">
                                        <i class="fas fa-download me-1"></i>Importar
                                    </button>
                                    <button id="mp-import-url-cancel" class="btn mp-btn-outline" type="button">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="mp-upload-progress" class="mt-3 d-none mx-auto mp-progress-wrap">
                                <div class="progress mp-progress-thin mb-1">
                                    <div id="mp-progress-bar" class="progress-bar" role="progressbar"></div>
                                </div>
                                <small id="mp-upload-status" class="text-muted">Subiendo...</small>
                            </div>
                        </div>
                    </div>

                    {{-- Grid --}}
                    <div class="overflow-auto flex-grow-1 p-4 mp-body" id="mp-body">
                        <div id="mp-grid" class="row g-3"></div>
                    </div>

                </div>

                {{-- Detail panel --}}
                <div id="mp-detail" class="mp-detail-panel d-none flex-column">
                    <div class="p-4">

                        {{-- Header --}}
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <span class="mp-detail-title">Detalles</span>
                            <button type="button" id="mp-detail-close" class="mp-upload-close" aria-label="Cerrar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        {{-- Preview --}}
                        <div id="mp-detail-preview"
                             class="mp-detail-preview mb-4 d-flex align-items-center justify-content-center"></div>

                        {{-- Metadata --}}
                        <p class="mp-section-label mb-2">Metadata</p>
                        <dl class="mp-meta-table mb-4" id="mp-detail-meta-dl"></dl>
                        <div id="mp-detail-meta"></div>

                        {{-- Insert --}}
                        <button type="button" id="mp-detail-insert-btn" class="btn mp-btn-primary w-100 mb-2">
                            <i class="fas fa-check me-1"></i>Insertar archivo
                        </button>

                        {{-- Actions --}}
                        <div class="d-flex mp-detail-actions border-top mt-3 pt-3">
                            <button type="button" class="mp-detail-action" id="mp-copy-url-action" title="Copiar URL">
                                <i class="fas fa-link"></i>
                                <span>Copiar</span>
                            </button>
                            <button type="button" class="mp-detail-action" id="mp-download-action" title="Descargar">
                                <i class="fas fa-download"></i>
                                <span>Descargar</span>
                            </button>
                        </div>

                    </div>

                    {{-- Hidden fields (used by JS copy logic) --}}
                    <input id="mp-detail-url" type="hidden">
                    <textarea id="mp-detail-code" class="d-none"></textarea>

                    {{-- Copy buttons kept in DOM for JS binding --}}
                    <button id="mp-copy-url" type="button" class="d-none"></button>
                    <button id="mp-copy-code" type="button" class="d-none"></button>

                </div>

            </div>

            {{-- Footer --}}
            <div class="mp-footer d-flex align-items-center gap-3 px-4 py-3">
                <small id="mp-selected-name" class="text-muted fst-italic flex-grow-1 text-truncate">
                    Ningún archivo seleccionado
                </small>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="mp-insert-btn" class="btn btn-sm mp-btn-primary px-4" disabled>
                    <i class="fas fa-check me-1"></i>Insertar
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Modal: Nueva carpeta --}}
<div id="mp-folder-modal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fas fa-folder-plus me-2 mp-upload-icon"></i>Nueva carpeta
                </h6>
                <button type="button" class="mp-upload-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body py-3">
                <label class="form-label small fw-semibold mb-1">Nombre de la carpeta</label>
                <input id="mp-folder-name" type="text" class="form-control"
                       placeholder="Ej: Imágenes del blog" maxlength="100" autocomplete="off">
                <div id="mp-folder-error" class="text-danger small mt-1 d-none"></div>
            </div>
            <div class="modal-footer py-2 d-flex flex-column">
                <button type="button" id="mp-folder-save" class="btn btn-sm mp-btn-primary w-100 mb-2">
                    <i class="fas fa-folder-plus me-1"></i>Crear carpeta
                </button>
                <button type="button" class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

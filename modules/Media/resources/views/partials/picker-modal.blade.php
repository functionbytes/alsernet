{{-- Gestor de medios - Modal global (usado por window.MediaPicker) --}}
<div id="mp-modal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:92vw;">
        <div class="modal-content" style="height:88vh; display:flex; flex-direction:column;">

            {{-- Header --}}
            <div class="modal-header py-2 border-bottom">
                <h5 id="mp-title" class="modal-title fw-bold mb-0">
                    <i class="fas fa-images me-2 text-primary"></i>Gestor de medios
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body: sidebar + main + detail --}}
            <div class="d-flex flex-grow-1" style="min-height:0; overflow:hidden;">

                {{-- Sidebar --}}
                <div class="border-end bg-white d-flex flex-column" style="width:175px; flex-shrink:0;">
                    <div class="p-2">
                        {{-- Disco --}}
                        <select id="mp-disk-select" class="form-select form-select-sm w-100 mb-3">
                            @foreach(array_filter(config('filesystems.disks', []), fn($d) => ($d['driver'] ?? '') !== 'local' || ($d['root'] ?? '') === public_path('media'), ARRAY_FILTER_USE_BOTH) as $disk => $config)
                                <option value="{{ $disk }}">{{ ucfirst($disk) }} ({{ $config['driver'] ?? 'local' }})</option>
                            @endforeach
                        </select>

                        {{-- Tabs de navegación --}}
                        <p class="text-muted fw-semibold mb-2 px-1 text-uppercase"
                           style="font-size:10px; letter-spacing:.5px;">Explorar</p>
                        <ul class="nav flex-column gap-1" id="mp-sidebar-tabs">
                            <li class="nav-item">
                                <button class="nav-link active d-flex align-items-center gap-2 w-100 text-start px-2 py-2 rounded text-dark mp-tab-btn"
                                        data-view="all_media" style="font-size:13px;">
                                    <i class="fas fa-folder-open" style="width:14px;"></i>Mis archivos
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link d-flex align-items-center gap-2 w-100 text-start px-2 py-2 rounded text-dark mp-tab-btn"
                                        data-view="recent" style="font-size:13px;">
                                    <i class="fas fa-clock" style="width:14px;"></i>Recientes
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Main content --}}
                <div class="d-flex flex-column flex-grow-1" style="min-width:0; overflow:hidden;">

                    {{-- Toolbar: breadcrumb + search + acciones --}}
                    <div class="border-bottom px-3 py-2 d-flex align-items-center gap-2 bg-white">
                        <nav id="mp-breadcrumb" class="flex-grow-1">
                            <ol class="breadcrumb mb-0 small"></ol>
                        </nav>
                        <div class="input-group" style="max-width:220px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted" style="font-size:13px;"></i>
                            </span>
                            <input id="mp-search" type="text" class="form-control form-control-sm border-start-0 ps-0"
                                   placeholder="Buscar archivos...">
                        </div>
                        <button id="mp-new-folder-btn" type="button"
                                class="btn btn-sm btn-outline-secondary flex-shrink-0" title="Nueva carpeta">
                            <i class="fas fa-folder-plus"></i>
                        </button>
                        <button id="mp-upload-btn" type="button" class="btn btn-sm btn-primary px-3 flex-shrink-0">
                            <i class="fas fa-upload me-1"></i>Subir
                        </button>
                    </div>

                    {{-- Upload zone (oculta por defecto) --}}
                    <div id="mp-dropzone" class="d-none border-bottom">
                        <div class="upload-zone-modern rounded-0 p-4 w-100 bg-primary-subtle overflow-hidden shadow-none">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="fw-bold mb-1">Arrastra y suelta archivos aquí</h6>
                                    <p class="mb-3 text-muted small">
                                        Suelta tus archivos en esta zona o selecciónalos desde tu dispositivo.
                                        <span class="d-block mt-1">
                                            Destino: <strong id="mp-upload-context">Raíz</strong> &mdash;
                                            Máximo <strong>100 MB</strong> por archivo.
                                        </span>
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <label class="btn btn-primary px-3 mb-0">
                                            <i class="fas fa-upload me-1"></i>Seleccionar archivos
                                            <input id="mp-file-input" type="file" class="d-none" multiple>
                                        </label>
                                        <button id="mp-import-url-btn" type="button" class="btn btn-outline-primary px-3">
                                            <i class="fas fa-link me-1"></i>Importar desde URL
                                        </button>
                                    </div>
                                    {{-- Import URL form --}}
                                    <div id="mp-import-url-form" class="d-none mt-3" style="max-width:480px;">
                                        <div class="input-group">
                                            <input id="mp-import-url-input" type="url" class="form-control form-control-sm"
                                                   placeholder="https://ejemplo.com/imagen.jpg">
                                            <button id="mp-import-url-submit" class="btn btn-sm btn-primary" type="button">
                                                <i class="fas fa-download me-1"></i>Importar
                                            </button>
                                            <button id="mp-import-url-cancel" class="btn btn-sm btn-outline-secondary" type="button">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Progress --}}
                                    <div id="mp-upload-progress" class="mt-3 d-none" style="max-width:400px;">
                                        <div class="progress mb-1" style="height:6px;">
                                            <div id="mp-progress-bar" class="progress-bar bg-primary" style="width:0%;"></div>
                                        </div>
                                        <small id="mp-upload-status" class="text-muted">Subiendo...</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Files grid --}}
                    <div class="overflow-auto flex-grow-1 p-3 bg-light" id="mp-body">
                        <div id="mp-grid" class="row g-3"></div>
                    </div>
                </div>

                {{-- Detail panel --}}
                <div id="mp-detail" class="border-start d-none flex-column bg-white"
                     style="width:230px; flex-shrink:0; overflow-y:auto;">
                    <div class="p-3">
                        <div id="mp-detail-preview"
                             class="mb-3 rounded overflow-hidden border bg-light text-center"
                             style="min-height:130px; display:flex; align-items:center; justify-content:center;"></div>
                        <div id="mp-detail-meta" class="mb-3"></div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">URL del archivo</label>
                            <div class="input-group input-group-sm">
                                <input id="mp-detail-url" type="text" class="form-control form-control-sm"
                                       readonly style="font-size:11px;">
                                <button class="btn btn-outline-secondary" id="mp-copy-url"
                                        type="button" title="Copiar URL">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Código HTML</label>
                            <textarea id="mp-detail-code" class="form-control form-control-sm" rows="3"
                                      readonly style="font-family:monospace; font-size:10px; resize:none;"></textarea>
                            <button class="btn btn-outline-secondary btn-sm mt-1 w-100" id="mp-copy-code" type="button">
                                <i class="fas fa-code me-1"></i>Copiar código
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="modal-footer py-2 border-top">
                <small id="mp-selected-name" class="text-muted fst-italic flex-grow-1 text-truncate me-2">
                    Ningún archivo seleccionado
                </small>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="mp-insert-btn" class="btn btn-primary btn-sm" disabled>
                    <i class="fas fa-check me-1"></i>Insertar
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Modal: Nueva carpeta --}}
<div id="mp-folder-modal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fas fa-folder-plus me-2 text-warning"></i>Nueva carpeta
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <label class="form-label small fw-semibold mb-1">Nombre de la carpeta</label>
                <input id="mp-folder-name" type="text" class="form-control"
                       placeholder="Ej: Imágenes del blog" maxlength="100" autocomplete="off">
                <div id="mp-folder-error" class="text-danger small mt-1 d-none"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm w-100 mb-2"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="mp-folder-save" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-folder-plus me-1"></i>Crear carpeta
                </button>
            </div>
        </div>
    </div>
</div>

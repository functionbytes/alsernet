@extends('layouts.theme')

@push('styles')
<style>
.extraction-mode-card {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
    padding: 1rem;
}
.extraction-mode-card:hover {
    border-color: #90bb13;
    box-shadow: 0 2px 8px rgba(144,187,19,0.15);
}
input[name="extraction_mode"]:checked + .extraction-mode-card {
    border-color: #90bb13;
    background-color: rgba(144,187,19,0.05);
    box-shadow: 0 2px 8px rgba(144,187,19,0.2);
}
.mode-radio-label {
    display: block;
    margin: 0;
    cursor: pointer;
}
#uploadDropzone {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 2.5rem;
    text-align: center;
    transition: border-color 0.2s, background-color 0.2s;
    cursor: pointer;
}
#uploadDropzone.dragover {
    border-color: #90bb13;
    background-color: rgba(144,187,19,0.05);
}
#uploadDropzone:hover {
    border-color: #90bb13;
}
.file-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}
.file-item .file-progress {
    height: 4px;
    border-radius: 2px;
    overflow: hidden;
    background: #e9ecef;
    margin-top: 4px;
}
.file-item .file-progress-bar {
    height: 100%;
    background: #90bb13;
    transition: width 0.3s;
}
</style>
@endpush

@section('content')

<div class="card w-100">

    <form id="formSource" method="POST" action="{{ route('settings.suppliers.sources.store', $supplier->uid) }}">

        {{ csrf_field() }}

        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Crear fuente de datos</h5>
                    <p class="card-subtitle mb-0 mt-2">
                        Define una nueva fuente de datos para el proveedor <strong>{{ $supplier->label }}</strong>.
                    </p>
                </div>
                <a href="{{ route('settings.suppliers.sources.index', $supplier->uid) }}" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>

            {{-- ============================================================ --}}
            {{-- DETECCIÓN AUTOMÁTICA --}}
            {{-- ============================================================ --}}
            <div class="card mb-3 border-primary" id="detectionCard">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary">
                        <i class="fas fa-search me-2"></i>Detección automática de plataforma
                    </h6>
                    <p class="text-muted small mb-3">
                        Pega la URL del proveedor y detectaremos automáticamente el tipo de plataforma y configuraremos el formulario.
                    </p>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-link"></i></span>
                        <input type="url" id="detectUrl" class="form-control form-control-lg"
                               placeholder="https://www.proveedor.com" autocomplete="off">
                        <button type="button" class="btn btn-primary" id="btnDetect">
                            <i class="fas fa-search me-1"></i>Analizar
                        </button>
                    </div>

                    <div id="detectResult" class="mt-3" style="display:none;"></div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECCIÓN 1: Información básica --}}
            {{-- ============================================================ --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2 text-muted"></i>Información básica
                    </h6>
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nombre de la fuente <span class="text-danger">*</span></label>
                            <input type="text" name="label" class="form-control @error('label') is-invalid @enderror"
                                   value="{{ old('label') }}" required placeholder="Ej: FTP Productos">
                            <small class="form-text text-muted">Nombre descriptivo para identificar la fuente</small>
                            @error('label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tipo de fuente <span class="text-danger">*</span></label>
                            <select name="source_type" id="sourceType" class="form-control select2 @error('source_type') is-invalid @enderror" required>
                                <option value="">Seleccionar...</option>
                                <option value="website" {{ old('source_type') == 'website' ? 'selected' : '' }}>Sitio web (scraping)</option>
                                <option value="ftp" {{ old('source_type') == 'ftp' ? 'selected' : '' }}>FTP</option>
                                <option value="sftp" {{ old('source_type') == 'sftp' ? 'selected' : '' }}>SFTP</option>
                                <option value="api" {{ old('source_type') == 'api' ? 'selected' : '' }}>API REST</option>
                                <option value="upload" {{ old('source_type') == 'upload' ? 'selected' : '' }}>Carga manual</option>
                            </select>
                            <small class="form-text text-muted">Método de obtención de datos</small>
                            @error('source_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="2" placeholder="Descripción de la fuente y su uso">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notas de uso</label>
                            <textarea name="usage_notes" class="form-control @error('usage_notes') is-invalid @enderror"
                                      rows="2" placeholder="Ej: Usar solo para inspiración, no copiar directamente">{{ old('usage_notes') }}</textarea>
                            <small class="form-text text-muted">Restricciones o instrucciones especiales</small>
                            @error('usage_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECCIÓN 2: Modo de extracción --}}
            {{-- ============================================================ --}}
            <div class="card mb-3" id="extractionModeSection" style="display: none;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-sliders-h me-2 text-muted"></i>Modo de extracción
                    </h6>
                    <div class="row g-3">

                        <div class="col-md-6">
                            <input type="radio" name="extraction_mode" id="modeAi" value="ai"
                                   class="visually-hidden" {{ old('extraction_mode', 'ai') == 'ai' ? 'checked' : '' }}>
                            <label class="mode-radio-label" for="modeAi">
                                <div class="extraction-mode-card h-100">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="text-primary fs-3 pt-1">
                                            <i class="fas fa-robot"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <strong>Modo AI</strong>
                                                <span class="badge text-bg-success">Recomendado</span>
                                            </div>
                                            <p class="text-muted small mb-0">
                                                GPT-4o analiza automáticamente el contenido y extrae los datos de los productos
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="col-md-6">
                            <input type="radio" name="extraction_mode" id="modeManual" value="manual"
                                   class="visually-hidden" {{ old('extraction_mode') == 'manual' ? 'checked' : '' }}>
                            <label class="mode-radio-label" for="modeManual">
                                <div class="extraction-mode-card h-100">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="text-secondary fs-3 pt-1">
                                            <i class="fas fa-code"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <strong>Modo manual</strong>
                                            </div>
                                            <p class="text-muted small mb-0">
                                                Configura selectores CSS o patrones para extraer campos específicos
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECCIÓN 3: Configuración general --}}
            {{-- ============================================================ --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-cog me-2 text-muted"></i>Configuración general
                    </h6>
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Nivel de confianza</label>
                            <select name="trust_level" class="form-control select2 @error('trust_level') is-invalid @enderror">
                                <option value="high" {{ old('trust_level', 'medium') == 'high' ? 'selected' : '' }}>
                                    Alto — datos de alta calidad
                                </option>
                                <option value="medium" {{ old('trust_level', 'medium') == 'medium' ? 'selected' : '' }}>
                                    Medio — datos normales
                                </option>
                                <option value="low" {{ old('trust_level') == 'low' ? 'selected' : '' }}>
                                    Bajo — verificar manualmente
                                </option>
                            </select>
                            <small class="form-text text-muted">Fiabilidad de los datos extraídos</small>
                            @error('trust_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Prioridad</label>
                            <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror"
                                   value="{{ old('priority', 10) }}" min="1" max="100">
                            <small class="form-text text-muted">Orden de ejecución (1 = más alto)</small>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                       value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Activo</label>
                            </div>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECCIÓN 4: Configuración dinámica por tipo --}}
            {{-- ============================================================ --}}
            <div class="card mb-3" id="dynamicConfigSection" style="display: none;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2" id="dynamicConfigTitle">
                        <i class="fas fa-wrench me-2 text-muted"></i>Configuración específica
                    </h6>
                    <div id="dynamicConfigFields"></div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECCIÓN 5: Opciones avanzadas (colapsable) --}}
            {{-- ============================================================ --}}
            <div class="card mb-3">
                <div class="card-header p-0">
                    <button class="btn btn-link text-decoration-none text-dark w-100 text-start px-3 py-2"
                            type="button" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
                        <i class="fas fa-chevron-down me-2 text-muted small"></i>
                        <span class="fw-semibold">Opciones avanzadas</span>
                    </button>
                </div>
                <div class="collapse" id="advancedOptions">
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_retry" id="autoRetry"
                                           value="1" checked>
                                    <label class="form-check-label" for="autoRetry">Reintento automático</label>
                                </div>
                                <small class="text-muted">Reintentar automáticamente si falla la extracción</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Máximo de reintentos</label>
                                <input type="number" name="max_retries" class="form-control" value="3" min="0" max="10">
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="schedule_enabled"
                                           id="scheduleEnabled" value="1">
                                    <label class="form-check-label" for="scheduleEnabled">Programar extracción</label>
                                </div>
                                <small class="text-muted">Ejecutar automáticamente según una frecuencia</small>
                            </div>

                            <div class="col-md-6" id="scheduleFrequencyWrapper" style="display: none;">
                                <label class="form-label">Frecuencia</label>
                                <select name="schedule_frequency" class="form-control select2">
                                    <option value="hourly">Cada hora</option>
                                    <option value="every_6h">Cada 6 horas</option>
                                    <option value="daily" selected>Diaria</option>
                                    <option value="weekly">Semanal</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="card-footer">
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('settings.suppliers.sources.index', $supplier->uid) }}" class="btn btn-light">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Guardar fuente
                </button>
            </div>
        </div>

    </form>

</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const supplierUid = '{{ $supplier->uid }}';

    // ============================================================
    // Upload state
    // ============================================================
    const uploadedFiles = [];

    // ============================================================
    // Source type → sections visibility
    // ============================================================
    const TYPES_WITH_EXTRACTION_MODE = ['website'];
    const TYPES_WITH_UPLOAD = ['upload'];

    function onSourceTypeChange() {
        const type = $('#sourceType').val();

        // Extraction mode: only for website
        if (TYPES_WITH_EXTRACTION_MODE.includes(type)) {
            $('#extractionModeSection').show();
        } else {
            $('#extractionModeSection').hide();
        }

        // Dynamic config section
        if (type) {
            $('#dynamicConfigSection').show();
        } else {
            $('#dynamicConfigSection').hide();
            return;
        }

        renderDynamicConfig(type);
    }

    function getExtractionMode() {
        return $('input[name="extraction_mode"]:checked').val() || 'ai';
    }

    function renderDynamicConfig(type) {
        const mode = getExtractionMode();
        let html = '';

        switch (type) {
            case 'website':
                html = buildWebsiteConfig(mode);
                break;
            case 'ftp':
            case 'sftp':
                html = buildFtpConfig(type);
                break;
            case 'api':
                html = buildApiConfig();
                break;
            case 'upload':
                html = buildUploadConfig();
                break;
        }

        $('#dynamicConfigFields').html(html);

        if (type === 'upload') {
            initUploadDropzone();
        }
        if (type === 'ftp' || type === 'sftp') {
            initPasswordToggle('#toggleFtpPassword', '#ftpPassword');
        }
        if (type === 'api') {
            initPasswordToggle('#toggleApiKey', '#apiKey');
        }
    }

    // ============================================================
    // Build HTML: website
    // ============================================================
    function buildWebsiteConfig(mode) {
        if (mode === 'ai') {
            return `
                <div class="alert alert-info d-flex gap-2 align-items-start mb-3">
                    <i class="fas fa-robot mt-1"></i>
                    <div>
                        <strong>Modo AI activo.</strong>
                        GPT-4o extraerá automáticamente todos los productos de cada URL que configures.
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">URLs a procesar</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addUrlBtn">
                            <i class="fas fa-plus"></i>Agregar URL
                        </button>
                    </div>
                    <small class="text-muted d-block mb-3">Una URL por línea. GPT-4o analizará el contenido de cada página.</small>
                    <div id="aiUrlsContainer">
                        ${buildAiUrlRow(0)}
                    </div>
                </div>
                ${buildConnectionOptions()}
            `;
        }

        // Manual mode
        return `
            <div class="alert alert-secondary d-flex gap-2 align-items-start mb-3">
                <i class="fas fa-code mt-1"></i>
                <div>
                    <strong>Modo manual.</strong>
                    Define selectores CSS para extraer campos específicos de cada página.
                </div>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Configuraciones de URLs</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addUrlBtn">
                        <i class="fas fa-plus"></i>Agregar URL
                    </button>
                </div>
                <small class="text-muted d-block mb-3">Configura una URL y sus selectores CSS para cada página a procesar.</small>
                <div id="manualUrlsContainer">
                    ${buildManualUrlRow(0)}
                </div>
            </div>
            ${buildConnectionOptions()}
        `;
    }

    function buildAiUrlRow(index) {
        return `
            <div class="ai-url-row d-flex gap-2 mb-2" data-index="${index}">
                <input type="url" class="form-control" name="configuration[urls][${index}][url]"
                       placeholder="https://ejemplo.com/productos" required>
                <button type="button" class="btn btn-outline-danger btn-sm remove-url-btn flex-shrink-0"
                        style="display: none;" onclick="removeUrlRow(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }

    function buildManualUrlRow(index) {
        return `
            <div class="manual-url-item border rounded p-3 mb-3" data-index="${index}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-semibold">Configuración #${index + 1}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-url-btn"
                            style="display: none;" onclick="removeManualUrl(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">URL</label>
                        <input type="url" class="form-control" name="configuration[urls][${index}][url]"
                               placeholder="https://ejemplo.com/productos" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Selector de producto (CSS)</label>
                        <input type="text" class="form-control font-monospace" name="configuration[urls][${index}][product_selector]"
                               placeholder=".product-item, .card-product">
                        <small class="text-muted">Selector para identificar cada bloque de producto</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Referencia</label>
                        <input type="text" class="form-control font-monospace" name="configuration[urls][${index}][selectors][reference]"
                               placeholder=".sku, [data-ref]">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Nombre</label>
                        <input type="text" class="form-control font-monospace" name="configuration[urls][${index}][selectors][name]"
                               placeholder=".product-title, h2">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Precio</label>
                        <input type="text" class="form-control font-monospace" name="configuration[urls][${index}][selectors][price]"
                               placeholder=".price, [data-price]">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Descripción</label>
                        <input type="text" class="form-control font-monospace" name="configuration[urls][${index}][selectors][description]"
                               placeholder=".description, p.text">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Imagen (selector@atributo)</label>
                        <input type="text" class="form-control font-monospace" name="configuration[urls][${index}][selectors][image]"
                               placeholder="img.product@src">
                    </div>
                </div>
            </div>
        `;
    }

    function buildConnectionOptions() {
        return `
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label small">Timeout (segundos)</label>
                    <input type="number" class="form-control" name="configuration[timeout]" value="30" min="5" max="300">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Rate limit (req/min)</label>
                    <input type="number" class="form-control" name="configuration[rate_limit]" value="10" min="1" max="600">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">User Agent</label>
                    <input type="text" class="form-control" name="configuration[user_agent]"
                           placeholder="Mozilla/5.0...">
                </div>
            </div>
        `;
    }

    // ============================================================
    // Build HTML: FTP / SFTP
    // ============================================================
    function buildFtpConfig(type) {
        const defaultPort = type === 'sftp' ? 22 : 21;
        return `
            <div class="alert alert-info d-flex gap-2 align-items-start mb-3">
                <i class="fas fa-info-circle mt-1"></i>
                <div>Los archivos se descargarán y procesarán automáticamente con GPT-4o Vision.</div>
            </div>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Host <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="configuration[host]"
                           placeholder="ftp.ejemplo.com" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Puerto</label>
                    <input type="number" class="form-control" name="configuration[port]"
                           value="${defaultPort}" min="1" max="65535">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="configuration[username]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="configuration[password]"
                               id="ftpPassword" required>
                        <button class="btn btn-secondary" type="button" id="toggleFtpPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">Se almacenará de forma segura</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Directorio remoto</label>
                    <input type="text" class="form-control" name="configuration[directory]"
                           placeholder="/" value="/">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Patrón de archivos</label>
                    <input type="text" class="form-control font-monospace" name="configuration[file_pattern]"
                           placeholder="*.pdf,*.xlsx" value="*">
                    <small class="text-muted">Patrones separados por coma</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Tipos de archivo a procesar</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="configuration[file_types][]" value="pdf" id="ftPdf" checked>
                            <label class="form-check-label" for="ftPdf"><i class="fas fa-file-pdf text-danger me-1"></i>PDF</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="configuration[file_types][]" value="excel" id="ftExcel" checked>
                            <label class="form-check-label" for="ftExcel"><i class="fas fa-file-excel text-success me-1"></i>Excel</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="configuration[file_types][]" value="word" id="ftWord">
                            <label class="form-check-label" for="ftWord"><i class="fas fa-file-word text-primary me-1"></i>Word</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="configuration[file_types][]" value="image" id="ftImage">
                            <label class="form-check-label" for="ftImage"><i class="fas fa-file-image text-warning me-1"></i>Imágenes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="configuration[file_types][]" value="csv" id="ftCsv" checked>
                            <label class="form-check-label" for="ftCsv"><i class="fas fa-file-csv text-info me-1"></i>CSV</label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // ============================================================
    // Build HTML: API
    // ============================================================
    function buildApiConfig() {
        return `
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">URL base <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" name="configuration[base_url]"
                           placeholder="https://www.proveedor.com" required>
                    <small class="text-muted">Dominio raíz sin barra final</small>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Endpoint de productos</label>
                    <input type="text" class="form-control" name="configuration[endpoint]"
                           placeholder="/collections/all/products.json" value="/collections/all/products.json">
                    <small class="text-muted">Ruta relativa a la URL base</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Clave del array en JSON</label>
                    <input type="text" class="form-control" name="configuration[products_key]"
                           placeholder="products" value="products">
                    <small class="text-muted">Dejar vacío para auto-detectar</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Param. de página</label>
                    <input type="text" class="form-control" name="configuration[page_param]"
                           value="page" placeholder="page">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Param. de límite</label>
                    <input type="text" class="form-control" name="configuration[limit_param]"
                           value="limit" placeholder="limit">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Registros por página</label>
                    <input type="number" class="form-control" name="configuration[limit]"
                           value="250" min="1" max="500">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Timeout (seg)</label>
                    <input type="number" class="form-control" name="configuration[timeout]"
                           value="30" min="5" max="300">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Autenticación</label>
                    <select class="form-control" name="configuration[auth_type]" id="apiAuthType">
                        <option value="none">Sin autenticación</option>
                        <option value="bearer">Bearer Token</option>
                        <option value="basic">Basic Auth</option>
                        <option value="header">Header personalizado</option>
                    </select>
                </div>
                <div class="col-md-6" id="apiAuthTokenGroup">
                    <label class="form-label">Token / Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="configuration[auth_token]" id="apiToken">
                        <button class="btn btn-secondary" type="button" id="toggleApiKey">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // ============================================================
    // Build HTML: Upload
    // ============================================================
    function buildUploadConfig() {
        return `
            <p class="text-muted small mb-3">
                Sube archivos manualmente. Serán procesados automáticamente con GPT-4o Vision para extraer los datos de los productos.
            </p>
            <div id="uploadDropzone">
                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                <p class="mb-1 fw-semibold">Arrastra archivos aquí o haz clic para seleccionar</p>
                <p class="text-muted small mb-0">PDF, Excel, Word, imágenes, CSV — máximo 50 MB por archivo</p>
                <input type="file" id="uploadFileInput" multiple accept=".pdf,.xlsx,.xls,.docx,.doc,.jpg,.jpeg,.png,.webp,.csv"
                       class="visually-hidden">
            </div>
            <div id="uploadFileList" class="mt-3"></div>
            <p class="text-muted small mt-2 mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Los archivos se guardarán cuando hagas clic en "Guardar fuente".
            </p>
        `;
    }

    // ============================================================
    // Upload dropzone
    // ============================================================
    function initUploadDropzone() {
        const $zone = $('#uploadDropzone');
        const $input = $('#uploadFileInput');

        $zone.on('click', () => $input.trigger('click'));

        $zone.on('dragover dragenter', function (e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        $zone.on('dragleave drop', function (e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        $zone.on('drop', function (e) {
            const files = e.originalEvent.dataTransfer.files;
            handleFiles(files);
        });

        $input.on('change', function () {
            handleFiles(this.files);
            this.value = '';
        });
    }

    function handleFiles(files) {
        Array.from(files).forEach(file => addFileToList(file));
    }

    function getFileIcon(name) {
        const ext = name.split('.').pop().toLowerCase();
        const map = {
            pdf: '<i class="fas fa-file-pdf text-danger"></i>',
            xlsx: '<i class="fas fa-file-excel text-success"></i>',
            xls: '<i class="fas fa-file-excel text-success"></i>',
            docx: '<i class="fas fa-file-word text-primary"></i>',
            doc: '<i class="fas fa-file-word text-primary"></i>',
            csv: '<i class="fas fa-file-csv text-info"></i>',
            jpg: '<i class="fas fa-file-image text-warning"></i>',
            jpeg: '<i class="fas fa-file-image text-warning"></i>',
            png: '<i class="fas fa-file-image text-warning"></i>',
            webp: '<i class="fas fa-file-image text-warning"></i>',
        };
        return map[ext] || '<i class="fas fa-file text-secondary"></i>';
    }

    function formatBytes(bytes) {
        if (bytes < 1024) { return bytes + ' B'; }
        if (bytes < 1048576) { return (bytes / 1024).toFixed(1) + ' KB'; }
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function addFileToList(file) {
        const id = 'pending-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
        uploadedFiles.push({ id, file, status: 'pending' });

        const $item = $(`
            <div class="file-item" id="${id}">
                <span class="fs-5">${getFileIcon(file.name)}</span>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex justify-content-between">
                        <span class="text-truncate small fw-semibold">${file.name}</span>
                        <span class="text-muted small ms-2 flex-shrink-0">${formatBytes(file.size)}</span>
                    </div>
                    <div class="file-progress mt-1">
                        <div class="file-progress-bar" style="width: 0%"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0"
                        onclick="removeFileItem('${id}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);

        $('#uploadFileList').append($item);
        // Add hidden input so the file id is tracked
        $item.append(`<input type="hidden" name="pending_files[]" value="${id}">`);
    }

    window.removeFileItem = function (id) {
        const idx = uploadedFiles.findIndex(f => f.id === id);
        if (idx !== -1) { uploadedFiles.splice(idx, 1); }
        $(`#${id}`).remove();
    };

    // ============================================================
    // Password toggle
    // ============================================================
    function initPasswordToggle(btnSelector, inputSelector) {
        $(document).on('click', btnSelector, function () {
            const $input = $(inputSelector);
            const isPassword = $input.attr('type') === 'password';
            $input.attr('type', isPassword ? 'text' : 'password');
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });
    }

    // ============================================================
    // URL row management
    // ============================================================
    let urlIndex = 0;

    $(document).on('click', '#addUrlBtn', function () {
        urlIndex++;
        const type = $('#sourceType').val();
        const mode = getExtractionMode();
        let $row;

        if (type === 'website' && mode === 'ai') {
            $row = $(buildAiUrlRow(urlIndex));
            $('#aiUrlsContainer').append($row);
            updateRemoveButtons('.ai-url-row', '.remove-url-btn');
        } else {
            $row = $(buildManualUrlRow(urlIndex));
            $('#manualUrlsContainer').append($row);
            updateRemoveButtons('.manual-url-item', '.remove-url-btn');
        }
    });

    window.removeUrlRow = function (btn) {
        $(btn).closest('.ai-url-row').remove();
        updateRemoveButtons('.ai-url-row', '.remove-url-btn');
        renumberItems('.ai-url-row');
    };

    window.removeManualUrl = function (btn) {
        $(btn).closest('.manual-url-item').remove();
        updateRemoveButtons('.manual-url-item', '.remove-url-btn');
        renumberItems('.manual-url-item');
    };

    function updateRemoveButtons(rowSelector, btnSelector) {
        const $rows = $(rowSelector);
        $rows.find(btnSelector).toggle($rows.length > 1);
    }

    function renumberItems(selector) {
        $(selector + ' h6').each(function (i) {
            $(this).text('Configuración #' + (i + 1));
        });
    }

    // ============================================================
    // Schedule toggle
    // ============================================================
    $('#scheduleEnabled').on('change', function () {
        $('#scheduleFrequencyWrapper').toggle(this.checked);
    });

    // ============================================================
    // Extraction mode change — re-render dynamic config
    // ============================================================
    $('input[name="extraction_mode"]').on('change', function () {
        const type = $('#sourceType').val();
        if (type) { renderDynamicConfig(type); }
    });

    // ============================================================
    // Source type change
    // ============================================================
    $('#sourceType').on('change', onSourceTypeChange);

    // Initial render
    const initialType = $('#sourceType').val();
    if (initialType) { onSourceTypeChange(); }

    // ============================================================
    // Form submission
    // ============================================================
    $('#formSource').on('submit', function (e) {
        e.preventDefault();

        const $btn = $(this).find('button[type="submit"]');
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Fuente creada');
                    setTimeout(function () {
                        window.location.href = '{{ route("settings.suppliers.sources.index", $supplier->uid) }}';
                    }, 1000);
                } else {
                    toastr.error(response.message, 'Error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Error al crear la fuente';
                toastr.error(message, 'Error');
                $btn.prop('disabled', false).html(originalHtml);

                if (xhr.responseJSON?.errors) {
                    $.each(xhr.responseJSON.errors, function (key, messages) {
                        $('[name="' + key + '"]').addClass('is-invalid')
                            .after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                }
            }
        });
    });

}());

// ============================================================
// Auto-detection
// ============================================================
(function () {
    'use strict';

    const detectUrl   = '{{ route("settings.suppliers.sources.detect", $supplier->uid) }}';
    const CONFIDENCE_ICONS = { high: '✅', medium: '⚠️', low: 'ℹ️', none: '❌' };
    const CONFIDENCE_CLASSES = { high: 'success', medium: 'warning', low: 'info', none: 'danger' };

    let detectTimer = null;

    // Trigger on button click
    $('#btnDetect').on('click', function () {
        runDetection();
    });

    // Trigger on Enter key in input
    $('#detectUrl').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); runDetection(); }
    });

    // Auto-trigger when user pastes a full URL (debounced)
    $('#detectUrl').on('input', function () {
        const val = $(this).val().trim();
        if (val.length < 10 || !val.includes('.')) return;
        clearTimeout(detectTimer);
        detectTimer = setTimeout(runDetection, 900);
    });

    function runDetection() {
        const url = $('#detectUrl').val().trim();
        if (!url) { toastr.warning('Introduce una URL primero'); return; }

        const $btn = $('#btnDetect');
        const $result = $('#detectResult');

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Analizando...');
        $result.hide();

        $.ajax({
            url: detectUrl,
            method: 'POST',
            data: { url: url, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                applyDetectionResult(data);
                renderResultBadge(data);
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.error || 'Error al analizar la URL';
                $result.html('<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle me-2"></i>' + msg + '</div>').show();
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-search me-1"></i>Analizar');
            }
        });
    }

    function applyDetectionResult(data) {
        // 1. Set source type
        if (data.source_type) {
            $('#sourceType').val(data.source_type).trigger('change');
            if (typeof onSourceTypeChange === 'function') onSourceTypeChange();
        }

        // 2. Pre-fill configuration fields based on detected config
        const cfg = data.config || {};
        setTimeout(function () {
            if (data.source_type === 'api') {
                $('[name="configuration[base_url]"]').val(cfg.base_url || '');
                $('[name="configuration[endpoint]"]').val(cfg.endpoint || '');
                $('[name="configuration[page_param]"]').val(cfg.page_param || 'page');
                $('[name="configuration[limit_param]"]').val(cfg.limit_param || 'limit');
                $('[name="configuration[limit]"]').val(cfg.limit || 250);
                $('[name="configuration[products_key]"]').val(cfg.products_key || 'products');
            } else if (data.source_type === 'website') {
                // Clear and add detected URL
                if (cfg.urls && cfg.urls.length) {
                    $('#aiUrlsContainer').empty();
                    cfg.urls.forEach(function (item, i) {
                        const $row = $(buildAiUrlRow(i));
                        $row.find('input[type="url"]').val(item.url || item || '');
                        $('#aiUrlsContainer').append($row);
                    });
                }
            }
            // Timeout stats
            if (cfg.timeout) $('[name="configuration[timeout]"]').val(cfg.timeout);
        }, 100);

        // 3. Auto-fill name if empty
        if (!$('[name="label"]').val() && data.platform) {
            $('[name="label"]').val(data.platform + ' — ' + ($('#detectUrl').val().replace(/https?:\/\//, '').split('/')[0]));
        }
    }

    function renderResultBadge(data) {
        const $result = $('#detectResult');
        const icon = CONFIDENCE_ICONS[data.confidence] || 'ℹ️';
        const cls  = CONFIDENCE_CLASSES[data.confidence] || 'secondary';
        const total = data.stats?.total_products ? ' <strong>' + data.stats.total_products + ' productos</strong> disponibles.' : '';

        const typeLabels = { api: 'API REST', website: 'Web scraping', ftp: 'FTP', sftp: 'SFTP', upload: 'Carga manual' };
        const typeLabel  = typeLabels[data.source_type] || data.source_type;

        $result.html(
            '<div class="alert alert-' + cls + ' mb-0 d-flex align-items-start gap-3">' +
            '  <span class="fs-4">' + icon + '</span>' +
            '  <div>' +
            '    <strong>' + data.platform + '</strong> detectado &mdash; Tipo configurado como <strong>' + typeLabel + '</strong>.' +
            (total ? ' ' + total : '') +
            '    <div class="text-muted small mt-1">' + (data.description || '') + '</div>' +
            '  </div>' +
            '</div>'
        ).show();
    }

    // Helper defined in outer scope (build URL row) — referenced from prefillConfig
    function buildAiUrlRow(index) {
        return '<div class="input-group mb-2 ai-url-row">' +
            '<input type="url" name="configuration[urls][' + index + '][url]" class="form-control" placeholder="https://ejemplo.com/productos" required>' +
            '<button type="button" class="btn btn-outline-danger remove-url-row"><i class="fas fa-times"></i></button>' +
            '</div>';
    }
}());
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Detector de Plataforma')

@push('styles')
<style>
    .font-monospace {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
        font-size: 0.85rem;
        white-space: pre-wrap;
        word-break: break-word;
    }

    #configContent pre {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.375rem;
        max-height: 300px;
        overflow-y: auto;
    }

    .input-group-lg .form-control {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }

    .fw-500 {
        font-weight: 500;
    }
</style>
@endpush

@section('content')

<div class="card w-100">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h5 class="mb-0">
                    Detector de plataforma de proveedores
                </h5>
                <p class="card-subtitle mb-0 mt-2">
                    Analiza una URL para detectar automáticamente el tipo de plataforma (Shopify, WooCommerce, PrestaShop, Magento u otro)
                </p>
            </div>
        </div>

    </div>

    <div class="card-body p-4">

        <!-- URL Input Form -->
        <div class="mb-5">
            <label for="urlInput" class="form-label fw-500 mb-2">
                URL del Proveedor <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-lg">
                <input
                    type="url"
                    id="urlInput"
                    class="form-control"
                    placeholder="Ej: https://www.example.com"
                    autocomplete="off"
                >
                <button
                    id="analyzeBtn"
                    class="btn btn-primary px-4"
                    type="button"
                    disabled
                >
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <small class="text-muted d-block mt-2">
                Introduce una URL válida. Se detectará automáticamente el tipo de plataforma.
            </small>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="d-none text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Analizando...</span>
            </div>
            <p class="text-muted">Analizando URL...</p>
        </div>

        <!-- Results Container -->
        <div id="resultsContainer" class="d-none">
            <!-- Detection Result Card -->
            <div class="alert alert-info border-2 p-4 mb-4" id="detectionResult">
                <!-- Platform Badge -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="flex-grow-1">
                        <h5 id="platformName" class="mb-1"></h5>
                        <p id="platformDescription" class="text-muted mb-0"></p>
                    </div>
                    <div>
                        <span id="confidenceBadge" class="badge"></span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Detection Details -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-layer-group text-secondary"></i>
                            <div>
                                <small class="text-muted d-block">Tipo de Fuente</small>
                                <span id="sourceType" class="fw-500"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-bar-chart text-secondary"></i>
                            <div>
                                <small class="text-muted d-block">Confianza</small>
                                <span id="confidence" class="fw-500"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats if available -->
                <div id="statsSection" class="mt-4 pt-3 border-top d-none">
                    <h6 class="mb-3">Estadísticas Detectadas</h6>
                    <div id="statsContent"></div>
                </div>
            </div>

            <!-- Configuration Details -->
            <div class="card bg-light border-0 mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">
                        Configuración Detectada
                    </h6>
                </div>
                <div class="card-body">
                    <div id="configContent" class="font-monospace text-sm"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button
                    id="newSearchBtn"
                    class="btn btn-secondary"
                    type="button"
                >
                    <i class="fas fa-redo me-2"></i>
                    Nueva Búsqueda
                </button>
                <a
                    id="createSourceLink"
                    href="{{ route('settings.suppliers.index') }}"
                    class="btn btn-primary"
                >
                    <i class="fas fa-plus me-2"></i>
                    Ir a Proveedores
                </a>
            </div>
        </div>

        <!-- Error State -->
        <div id="errorState" class="d-none">
            <div class="alert alert-danger" role="alert">
                <h6 class="alert-heading mb-2">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error en la Detección
                </h6>
                <p id="errorMessage" class="mb-0"></p>
            </div>
            <button
                id="tryAgainBtn"
                class="btn btn-outline-danger mt-3"
                type="button"
            >
                <i class="fas fa-redo me-2"></i>
                Intentar de Nuevo
            </button>
        </div>

        <!-- Info Card -->
        <div id="infoCard" class="card border-0 bg-light mt-4">
            <div class="card-body">
                <h6 class="card-title mb-1">Tipos de fuente soportados</h6>
                <p class="text-muted small mb-3">El detector identifica automáticamente los dos primeros tipos. Los demás se configuran manualmente al crear la fuente.</p>
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="d-flex gap-2">

                            <div>
                                <small class="fw-500 d-block">API REST <span class="badge bg-success-subtle text-success border border-success ms-1" style="font-size:.65rem;">Auto-detectable</span></small>
                                <small class="text-muted">Conecta directamente con la API de la tienda. Compatible con <strong>Shopify</strong>, <strong>WooCommerce</strong>, <strong>PrestaShop</strong> y <strong>Magento</strong>. Ofrece la mayor fiabilidad y velocidad de extracción.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="d-flex gap-2">
                            <div>
                                <small class="fw-500 d-block">Web Scraping <span class="badge bg-success-subtle text-success border border-success ms-1" style="font-size:.65rem;">Auto-detectable</span></small>
                                <small class="text-muted">Extrae productos directamente del HTML de la página web usando IA. Útil cuando el proveedor no expone una API pública. Soporta sitios estáticos y dinámicos (Shopify, WooCommerce, PrestaShop, Magento vía HTML).</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="d-flex gap-2">
                            <div>
                                <small class="fw-500 d-block">FTP / SFTP</small>
                                <small class="text-muted">Descarga ficheros de catálogo (CSV, XML, Excel) desde un servidor FTP o SFTP del proveedor. Ideal para proveedores que actualizan su catálogo periódicamente mediante transferencia de ficheros.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="d-flex gap-2">
                            <div>
                                <small class="fw-500 d-block">Archivo manual</small>
                                <small class="text-muted">Permite subir un fichero de catálogo directamente (CSV, Excel, XML). Pensado para importaciones puntuales o proveedores que envían el catálogo por correo electrónico u otros medios.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById('urlInput');
    const analyzeBtn = document.getElementById('analyzeBtn');
    const loadingState = document.getElementById('loadingState');
    const resultsContainer = document.getElementById('resultsContainer');
    const errorState = document.getElementById('errorState');
    const infoCard = document.getElementById('infoCard');
    const newSearchBtn = document.getElementById('newSearchBtn');
    const tryAgainBtn = document.getElementById('tryAgainBtn');

    // Enable/disable analyze button based on input
    urlInput.addEventListener('input', function() {
        analyzeBtn.disabled = !this.value.trim();
    });

    // Analyze on button click
    analyzeBtn.addEventListener('click', analyzeUrl);

    // Enter key support
    urlInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !analyzeBtn.disabled) {
            analyzeUrl();
        }
    });

    // New search
    newSearchBtn.addEventListener('click', resetForm);
    tryAgainBtn.addEventListener('click', resetForm);

    function analyzeUrl() {
        const url = urlInput.value.trim();

        if (!url) {
            showError('Por favor ingresa una URL válida');
            return;
        }

        // Show loading state
        loadingState.classList.remove('d-none');
        resultsContainer.classList.add('d-none');
        errorState.classList.add('d-none');
        infoCard.classList.add('d-none');
        analyzeBtn.disabled = true;

        // Send request
        fetch('{{ route("settings.suppliers.detect.analyze") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ url })
        })
        .then(response => response.json())
        .then(data => {
            loadingState.classList.add('d-none');

            if (data.error) {
                showError(data.error);
            } else {
                displayResults(data, url);
            }
        })
        .catch(error => {
            loadingState.classList.add('d-none');
            showError('Error en la detección: ' + error.message);
        })
        .finally(() => {
            analyzeBtn.disabled = false;
        });
    }

    function displayResults(data, url) {
        // Update platform info
        document.getElementById('platformName').textContent = data.platform;
        document.getElementById('platformDescription').textContent = data.description;
        document.getElementById('sourceType').textContent = data.source_type;
        document.getElementById('confidence').textContent = capitalizeFirst(data.confidence);

        // Update confidence badge
        const badgeEl = document.getElementById('confidenceBadge');
        const badgeClass = data.confidence === 'high' ? 'bg-success' :
                          data.confidence === 'medium' ? 'bg-warning' : 'bg-secondary';
        badgeEl.className = `badge ${badgeClass}`;
        badgeEl.textContent = capitalizeFirst(data.confidence);

        // Display stats if available
        const statsSection = document.getElementById('statsSection');
        if (data.stats && Object.keys(data.stats).length > 0) {
            const statsContent = document.getElementById('statsContent');
            statsContent.innerHTML = Object.entries(data.stats)
                .map(([key, value]) => {
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                    return `
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>${label}</span>
                            <strong>${value.toLocaleString()}</strong>
                        </div>
                    `;
                })
                .join('');
            statsSection.classList.remove('d-none');
        } else {
            statsSection.classList.add('d-none');
        }

        // Display configuration
        displayConfig(data.config);

        // Store data for source creation
        window.detectionData = data;
        window.detectedUrl = url;

        // Update create source link
        document.getElementById('createSourceLink').addEventListener('click', function(e) {
            e.preventDefault();
            // Store in session storage for the create form to use
            sessionStorage.setItem('detectionData', JSON.stringify(data));
            sessionStorage.setItem('detectionUrl', url);
            // Redirect to suppliers page
            window.location.href = this.href;
        });

        // Show results
        resultsContainer.classList.remove('d-none');
        errorState.classList.add('d-none');
        infoCard.classList.add('d-none');
    }

    function displayConfig(config) {
        const configContent = document.getElementById('configContent');
        configContent.innerHTML = `<pre class="mb-0">${JSON.stringify(config, null, 2)}</pre>`;
    }

    function showError(message) {
        document.getElementById('errorMessage').textContent = message;
        errorState.classList.remove('d-none');
        resultsContainer.classList.add('d-none');
        loadingState.classList.add('d-none');
        infoCard.classList.remove('d-none');
    }

    function resetForm() {
        urlInput.value = '';
        urlInput.focus();
        resultsContainer.classList.add('d-none');
        errorState.classList.add('d-none');
        loadingState.classList.add('d-none');
        infoCard.classList.remove('d-none');
        analyzeBtn.disabled = true;
        sessionStorage.removeItem('detectionData');
        sessionStorage.removeItem('detectionUrl');
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Auto-focus input
    urlInput.focus();
});
</script>
@endpush

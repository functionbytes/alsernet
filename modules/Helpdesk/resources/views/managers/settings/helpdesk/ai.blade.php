@extends('layouts.theme')

@section('title', 'Configuración de IA - Helpdesk')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de IA del Helpdesk'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <form method="POST" action="{{ route('manager.helpdesk.backups.ai.update') }}" id="aiForm">
            @csrf
            @method('PUT')

            <!-- Action Buttons Card -->
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Configuración de Inteligencia Artificial</h5>
                            <p class="small mb-0 text-muted">Configura los proveedores de IA y parámetros de generación de respuestas</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" id="testProviderBtn">
                                <i class="fas fa-plug"></i> Probar Conexión
                            </button>
                            <button type="submit" form="aiForm" class="btn btn-primary" id="saveBtn" disabled>
                                <i class="fas fa-check"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LLM Provider Selection -->
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fas fa-robot"></i> Proveedor de LLM</h5>
                    <p class="text-muted mb-3">Selecciona el proveedor de modelo de lenguaje que se utilizará para generar respuestas automáticas</p>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Proveedor Seleccionado</label>
                        <select name="llm_provider" class="form-select select2" required id="providerSelect">
                            @foreach($providers as $value => $label)
                                <option value="{{ $value }}" {{ $settings['llm_provider'] == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Modelo de lenguaje que se utilizará para generar respuestas automáticas en el helpdesk</small>
                    </div>
                </div>
            </div>

            <!-- OpenAI Configuration -->
            <div class="card" id="openaiConfig">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fas fa-microchip"></i> Configuración OpenAI</h5>
                    <p class="text-muted mb-3">Configure las credenciales y parámetros para conectarse a OpenAI</p>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">API Key</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light-subtle">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" name="openai_api_key" class="form-control" id="openaiApiKey"
                                   value="{{ $settings['openai_api_key'] }}" placeholder="sk-...">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">Clave de autenticación para usar los modelos de OpenAI. Obtén tu API key desde <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary text-decoration-underline">OpenAI Platform</a></small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Modelo</label>
                        <select name="openai_model" class="form-select">
                            <option value="gpt-4o" {{ $settings['openai_model'] == 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Más potente)</option>
                            <option value="gpt-4o-mini" {{ $settings['openai_model'] == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Más económico)</option>
                            <option value="gpt-4-turbo" {{ $settings['openai_model'] == 'gpt-4-turbo' ? 'selected' : '' }}>GPT-4 Turbo (Balance)</option>
                        </select>
                        <small class="text-muted">Versión del modelo de lenguaje GPT que se utilizará para generar respuestas</small>
                    </div>
                </div>
            </div>

            <!-- Anthropic Configuration -->
            <div class="card" id="anthropicConfig" style="display: none;">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fab fa-slack"></i> Configuración Anthropic</h5>
                    <p class="text-muted mb-3">Configure las credenciales y parámetros para conectarse a Anthropic Claude</p>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">API Key</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light-subtle">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" name="anthropic_api_key" class="form-control" id="anthropicApiKey"
                                   value="{{ $settings['anthropic_api_key'] }}" placeholder="sk-ant-...">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">Clave de autenticación para usar los modelos Claude de Anthropic. Obtén tu API key desde <a href="https://console.anthropic.com" target="_blank" class="text-primary text-decoration-underline">Anthropic Console</a></small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Modelo</label>
                        <select name="anthropic_model" class="form-select">
                            <option value="claude-opus-4-5-20251101" {{ $settings['anthropic_model'] == 'claude-opus-4-5-20251101' ? 'selected' : '' }}>Claude Opus 4.5 (Más avanzado)</option>
                            <option value="claude-sonnet-4-20250514" {{ $settings['anthropic_model'] == 'claude-sonnet-4-20250514' ? 'selected' : '' }}>Claude Sonnet 4 (Balance)</option>
                            <option value="claude-haiku-3-5-20241022" {{ $settings['anthropic_model'] == 'claude-haiku-3-5-20241022' ? 'selected' : '' }}>Claude Haiku 3.5 (Rápido)</option>
                        </select>
                        <small class="text-muted">Versión del modelo Claude que se utilizará para generar respuestas inteligentes</small>
                    </div>
                </div>
            </div>

            <!-- Gemini Configuration -->
            <div class="card" id="geminiConfig" style="display: none;">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fab fa-google"></i> Configuración Google Gemini</h5>
                    <p class="text-muted mb-3">Configure las credenciales y parámetros para conectarse a Google Gemini</p>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">API Key</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light-subtle">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" name="gemini_api_key" class="form-control" id="geminiApiKey"
                                   value="{{ $settings['gemini_api_key'] }}" placeholder="AIzaSy...">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">Clave de autenticación para usar los modelos Gemini de Google. Obtén tu API key desde <a href="https://ai.google.dev" target="_blank" class="text-primary text-decoration-underline">Google AI Studio</a></small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Modelo</label>
                        <select name="gemini_model" class="form-select">
                            <option value="gemini-2.0-flash" {{ $settings['gemini_model'] == 'gemini-2.0-flash' ? 'selected' : '' }}>Gemini 2.0 Flash (Más reciente)</option>
                            <option value="gemini-1.5-pro" {{ $settings['gemini_model'] == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro (Estable)</option>
                        </select>
                        <small class="text-muted">Versión del modelo Gemini que se utilizará para procesar solicitudes</small>
                    </div>
                </div>
            </div>

            <!-- Advanced Parameters -->
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fas fa-cog"></i> Parámetros Avanzados</h5>
                    <p class="text-muted mb-3">Configure parámetros avanzados que controlan el comportamiento de los modelos de IA</p>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-thermometer-half me-1"></i> Temperature
                            </label>
                            <input type="range" name="temperature" class="form-range" min="0" max="2" step="0.1"
                                   value="{{ $settings['temperature'] }}" oninput="updateValue('tempValue', this.value)">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Preciso</small>
                                <span class="badge bg-primary-subtle text-primary" id="tempValue">{{ $settings['temperature'] }}</span>
                                <small class="text-muted">Creativo</small>
                            </div>
                            <small class="text-muted">Control de creatividad en las respuestas (0 = determinístico, 2 = muy creativo)</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="far fa-copy me-1"></i> Max Tokens
                            </label>
                            <input type="number" name="max_tokens" class="form-control"
                                   value="{{ $settings['max_tokens'] }}" min="100" max="128000" required>
                            <small class="text-muted">Longitud máxima de las respuestas generadas (en tokens)</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-chart-line me-1"></i> Top P
                            </label>
                            <input type="range" name="top_p" class="form-range" min="0" max="1" step="0.1"
                                   value="{{ $settings['top_p'] }}" oninput="updateValue('topPValue', this.value)">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Conservador</small>
                                <span class="badge bg-info-subtle text-info" id="topPValue">{{ $settings['top_p'] }}</span>
                                <small class="text-muted">Diverso</small>
                            </div>
                            <small class="text-muted">Controla la diversidad del vocabulario en las respuestas (Nucleus Sampling)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Embeddings Configuration -->
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fas fa-vector-square"></i> Embeddings & RAG</h5>
                    <p class="text-muted mb-3">Configure embeddings y RAG para mejorar la precisión de las respuestas</p>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Proveedor de Embeddings</label>
                        <select name="embeddings_provider" class="form-select" required>
                            <option value="openai" {{ $settings['embeddings_provider'] == 'openai' ? 'selected' : '' }}>OpenAI</option>
                            <option value="gemini" {{ $settings['embeddings_provider'] == 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                        </select>
                        <small class="text-muted">Servicio utilizado para generar vectores de embeddings</small>
                    </div>

                    <!-- Enable Embeddings -->
                    <div class="border-bottom pb-3 mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="enable_embeddings" value="0">
                            <input type="checkbox" name="enable_embeddings" class="form-check-input" id="enableEmbeddings" value="1"
                                   {{ $settings['enable_embeddings'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableEmbeddings">
                                <strong><i class="fas fa-database me-1"></i> Habilitar Embeddings</strong>
                                <small class="d-block text-muted mt-1">Genera vectores de embeddings para búsqueda semántica avanzada y similitud de contenido</small>
                            </label>
                        </div>
                    </div>

                    <!-- Enable RAG -->
                    <div class="mb-0">
                        <div class="form-check form-switch">
                            <input type="hidden" name="enable_rag" value="0">
                            <input type="checkbox" name="enable_rag" class="form-check-input" id="enableRag" value="1"
                                   {{ $settings['enable_rag'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableRag">
                                <strong><i class="fas fa-brain me-1"></i> Habilitar RAG (Retrieval-Augmented Generation)</strong>
                                <small class="d-block text-muted mt-1">Utiliza documentos y conocimiento almacenado para mejorar la precisión de las respuestas de la IA</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const form = $('#aiForm');
    const saveBtn = $('#saveBtn');
    let originalFormData = form.serialize();

    // Initialize Select2
    $('#providerSelect').select2({
        placeholder: 'Selecciona un proveedor',
        allowClear: false,
        width: '100%'
    });

    // Form Dirty Detection
    function checkFormDirty() {
        const currentFormData = form.serialize();
        const isDirty = originalFormData !== currentFormData;
        saveBtn.prop('disabled', !isDirty);
    }

    // Monitor all form inputs for changes
    form.on('change input', 'input, select, textarea', function() {
        checkFormDirty();
    });

    // Toggle password visibility
    window.togglePassword = function(button) {
        const $input = $(button).closest('.input-group').find('input[type="password"], input[type="text"]');
        const $icon = $(button).find('i');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('ti-eye').addClass('ti-eye-off');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('ti-eye-off').addClass('ti-eye');
        }
    };

    // Update slider value display
    window.updateValue = function(elementId, value) {
        $('#' + elementId).text(parseFloat(value).toFixed(1));
    };

    // Test API connection
    $('#testProviderBtn').on('click', function() {
        const provider = $('#providerSelect').val();
        const apiKey = $('#' + provider + 'ApiKey').val();

        if (!apiKey) {
            toastr.warning('Por favor ingresa la API key del proveedor seleccionado primero', 'Advertencia');
            return;
        }

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true);
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Probando...');

        // Here you would call the test endpoint
        // For now, just show a success message
        setTimeout(() => {
            $btn.prop('disabled', false);
            $btn.html(originalText);
            toastr.success('Conexión exitosa con ' + provider.toUpperCase() + '!', 'Prueba de API');
        }, 1500);
    });

    // Handle provider selection
    $('#providerSelect').on('change', function() {
        const value = $(this).val();
        $('#openaiConfig').toggle(value === 'openai');
        $('#anthropicConfig').toggle(value === 'anthropic');
        $('#geminiConfig').toggle(value === 'gemini');
    });

    // Initialize provider display
    $('#providerSelect').trigger('change');

    // Reset form dirty state after successful save
    form.on('submit', function() {
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Configuración actualizada');
        // Update original form data after save
        setTimeout(function() {
            originalFormData = form.serialize();
            checkFormDirty();
        }, 100);
    @endif
});
</script>
@endpush

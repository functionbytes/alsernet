@extends('layouts.theme')

@section('title', 'Auto-respuesta con IA')

@section('content')

    @include('core::components.card', ['title' => 'Auto-respuesta con IA'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda: formulario --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.reviews.ai.update') }}" method="POST" id="aiSettingsForm">
                    @csrf

                    <div class="card">
                        <div class="card-body">

                            {{-- Estado --}}
                            <h6 class="fw-bold text-dark mb-1">Estado del servicio</h6>
                            <p class="text-muted mb-3">Activa la generación de borradores de respuesta mediante IA en cada reseña.</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="is_enabled"
                                       name="is_enabled" value="1"
                                       {{ $aiSettings?->is_enabled ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_enabled">
                                    Activar auto-respuesta con IA
                                </label>
                            </div>
                            <small class="text-muted d-block mb-0">Cuando está activo aparecerá el botón "Generar con IA" en cada reseña.</small>

                        </div>

                        <div id="ai-enabled-fields" class="{{ $aiSettings?->is_enabled ? '' : 'd-none' }}">

                        <hr class="my-0">

                        {{-- Proveedor y modelo --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Proveedor y modelo</h6>
                            <p class="text-muted mb-3">Selecciona el proveedor de IA y el modelo que generará las respuestas.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="provider" class="form-label fw-semibold">Proveedor</label>
                                    <select class="form-select select2" id="provider" name="provider" required>
                                        <option value="anthropic" {{ ($aiSettings?->provider ?? 'anthropic') === 'anthropic' ? 'selected' : '' }}>Anthropic (Claude)</option>
                                        <option value="openai"    {{ ($aiSettings?->provider ?? '') === 'openai' ? 'selected' : '' }}>OpenAI (GPT)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="model" class="form-label fw-semibold">Modelo</label>
                                    <select class="form-select select2" id="model" name="model" required>
                                        {{-- Populated by JS --}}
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- API Key --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">API Key</h6>
                            <p class="text-muted mb-3">Clave de autenticación del proveedor seleccionado. Se almacena encriptada.</p>

                            <label for="api_key" class="form-label fw-semibold">Clave de API</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="api_key" name="api_key"
                                       value="{{ $aiSettings?->api_key ? '••••••••••••' : '' }}"
                                       placeholder="Ingresa tu API key...">
                                <button type="button" class="btn btn-secondary" id="toggle-api-key" title="Mostrar / Ocultar">
                                    <i class="fas fa-eye" id="toggle-icon"></i>
                                </button>
                                <button type="button" class="btn btn-primary" id="test-connection" title="Probar conexión">
                                    <i class="fas fa-plug"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">Deja el campo en blanco para conservar la key guardada.</small>
                            <div id="test-result" class="mt-2 d-none"></div>
                        </div>

                        <hr class="my-0">

                        {{-- Comportamiento --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Comportamiento de las respuestas</h6>
                            <p class="text-muted mb-3">Define el tono, idioma y longitud máxima de las respuestas generadas.</p>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="tone" class="form-label fw-semibold">Tono</label>
                                    <select class="form-select select2" id="tone" name="tone" required>
                                        <option value="professional" {{ ($aiSettings?->tone ?? 'professional') === 'professional' ? 'selected' : '' }}>Profesional</option>
                                        <option value="friendly"     {{ ($aiSettings?->tone ?? '') === 'friendly'     ? 'selected' : '' }}>Amigable</option>
                                        <option value="formal"       {{ ($aiSettings?->tone ?? '') === 'formal'       ? 'selected' : '' }}>Formal</option>
                                        <option value="casual"       {{ ($aiSettings?->tone ?? '') === 'casual'       ? 'selected' : '' }}>Casual</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="language" class="form-label fw-semibold">Idioma de respuesta</label>
                                    <select class="form-select select2" id="language" name="language" required>
                                        <option value="es" {{ ($aiSettings?->language ?? 'es') === 'es' ? 'selected' : '' }}>Español</option>
                                        <option value="en" {{ ($aiSettings?->language ?? '') === 'en' ? 'selected' : '' }}>Inglés</option>
                                        <option value="pt" {{ ($aiSettings?->language ?? '') === 'pt' ? 'selected' : '' }}>Portugués</option>
                                        <option value="fr" {{ ($aiSettings?->language ?? '') === 'fr' ? 'selected' : '' }}>Francés</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="max_tokens" class="form-label fw-semibold">Longitud máxima (tokens)</label>
                                    <input type="number" class="form-control" id="max_tokens" name="max_tokens"
                                           value="{{ $aiSettings?->max_tokens ?? 500 }}"
                                           min="100" max="2000" required>
                                    <small class="text-muted d-block mt-1">Entre 100 y 2000 · ~1 token ≈ 4 caracteres</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Instrucciones personalizadas --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Instrucciones personalizadas <span class="text-muted fw-normal small">(opcional)</span></h6>
                            <p class="text-muted mb-3">Texto adicional que se incluirá en cada prompt para personalizar el estilo de respuesta.</p>

                            <textarea class="form-control" id="custom_instructions" name="custom_instructions"
                                      rows="3" maxlength="500"
                                      placeholder="Ej: Siempre menciona que tenemos promociones en temporada alta...">{{ $aiSettings?->custom_instructions }}</textarea>
                            <small class="text-muted d-block mt-1">Máximo 500 caracteres.</small>
                        </div>

                        </div>{{-- #ai-enabled-fields --}}

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha: sidebar --}}
            <div class="col-lg-4">

                {{-- Proveedores --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Proveedores disponibles</h6>
                    </div>
                    <div class="card-body">

                        <h6 class="fw-semibold mb-1">Anthropic (Claude)</h6>
                        <p class="text-muted mb-1">Modelos Claude Sonnet, Opus y Haiku. Mejor para respuestas naturales y detalladas.</p>
                        <p class="text-muted mb-3">API key en <a href="https://console.anthropic.com" target="_blank" rel="noopener" class="fw-semibold">console.anthropic.com</a></p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-1">OpenAI (GPT)</h6>
                        <p class="text-muted mb-1">Modelos GPT-4o, GPT-4 Turbo y GPT-3.5.</p>
                        <p class="text-muted mb-0">API key en <a href="https://platform.openai.com" target="_blank" rel="noopener" class="fw-semibold">platform.openai.com</a></p>

                    </div>
                </div>

                {{-- Consejos --}}
                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Consejos de uso</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted  mb-0">
                            <li class="mb-2"><strong>Sonnet</strong> y <strong>GPT-4o</strong> ofrecen el mejor balance entre calidad y costo.</li>
                            <li class="mb-2">Las instrucciones personalizadas permiten incorporar el tono de voz de tu marca.</li>
                            <li class="mb-2">Con 500 tokens obtienes respuestas de 2-3 párrafos. Aumenta si necesitas más detalle.</li>
                            <li class="mb-0">La respuesta generada es siempre un <strong>borrador</strong> — puedes editarla antes de publicar.</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>
    </div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('core/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('core/select2/js/select2.min.js') }}"></script>
<script>
$(document).ready(function () {
    const PROVIDER_MODELS = {
        anthropic: {
            'claude-opus-4-6':          'Claude Opus 4.6',
            'claude-sonnet-4-6':        'Claude Sonnet 4.6',
            'claude-haiku-4-5-20251001':'Claude Haiku 4.5',
        },
        openai: {
            'gpt-4o':          'GPT-4o',
            'gpt-4o-mini':     'GPT-4o Mini',
            'gpt-4-turbo':     'GPT-4 Turbo',
            'gpt-3.5-turbo':   'GPT-3.5 Turbo',
        },
    };

    const currentModel = '{{ $aiSettings?->model ?? 'claude-sonnet-4-6' }}';

    function populateModels(provider) {
        const $select = $('#model');
        const models = PROVIDER_MODELS[provider] || {};

        $select.empty();
        $.each(models, function (value, label) {
            $select.append($('<option>', { value, text: label, selected: value === currentModel }));
        });

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change');
        }
    }

    // Toggle campos dependientes
    $('#is_enabled').on('change', function () {
        $('#ai-enabled-fields').toggleClass('d-none', !this.checked);
    });

    // Init Select2
    $('.select2').select2({ width: '100%' });

    // Initial model population
    populateModels($('#provider').val());
    $('#model').select2({ width: '100%' });

    $('#provider').on('change', function () {
        populateModels($(this).val());
        $('#model').select2({ width: '100%' });
    });

    // Toggle API key visibility
    $('#toggle-api-key').on('click', function () {
        const $input = $('#api_key');
        const isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $('#toggle-icon').toggleClass('fa-eye', !isPassword).toggleClass('fa-eye-slash', isPassword);
    });

    // Test connection
    $('#test-connection').on('click', function () {
        const $btn = $(this);
        const $result = $('#test-result');
        const apiKey = $('#api_key').val();

        if (!apiKey || apiKey.includes('•')) {
            toastr.warning('Ingresa una API key válida antes de probar la conexión');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Probando...');
        $result.addClass('d-none').removeClass('alert alert-success alert-danger');

        $.ajax({
            url: '{{ route('settings.reviews.ai.test') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { provider: $('#provider').val(), api_key: apiKey, model: $('#model').val() },
            success: function (response) {
                const cls  = response.success ? 'alert-success' : 'alert-danger';
                const icon = response.success ? 'fa-check-circle' : 'fa-times-circle';
                $result.removeClass('d-none').addClass('alert ' + cls)
                    .html('<i class="fas ' + icon + '"></i> ' + response.message);
            },
            error: function () {
                $result.removeClass('d-none').addClass('alert alert-danger')
                    .html('<i class="fas fa-times-circle"></i> Error al realizar la prueba. Intenta nuevamente.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Probar');
            },
        });
    });
});
</script>
@endpush

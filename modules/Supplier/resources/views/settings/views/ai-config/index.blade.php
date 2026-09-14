@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de IA'])
@endsection

@section('content')
    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> Por favor, corrige los siguientes errores:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('settings.suppliers.ai-config.update') }}"
          method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-body">

                <div class="alert alert-info py-2 px-3 mb-4" role="alert">
                    <i class="fas fa-info-circle me-1"></i>
                    Configura las claves API de los proveedores de inteligencia artificial.
                    Las claves se almacenan de forma segura en la base de datos.
                    Si dejas vacío un campo, se usará el valor del archivo <code>.env</code> como respaldo.
                </div>

                {{-- ── OpenAI ──────────────────────────────── --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">
                        <i class="fab fa-openai me-1"></i> OpenAI
                    </h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="openai_api_key">API Key de OpenAI</label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('openai_api_key') is-invalid @enderror"
                                   id="openai_api_key"
                                   name="openai_api_key"
                                   placeholder="sk-..."
                                   value="{{ old('openai_api_key', $config['openai_api_key']) }}">
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    onclick="toggleVisibility('openai_api_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-secondary"
                                    onclick="testApiKey('openai')"
                                    title="Probar">
                                <i class="fas fa-plug"></i>
                            </button>
                            @error('openai_api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted d-block mt-1">
                            Formato: <code>sk-...</code> o <code>sk-proj-...</code>
                        </small>
                    </div>
                </div>

                <hr class="my-4">

                {{-- ── Anthropic ──────────────────────────────── --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">
                        Anthropic (Claude)
                    </h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="anthropic_api_key">API Key de Anthropic</label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('anthropic_api_key') is-invalid @enderror"
                                   id="anthropic_api_key"
                                   name="anthropic_api_key"
                                   placeholder="sk-ant-..."
                                   value="{{ old('anthropic_api_key', $config['anthropic_api_key']) }}">
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    onclick="toggleVisibility('anthropic_api_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-secondary"
                                    onclick="testApiKey('anthropic')"
                                    title="Probar">
                                <i class="fas fa-plug"></i>
                            </button>
                            @error('anthropic_api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted d-block mt-1">
                            Formato: <code>sk-ant-...</code>
                        </small>
                    </div>
                </div>

                <hr class="my-4">

                {{-- ── Google Gemini ──────────────────────────────── --}}
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                        Google
                    </h6>
                    <p class="text-muted small mb-2">
                        Incluye Google Search grounding — las URLs de fuentes se capturan automáticamente.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="google_api_key">API Key de Google AI</label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('google_api_key') is-invalid @enderror"
                                   id="google_api_key"
                                   name="google_api_key"
                                   placeholder="AIza..."
                                   value="{{ old('google_api_key', $config['google_api_key']) }}">
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    onclick="toggleVisibility('google_api_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-secondary"
                                    onclick="testApiKey('google')"
                                    title="Probar">
                                <i class="fas fa-plug"></i>
                            </button>
                            @error('google_api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted d-block mt-1">
                            Obtén tu clave en <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">Google AI Studio</a>. Formato: <code>AIza...</code>
                        </small>
                    </div>
                </div>

                <hr class="my-4">

                {{-- ── Modelo por defecto ──────────────────────────────── --}}
                <div class="mb-2">
                    <h6 class="mb-1 fw-bold text-dark">Modelo por defecto</h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="default_model">Modelo</label>
                        <select class="form-select select2 @error('default_model') is-invalid @enderror"
                                id="default_model"
                                name="default_model">
                            <optgroup label="OpenAI">
                                <option value="gpt-4o-mini" {{ old('default_model', $config['default_model']) === 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini — rápido y económico ($0.15/1M)</option>
                                <option value="gpt-4o" {{ old('default_model', $config['default_model']) === 'gpt-4o' ? 'selected' : '' }}>GPT-4o — calidad superior ($2.50/1M)</option>
                                <option value="gpt-4o-search-preview" {{ old('default_model', $config['default_model']) === 'gpt-4o-search-preview' ? 'selected' : '' }}>GPT-4o Search Preview — web search ($2.50/1M)</option>
                            </optgroup>
                            <optgroup label="Anthropic">
                                <option value="claude-3-5-sonnet" {{ old('default_model', $config['default_model']) === 'claude-3-5-sonnet' ? 'selected' : '' }}>Claude 3.5 Sonnet ($3.00/1M)</option>
                                <option value="claude-3-5-haiku" {{ old('default_model', $config['default_model']) === 'claude-3-5-haiku' ? 'selected' : '' }}>Claude 3.5 Haiku ($0.80/1M)</option>
                            </optgroup>
                            <optgroup label="Google Gemini ✦ Google Search grounding incluido">
                                <option value="gemini-2.5-flash" {{ in_array(old('default_model', $config['default_model']), ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-2.0-flash-lite']) ? 'selected' : '' }}>Gemini 2.5 Flash — rápido ($0.15/1M)</option>
                                <option value="gemini-2.5-pro" {{ old('default_model', $config['default_model']) === 'gemini-2.5-pro' ? 'selected' : '' }}>Gemini 2.5 Pro — máxima calidad ($1.25/1M)</option>
                            </optgroup>
                        </select>
                        @error('default_model')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Resultado de prueba --}}
                <div id="test-result" class="mt-3"></div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar configuración
                </button>
                <a href="{{ route('settings.suppliers.sync.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
<script>
function setTestResult(container, type, icon, message) {
    var div = document.createElement('div');
    div.className = 'alert alert-' + type + ' py-2 px-3';
    var ico = document.createElement('i');
    ico.className = 'fas fa-' + icon + ' me-1';
    var txt = document.createTextNode(message);
    div.appendChild(ico);
    div.appendChild(txt);
    container.innerHTML = '';
    container.appendChild(div);
}

function toggleVisibility(inputId) {
    var input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function testApiKey(provider) {
    var inputMap = { openai: 'openai_api_key', anthropic: 'anthropic_api_key', google: 'google_api_key' };
    var inputId = inputMap[provider] || (provider + '_api_key');
    var apiKey = document.getElementById(inputId).value.trim();
    var $result = document.getElementById('test-result');

    if (!apiKey) {
        setTestResult($result, 'warning', 'exclamation-triangle', 'Ingresa una API key antes de probar.');
        return;
    }

    setTestResult($result, 'secondary', 'spinner fa-spin', 'Probando conexión con ' + provider + '...');

    fetch('{{ route("settings.suppliers.ai-config.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            provider: provider,
            api_key: apiKey,
        }),
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            setTestResult($result, 'success', 'check-circle', data.message || 'Conexión exitosa.');
        } else {
            setTestResult($result, 'danger', 'times-circle', data.message || 'Error desconocido.');
        }
    })
    .catch(function(err) {
        setTestResult($result, 'danger', 'times-circle', 'Error de red: ' + err.message);
    });
}
</script>
@endpush

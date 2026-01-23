<div class="row">
    <!-- Main Settings Form -->
    <div class="col-lg-8">
        <form method="POST" action="{{ route('manager.helpdesk.ai.settings.update') }}" id="settingsForm">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div class="card mb-3">
                <div class="card-header bg-light-primary">
                    <h5 class="mb-0"><i class="ti ti-info-circle me-2"></i>Información Básica</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del Agente <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $agent->name ?? 'Asistente IA') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                            rows="2" placeholder="Breve descripción del propósito del agente">{{ old('description', $agent->description ?? '') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $agent->status ?? 'inactive') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Personality/System Prompt -->
            <div class="card mb-3">
                <div class="card-header bg-light-info">
                    <h5 class="mb-0"><i class="ti ti-message-circle me-2"></i>Personalidad</h5>
                </div>
                <div class="card-body">
                    <label class="form-label fw-semibold">System Prompt (Instrucciones Base) <span class="text-danger">*</span></label>
                    <textarea name="personality" class="form-control @error('personality') is-invalid @enderror"
                        rows="6" required placeholder="Define el comportamiento y personalidad del agente...">{{ old('personality', $agent->personality ?? 'Eres un asistente útil y amable.') }}</textarea>
                    <small class="text-muted d-block mt-2">Este prompt controla cómo el agente se comporta y responde a los usuarios.</small>
                    @error('personality')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- LLM Provider & Model -->
            <div class="card mb-3">
                <div class="card-header bg-light-success">
                    <h5 class="mb-0"><i class="ti ti-cpu me-2"></i>Proveedor LLM</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Proveedor <span class="text-danger">*</span></label>
                        <select name="provider" id="provider" class="form-select @error('provider') is-invalid @enderror" required>
                            <option value="">-- Seleccionar Proveedor --</option>
                            @foreach ($providers as $key => $provider)
                                <option value="{{ $key }}" {{ old('provider', $agent->provider ?? '') === $key ? 'selected' : '' }}>
                                    {{ $provider['icon'] }} {{ $provider['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('provider')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Modelo <span class="text-danger">*</span></label>
                        <select name="model" id="model" class="form-select @error('model') is-invalid @enderror" required>
                            <option value="">-- Seleccionar Modelo --</option>
                            @if ($agent->provider ?? false)
                                @foreach ($providers[$agent->provider]['models'] as $model)
                                    <option value="{{ $model }}" {{ old('model', $agent->model ?? '') === $model ? 'selected' : '' }}>
                                        {{ $model }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('model')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">API Key</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-key"></i></span>
                            <input type="password" name="api_key" id="api_key" class="form-control @error('api_key') is-invalid @enderror"
                                placeholder="sk-... o xxxx-xxxx-xxxx" value="{{ old('api_key', $agent->settings['api_key'] ?? '') }}">
                            <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                <i class="ti ti-eye"></i>
                            </button>
                            <button class="btn btn-primary" type="button" id="testConnection">
                                <i class="ti ti-plug"></i> Probar
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">Tu API key se cifra y almacena de forma segura.</small>
                        @error('api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Advanced Parameters -->
            <div class="card mb-3">
                <div class="card-header bg-light-warning">
                    <h5 class="mb-0"><i class="ti ti-adjustments me-2"></i>Parámetros Avanzados</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Temperature (0-2)</label>
                            <input type="number" name="temperature" class="form-control" step="0.1" min="0" max="2"
                                value="{{ old('temperature', $agent->settings['temperature'] ?? 0.7) }}">
                            <small class="text-muted">Mayor = más creativo, Menor = más determinista</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Max Tokens</label>
                            <input type="number" name="max_tokens" class="form-control" min="1" max="128000"
                                value="{{ old('max_tokens', $agent->settings['max_tokens'] ?? 2048) }}">
                            <small class="text-muted">Máximo de tokens en la respuesta</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Top P (0-1)</label>
                            <input type="number" name="top_p" class="form-control" step="0.01" min="0" max="1"
                                value="{{ old('top_p', $agent->settings['top_p'] ?? 1.0) }}">
                            <small class="text-muted">Nucleus sampling</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Frequency Penalty (-2 a 2)</label>
                            <input type="number" name="frequency_penalty" class="form-control" step="0.1" min="-2" max="2"
                                value="{{ old('frequency_penalty', $agent->settings['frequency_penalty'] ?? 0) }}">
                            <small class="text-muted">Penaliza palabras repetidas</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="ti ti-check me-2"></i>Guardar Configuración
                </button>
            </div>
        </form>
    </div>

    <!-- Sidebar - Statistics & Info -->
    <div class="col-lg-4">
        <!-- Status Card -->
        <div class="card mb-3">
            <div class="card-header bg-light-secondary">
                <h5 class="mb-0"><i class="ti ti-chart-pie me-2"></i>Estado del Agente</h5>
            </div>
            <div class="card-body">
                @if ($hasAgent ?? false)
                    <div class="text-center">
                        <div class="mb-3">
                            <span class="badge bg-{{ $agent->status === 'active' ? 'success' : 'secondary' }} p-3" style="font-size: 16px">
                                <i class="ti ti-robot me-1"></i>{{ $agent->status_label }}
                            </span>
                        </div>
                        <h5 class="mb-2">{{ $agent->name ?? 'Sin nombre' }}</h5>
                        <p class="text-muted small mb-1">Proveedor: <strong>{{ $agent->provider_label ?? 'N/A' }}</strong></p>
                        <p class="text-muted small mb-3">Modelo: <strong>{{ $agent->model ?? 'N/A' }}</strong></p>

                        @if ($agent->enabled_at)
                            <div class="alert alert-info py-2">
                                <small><i class="ti ti-calendar me-1"></i>Activado: {{ $agent->enabled_at->format('d/m/Y H:i') }}</small>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>Sin configurar</strong>
                        <p class="mb-0 mt-2 small">Rellena el formulario para crear tu primer agente IA.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Links Card -->
        <div class="card">
            <div class="card-header bg-light-info">
                <h5 class="mb-0"><i class="ti ti-help me-2"></i>Ayuda Rápida</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-2">¿Cómo obtener API Keys?</h6>
                <ul class="small mb-3">
                    <li><a href="https://platform.openai.com/api-keys" target="_blank" class="text-decoration-none">OpenAI <i class="ti ti-external-link"></i></a></li>
                    <li><a href="https://console.anthropic.com/" target="_blank" class="text-decoration-none">Anthropic <i class="ti ti-external-link"></i></a></li>
                    <li><a href="https://aistudio.google.com/" target="_blank" class="text-decoration-none">Google Gemini <i class="ti ti-external-link"></i></a></li>
                    <li><a href="https://ollama.ai/" target="_blank" class="text-decoration-none">Ollama (Local) <i class="ti ti-external-link"></i></a></li>
                </ul>

                <hr>

                <h6 class="mb-2">Parámetros</h6>
                <ul class="small mb-0">
                    <li><strong>Temperature:</strong> Creatividad (0=determinista, 2=muy creativo)</li>
                    <li><strong>Max Tokens:</strong> Largo máximo de la respuesta</li>
                    <li><strong>Top P:</strong> Diversidad de tokens seleccionados</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const providers = @json($providers);

// Toggle API key visibility
document.getElementById('toggleApiKey')?.addEventListener('click', function() {
    const input = document.getElementById('api_key');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ti-eye');
        icon.classList.add('ti-eye-off');
    } else {
        input.type = 'password';
        icon.classList.remove('ti-eye-off');
        icon.classList.add('ti-eye');
    }
});

// Update model dropdown when provider changes
document.getElementById('provider')?.addEventListener('change', function() {
    const provider = this.value;
    const modelSelect = document.getElementById('model');
    modelSelect.innerHTML = '<option value="">-- Seleccionar Modelo --</option>';

    if (provider && providers[provider]) {
        providers[provider].models.forEach(model => {
            const option = document.createElement('option');
            option.value = model;
            option.textContent = model;
            modelSelect.appendChild(option);
        });
    }
});

// Test connection
document.getElementById('testConnection')?.addEventListener('click', function(e) {
    e.preventDefault();
    const provider = document.getElementById('provider').value;
    const model = document.getElementById('model').value;
    const apiKey = document.getElementById('api_key').value;

    if (!provider || !model) {
        toastr.warning('Por favor selecciona proveedor y modelo', 'Advertencia');
        return;
    }

    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Probando...';

    fetch('{{ route("manager.helpdesk.ai.settings.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ provider, model, api_key: apiKey }),
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message, 'Conexión exitosa');
        } else {
            toastr.error(data.message, 'Error');
        }
    })
    .catch(err => {
        toastr.error('Error al conectar: ' + err.message, 'Error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});
</script>
@endpush

<div class="ais-body">

    {{-- ── Formulario ───────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('helpdesk.ai.settings.update') }}" id="settingsForm" class="ais-main">
            @csrf
            @method('PUT')

            {{-- Identidad --}}
            <div class="ais-card">
                <div>
                    <h2 class="ais-section-title">Identidad</h2>
                    <p class="ais-section-note">Cómo se llama el agente y si está atendiendo ahora mismo.</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre <span class="ais-required">· obligatorio</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $agent->name ?? 'Asistente IA') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado <span class="ais-required">· obligatorio</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $agent->status ?? 'inactive') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <div class="ais-help mt-1">En pausa deja de responder pero conserva la configuración.</div>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                            rows="2" placeholder="Para qué existe este agente">{{ old('description', $agent->description ?? '') }}</textarea>
                        <div class="ais-help mt-1">Solo para el equipo. El cliente nunca la ve.</div>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Instrucciones --}}
            <div class="ais-card">
                <div>
                    <h2 class="ais-section-title">Instrucciones</h2>
                    <p class="ais-section-note">Lo que el agente tiene siempre presente antes de leer el mensaje del cliente.</p>
                </div>

                <div>
                    <label class="form-label fw-semibold">Instrucciones base <span class="ais-required">· obligatorio</span></label>
                    <textarea name="personality" class="form-control ais-prompt @error('personality') is-invalid @enderror"
                        rows="7" required placeholder="Qué tono usa, qué puede prometer y cuándo debe pasar la conversación a una persona">{{ old('personality', $agent->personality ?? 'Eres un asistente útil y amable.') }}</textarea>
                    <div class="ais-help mt-1">Es lo que más nota el cliente: define tono, límites y cuándo escalar a un agente humano.</div>
                    @error('personality')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Proveedor y modelo --}}
            <div class="ais-card">
                <div>
                    <h2 class="ais-section-title">Proveedor y modelo</h2>
                    <p class="ais-section-note">Quién ejecuta el modelo y con qué credencial se le llama.</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Proveedor <span class="ais-required">· obligatorio</span></label>
                        <select name="provider" id="provider" class="form-select @error('provider') is-invalid @enderror" required>
                            <option value="">Seleccionar proveedor</option>
                            @foreach ($providers as $key => $provider)
                                <option value="{{ $key }}" {{ old('provider', $agent->provider ?? '') === $key ? 'selected' : '' }}>
                                    {{ $provider['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('provider')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Modelo <span class="ais-required">· obligatorio</span></label>
                        <select name="model" id="model" class="form-select @error('model') is-invalid @enderror" required>
                            <option value="">Seleccionar modelo</option>
                            @if ($agent->provider ?? false)
                                {{-- models es {clave: etiqueta} (p.ej. "claude-opus-5" =>
                                     "Claude Opus 5"); con "as $model" (forma de un solo
                                     valor) PHP liga $model a la ETIQUETA, no a la clave,
                                     así el value nunca coincidía con el slug guardado en
                                     $agent->model y la opción correcta no quedaba
                                     seleccionada al editar. --}}
                                @foreach ($providers[$agent->provider]['models'] as $modelKey => $modelLabel)
                                    <option value="{{ $modelKey }}" {{ old('model', $agent->model ?? '') === $modelKey ? 'selected' : '' }}>
                                        {{ $modelLabel }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('model')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Clave de API</label>
                        <div class="input-group">
                            <input type="password" name="api_key" id="api_key" class="form-control @error('api_key') is-invalid @enderror"
                                placeholder="{{ ($agent->getApiKey() ?? null) ? '••••••••••••••••••••' : 'sk-... o xxxx-xxxx-xxxx' }}" autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="toggleApiKey" title="Mostrar u ocultar la clave" aria-label="Mostrar u ocultar la clave">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="btn btn-secondary" type="button" id="testConnection">Probar</button>
                        </div>
                        <div class="ais-help mt-1">
                            @if ($agent->getApiKey() ?? null)
                                Se guarda cifrada. Déjala en blanco para conservar la que ya hay.
                            @else
                                Se guarda cifrada. El botón Probar confirma que vale antes de guardar nada.
                            @endif
                        </div>
                        @error('api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Ajuste fino --}}
            <div class="ais-card">
                <div>
                    <h2 class="ais-section-title">Ajuste fino</h2>
                    <p class="ais-section-note">Los valores por defecto funcionan bien. Tócalos solo si sabes qué esperas cambiar.</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Temperatura</label>
                        <input type="number" name="temperature" class="form-control" step="0.1" min="0" max="2"
                            value="{{ old('temperature', $agent->parameters['temperature'] ?? 0.7) }}">
                        <div class="ais-help mt-1">0 responde siempre igual; 2, muy suelto. Entre 0 y 2.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Longitud máxima</label>
                        <input type="number" name="max_tokens" class="form-control" min="1" max="128000"
                            value="{{ old('max_tokens', $agent->parameters['max_tokens'] ?? 2048) }}">
                        <div class="ais-help mt-1">Tokens como techo de cada respuesta.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Top P</label>
                        <input type="number" name="top_p" class="form-control" step="0.01" min="0" max="1"
                            value="{{ old('top_p', $agent->parameters['top_p'] ?? 1.0) }}">
                        <div class="ais-help mt-1">Cuánto vocabulario se permite. Entre 0 y 1.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Penalización por repetición</label>
                        <input type="number" name="frequency_penalty" class="form-control" step="0.1" min="-2" max="2"
                            value="{{ old('frequency_penalty', $agent->parameters['frequency_penalty'] ?? 0) }}">
                        <div class="ais-help mt-1">Sube si repite frases. Entre −2 y 2.</div>
                    </div>
                </div>
            </div>

            {{-- Pie de acciones --}}
            <div class="ais-foot">
                <span class="ais-foot-hint">Los cambios entran en vigor en la siguiente conversación que atienda el agente.</span>
                <button type="submit" class="btn btn-primary">{{ $hasAgent ? 'Guardar cambios' : 'Crear el agente' }}</button>
            </div>

    </form>

    {{-- ── Ayuda ────────────────────────────────────────────────────── --}}
    <div class="ais-rail">
        @include('helpdeskagents::managers.ai-agent.partials.settings-rail', ['showUsage' => $hasAgent])
        <div class="ais-note-card">
            <h3 class="ais-note-title">Antes de activarlo</h3>
            <p class="ais-note-body">Prueba la conexión y escribe las instrucciones antes de poner el estado en Activo: en cuanto lo esté, contestará al siguiente cliente que escriba.</p>
        </div>
    </div>

</div>

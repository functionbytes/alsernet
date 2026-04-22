<div class="row g-3">

    {{-- Name --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $webhook->name ?? '') }}"
            placeholder="Ej: Notificaciones a Slack">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- URL --}}
    <div class="col-12">
        <label class="form-label">
            URL destino <span class="text-danger">*</span>
        </label>
        <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
            value="{{ old('url', $webhook->url ?? '') }}"
            placeholder="https://hooks.example.com/endpoint">
        <div class="form-text">URL que recibirá las peticiones POST con el payload JSON</div>
        @error('url')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Integration type --}}
    <div class="col-12">
        <label class="form-label">Tipo de integracion</label>
        <select name="integration_type" class="form-select @error('integration_type') is-invalid @enderror">
            <option value="generic" @selected(old('integration_type', $webhook->integration_type ?? 'generic') === 'generic')>Generic (JSON payload)</option>
            <option value="slack" @selected(old('integration_type', $webhook->integration_type ?? '') === 'slack')>Slack (block kit)</option>
            <option value="discord" @selected(old('integration_type', $webhook->integration_type ?? '') === 'discord')>Discord (embed)</option>
            <option value="teams" @selected(old('integration_type', $webhook->integration_type ?? '') === 'teams')>Microsoft Teams (adaptive card)</option>
        </select>
        <div class="form-text">Para Slack/Discord/Teams, usa la URL del webhook entrante de cada plataforma.</div>
        @error('integration_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Events --}}
    <div class="col-12">
        <label class="form-label">
            Eventos <span class="text-danger">*</span>
        </label>
        <div class="border rounded p-3 @error('events') border-danger @enderror">
            @foreach($availableEvents as $eventKey => $eventLabel)
                <div class="form-check mb-2">
                    <input type="checkbox"
                        class="form-check-input"
                        name="events[]"
                        value="{{ $eventKey }}"
                        id="event_{{ Str::slug($eventKey, '_') }}"
                        @checked(in_array($eventKey, old('events', $webhook->events ?? [])))>
                    <label class="form-check-label" for="event_{{ Str::slug($eventKey, '_') }}">
                        <span class="fw-semibold">{{ $eventLabel }}</span>
                        <code class="ms-2 small text-muted">{{ $eventKey }}</code>
                    </label>
                </div>
            @endforeach
        </div>
        @error('events')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- Secret --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Secreto HMAC</label>
        <input type="text" name="secret" class="form-control @error('secret') is-invalid @enderror"
            value="{{ old('secret', $webhook->secret ?? '') }}"
            placeholder="Clave secreta opcional">
        <div class="form-text">Se usa para firmar el payload con HMAC-SHA256</div>
        @error('secret')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is active --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Estado</label>
        <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
            <option value="1" @selected(old('is_active', $webhook->is_active ?? true) == true)>Activado</option>
            <option value="0" @selected(old('is_active', $webhook->is_active ?? true) == false)>Desactivado</option>
        </select>
        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Custom headers --}}
    <div class="col-12">
        <label class="form-label">Cabeceras personalizadas <span class="text-muted">(opcional)</span></label>
        <textarea name="headers" rows="3"
            class="form-control font-monospace @error('headers') is-invalid @enderror"
            placeholder='{"Authorization": "Bearer mi-token", "X-Custom": "valor"}'>{{ old('headers', isset($webhook) && $webhook->headers ? json_encode($webhook->headers, JSON_PRETTY_PRINT) : '') }}</textarea>
        <div class="form-text">JSON con cabeceras HTTP adicionales a incluir en cada peticion</div>
        @error('headers')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>

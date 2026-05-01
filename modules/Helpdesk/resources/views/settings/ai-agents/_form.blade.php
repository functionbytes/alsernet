@php
    $channels = ['widget', 'facebook', 'instagram', 'whatsapp', 'email'];
    $enabledChannels = old('enabled_channels', $aiAgent->enabled_channels ?? []);
    $keywordsText = old('escalation_keywords', implode("\n", $aiAgent->escalation_keywords ?? []));
    $knowledgeJson = old('knowledge_sources', json_encode($aiAgent->knowledge_sources ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
@endphp

<div class="row g-3">

    {{-- Seccion: Informacion basica --}}
    <div class="col-12">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Informacion basica</h6>
    </div>

    <div class="col-12">
        <label class="form-label">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $aiAgent->name ?? '') }}"
            placeholder="Ej: Asistente de soporte tecnico">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Descripcion</label>
        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
            value="{{ old('description', $aiAgent->description ?? '') }}"
            placeholder="Breve descripcion del rol y proposito del agente">
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion: Prompt del sistema --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Prompt del sistema</h6>
    </div>

    <div class="col-12">
        <label class="form-label">Instrucciones del agente <span class="text-danger">*</span></label>
        <textarea name="system_prompt" rows="8"
            class="form-control @error('system_prompt') is-invalid @enderror"
            placeholder="Eres un asistente de soporte al cliente amable y profesional. Tu objetivo es ayudar a los usuarios a resolver sus dudas sobre...">{{ old('system_prompt', $aiAgent->system_prompt ?? '') }}</textarea>
        <div class="form-text">Define el comportamiento, tono y limitaciones del agente. Cuanto mas detallado, mejor sera su desempeno.</div>
        @error('system_prompt')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion: Canales habilitados --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Canales habilitados</h6>
    </div>

    <div class="col-12">
        <label class="form-label">Canales donde actuara el agente</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach($channels as $channel)
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="enabled_channels[]"
                        value="{{ $channel }}"
                        id="channel_{{ $channel }}"
                        @checked(in_array($channel, $enabledChannels))>
                    <label class="form-check-label text-capitalize" for="channel_{{ $channel }}">
                        {{ $channel }}
                    </label>
                </div>
            @endforeach
        </div>
        <div class="form-text">Si no seleccionas ningun canal, el agente actuara en todos.</div>
        @error('enabled_channels')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion: Escalacion --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Escalacion</h6>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">Mensajes antes de escalar <span class="text-danger">*</span></label>
        <input type="number" name="max_messages_before_escalation"
            class="form-control @error('max_messages_before_escalation') is-invalid @enderror"
            value="{{ old('max_messages_before_escalation', $aiAgent->max_messages_before_escalation ?? 5) }}"
            min="1" max="100">
        <div class="form-text">El agente escalara al soporte humano tras este numero de intercambios.</div>
        @error('max_messages_before_escalation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-8">
        <label class="form-label">Palabras clave de escalacion</label>
        <textarea name="escalation_keywords" rows="4"
            class="form-control @error('escalation_keywords') is-invalid @enderror"
            placeholder="hablar con humano&#10;agente&#10;cancelar suscripcion&#10;reembolso">{{ $keywordsText }}</textarea>
        <div class="form-text">Una palabra o frase por linea. Cuando el usuario las escriba, el agente escalara inmediatamente.</div>
        @error('escalation_keywords')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion: Fuentes de conocimiento --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Fuentes de conocimiento</h6>
    </div>

    <div class="col-12">
        <label class="form-label">Fuentes de conocimiento (JSON)</label>
        <textarea name="knowledge_sources" rows="4"
            class="form-control font-monospace @error('knowledge_sources') is-invalid @enderror"
            placeholder='["https://docs.example.com", "https://faq.example.com"]'>{{ $knowledgeJson }}</textarea>
        <div class="form-text">Array JSON de URLs o identificadores de bases de conocimiento que el agente puede consultar.</div>
        @error('knowledge_sources')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Estado --}}
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" name="is_active" value="1"
                id="is_active"
                @checked(old('is_active', $aiAgent->is_active ?? true))>
            <label class="form-check-label" for="is_active">
                Agente activo
            </label>
            <div class="form-text">Los agentes inactivos no intervendran en las conversaciones.</div>
        </div>
    </div>

</div>

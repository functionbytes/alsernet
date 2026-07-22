<div class="row g-3">

    {{-- Seccion: Disponibilidad --}}
    <div class="col-12">
        <h6 class="fw-bold mb-3 border-bottom pb-2">Disponibilidad</h6>
    </div>

    {{-- Disponible --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Estado de disponibilidad</label>
        <select name="is_available" class="form-select @error('is_available') is-invalid @enderror">
            <option value="1" @selected(old('is_available', $agentSettings->is_available ?? true) == '1')>
                Disponible
            </option>
            <option value="0" @selected(old('is_available', $agentSettings->is_available ?? true) == '0')>
                No disponible
            </option>
        </select>
        <div class="form-text">Indica si el agente puede recibir asignaciones</div>
        @error('is_available')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Acepta conversaciones --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Acepta conversaciones</label>
        <select name="accepts_conversations" class="form-select @error('accepts_conversations') is-invalid @enderror">
            <option value="yes" @selected(old('accepts_conversations', $agentSettings->accepts_conversations ?? 'yes') === 'yes')>
                Siempre
            </option>
            <option value="working_hours" @selected(old('accepts_conversations', $agentSettings->accepts_conversations ?? 'yes') === 'working_hours')>
                Solo en horario de trabajo
            </option>
            <option value="no" @selected(old('accepts_conversations', $agentSettings->accepts_conversations ?? 'yes') === 'no')>
                Nunca
            </option>
        </select>
        <div class="form-text">Cuando puede recibir conversaciones nuevas</div>
        @error('accepts_conversations')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Auto asignacion --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Asignacion automatica</label>
        <select name="auto_assign" class="form-select @error('auto_assign') is-invalid @enderror">
            <option value="1" @selected(old('auto_assign', $agentSettings->auto_assign ?? true) == '1')>
                Si
            </option>
            <option value="0" @selected(old('auto_assign', $agentSettings->auto_assign ?? true) == '0')>
                No
            </option>
        </select>
        <div class="form-text">Si el sistema puede asignar conversaciones automaticamente</div>
        @error('auto_assign')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion: Limites --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-3 border-bottom pb-2">Limites</h6>
    </div>

    {{-- Maximo de conversaciones --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Maximo de conversaciones abiertas</label>
        <input type="number" name="max_concurrent_conversations"
            class="form-control @error('max_concurrent_conversations') is-invalid @enderror"
            value="{{ old('max_concurrent_conversations', $agentSettings->max_concurrent_conversations ?? 0) }}"
            min="0" max="100"
            placeholder="0">
        <div class="form-text">0 significa sin limite. Maximo permitido: 100</div>
        @error('max_concurrent_conversations')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion: Vacaciones --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-3 border-bottom pb-2">Vacaciones</h6>
    </div>

    {{-- Vacaciones hasta --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Vacaciones hasta</label>
        <input type="datetime-local" name="vacation_until"
            class="form-control @error('vacation_until') is-invalid @enderror"
            value="{{ old('vacation_until', $agentSettings->vacation_until?->format('Y-m-d\TH:i') ?? '') }}">
        <div class="form-text">Deja en blanco si el agente no esta de vacaciones</div>
        @error('vacation_until')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Idiomas que habla (ruteo por idioma de tickets) --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Idiomas que habla</label>
        <input type="text" name="languages"
            class="form-control @error('languages') is-invalid @enderror"
            value="{{ old('languages', implode(', ', $agentSettings->languages ?? [])) }}"
            placeholder="es, en, fr">
        <div class="form-text">Codigos de idioma separados por comas (ISO-639, ej. "es, en"). Usado por el ruteo por idioma en la asignacion automatica de tickets</div>
        @error('languages')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion: Habilidades --}}
    @if($skills->count() > 0)
        <div class="col-12 mt-2">
            <h6 class="fw-bold mb-3 border-bottom pb-2">Habilidades asignadas</h6>
        </div>

        <div class="col-12">
            <label class="form-label">Habilidades</label>
            <select name="skills[]" class="form-select" multiple size="{{ min($skills->count(), 8) }}">
                @foreach($skills as $skill)
                    <option value="{{ $skill->id }}"
                        @selected(in_array($skill->id, old('skills', $assignedSkillIds)))>
                        {{ $skill->name }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Mantén presionado Ctrl (o Cmd en Mac) para seleccionar varias habilidades</div>
            @error('skills')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    @endif

</div>

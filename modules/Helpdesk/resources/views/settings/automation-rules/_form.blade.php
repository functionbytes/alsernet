<div class="row g-3">

    {{-- Name --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-brand">*</span>
        </label>
        <input type="text" name="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $automationRule->name ?? '') }}"
            placeholder="Ej: Asignar agente cuando se crea conversacion">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Description --}}
    <div class="col-12">
        <label class="form-label">Descripcion</label>
        <textarea name="description" rows="2"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Descripcion opcional de lo que hace esta regla">{{ old('description', $automationRule->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Event name --}}
    <div class="col-12 col-md-6">
        <label class="form-label">
            Evento de disparo <span class="text-brand">*</span>
        </label>
        <select name="event_name" class="form-select @error('event_name') is-invalid @enderror">
            <option value="">Seleccionar evento...</option>
            @foreach($events as $key => $label)
                <option value="{{ $key }}" @selected(old('event_name', $automationRule->trigger_event ?? '') === $key)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <div class="form-text">La regla se ejecutara cada vez que ocurra este evento</div>
        @error('event_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Execution order --}}
    <div class="col-12 col-md-3">
        <label class="form-label">Orden de ejecucion</label>
        <input type="number" name="order" min="0"
            class="form-control @error('order') is-invalid @enderror"
            value="{{ old('order', $automationRule->order ?? 0) }}">
        <div class="form-text">Las reglas de menor numero se ejecutan primero</div>
        @error('order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Active toggle --}}
    <div class="col-12 col-md-3 d-flex flex-column justify-content-center gap-2 pt-md-3">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" id="is_active"
                class="form-check-input"
                @checked(old('is_active', $automationRule->is_active ?? true))>
            <label class="form-check-label" for="is_active">Regla activa</label>
        </div>
    </div>

    {{-- Conditions section --}}
    <div class="col-12">
        <hr class="my-1">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h6 class="mb-0">Condiciones</h6>
                <small class="text-muted">Todas las condiciones deben cumplirse para ejecutar la regla (AND)</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-condition">
                Agregar condicion
            </button>
        </div>

        <div id="conditions-container">
            {{-- Rows inserted by JS --}}
        </div>

        <div id="conditions-empty" class="text-center py-3 text-muted small border rounded">
            Sin condiciones — la regla se ejecutara siempre que ocurra el evento
        </div>

        <input type="hidden" name="conditions_json" id="conditions_json"
            value="{{ old('conditions_json', isset($automationRule) ? json_encode($automationRule->conditions) : '[]') }}">
    </div>

    {{-- Actions section --}}
    <div class="col-12">
        <hr class="my-1">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h6 class="mb-0">Acciones</h6>
                <small class="text-muted">Acciones a ejecutar cuando se cumplan las condiciones</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-action">
                Agregar accion
            </button>
        </div>

        <div id="actions-container">
            {{-- Rows inserted by JS --}}
        </div>

        <div id="actions-empty" class="text-center py-3 text-muted small border rounded">
            Sin acciones — agrega al menos una accion para que la regla sea util
        </div>

        <input type="hidden" name="actions_json" id="actions_json"
            value="{{ old('actions_json', isset($automationRule) ? json_encode($automationRule->actions) : '[]') }}">
    </div>

</div>

@push('scripts')
<script>
window.HdAutomationRuleFormConfig = {
    conditionFields: @json($conditionFields),
    conditionOperators: @json($conditionOperators),
    actionTypes: @json($actionTypes),
};
</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/automation-rules-form.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/automation-rules-form.js')) }}" defer></script>
@endpush

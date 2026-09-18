<div class="row g-3">

    {{-- Name --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
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
            Evento de disparo <span class="text-danger">*</span>
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
                <i class="fas fa-plus"></i> Agregar condicion
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
                <i class="fas fa-plus"></i> Agregar accion
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
(function () {
    const conditionFields   = @json($conditionFields);
    const conditionOperators = @json($conditionOperators);
    const actionTypes       = @json($actionTypes);

    // ── helpers ─────────────────────────────────────────────────────────────

    function buildSelect(name, options, selected) {
        let html = `<select name="${name}" class="form-select form-select-sm">`;
        for (const [val, label] of Object.entries(options)) {
            const sel = val === selected ? ' selected' : '';
            html += `<option value="${val}"${sel}>${label}</option>`;
        }
        html += '</select>';
        return html;
    }

    function updateEmpty(containerId, emptyId) {
        const hasRows = $(`#${containerId} .dynamic-row`).length > 0;
        $(`#${emptyId}`).toggle(!hasRows);
    }

    function serializeConditions() {
        const rows = [];
        $('#conditions-container .dynamic-row').each(function () {
            rows.push({
                field:    $(this).find('[data-role="field"]').val(),
                operator: $(this).find('[data-role="operator"]').val(),
                value:    $(this).find('[data-role="value"]').val(),
            });
        });
        $('#conditions_json').val(JSON.stringify(rows));
    }

    function serializeActions() {
        const rows = [];
        $('#actions-container .dynamic-row').each(function () {
            rows.push({
                type:  $(this).find('[data-role="type"]').val(),
                value: $(this).find('[data-role="value"]').val(),
            });
        });
        $('#actions_json').val(JSON.stringify(rows));
    }

    // ── condition row ────────────────────────────────────────────────────────

    function addConditionRow(field, operator, value) {
        field    = field    || Object.keys(conditionFields)[0];
        operator = operator || Object.keys(conditionOperators)[0];
        value    = value    || '';

        const fieldSel    = buildSelect('', conditionFields, field).replace('<select name=""', '<select data-role="field"');
        const operatorSel = buildSelect('', conditionOperators, operator).replace('<select name=""', '<select data-role="operator"');

        const row = $(`
            <div class="dynamic-row row g-2 align-items-center mb-2">
                <div class="col-md-4">${fieldSel}</div>
                <div class="col-md-3">${operatorSel}</div>
                <div class="col-md-4">
                    <input type="text" data-role="value" class="form-control form-control-sm"
                        placeholder="Valor" value="${$('<div>').text(value).html()}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `);

        $('#conditions-container').append(row);
        updateEmpty('conditions-container', 'conditions-empty');
    }

    // ── action row ───────────────────────────────────────────────────────────

    function addActionRow(type, value) {
        type  = type  || Object.keys(actionTypes)[0];
        value = value || '';

        const typeSel = buildSelect('', actionTypes, type).replace('<select name=""', '<select data-role="type"');

        const row = $(`
            <div class="dynamic-row row g-2 align-items-center mb-2">
                <div class="col-md-5">${typeSel}</div>
                <div class="col-md-6">
                    <input type="text" data-role="value" class="form-control form-control-sm"
                        placeholder="Valor (agente, etiqueta, estado...)" value="${$('<div>').text(value).html()}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `);

        $('#actions-container').append(row);
        updateEmpty('actions-container', 'actions-empty');
    }

    // ── load existing data ───────────────────────────────────────────────────

    try {
        const existingConditions = JSON.parse($('#conditions_json').val() || '[]');
        existingConditions.forEach(c => addConditionRow(c.field, c.operator, c.value));
    } catch (e) {}

    try {
        const existingActions = JSON.parse($('#actions_json').val() || '[]');
        existingActions.forEach(a => addActionRow(a.type, a.value));
    } catch (e) {}

    updateEmpty('conditions-container', 'conditions-empty');
    updateEmpty('actions-container', 'actions-empty');

    // ── events ───────────────────────────────────────────────────────────────

    $('#btn-add-condition').on('click', function () {
        addConditionRow();
    });

    $('#btn-add-action').on('click', function () {
        addActionRow();
    });

    $(document).on('click', '#conditions-container .btn-remove', function () {
        $(this).closest('.dynamic-row').remove();
        updateEmpty('conditions-container', 'conditions-empty');
    });

    $(document).on('click', '#actions-container .btn-remove', function () {
        $(this).closest('.dynamic-row').remove();
        updateEmpty('actions-container', 'actions-empty');
    });

    // Serialize before submit
    $('form').on('submit', function () {
        serializeConditions();
        serializeActions();
    });

    // Tooltips
    $('[data-bs-toggle="tooltip"]').each(function () {
        new bootstrap.Tooltip(this);
    });

}());
</script>
@endpush

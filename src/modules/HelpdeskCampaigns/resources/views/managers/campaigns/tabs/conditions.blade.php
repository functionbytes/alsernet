{{-- Conditions tab --}}
<form method="POST" action="{{ route('helpdesk.campaigns.update', $campaign) }}" id="conditions-form">
    @csrf
    @method('PUT')

    <h6 class="fw-semibold mb-1 border-bottom pb-2">Condiciones de visualizacion</h6>
    <p class="text-muted small mb-3">Definen cuando y a quien se mostrara la campana. Sin condiciones, se muestra a todos los visitantes.</p>

    @php $conditions = old('conditions', $campaign->conditions ?? []); @endphp

    @if(empty($conditions))
        <div class="alert alert-warning mb-3" id="no-conditions-alert">
            Sin condiciones configuradas. La campana se mostrara a todos los visitantes.
        </div>
    @else
        <div class="alert alert-success mb-3">
            Tienes {{ count($conditions) }} condicion(es) configurada(s). La campana se mostrara cuando todas se cumplan (logica AND).
        </div>
    @endif

    <div id="conditions-container" class="mb-3">
        @foreach($conditions as $index => $condition)
            @include('helpdeskcampaigns::managers.campaigns.partials.condition-block', ['condition' => $condition, 'index' => $index])
        @endforeach
    </div>

    {{-- Preset conditions --}}
    <div class="card bg-light mb-4">
        <div class="card-body">
            <h6 class="card-title mb-2">Condiciones comunes</h6>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPresetCondition('new-visitor')">
                    Visitante nuevo
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPresetCondition('returning-visitor')">
                    Visitante recurrente
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPresetCondition('specific-page')">
                    Pagina especifica
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPresetCondition('time-on-site')">
                    Tiempo en sitio
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPresetCondition('exit-intent')">
                    Intencion de salida
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addCondition()">
                    Condicion personalizada
                </button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between border-top pt-3">
        <a href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'appearance']) }}" class="btn btn-light">
            Anterior: Apariencia
        </a>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar condiciones</button>
            <a href="{{ route('helpdesk.campaigns.show', $campaign) }}" class="btn btn-light">
                Ver campana
            </a>
        </div>
    </div>
</form>

{{-- Condition block template --}}
<template id="condition-template">
    <div class="card mb-2 condition-block">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Campo</label>
                    <select class="form-select form-select-sm condition-field" name="conditions[][field]" onchange="updateConditionOperators(this)">
                        <option value="">Seleccionar...</option>
                        <optgroup label="Visitante">
                            <option value="visitor_type">Tipo de visitante</option>
                            <option value="visit_count">Numero de visitas</option>
                            <option value="time_on_site">Tiempo en sitio</option>
                            <option value="pages_visited">Paginas visitadas</option>
                        </optgroup>
                        <optgroup label="Pagina">
                            <option value="current_url">URL actual</option>
                            <option value="referrer">Referrer</option>
                            <option value="device_type">Tipo de dispositivo</option>
                        </optgroup>
                        <optgroup label="Comportamiento">
                            <option value="exit_intent">Intencion de salida</option>
                            <option value="scroll_depth">Profundidad de scroll</option>
                            <option value="idle_time">Tiempo inactivo</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Operador</label>
                    <select class="form-select form-select-sm condition-operator" name="conditions[][operator]">
                        <option value="equals">Es igual a</option>
                        <option value="not_equals">No es igual a</option>
                        <option value="contains">Contiene</option>
                        <option value="not_contains">No contiene</option>
                        <option value="greater_than">Mayor que</option>
                        <option value="less_than">Menor que</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Valor</label>
                    <input type="text" class="form-control form-control-sm condition-value"
                           name="conditions[][value]" placeholder="Valor a comparar">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-light-danger w-100" onclick="removeCondition(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
let conditionCounter = {{ count($conditions) }};

function addCondition() {
    const template = document.getElementById('condition-template');
    const clone = template.content.cloneNode(true);
    const container = document.getElementById('conditions-container');

    clone.querySelectorAll('[name^="conditions[]"]').forEach(input => {
        input.name = input.name.replace('[]', `[${conditionCounter}]`);
    });

    container.appendChild(clone);

    const alert = document.getElementById('no-conditions-alert');
    if (alert) { alert.remove(); }

    conditionCounter++;
}

function removeCondition(btn) {
    btn.closest('.condition-block').remove();

    const container = document.getElementById('conditions-container');
    if (container.children.length === 0 && !document.getElementById('no-conditions-alert')) {
        container.insertAdjacentHTML('beforebegin',
            '<div class="alert alert-warning mb-3" id="no-conditions-alert">Sin condiciones configuradas. La campana se mostrara a todos los visitantes.</div>'
        );
    }
}

function updateConditionOperators(select) {
    const row = select.closest('.row');
    const operatorSelect = row.querySelector('.condition-operator');
    const valueInput = row.querySelector('.condition-value');
    const field = select.value;

    const numericFields = ['visit_count', 'time_on_site', 'pages_visited', 'scroll_depth', 'idle_time'];
    const booleanFields = ['exit_intent'];

    if (numericFields.includes(field)) {
        operatorSelect.innerHTML = `
            <option value="equals">Es igual a</option>
            <option value="not_equals">No es igual a</option>
            <option value="greater_than">Mayor que</option>
            <option value="less_than">Menor que</option>
            <option value="greater_or_equal">Mayor o igual</option>
            <option value="less_or_equal">Menor o igual</option>`;
        valueInput.type = 'number';
        valueInput.placeholder = 'Numero';
    } else if (booleanFields.includes(field)) {
        operatorSelect.innerHTML = '<option value="equals">Es igual a</option>';
        valueInput.value = 'true';
        valueInput.placeholder = 'true o false';
    } else {
        operatorSelect.innerHTML = `
            <option value="equals">Es igual a</option>
            <option value="not_equals">No es igual a</option>
            <option value="contains">Contiene</option>
            <option value="not_contains">No contiene</option>
            <option value="starts_with">Empieza con</option>
            <option value="ends_with">Termina con</option>`;
        valueInput.type = 'text';
        valueInput.placeholder = 'Texto';
    }
}

const presets = {
    'new-visitor': { field: 'visitor_type', operator: 'equals', value: 'new' },
    'returning-visitor': { field: 'visitor_type', operator: 'equals', value: 'returning' },
    'specific-page': { field: 'current_url', operator: 'contains', value: '/productos' },
    'time-on-site': { field: 'time_on_site', operator: 'greater_than', value: '30' },
    'exit-intent': { field: 'exit_intent', operator: 'equals', value: 'true' },
};

function addPresetCondition(preset) {
    addCondition();
    const lastBlock = document.querySelector('#conditions-container .condition-block:last-child');
    const config = presets[preset];

    if (!config || !lastBlock) { return; }

    const fieldSelect = lastBlock.querySelector('.condition-field');
    fieldSelect.value = config.field;
    updateConditionOperators(fieldSelect);
    lastBlock.querySelector('.condition-operator').value = config.operator;
    lastBlock.querySelector('.condition-value').value = config.value;
}
</script>
@endpush

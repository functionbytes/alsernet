{{-- Conditions/Targeting Tab --}}
<form method="POST" action="{{ route('manager.helpdesk.campaigns.update', $campaign) }}" id="conditions-form">
    @csrf
    @method('PUT')

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> Condiciones de Visualización
            </h5>
            <button type="button" class="btn btn-sm btn-primary" onclick="addCondition()">
                <i class="fas fa-plus"></i> Agregar Condición
            </button>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>¿Qué son las condiciones?</strong><br>
            Las condiciones determinan <strong>cuándo y a quién</strong> se mostrará la campaña.
            Si no agregas condiciones, la campaña se mostrará a todos los visitantes.
        </div>

        @php
            $conditions = old('conditions', $campaign->conditions ?? []);
        @endphp

        @if(empty($conditions))
            <div class="alert alert-warning" id="no-conditions-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Sin condiciones</strong><br>
                La campaña se mostrará a todos los visitantes. Agrega condiciones para segmentar mejor tu audiencia.
            </div>
        @endif

        <div id="conditions-container" class="mb-3">
            @foreach($conditions as $index => $condition)
                @include('managers.views.helpdesk.campaigns.partials.condition-block', [
                    'condition' => $condition,
                    'index' => $index
                ])
            @endforeach
        </div>

        @if(count($conditions) > 0)
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <strong>Tienes {{ count($conditions) }} condición(es) configurada(s).</strong><br>
                La campaña se mostrará solo cuando <strong>todas</strong> las condiciones se cumplan (lógica AND).
            </div>
        @endif
    </div>

    {{-- Common Condition Templates --}}
    <div class="card bg-light mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="far fa-file-alt"></i> Plantillas Comunes</h6>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-2">Haz clic para agregar una condición predefinida:</p>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPresetCondition('new-visitor')">
                    <i class="fas fa-user-plus"></i> Visitante Nuevo
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPresetCondition('returning-visitor')">
                    <i class="fas fa-user-check"></i> Visitante Recurrente
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPresetCondition('specific-page')">
                    <i class="far fa-file"></i> Página Específica
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPresetCondition('time-on-site')">
                    <i class="far fa-clock"></i> Tiempo en Sitio
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPresetCondition('exit-intent')">
                    <i class="fas fa-sign-out-alt"></i> Intención de Salida
                </button>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between border-top pt-3">
        <a href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'appearance']) }}" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Anterior: Apariencia
        </a>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="far fa-save"></i> Guardar Condiciones
            </button>
            <a href="{{ route('manager.helpdesk.campaigns.show', $campaign) }}" class="btn btn-success">
                Finalizar y Ver Campaña <i class="fas fa-eye"></i>
            </a>
        </div>
    </div>
</form>

{{-- Condition Block Template (Hidden) --}}
<template id="condition-template">
    <div class="card mb-2 condition-block">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Campo</label>
                    <select class="form-select form-select-sm condition-field" name="conditions[][field]" onchange="updateConditionOperators(this)">
                        <option value="">Seleccionar...</option>
                        <optgroup label="Visitante">
                            <option value="visitor_type">Tipo de Visitante</option>
                            <option value="visit_count">Número de Visitas</option>
                            <option value="time_on_site">Tiempo en Sitio</option>
                            <option value="pages_visited">Páginas Visitadas</option>
                        </optgroup>
                        <optgroup label="Página">
                            <option value="current_url">URL Actual</option>
                            <option value="referrer">Referrer (de dónde viene)</option>
                            <option value="device_type">Tipo de Dispositivo</option>
                        </optgroup>
                        <optgroup label="Comportamiento">
                            <option value="exit_intent">Intención de Salida</option>
                            <option value="scroll_depth">Profundidad de Scroll</option>
                            <option value="idle_time">Tiempo Inactivo</option>
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
                    <input type="text" class="form-control form-control-sm condition-value" name="conditions[][value]" placeholder="Valor a comparar">
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

    // Update name attributes with index
    clone.querySelectorAll('[name^="conditions[]"]').forEach(input => {
        input.name = input.name.replace('[]', `[${conditionCounter}]`);
    });

    container.appendChild(clone);

    // Hide "no conditions" alert
    const alert = document.getElementById('no-conditions-alert');
    if (alert) alert.remove();

    conditionCounter++;
}

function removeCondition(btn) {
    if (confirm('¿Eliminar esta condición?')) {
        btn.closest('.condition-block').remove();

        // Show alert if no conditions left
        const container = document.getElementById('conditions-container');
        if (container.children.length === 0 && !document.getElementById('no-conditions-alert')) {
            container.insertAdjacentHTML('beforebegin', `
                <div class="alert alert-warning" id="no-conditions-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Sin condiciones</strong><br>
                    La campaña se mostrará a todos los visitantes. Agrega condiciones para segmentar mejor tu audiencia.
                </div>
            `);
        }
    }
}

function updateConditionOperators(select) {
    const field = select.value;
    const operatorSelect = select.closest('.row').querySelector('.condition-operator');
    const valueInput = select.closest('.row').querySelector('.condition-value');

    // Adjust operators based on field type
    const numericFields = ['visit_count', 'time_on_site', 'pages_visited', 'scroll_depth', 'idle_time'];
    const booleanFields = ['exit_intent'];

    if (numericFields.includes(field)) {
        operatorSelect.innerHTML = `
            <option value="equals">Es igual a</option>
            <option value="not_equals">No es igual a</option>
            <option value="greater_than">Mayor que</option>
            <option value="less_than">Menor que</option>
            <option value="greater_or_equal">Mayor o igual</option>
            <option value="less_or_equal">Menor o igual</option>
        `;
        valueInput.type = 'number';
        valueInput.placeholder = 'Número';
    } else if (booleanFields.includes(field)) {
        operatorSelect.innerHTML = `
            <option value="equals">Es igual a</option>
        `;
        valueInput.type = 'text';
        valueInput.value = 'true';
        valueInput.placeholder = 'true o false';
    } else {
        operatorSelect.innerHTML = `
            <option value="equals">Es igual a</option>
            <option value="not_equals">No es igual a</option>
            <option value="contains">Contiene</option>
            <option value="not_contains">No contiene</option>
            <option value="starts_with">Empieza con</option>
            <option value="ends_with">Termina con</option>
        `;
        valueInput.type = 'text';
        valueInput.placeholder = 'Texto';
    }
}

function addPresetCondition(preset) {
    addCondition();
    const lastCondition = document.querySelector('#conditions-container .condition-block:last-child');

    const presets = {
        'new-visitor': {
            field: 'visitor_type',
            operator: 'equals',
            value: 'new'
        },
        'returning-visitor': {
            field: 'visitor_type',
            operator: 'equals',
            value: 'returning'
        },
        'specific-page': {
            field: 'current_url',
            operator: 'contains',
            value: '/productos'
        },
        'time-on-site': {
            field: 'time_on_site',
            operator: 'greater_than',
            value: '30'
        },
        'exit-intent': {
            field: 'exit_intent',
            operator: 'equals',
            value: 'true'
        }
    };

    const config = presets[preset];
    if (config && lastCondition) {
        const fieldSelect = lastCondition.querySelector('.condition-field');
        const operatorSelect = lastCondition.querySelector('.condition-operator');
        const valueInput = lastCondition.querySelector('.condition-value');

        fieldSelect.value = config.field;
        updateConditionOperators(fieldSelect);
        operatorSelect.value = config.operator;
        valueInput.value = config.value;
    }
}
</script>
@endpush

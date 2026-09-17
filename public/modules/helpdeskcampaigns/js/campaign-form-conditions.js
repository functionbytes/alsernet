/**
 * HelpdeskCampaigns — campaign-form-conditions.js
 * Pestaña "Condiciones" del editor de campaña: agrega/quita condiciones,
 * adapta los operadores disponibles al campo elegido y aplica presets.
 * Espera `window.HcmConditionsTab = { conditionCounter }` inyectado desde
 * managers/campaigns/tabs/conditions.blade.php. Las funciones son globales
 * porque se invocan desde atributos onclick/onchange en el HTML.
 */
let conditionCounter = (window.HcmConditionsTab || {}).conditionCounter || 0;

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

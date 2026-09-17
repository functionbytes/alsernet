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
    const template = $('#condition-template')[0];
    const clone = template.content.cloneNode(true);
    const $container = $('#conditions-container');

    $(clone).find('[name^="conditions[]"]').each(function () {
        this.name = this.name.replace('[]', `[${conditionCounter}]`);
    });

    $container.append(clone);

    $('#no-conditions-alert').remove();

    conditionCounter++;
}

function removeCondition(btn) {
    $(btn).closest('.condition-block').remove();

    const $container = $('#conditions-container');
    if ($container.children().length === 0 && $('#no-conditions-alert').length === 0) {
        $container.before(
            '<div class="alert alert-warning mb-3" id="no-conditions-alert">Sin condiciones configuradas. La campana se mostrara a todos los visitantes.</div>'
        );
    }
}

function updateConditionOperators(select) {
    const $row = $(select).closest('.row');
    const $operatorSelect = $row.find('.condition-operator');
    const $valueInput = $row.find('.condition-value');
    const field = select.value;

    const numericFields = ['visit_count', 'time_on_site', 'pages_visited', 'scroll_depth', 'idle_time'];
    const booleanFields = ['exit_intent'];

    if (numericFields.includes(field)) {
        $operatorSelect.html(`
            <option value="equals">Es igual a</option>
            <option value="not_equals">No es igual a</option>
            <option value="greater_than">Mayor que</option>
            <option value="less_than">Menor que</option>
            <option value="greater_or_equal">Mayor o igual</option>
            <option value="less_or_equal">Menor o igual</option>`);
        $valueInput.attr('type', 'number').attr('placeholder', 'Numero');
    } else if (booleanFields.includes(field)) {
        $operatorSelect.html('<option value="equals">Es igual a</option>');
        $valueInput.val('true').attr('placeholder', 'true o false');
    } else {
        $operatorSelect.html(`
            <option value="equals">Es igual a</option>
            <option value="not_equals">No es igual a</option>
            <option value="contains">Contiene</option>
            <option value="not_contains">No contiene</option>
            <option value="starts_with">Empieza con</option>
            <option value="ends_with">Termina con</option>`);
        $valueInput.attr('type', 'text').attr('placeholder', 'Texto');
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
    const $lastBlock = $('#conditions-container .condition-block:last-child');
    const config = presets[preset];

    if (!config || !$lastBlock.length) { return; }

    const $fieldSelect = $lastBlock.find('.condition-field');
    $fieldSelect.val(config.field);
    updateConditionOperators($fieldSelect[0]);
    $lastBlock.find('.condition-operator').val(config.operator);
    $lastBlock.find('.condition-value').val(config.value);
}

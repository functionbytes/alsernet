/**
 * Helpdesk · Settings → Reglas de automatizacion (crear / editar).
 * Constructor dinamico de filas de condiciones (AND) y acciones: cada fila
 * es un mini formulario (campo/operador/valor o tipo/valor) que se
 * serializa a JSON en los inputs ocultos #conditions_json / #actions_json
 * justo antes de enviar el formulario. Los catalogos de campos, operadores
 * y tipos de accion llegan del servidor via window.HdAutomationRuleFormConfig
 * (son listas de configuracion, no datos de sesion).
 *
 * Select2 sobre los selects "estaticos" del formulario lo cubre
 * settings-common.js; las filas dinamicas se quedan con el <select> nativo,
 * igual que en el script original.
 */
(function ($) {
    'use strict';

    $(function () {
        const config = window.HdAutomationRuleFormConfig || {};
        const conditionFields = config.conditionFields || {};
        const conditionOperators = config.conditionOperators || {};
        const actionTypes = config.actionTypes || {};

        // ── helpers ─────────────────────────────────────────────────────────

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
                    field: $(this).find('[data-role="field"]').val(),
                    operator: $(this).find('[data-role="operator"]').val(),
                    value: $(this).find('[data-role="value"]').val(),
                });
            });
            $('#conditions_json').val(JSON.stringify(rows));
        }

        function serializeActions() {
            const rows = [];
            $('#actions-container .dynamic-row').each(function () {
                rows.push({
                    type: $(this).find('[data-role="type"]').val(),
                    value: $(this).find('[data-role="value"]').val(),
                });
            });
            $('#actions_json').val(JSON.stringify(rows));
        }

        // ── condition row ────────────────────────────────────────────────────

        function addConditionRow(field, operator, value) {
            field = field || Object.keys(conditionFields)[0];
            operator = operator || Object.keys(conditionOperators)[0];
            value = value || '';

            const fieldSel = buildSelect('', conditionFields, field).replace('<select name=""', '<select data-role="field"');
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

        // ── action row ───────────────────────────────────────────────────────

        function addActionRow(type, value) {
            type = type || Object.keys(actionTypes)[0];
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

        // ── load existing data ───────────────────────────────────────────────

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

        // ── events ───────────────────────────────────────────────────────────

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
    });
})(jQuery);

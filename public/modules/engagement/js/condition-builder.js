/**
 * Condition Builder for Engagement triggers, segments and personalizations.
 * Uses jQuery and Bootstrap 5.
 */
class ConditionBuilder {
    constructor(containerSelector, options = {}) {
        this.$container = $(containerSelector);
        this.rules = options.initialRules || [];
        this.operator = options.initialOperator || 'AND';
        this.onChange = options.onChange || (() => {});
        this.render();
    }

    render() {
        const operatorHtml = `
            <div class="mb-2">
                <select class="form-select form-select-sm d-inline-block w-auto cb-operator">
                    <option value="AND" ${this.operator === 'AND' ? 'selected' : ''}>TODAS (AND)</option>
                    <option value="OR" ${this.operator === 'OR' ? 'selected' : ''}>ALGUNA (OR)</option>
                </select>
            </div>
        `;

        const rulesHtml = this.rules.map((rule, idx) => this.renderRule(rule, idx)).join('');

        this.$container.html(`
            <div class="condition-builder border rounded p-3 bg-light">
                ${operatorHtml}
                <div class="cb-rules">${rulesHtml}</div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2 cb-add">
                    <i class="fas fa-plus"></i> Agregar condición
                </button>
            </div>
        `);

        this.bindEvents();
    }

    renderRule(rule, idx) {
        const fields = [
            {value: 'score', label: 'Score'},
            {value: 'segment', label: 'Segmento'},
            {value: 'event_count', label: 'Conteo de eventos'},
            {value: 'has_event', label: 'Tiene evento'},
            {value: 'country', label: 'País'},
            {value: 'city', label: 'Ciudad'},
            {value: 'language', label: 'Idioma'},
        ];

        const ops = [
            {value: 'eq', label: '='},
            {value: 'ne', label: '≠'},
            {value: 'gt', label: '>'},
            {value: 'gte', label: '≥'},
            {value: 'lt', label: '<'},
            {value: 'lte', label: '≤'},
            {value: 'contains', label: 'contiene'},
            {value: 'in', label: 'en'},
        ];

        return `
            <div class="d-flex align-items-center gap-2 mb-2 cb-rule" data-idx="${idx}">
                <select class="form-select form-select-sm cb-field" style="width:160px">
                    ${fields.map(f => `<option value="${f.value}" ${rule.field === f.value ? 'selected' : ''}>${f.label}</option>`).join('')}
                </select>
                <select class="form-select form-select-sm cb-op" style="width:100px">
                    ${ops.map(o => `<option value="${o.value}" ${rule.operator === o.value ? 'selected' : ''}>${o.label}</option>`).join('')}
                </select>
                <input type="text" class="form-control form-control-sm cb-value" value="${rule.value ?? ''}" placeholder="Valor">
                <button type="button" class="btn btn-sm btn-outline-danger cb-remove"><i class="fas fa-times"></i></button>
            </div>
        `;
    }

    bindEvents() {
        const self = this;
        this.$container.off();

        this.$container.on('change', '.cb-operator', function () {
            self.operator = $(this).val();
            self.emit();
        });

        this.$container.on('change input', '.cb-field, .cb-op, .cb-value', function () {
            self.syncRules();
            self.emit();
        });

        this.$container.on('click', '.cb-add', function () {
            self.rules.push({field: 'score', operator: 'gte', value: ''});
            self.render();
            self.emit();
        });

        this.$container.on('click', '.cb-remove', function () {
            $(this).closest('.cb-rule').remove();
            self.syncRules();
            self.emit();
        });
    }

    syncRules() {
        this.rules = [];
        this.$container.find('.cb-rule').each((_, el) => {
            this.rules.push({
                field: $(el).find('.cb-field').val(),
                operator: $(el).find('.cb-op').val(),
                value: $(el).find('.cb-value').val(),
            });
        });
    }

    emit() {
        this.onChange({operator: this.operator, rules: this.rules});
    }

    getValue() {
        return {operator: this.operator, rules: this.rules};
    }
}

window.ConditionBuilder = ConditionBuilder;

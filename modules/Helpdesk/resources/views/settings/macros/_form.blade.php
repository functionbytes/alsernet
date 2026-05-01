<div class="row g-3">

    {{-- Name --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $macro->name ?? '') }}"
            placeholder="Ej: Resolver y asignar al equipo tecnico">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Description --}}
    <div class="col-12">
        <label class="form-label">Descripcion <span class="text-muted">(opcional)</span></label>
        <textarea name="description" rows="3"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Describe brevemente que hace este macro...">{{ old('description', $macro->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Visibility --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Visibilidad <span class="text-danger">*</span></label>
        <select name="visibility" class="form-select @error('visibility') is-invalid @enderror">
            <option value="global" @selected(old('visibility', ($macro->is_shared ?? true) ? 'global' : 'personal') === 'global')>Global</option>
            <option value="personal" @selected(old('visibility', ($macro->is_shared ?? true) ? 'global' : 'personal') === 'personal')>Personal</option>
        </select>
        <div class="form-text">Los macros globales estan disponibles para todos los agentes</div>
        @error('visibility')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is active --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Estado</label>
        <div class="form-check mt-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" id="is_active"
                class="form-check-input"
                @checked(old('is_active', $macro->is_active ?? true))>
            <label class="form-check-label" for="is_active">Macro activo</label>
        </div>
        <div class="form-text">Los macros inactivos no aparecen en la lista de ejecucion</div>
    </div>

    {{-- Actions --}}
    <div class="col-12">
        <label class="form-label">
            Acciones del macro <span class="text-danger">*</span>
        </label>
        <div class="form-text mb-2">Define las acciones que se ejecutaran al aplicar este macro</div>

        @error('actions')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror

        <div id="macro-actions-list">
            {{-- Populated by JS from existing data or empty --}}
        </div>

        <button type="button" id="btn-add-action" class="btn btn-outline-secondary btn-sm mt-2">
            <i class="fas fa-plus"></i> Agregar accion
        </button>

        {{-- Hidden input that stores the serialized JSON of all actions --}}
        <input type="hidden" name="_actions_json" id="actions-json-input"
            value="{{ old('_actions_json', isset($macro) ? json_encode($macro->actions ?? []) : '[]') }}">
    </div>

</div>

{{-- Action row template (hidden, cloned by JS) --}}
<template id="action-row-template">
    <div class="action-row border rounded p-3 mb-2 bg-light">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <select class="form-select action-type-select">
                    <option value="">Seleccionar tipo...</option>
                    @foreach($actionTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7 action-value-wrapper">
                <input type="text" class="form-control action-value-input"
                    placeholder="Valor (opcional segun el tipo)">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-action">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
(function () {
    const actionTypeLabels = @json($actionTypes);

    // Action types that do NOT require a text value
    const noValueTypes = ['resolve_conversation', 'close_conversation'];

    function buildActionRow(type, value) {
        const template = document.getElementById('action-row-template');
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.action-row');
        const select = row.querySelector('.action-type-select');
        const input = row.querySelector('.action-value-input');

        if (type) {
            select.value = type;
            toggleValueInput(select, input);
        }

        if (value) {
            input.value = value;
        }

        select.addEventListener('change', function () {
            toggleValueInput(this, input);
            syncActions();
        });

        input.addEventListener('input', syncActions);

        row.querySelector('.btn-remove-action').addEventListener('click', function () {
            row.remove();
            syncActions();
        });

        return row;
    }

    function toggleValueInput(select, input) {
        const isNoValue = noValueTypes.includes(select.value);
        input.disabled = isNoValue;
        input.placeholder = isNoValue ? 'No requiere valor' : 'Valor (opcional segun el tipo)';
        if (isNoValue) {
            input.value = '';
        }
    }

    function syncActions() {
        const rows = document.querySelectorAll('.action-row');
        const actions = [];

        rows.forEach(function (row) {
            const type = row.querySelector('.action-type-select').value;
            const value = row.querySelector('.action-value-input').value.trim();
            if (type) {
                actions.push({ type: type, value: value || null });
            }
        });

        document.getElementById('actions-json-input').value = JSON.stringify(actions);
    }

    function loadExistingActions() {
        let existing = [];
        try {
            existing = JSON.parse(document.getElementById('actions-json-input').value) || [];
        } catch (e) {
            existing = [];
        }

        const list = document.getElementById('macro-actions-list');

        if (existing.length === 0) {
            const row = buildActionRow('', '');
            list.appendChild(row);
        } else {
            existing.forEach(function (action) {
                const row = buildActionRow(action.type, action.value);
                list.appendChild(row);
            });
        }
    }

    document.getElementById('btn-add-action').addEventListener('click', function () {
        const list = document.getElementById('macro-actions-list');
        const row = buildActionRow('', '');
        list.appendChild(row);
    });

    // Serialize before submit
    const form = document.getElementById('btn-add-action').closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            syncActions();

            // Convert _actions_json into proper actions[] fields
            const jsonInput = document.getElementById('actions-json-input');
            let actions = [];
            try {
                actions = JSON.parse(jsonInput.value) || [];
            } catch (e) {
                actions = [];
            }

            // Remove existing hidden action inputs before re-adding
            form.querySelectorAll('input[name^="actions["]').forEach(function (el) {
                el.remove();
            });

            actions.forEach(function (action, index) {
                const typeInput = document.createElement('input');
                typeInput.type = 'hidden';
                typeInput.name = 'actions[' + index + '][type]';
                typeInput.value = action.type;
                form.appendChild(typeInput);

                const valueInput = document.createElement('input');
                valueInput.type = 'hidden';
                valueInput.name = 'actions[' + index + '][value]';
                valueInput.value = action.value || '';
                form.appendChild(valueInput);
            });
        });
    }

    loadExistingActions();
})();
</script>
@endpush

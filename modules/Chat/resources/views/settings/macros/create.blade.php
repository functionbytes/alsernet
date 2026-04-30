@extends('layouts.theme')

@section('title', 'Crear macro')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Crear macro</h4>
            <p class="mb-0 text-muted">Define acciones automatizadas y condiciones para ejecutar en conversaciones</p>
        </div>
    </div>

    @include('core::components.alerts')

    <form id="macroForm" method="POST" action="{{ route('settings.chat.macros.store') }}">
        @csrf
        <input type="hidden" name="actions" id="actions-json">
        <input type="hidden" name="conditions" id="conditions-json">

        <div class="row">
            <!-- Columna principal (izquierda) -->
            <div class="col-lg-8">

                <!-- Card: Información básica -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Información básica</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">
                                    Nombre del macro
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required placeholder="ej: Asignar tickets urgentes">
                                <small class="form-text text-muted">Nombre descriptivo para este macro</small>
                                @error('name')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">
                                    Visibilidad
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="visibility" class="form-control select2 @error('visibility') is-invalid @enderror" required>
                                    <option value="{{ $visibilityOptions['global'] }}" {{ old('visibility') == $visibilityOptions['global'] ? 'selected' : '' }}>
                                        Global (Todos)
                                    </option>
                                    <option value="{{ $visibilityOptions['personal'] }}" {{ old('visibility') == $visibilityOptions['personal'] ? 'selected' : '' }}>
                                        Personal (Solo yo)
                                    </option>
                                    <option value="{{ $visibilityOptions['team'] }}" {{ old('visibility') == $visibilityOptions['team'] ? 'selected' : '' }}>
                                        Equipo
                                    </option>
                                </select>
                                @error('visibility')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                          rows="3" placeholder="Descripción opcional de lo que hace este macro">{{ old('description') }}</textarea>
                                <small class="form-text text-muted">Ayuda a otros usuarios a entender el propósito de este macro</small>
                                @error('description')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enabled" value="0">
                                    <input type="checkbox" name="enabled" class="form-check-input" id="enabledCheck"
                                           value="1" {{ old('enabled', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabledCheck">
                                        <strong>Macro activo</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">Cuando está activo, este macro puede ser ejecutado</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Condiciones -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Condiciones</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="add-condition">
                            <i class="fa fa-plus me-1"></i> Agregar
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Define cuándo debe ejecutarse este macro. Si no defines condiciones, el macro siempre se ejecutará.</p>

                        <div id="conditions-container"></div>

                        <div id="no-conditions-msg" class="text-center py-4">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-inline-flex align-items-center justify-content-center">
                                <i class="fa fa-filter fs-7"></i>
                            </div>
                            <h6 class="mb-1">Sin condiciones definidas</h6>
                            <p class="text-muted small mb-0">El macro siempre se ejecutará cuando sea invocado</p>
                        </div>
                    </div>
                </div>

                <!-- Card: Acciones -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            Acciones
                            <span class="text-danger">*</span>
                        </h5>
                        <button type="button" class="btn btn-primary btn-sm" id="add-action">
                            <i class="fa fa-plus me-1"></i> Agregar
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Define qué acciones se ejecutarán. Debes agregar al menos una acción.</p>

                        <div id="actions-container"></div>

                        <div id="no-actions-msg" class="text-center py-4">
                            <div class="round-48 rounded-circle bg-danger-subtle text-danger mb-3 d-inline-flex align-items-center justify-content-center">
                                <i class="fa fa-exclamation-triangle fs-7"></i>
                            </div>
                            <h6 class="mb-1 text-danger">Se requiere al menos una acción</h6>
                            <p class="text-muted small mb-0">Agrega acciones para definir qué hará este macro</p>
                        </div>
                    </div>
                </div>

                <!-- Footer con botones -->
                <div class="d-flex gap-2 justify-content-end mb-3">
                    <a href="{{ route('settings.chat.macros.index') }}" class="btn btn-light-danger">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i> Guardar
                    </button>
                </div>

            </div>

            <!-- Columna lateral (derecha) -->
            <div class="col-lg-4">

                <!-- Vista previa / Resumen -->
                <div class="card mb-3">
                    <div class="card-header bg-light-info">
                        <h6 class="card-title mb-0">
                            <i class="fa fa-eye me-2"></i>Vista previa
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="macro-preview">
                            <p class="text-muted small">El resumen se mostrará cuando agregues acciones</p>
                        </div>
                    </div>
                </div>

                <!-- Ayuda -->
                <div class="card mb-3">
                    <div class="card-header bg-light-primary">
                        <h6 class="card-title mb-0">
                            <i class="fa fa-circle-question me-2"></i>Ayuda
                        </h6>
                    </div>
                    <div class="card-body">
                        <h6 class="small fw-semibold mb-2">¿Qué es un macro?</h6>
                        <p class="small mb-3">Los macros son acciones automatizadas que puedes ejecutar en conversaciones.</p>

                        <h6 class="small fw-semibold mb-2">Condiciones</h6>
                        <p class="small mb-3">Define cuándo se puede ejecutar el macro (opcional).</p>

                        <h6 class="small fw-semibold mb-2">Acciones</h6>
                        <p class="small mb-0">Define qué hacer cuando se ejecute (requerido).</p>
                    </div>
                </div>

                <!-- Tips -->
                <div class="card">
                    <div class="card-header bg-light-success">
                        <h6 class="card-title mb-0">
                            <i class="fa fa-lightbulb me-2"></i>Consejos
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="small ps-3 mb-0">
                            <li class="mb-2">Usa nombres descriptivos</li>
                            <li class="mb-2">Combina condiciones para mayor precisión</li>
                            <li>Puedes agregar múltiples acciones</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </form>

@php
    // $users, $teams, $labels passed from controller
@endphp

@endsection

@push('scripts')
<script>
const dropdownData = {
    users: @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->full_name])),
    teams: @json($teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name])),
    labels: @json($labels->map(fn($l) => ['title' => $l->name]))
};

$(document).ready(function() {
    let actionIndex = 0;
    let conditionIndex = 0;

    const conditionFields = {
        'status': { label: 'Estado', type: 'select', options: ['open', 'pending', 'resolved'], icon: 'fa-circle-dot', badge: 'bg-success-subtle text-success' },
        'priority': { label: 'Prioridad', type: 'select', options: ['low', 'medium', 'high', 'urgent'], icon: 'fa-flag', badge: 'bg-danger-subtle text-danger' },
        'assignee_id': { label: 'Asignado a', type: 'select', options: 'users', icon: 'fa-user', badge: 'bg-info-subtle text-info' },
        'team_id': { label: 'Equipo', type: 'select', options: 'teams', icon: 'fa-users', badge: 'bg-info-subtle text-info' },
        'labels': { label: 'Etiquetas', type: 'text', icon: 'fa-tags', badge: 'bg-warning-subtle text-warning' },
        'message_count': { label: 'Cantidad de mensajes', type: 'number', icon: 'fa-comments', badge: 'bg-primary-subtle text-primary' },
        'waiting_since': { label: 'Esperando (minutos)', type: 'number', icon: 'fa-clock', badge: 'bg-secondary-subtle text-secondary' },
        'created_hours_ago': { label: 'Antigüedad (horas)', type: 'number', icon: 'fa-calendar', badge: 'bg-secondary-subtle text-secondary' },
        'contact_email': { label: 'Email del cliente', type: 'text', icon: 'fa-envelope', badge: 'bg-primary-subtle text-primary' },
    };

    const operators = {
        'equals': { label: 'Igual a', icon: 'fa-equals' },
        'not_equals': { label: 'Diferente de', icon: 'fa-not-equal' },
        'contains': { label: 'Contiene', icon: 'fa-search' },
        'greater_than': { label: 'Mayor que', icon: 'fa-greater-than' },
        'less_than': { label: 'Menor que', icon: 'fa-less-than' },
        'is_empty': { label: 'Está vacío', icon: 'fa-circle-xmark' },
        'is_not_empty': { label: 'No está vacío', icon: 'fa-circle-check' },
    };

    // === CONDICIONES ===
    function addCondition() {
        const index = conditionIndex++;
        const html = `
            <div class="card mb-3 border-start border-3 border-primary condition-item" data-index="${index}">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="round-40 rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center">
                            <i class="fa fa-filter text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-2">
                                <label class="form-label small mb-1">Campo</label>
                                <select class="form-control select2 form-select-sm condition-field" data-index="${index}">
                                    <option value="">Seleccionar campo...</option>
                                    ${Object.entries(conditionFields).map(([key, field]) =>
                                        `<option value="${key}" data-icon="${field.icon}" data-badge="${field.badge}">${field.label}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Operador</label>
                                    <select class="form-control select2 form-select-sm condition-operator" data-index="${index}">
                                        <option value="">Seleccionar...</option>
                                        ${Object.entries(operators).map(([key, op]) =>
                                            `<option value="${key}">${op.label}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="col-12 col-md-8">
                                    <label class="form-label small mb-1">Valor</label>
                                    <div class="condition-value-container" data-index="${index}">
                                        <input type="text" class="form-control form-control-sm" placeholder="Primero selecciona un campo" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 condition-badge-container" data-index="${index}"></div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-light-danger remove-condition" data-index="${index}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#conditions-container').append(html);
        updateConditionsVisibility();
    }

    function updateConditionValue(index) {
        const $item = $(`.condition-item[data-index="${index}"]`);
        const field = $item.find('.condition-field').val();
        const $container = $item.find('.condition-value-container');
        const $badgeContainer = $item.find('.condition-badge-container');
        const $iconContainer = $item.find('.round-40 i');

        if (!field) {
            $container.html('<input type="text" class="form-control form-control-sm" placeholder="Primero selecciona un campo" disabled>');
            $badgeContainer.html('');
            $iconContainer.attr('class', 'fa fa-filter text-primary');
            return;
        }

        const fieldDef = conditionFields[field];

        // Update icon
        $iconContainer.attr('class', `fa ${fieldDef.icon} text-primary`);

        // Update badge
        $badgeContainer.html(`<span class="badge ${fieldDef.badge}">${fieldDef.label}</span>`);

        let html = '';

        if (fieldDef.type === 'select') {
            const options = fieldDef.options === 'users' ? dropdownData.users :
                          fieldDef.options === 'teams' ? dropdownData.teams :
                          fieldDef.options;

            html = `<select class="form-control select2 form-select-sm condition-value" data-index="${index}">
                <option value="">Seleccionar...</option>`;

            if (Array.isArray(options) && typeof options[0] === 'object') {
                options.forEach(opt => {
                    html += `<option value="${opt.id}">${opt.name}</option>`;
                });
            } else {
                options.forEach(opt => {
                    html += `<option value="${opt}">${opt}</option>`;
                });
            }

            html += '</select>';
        } else if (fieldDef.type === 'number') {
            html = `<input type="number" class="form-control form-control-sm condition-value" data-index="${index}" placeholder="Ingresa un número">`;
        } else {
            html = `<input type="text" class="form-control form-control-sm condition-value" data-index="${index}" placeholder="Ingresa un valor">`;
        }

        $container.html(html);
    }

    function updateConditionsVisibility() {
        const hasConditions = $('.condition-item').length > 0;
        $('#no-conditions-msg').toggle(!hasConditions);
    }

    // === ACCIONES ===
    const actionTypes = {
        'assign_agent': { label: 'Asignar a agente', icon: 'fa-user-check', badge: 'bg-info-subtle text-info' },
        'assign_team': { label: 'Asignar a equipo', icon: 'fa-user-group', badge: 'bg-info-subtle text-info' },
        'add_label': { label: 'Agregar etiqueta', icon: 'fa-tag', badge: 'bg-warning-subtle text-warning' },
        'remove_label': { label: 'Quitar etiqueta', icon: 'fa-tag', badge: 'bg-warning-subtle text-warning' },
        'change_status': { label: 'Cambiar estado', icon: 'fa-toggle-on', badge: 'bg-success-subtle text-success' },
        'change_priority': { label: 'Cambiar prioridad', icon: 'fa-flag', badge: 'bg-danger-subtle text-danger' },
        'send_message': { label: 'Enviar mensaje', icon: 'fa-message', badge: 'bg-primary-subtle text-primary' },
        'add_private_note': { label: 'Agregar nota privada', icon: 'fa-note-sticky', badge: 'bg-secondary-subtle text-secondary' },
        'resolve_conversation': { label: 'Resolver conversación', icon: 'fa-circle-check', badge: 'bg-success-subtle text-success' },
        'reopen_conversation': { label: 'Reabrir conversación', icon: 'fa-arrow-rotate-left', badge: 'bg-warning-subtle text-warning' },
    };

    function addAction() {
        const index = actionIndex++;
        const html = `
            <div class="card mb-3 border-start border-3 border-success action-item" data-index="${index}">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="round-40 rounded-circle bg-success-subtle d-flex align-items-center justify-content-center">
                            <i class="fa fa-bolt text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-2">
                                <label class="form-label small mb-1">Tipo de acción <span class="text-danger">*</span></label>
                                <select class="form-control select2 form-select-sm action-type" data-index="${index}" required>
                                    <option value="">Seleccionar acción...</option>
                                    ${Object.entries(actionTypes).map(([key, action]) =>
                                        `<option value="${key}" data-icon="${action.icon}" data-badge="${action.badge}">${action.label}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="action-value-container" data-index="${index}"></div>
                            <div class="mt-2 action-badge-container" data-index="${index}"></div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-light-danger remove-action" data-index="${index}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#actions-container').append(html);
        updateActionsVisibility();
    }

    function updateActionValue(index) {
        const $item = $(`.action-item[data-index="${index}"]`);
        const actionType = $item.find('.action-type').val();
        const $container = $item.find('.action-value-container');
        const $badgeContainer = $item.find('.action-badge-container');
        const $iconContainer = $item.find('.round-40 i');

        if (!actionType) {
            $container.html('');
            $badgeContainer.html('');
            $iconContainer.attr('class', 'fa fa-bolt text-success');
            return;
        }

        const actionDef = actionTypes[actionType];

        // Update icon
        $iconContainer.attr('class', `fa ${actionDef.icon} text-success`);

        // Update badge
        $badgeContainer.html(`<span class="badge ${actionDef.badge}">${actionDef.label}</span>`);

        let html = '';

        switch(actionType) {
            case 'assign_agent':
                html = `
                    <label class="form-label small mb-1">Agente <span class="text-danger">*</span></label>
                    <select class="form-control select2 form-select-sm action-value" data-index="${index}" required>
                        <option value="">Seleccionar agente...</option>
                        ${dropdownData.users.map(u => `<option value="${u.id}"><i class="fa fa-user me-1"></i>${u.name}</option>`).join('')}
                    </select>`;
                break;

            case 'assign_team':
                html = `
                    <label class="form-label small mb-1">Equipo <span class="text-danger">*</span></label>
                    <select class="form-control select2 form-select-sm action-value" data-index="${index}" required>
                        <option value="">Seleccionar equipo...</option>
                        ${dropdownData.teams.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
                    </select>`;
                break;

            case 'add_label':
            case 'remove_label':
                html = `
                    <label class="form-label small mb-1">Etiqueta <span class="text-danger">*</span></label>
                    <select class="form-control select2 form-select-sm action-value" data-index="${index}" required>
                        <option value="">Seleccionar etiqueta...</option>
                        ${dropdownData.labels.map(l => `<option value="${l.title}">${l.title}</option>`).join('')}
                    </select>`;
                break;

            case 'change_status':
                html = `
                    <label class="form-label small mb-1">Estado <span class="text-danger">*</span></label>
                    <select class="form-control select2 form-select-sm action-value" data-index="${index}" required>
                        <option value="">Seleccionar estado...</option>
                        <option value="open">Abierto</option>
                        <option value="pending">Pendiente</option>
                        <option value="resolved">Resuelto</option>
                    </select>`;
                break;

            case 'change_priority':
                html = `
                    <label class="form-label small mb-1">Prioridad <span class="text-danger">*</span></label>
                    <select class="form-control select2 form-select-sm action-value" data-index="${index}" required>
                        <option value="">Seleccionar prioridad...</option>
                        <option value="low">Baja</option>
                        <option value="medium">Media</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>`;
                break;

            case 'send_message':
            case 'add_private_note':
                html = `
                    <label class="form-label small mb-1">Mensaje <span class="text-danger">*</span></label>
                    <textarea class="form-control form-control-sm action-value" data-index="${index}" rows="3" required placeholder="Escribir mensaje..."></textarea>`;
                break;

            case 'resolve_conversation':
            case 'reopen_conversation':
                html = `
                    <input type="hidden" class="action-value" data-index="${index}" value="">
                    <div class="alert alert-info mb-0 py-2">
                        <small><i class="fa fa-info-circle me-1"></i>Esta acción no requiere configuración adicional</small>
                    </div>`;
                break;

            default:
                html = '';
        }

        $container.html(html);
    }

    function updateActionsVisibility() {
        const hasActions = $('.action-item').length > 0;
        $('#no-actions-msg').toggle(!hasActions);
    }

    // === SERIALIZACIÓN ===
    function serializeConditions() {
        const conditionsList = [];

        $('.condition-item').each(function() {
            const $item = $(this);
            const index = $item.data('index');
            const field = $item.find('.condition-field').val();
            const operator = $item.find('.condition-operator').val();
            const value = $item.find('.condition-value').val();

            if (field && operator) {
                conditionsList.push({ field, operator, value });
            }
        });

        return conditionsList.length > 0 ? { operator: 'and', conditions: conditionsList } : null;
    }

    function serializeActions() {
        const actionsList = [];

        $('.action-item').each(function() {
            const $item = $(this);
            const index = $item.data('index');
            const action = $item.find('.action-type').val();
            const value = $item.find('.action-value').val();

            if (action) {
                actionsList.push({ action, value: value || '' });
            }
        });

        return actionsList;
    }

    // === EVENTOS ===
    $('#add-condition').on('click', function() {
        addCondition();
    });

    $('#add-action').on('click', function() {
        addAction();
    });

    $(document).on('change', '.condition-field, .condition-operator', function() {
        const index = $(this).data('index');
        updateConditionValue(index);
    });

    $(document).on('change', '.action-type', function() {
        const index = $(this).data('index');
        updateActionValue(index);
    });

    $(document).on('click', '.remove-condition', function() {
        $(this).closest('.condition-item').remove();
        updateConditionsVisibility();
    });

    $(document).on('click', '.remove-action', function() {
        $(this).closest('.action-item').remove();
        updateActionsVisibility();
    });

    // Envío del formulario
    $('#macroForm').on('submit', function(e) {
        const actions = serializeActions();
        const conditions = serializeConditions();

        if (actions.length === 0) {
            e.preventDefault();
            toastr.error('Por favor agrega al menos una acción', 'Error de validación');
            return false;
        }

        $('#actions-json').val(JSON.stringify(actions));
        $('#conditions-json').val(conditions ? JSON.stringify(conditions) : '');
    });

    // Actualizar vista previa del macro
    function updateMacroPreview() {
        const name = $('input[name="name"]').val() || 'Sin nombre';
        const actionsCount = $('.action-item').length;
        const conditionsCount = $('.condition-item').length;

        let html = `
            <div class="mb-3">
                <small class="text-muted d-block mb-1">Nombre</small>
                <strong>${name}</strong>
            </div>
            <div class="mb-3">
                <small class="text-muted d-block mb-1">Acciones</small>
                <span class="badge bg-success-subtle text-success">${actionsCount}</span>
            </div>
            <div class="mb-0">
                <small class="text-muted d-block mb-1">Condiciones</small>
                <span class="badge bg-info-subtle text-info">${conditionsCount}</span>
            </div>
        `;

        $('#macro-preview').html(html);
    }

    // Actualizar vista previa cuando cambien los campos
    $(document).on('change input', 'input[name="name"]', updateMacroPreview);
    $(document).on('click', '#add-action, .remove-action, #add-condition, .remove-condition', function() {
        setTimeout(updateMacroPreview, 100);
    });

    // Inicializar con una acción
    addAction();

    // Inicializar vista previa
    updateMacroPreview();

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
});
</script>
@endpush

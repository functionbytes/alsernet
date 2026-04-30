@extends('layouts.theme')

@section('title', 'Editar macro')

@section('content')

    @include('core::components.card', ['title' => 'Editar macro'])

    @include('core::components.alerts')

    <form id="macroForm" method="POST" action="{{ route('settings.chat.macros.update', $macro) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="actions" id="actions-json">
        <input type="hidden" name="conditions" id="conditions-json">

        <div class="row">
            <!-- Columna principal (izquierda) -->
            <div class="col-lg-8">

                <!-- Card unificado con todas las secciones -->
                <div class="card mb-3">
                    <div class="card-body">
                        <!-- Sección: Información básica -->
                        <h5 class="fw-bold mb-1">Información básica</h5>
                        <p class="small mb-3 text-muted">Datos generales del macro</p>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">
                                    Nombre del macro
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $macro->name) }}" required placeholder="ej: Asignar tickets urgentes">
                                <small class="form-text text-muted">Nombre descriptivo para este macro</small>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">
                                    Visibilidad
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="visibility" class="form-control select2 @error('visibility') is-invalid @enderror" required>
                                    <option value="{{ $visibilityOptions['global'] }}" {{ old('visibility', $macro->visibility) == $visibilityOptions['global'] ? 'selected' : '' }}>
                                        Global (Todos)
                                    </option>
                                    <option value="{{ $visibilityOptions['personal'] }}" {{ old('visibility', $macro->visibility) == $visibilityOptions['personal'] ? 'selected' : '' }}>
                                        Personal (Solo yo)
                                    </option>
                                    <option value="{{ $visibilityOptions['team'] }}" {{ old('visibility', $macro->visibility) == $visibilityOptions['team'] ? 'selected' : '' }}>
                                        Equipo
                                    </option>
                                </select>
                                @error('visibility')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                          rows="3" placeholder="Descripción opcional de lo que hace este macro">{{ old('description', $macro->description) }}</textarea>
                                <small class="form-text text-muted">Ayuda a otros usuarios a entender el propósito de este macro</small>
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enabled" value="0">
                                    <input type="checkbox" name="enabled" class="form-check-input" id="enabledCheck"
                                           value="1" {{ old('enabled', $macro->enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabledCheck">
                                        <strong>Macro activo</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">Cuando está activo, este macro puede ser ejecutado</small>
                            </div>
                        </div>

                        <hr>

                        <!-- Sección: Condiciones -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Condiciones</h5>
                                <p class="small mb-0 text-muted">Define cuándo debe ejecutarse este macro</p>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="add-condition">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>

                        <div id="conditions-container" class="list-group list-group-flush"></div>

                        <div id="no-conditions-msg" class="text-center py-4 mb-4">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-inline-flex align-items-center justify-content-center">
                                <i class="fas fa-filter fs-7"></i>
                            </div>
                            <h6 class="mb-1">Sin condiciones definidas</h6>
                            <p class="text-muted small mb-0">El macro siempre se ejecutará cuando sea invocado</p>
                        </div>

                        <hr>

                        <!-- Sección: Acciones -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">
                                    Acciones
                                    <span class="text-danger">*</span>
                                </h5>
                                <p class="small mb-0 text-muted">Define qué acciones se ejecutarán al usar el macro</p>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="add-action">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>

                        <div id="actions-container" class="list-group list-group-flush"></div>

                        <div id="no-actions-msg" class="text-center py-4 mb-4">
                            <div class="round-48 rounded-circle bg-danger-subtle text-danger mb-3 d-inline-flex align-items-center justify-content-center">
                                <i class="fas fa-exclamation-triangle fs-7"></i>
                            </div>
                            <h6 class="mb-1 text-danger">Se requiere al menos una acción</h6>
                            <p class="text-muted small mb-0">Agrega acciones para definir qué hará este macro</p>
                        </div>

                        <hr>

                        <!-- Botones de acción -->
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-check me-1"></i> Actualizar macro
                        </button>
                        <a href="{{ route('settings.chat.macros.index') }}" class="btn btn-light w-100">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                    </div>
                </div>

            </div>

            <!-- Columna lateral (derecha) -->
            <div class="col-lg-4">

                <!-- Estadísticas de uso -->
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-chart-simple me-2"></i>Estadísticas
                        </h5>
                        <p class="small mb-0 text-muted">Información de creación y modificación</p>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Creado</label>
                            <p class="mb-0">{{ $macro->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold small">Última modificación</label>
                            <p class="mb-0">{{ $macro->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Vista previa / Resumen -->
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-eye me-2"></i>Vista previa
                        </h5>
                        <p class="small mb-0 text-muted">Resumen del macro actual</p>
                    </div>
                    <div class="card-body">
                        <div id="macro-preview">
                            <p class="text-muted small mb-0">El resumen se mostrará cuando agregues acciones</p>
                        </div>
                    </div>
                </div>

                <!-- Ayuda -->
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-circle-question me-2"></i>Ayuda
                        </h5>
                        <p class="small mb-0 text-muted">Información útil sobre macros</p>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-1">¿Qué es un macro?</h6>
                            <p class="small mb-0 text-muted">Los macros son acciones automatizadas que puedes ejecutar en conversaciones.</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-semibold mb-1">Condiciones</h6>
                            <p class="small mb-0 text-muted">Define cuándo se puede ejecutar el macro (opcional).</p>
                        </div>

                        <div class="mb-0">
                            <h6 class="fw-semibold mb-1">Acciones</h6>
                            <p class="small mb-0 text-muted">Define qué hacer cuando se ejecute (requerido).</p>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="card">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-lightbulb me-2"></i>Consejos
                        </h5>
                        <p class="small mb-0 text-muted">Tips para crear macros efectivos</p>
                    </div>
                    <div class="card-body">
                        <ul class="small ps-3 mb-0 text-muted">
                            <li class="mb-2">Usa nombres descriptivos para identificar fácilmente el macro</li>
                            <li class="mb-2">Combina condiciones para mayor precisión en la ejecución</li>
                            <li>Puedes agregar múltiples acciones que se ejecutarán en orden</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </form>

@php
    // $users, $teams, $labels passed from controller
    $existingActions = old('actions', $macro->actions ?? []);
    $existingConditions = old('conditions', $macro->conditions ?? null);
@endphp

@endsection

@push('styles')
<style>
/* Estilos mejorados para condiciones y acciones */
.condition-item .card,
.action-item .card {
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.condition-item .card:hover,
.action-item .card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
    border-color: #d0d5dd;
}

/* Botón de eliminar */
.btn-light-danger {
    background-color: #fff1f0;
    border: 1px solid #ffccc7;
    color: #cf1322;
    transition: all 0.2s ease;
}

.btn-light-danger:hover {
    background-color: #ffccc7;
    border-color: #ffa39e;
    color: #a8071a;
}

/* Input group con iconos */
.input-group-text {
    border-right: 0;
    font-size: 0.875rem;
}

.input-group .form-select,
.input-group .form-control {
    border-left: 0;
}

.input-group .form-select:focus,
.input-group .form-control:focus {
    border-color: #ced4da;
    box-shadow: none;
}

/* Colores sutiles para iconos */
.bg-primary-subtle {
    background-color: #e6f4ff !important;
    border-color: #91caff !important;
}

.bg-info-subtle {
    background-color: #e6f7ff !important;
    border-color: #91d5ff !important;
}

.bg-success-subtle {
    background-color: #f6ffed !important;
    border-color: #b7eb8f !important;
}

.bg-warning-subtle {
    background-color: #fff7e6 !important;
    border-color: #ffd591 !important;
}

/* Ocultar contenedores vacíos */
#conditions-container:empty,
#actions-container:empty {
    display: none;
}

/* Mensajes de estado vacío */
#no-conditions-msg,
#no-actions-msg {
    border: 2px dashed #e9ecef;
    border-radius: 8px;
    background-color: #fafafa;
}

#no-conditions-msg .round-48,
#no-actions-msg .round-48 {
    width: 48px;
    height: 48px;
}

/* Mejorar labels en formulario */
.form-label.small {
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

/* Animación suave para agregar elementos */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.condition-item,
.action-item {
    animation: slideIn 0.3s ease;
}
</style>
@endpush

@push('scripts')
<script>
const dropdownData = {
    users: @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->full_name])),
    teams: @json($teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name])),
    labels: @json($labels->map(fn($l) => ['title' => $l->name]))
};

const existingActions = @json($existingActions);
const existingConditions = @json($existingConditions);

$(document).ready(function() {
    let actionIndex = 0;
    let conditionIndex = 0;

    const conditionFields = {
        'status': { label: 'Estado', type: 'select', options: ['open', 'pending', 'resolved'] },
        'priority': { label: 'Prioridad', type: 'select', options: ['low', 'medium', 'high', 'urgent'] },
        'assignee_id': { label: 'Asignado a', type: 'select', options: 'users' },
        'team_id': { label: 'Equipo', type: 'select', options: 'teams' },
        'labels': { label: 'Etiquetas', type: 'text' },
        'message_count': { label: 'Cantidad de mensajes', type: 'number' },
        'waiting_since': { label: 'Esperando (minutos)', type: 'number' },
        'created_hours_ago': { label: 'Antigüedad (horas)', type: 'number' },
        'contact_email': { label: 'Email del cliente', type: 'text' },
    };

    const operators = {
        'equals': 'Igual a',
        'not_equals': 'Diferente de',
        'contains': 'Contiene',
        'greater_than': 'Mayor que',
        'less_than': 'Menor que',
        'is_empty': 'Está vacío',
        'is_not_empty': 'No está vacío',
    };

    // === CONDICIONES ===
    function addCondition(field = '', operator = '', value = '') {
        const index = conditionIndex++;
        const html = `
            <div class="list-group-item condition-item mb-3 p-0 border-0" data-index="${index}">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 fw-semibold text-primary">
                                <i class="fas fa-filter me-2"></i>
                                Condición #${index + 1}
                            </h6>
                            <button type="button" class="btn btn-sm btn-light-danger text-danger remove-condition" data-index="${index}" title="Eliminar condición">
                                <i class="fas fa-trash-alt me-1"></i> Eliminar
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-2">Campo</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-primary-subtle">
                                        <i class="fas fa-tag text-primary"></i>
                                    </span>
                                    <select class="form-control select2 condition-field" data-index="${index}">
                                        <option value="">Seleccionar campo...</option>
                                        ${Object.entries(conditionFields).map(([key, fieldDef]) =>
                                            `<option value="${key}" ${field === key ? 'selected' : ''}>${fieldDef.label}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-2">Operador</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-info-subtle">
                                        <i class="fas fa-equals text-info"></i>
                                    </span>
                                    <select class="form-control select2 condition-operator" data-index="${index}">
                                        <option value="">Operador...</option>
                                        ${Object.entries(operators).map(([key, label]) =>
                                            `<option value="${key}" ${operator === key ? 'selected' : ''}>${label}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-2">Valor</label>
                                <div class="condition-value-container" data-index="${index}">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-success-subtle">
                                            <i class="fas fa-keyboard text-success"></i>
                                        </span>
                                        <input type="text" class="form-control condition-value" value="${value || ''}" placeholder="Valor">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#conditions-container').append(html);

        if (field) {
            updateConditionValue(index, value);
        }

        updateConditionsVisibility();
    }

    function updateConditionValue(index, currentValue = '') {
        const $item = $(`.condition-item[data-index="${index}"]`);
        const field = $item.find('.condition-field').val();
        const $container = $item.find('.condition-value-container');

        if (!field) {
            $container.html(`
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">
                        <i class="fas fa-keyboard text-muted"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Valor" disabled>
                </div>
            `);
            return;
        }

        const fieldDef = conditionFields[field];
        let html = '';

        if (fieldDef.type === 'select') {
            const options = fieldDef.options === 'users' ? dropdownData.users :
                          fieldDef.options === 'teams' ? dropdownData.teams :
                          fieldDef.options;

            html = `
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-success-subtle">
                        <i class="fas fa-list text-success"></i>
                    </span>
                    <select class="form-control select2 condition-value" data-index="${index}">
                        <option value="">Seleccionar...</option>`;

            if (Array.isArray(options) && typeof options[0] === 'object') {
                options.forEach(opt => {
                    html += `<option value="${opt.id}" ${currentValue == opt.id ? 'selected' : ''}>${opt.name}</option>`;
                });
            } else {
                options.forEach(opt => {
                    html += `<option value="${opt}" ${currentValue == opt ? 'selected' : ''}>${opt}</option>`;
                });
            }

            html += `</select>
                </div>`;
        } else if (fieldDef.type === 'number') {
            html = `
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-success-subtle">
                        <i class="fas fa-hashtag text-success"></i>
                    </span>
                    <input type="number" class="form-control condition-value" data-index="${index}" value="${currentValue || ''}" placeholder="0">
                </div>`;
        } else {
            html = `
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-success-subtle">
                        <i class="fas fa-keyboard text-success"></i>
                    </span>
                    <input type="text" class="form-control condition-value" data-index="${index}" value="${currentValue || ''}" placeholder="Valor">
                </div>`;
        }

        $container.html(html);
    }

    function updateConditionsVisibility() {
        const hasConditions = $('.condition-item').length > 0;
        $('#no-conditions-msg').toggle(!hasConditions);
    }

    // === ACCIONES ===
    function addAction(actionType = '', actionValue = '') {
        const index = actionIndex++;
        const html = `
            <div class="list-group-item action-item mb-3 p-0 border-0" data-index="${index}">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 fw-semibold text-success">
                                <i class="fas fa-bolt me-2"></i>
                                Acción #${index + 1}
                            </h6>
                            <button type="button" class="btn btn-sm btn-light-danger text-danger remove-action" data-index="${index}" title="Eliminar acción">
                                <i class="fas fa-trash-alt me-1"></i> Eliminar
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label small mb-2">Tipo de acción</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-success-subtle">
                                        <i class="fas fa-cog text-success"></i>
                                    </span>
                                    <select class="form-control select2 action-type" data-index="${index}" required>
                                        <option value="">Seleccionar acción...</option>
                                        <option value="assign_agent" ${actionType === 'assign_agent' ? 'selected' : ''}>Asignar a agente</option>
                                        <option value="assign_team" ${actionType === 'assign_team' ? 'selected' : ''}>Asignar a equipo</option>
                                        <option value="add_label" ${actionType === 'add_label' ? 'selected' : ''}>Agregar etiqueta</option>
                                        <option value="remove_label" ${actionType === 'remove_label' ? 'selected' : ''}>Quitar etiqueta</option>
                                        <option value="change_status" ${actionType === 'change_status' ? 'selected' : ''}>Cambiar estado</option>
                                        <option value="change_priority" ${actionType === 'change_priority' ? 'selected' : ''}>Cambiar prioridad</option>
                                        <option value="send_message" ${actionType === 'send_message' ? 'selected' : ''}>Enviar mensaje</option>
                                        <option value="add_private_note" ${actionType === 'add_private_note' ? 'selected' : ''}>Agregar nota privada</option>
                                        <option value="resolve_conversation" ${actionType === 'resolve_conversation' ? 'selected' : ''}>Resolver</option>
                                        <option value="reopen_conversation" ${actionType === 'reopen_conversation' ? 'selected' : ''}>Reabrir</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-7">
                                <label class="form-label small mb-2">Valor</label>
                                <div class="action-value-container" data-index="${index}"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#actions-container').append(html);

        if (actionType) {
            updateActionValue(index, actionValue);
        }

        updateActionsVisibility();
    }

    function updateActionValue(index, currentValue = '') {
        const $item = $(`.action-item[data-index="${index}"]`);
        const actionType = $item.find('.action-type').val();
        const $container = $item.find('.action-value-container');

        let html = '';

        switch(actionType) {
            case 'assign_agent':
                html = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-warning-subtle">
                            <i class="fas fa-user text-warning"></i>
                        </span>
                        <select class="form-control select2 action-value" data-index="${index}" required>
                            <option value="">Seleccionar agente...</option>
                            ${dropdownData.users.map(u => `<option value="${u.id}" ${currentValue == u.id ? 'selected' : ''}>${u.name}</option>`).join('')}
                        </select>
                    </div>`;
                break;

            case 'assign_team':
                html = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-warning-subtle">
                            <i class="fas fa-users text-warning"></i>
                        </span>
                        <select class="form-control select2 action-value" data-index="${index}" required>
                            <option value="">Seleccionar equipo...</option>
                            ${dropdownData.teams.map(t => `<option value="${t.id}" ${currentValue == t.id ? 'selected' : ''}>${t.name}</option>`).join('')}
                        </select>
                    </div>`;
                break;

            case 'add_label':
            case 'remove_label':
                html = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-warning-subtle">
                            <i class="fas fa-tags text-warning"></i>
                        </span>
                        <select class="form-control select2 action-value" data-index="${index}" required>
                            <option value="">Seleccionar etiqueta...</option>
                            ${dropdownData.labels.map(l => `<option value="${l.title}" ${currentValue == l.title ? 'selected' : ''}>${l.title}</option>`).join('')}
                        </select>
                    </div>`;
                break;

            case 'change_status':
                html = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-warning-subtle">
                            <i class="fas fa-circle-dot text-warning"></i>
                        </span>
                        <select class="form-control select2 action-value" data-index="${index}" required>
                            <option value="">Seleccionar estado...</option>
                            <option value="open" ${currentValue === 'open' ? 'selected' : ''}>Abierto</option>
                            <option value="pending" ${currentValue === 'pending' ? 'selected' : ''}>Pendiente</option>
                            <option value="resolved" ${currentValue === 'resolved' ? 'selected' : ''}>Resuelto</option>
                        </select>
                    </div>`;
                break;

            case 'change_priority':
                html = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-warning-subtle">
                            <i class="fas fa-flag text-warning"></i>
                        </span>
                        <select class="form-control select2 action-value" data-index="${index}" required>
                            <option value="">Seleccionar prioridad...</option>
                            <option value="low" ${currentValue === 'low' ? 'selected' : ''}>Baja</option>
                            <option value="medium" ${currentValue === 'medium' ? 'selected' : ''}>Media</option>
                            <option value="high" ${currentValue === 'high' ? 'selected' : ''}>Alta</option>
                            <option value="urgent" ${currentValue === 'urgent' ? 'selected' : ''}>Urgente</option>
                        </select>
                    </div>`;
                break;

            case 'send_message':
            case 'add_private_note':
                html = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-warning-subtle align-items-start pt-2">
                            <i class="fas fa-comment text-warning"></i>
                        </span>
                        <textarea class="form-control action-value" data-index="${index}" rows="3" required placeholder="Escribir mensaje...">${currentValue || ''}</textarea>
                    </div>`;
                break;

            case 'resolve_conversation':
            case 'reopen_conversation':
                html = `
                    <input type="hidden" class="action-value" data-index="${index}" value="">
                    <div class="alert alert-info mb-0 py-2 px-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Esta acción no requiere un valor adicional</small>
                    </div>`;
                break;

            default:
                html = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-keyboard text-muted"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Seleccionar acción primero" disabled>
                    </div>`;
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
        const $item = $(`.condition-item[data-index="${index}"]`);
        const currentValue = $item.find('.condition-value').val();
        updateConditionValue(index, currentValue);
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
                <label class="form-label fw-semibold small">Nombre</label>
                <p class="mb-0">${name}</p>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Acciones configuradas</label>
                <span class="badge ${actionsCount > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">${actionsCount}</span>
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold small">Condiciones definidas</label>
                <span class="badge ${conditionsCount > 0 ? 'bg-info-subtle text-info' : 'bg-light-subtle text-muted'}">${conditionsCount}</span>
            </div>
        `;

        $('#macro-preview').html(html);
    }

    // Actualizar vista previa cuando cambien los campos
    $(document).on('change input', 'input[name="name"]', updateMacroPreview);
    $(document).on('click', '#add-action, .remove-action, #add-condition, .remove-condition', function() {
        setTimeout(updateMacroPreview, 100);
    });

    // Cargar acciones existentes
    if (existingActions && Array.isArray(existingActions)) {
        existingActions.forEach(action => {
            // Handle both old format (action_name, action_params) and new format (action, value)
            const actionType = action.action || action.action_name;
            let actionValue = action.value;
            if (!actionValue && action.action_params) {
                const paramValue = Object.values(action.action_params)[0];
                actionValue = Array.isArray(paramValue) ? paramValue[0] : paramValue;
            }
            addAction(actionType, actionValue);
        });
    } else {
        addAction();
    }

    // Cargar condiciones existentes
    if (existingConditions && existingConditions.conditions && Array.isArray(existingConditions.conditions)) {
        existingConditions.conditions.forEach(condition => {
            addCondition(condition.field, condition.operator, condition.value);
        });
    }

    // Inicializar vista previa
    updateMacroPreview();

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

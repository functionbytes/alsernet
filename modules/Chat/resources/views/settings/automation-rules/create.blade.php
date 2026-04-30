@extends('layouts.theme')

@section('title', 'Crear regla de automatización')

@section('content')

    <div class="card w-100">

        <form action="{{ route('settings.chat.automation-rules.store') }}" method="POST" id="automation-form">

            @csrf

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear regla de automatización</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Define condiciones y acciones para automatizar procesos en las conversaciones.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y descripción de la regla.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="name" class="control-label col-form-label">
                                Nombre de la regla <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Ej: Asignar conversaciones nuevas a equipo de soporte"
                                   required>
                            @error('name')
                                <span class="field-validation-error">
                                    <i class="fas fa-circle-exclamation"></i> {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="event_name" class="control-label col-form-label">
                                Evento disparador <span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2 @error('event_name') is-invalid @enderror"
                                    id="event_name"
                                    name="event_name"
                                    required>
                                <option value="">Selecciona un evento...</option>
                                <option value="conversation_created" {{ old('event_name') == 'conversation_created' ? 'selected' : '' }}>
                                    Conversación creada
                                </option>
                                <option value="conversation_updated" {{ old('event_name') == 'conversation_updated' ? 'selected' : '' }}>
                                    Conversación actualizada
                                </option>
                                <option value="conversation_status_changed" {{ old('event_name') == 'conversation_status_changed' ? 'selected' : '' }}>
                                    Estado de conversación cambiado
                                </option>
                                <option value="message_created" {{ old('event_name') == 'message_created' ? 'selected' : '' }}>
                                    Mensaje creado
                                </option>
                            </select>
                            @error('event_name')
                                <span class="field-validation-error">
                                    <i class="fas fa-circle-exclamation"></i> {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="description" class="control-label col-form-label">Descripción</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="2"
                                      placeholder="Describe qué hace esta regla...">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="field-validation-error">
                                    <i class="fas fa-circle-exclamation"></i> {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <!-- Conditions -->
                    <div class="col-12">
                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="mb-1 fw-semibold">Condiciones <span class="text-danger">*</span></h6>
                                <p class="text-muted small mb-0">Define cuándo debe ejecutarse esta regla.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-condition">
                                <i class="fas fa-plus-circle"></i> Agregar condición
                            </button>
                        </div>

                        <div id="conditions-container"></div>

                        @error('conditions')
                            <span class="field-validation-error d-block mt-2">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Actions -->
                    <div class="col-12">
                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="mb-1 fw-semibold">Acciones <span class="text-danger">*</span></h6>
                                <p class="text-muted small mb-0">Define qué acciones se ejecutarán cuando se cumplan las condiciones.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-action">
                                <i class="fas fa-plus-circle"></i> Agregar acción
                            </button>
                        </div>

                        <div id="actions-container"></div>

                        @error('actions')
                            <span class="field-validation-error d-block mt-2">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración</h6>
                        <p class="text-muted small mb-3">Configura el estado de la regla.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="active"
                                       name="active"
                                       value="1"
                                       {{ old('active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">
                                    Activar regla
                                </label>
                            </div>
                            <small class="form-text text-muted">Se ejecutará automáticamente cuando se cumplan las condiciones</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.automation-rules.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@php
    // $users, $teams, $labels passed from controller
@endphp

@push('scripts')
<script>
const dropdownData = {
    users: @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->full_name])),
    teams: @json($teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name])),
    labels: @json($labels->map(fn($l) => ['id' => $l->name, 'name' => $l->name]))
};

$(document).ready(function() {
    let conditionIndex = 0;
    let actionIndex = 0;

    function createConditionValueInput(conditionType, index, currentValue = '') {
        let html = '';

        switch(conditionType) {
            case 'status':
                html = `<select class="form-control select2 form-select-sm" name="conditions[${index}][value]" required>
                    <option value="">Selecciona estado...</option>
                    <option value="open" ${currentValue == 'open' ? 'selected' : ''}>Abierto</option>
                    <option value="pending" ${currentValue == 'pending' ? 'selected' : ''}>Pendiente</option>
                    <option value="resolved" ${currentValue == 'resolved' ? 'selected' : ''}>Resuelto</option>
                </select>`;
                break;

            case 'priority':
                html = `<select class="form-control select2 form-select-sm" name="conditions[${index}][value]" required>
                    <option value="">Selecciona prioridad...</option>
                    <option value="low" ${currentValue == 'low' ? 'selected' : ''}>Baja</option>
                    <option value="medium" ${currentValue == 'medium' ? 'selected' : ''}>Media</option>
                    <option value="high" ${currentValue == 'high' ? 'selected' : ''}>Alta</option>
                    <option value="urgent" ${currentValue == 'urgent' ? 'selected' : ''}>Urgente</option>
                </select>`;
                break;

            case 'assignee_id':
                html = `<select class="form-control select2 form-select-sm" name="conditions[${index}][value]" required>
                    <option value="">Selecciona agente...</option>
                    ${dropdownData.users.map(u => `<option value="${u.id}" ${currentValue == u.id ? 'selected' : ''}>${u.name}</option>`).join('')}
                </select>`;
                break;

            case 'team_id':
                html = `<select class="form-control select2 form-select-sm" name="conditions[${index}][value]" required>
                    <option value="">Selecciona equipo...</option>
                    ${dropdownData.teams.map(t => `<option value="${t.id}" ${currentValue == t.id ? 'selected' : ''}>${t.name}</option>`).join('')}
                </select>`;
                break;

            default:
                html = `<input type="text" class="form-control form-control-sm" name="conditions[${index}][value]" placeholder="Valor" value="${currentValue}" required>`;
        }

        return html;
    }

    function createActionValueInput(actionType, index, currentValue = '') {
        let html = '';

        switch(actionType) {
            case 'assign_agent':
                html = `<select class="form-control select2 form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Selecciona agente...</option>
                    ${dropdownData.users.map(u => `<option value="${u.id}" ${currentValue == u.id ? 'selected' : ''}>${u.name}</option>`).join('')}
                </select>`;
                break;

            case 'assign_team':
                html = `<select class="form-control select2 form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Selecciona equipo...</option>
                    ${dropdownData.teams.map(t => `<option value="${t.id}" ${currentValue == t.id ? 'selected' : ''}>${t.name}</option>`).join('')}
                </select>`;
                break;

            case 'add_label':
            case 'remove_label':
                html = `<select class="form-control select2 form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Selecciona etiqueta...</option>
                    ${dropdownData.labels.map(l => `<option value="${l.id}" ${currentValue == l.id ? 'selected' : ''}>${l.name}</option>`).join('')}
                </select>`;
                break;

            case 'change_status':
                html = `<select class="form-control select2 form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Selecciona estado...</option>
                    <option value="open" ${currentValue == 'open' ? 'selected' : ''}>Abierto</option>
                    <option value="pending" ${currentValue == 'pending' ? 'selected' : ''}>Pendiente</option>
                    <option value="resolved" ${currentValue == 'resolved' ? 'selected' : ''}>Resuelto</option>
                </select>`;
                break;

            case 'change_priority':
                html = `<select class="form-control select2 form-select-sm" name="actions[${index}][value]" required>
                    <option value="">Selecciona prioridad...</option>
                    <option value="low" ${currentValue == 'low' ? 'selected' : ''}>Baja</option>
                    <option value="medium" ${currentValue == 'medium' ? 'selected' : ''}>Media</option>
                    <option value="high" ${currentValue == 'high' ? 'selected' : ''}>Alta</option>
                    <option value="urgent" ${currentValue == 'urgent' ? 'selected' : ''}>Urgente</option>
                </select>`;
                break;

            case 'send_message':
            case 'add_private_note':
                html = `<textarea class="form-control form-control-sm" name="actions[${index}][value]" rows="3" required placeholder="Escribe el mensaje...">${currentValue}</textarea>`;
                break;

            case 'resolve_conversation':
            case 'reopen_conversation':
                html = `<input type="hidden" name="actions[${index}][value]" value="">
                        <small class="text-muted">No se requiere valor para esta acción</small>`;
                break;

            default:
                html = `<input type="text" class="form-control form-control-sm" name="actions[${index}][value]" placeholder="Valor" value="${currentValue}">`;
        }

        return html;
    }

    $('#add-condition').on('click', function() {
        const conditionHtml = `
            <div class="card mb-2 condition-item" data-index="${conditionIndex}">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-start">
                        <div class="col-md-3">
                            <label class="form-label small">Atributo</label>
                            <select class="form-control select2 form-select-sm condition-attribute"
                                    name="conditions[${conditionIndex}][attribute]" required>
                                <option value="">Selecciona...</option>
                                <option value="status">Estado</option>
                                <option value="priority">Prioridad</option>
                                <option value="assignee_id">Agente asignado</option>
                                <option value="team_id">Equipo</option>
                                <option value="message_content">Contenido del mensaje</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Operador</label>
                            <select class="form-control select2 form-select-sm"
                                    name="conditions[${conditionIndex}][operator]" required>
                                <option value="equal_to">Igual a</option>
                                <option value="not_equal_to">Diferente de</option>
                                <option value="contains">Contiene</option>
                                <option value="does_not_contain">No contiene</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Valor</label>
                            <div class="condition-value-container"></div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-danger remove-condition d-block w-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#conditions-container').append(conditionHtml);
        conditionIndex++;
    });

    $('#add-action').on('click', function() {
        const actionHtml = `
            <div class="card mb-2 action-item" data-index="${actionIndex}">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-start">
                        <div class="col-md-4">
                            <label class="form-label small">Tipo de acción</label>
                            <select class="form-control select2 form-select-sm action-type"
                                    name="actions[${actionIndex}][action]" required>
                                <option value="">Selecciona...</option>
                                <option value="assign_agent">Asignar a agente</option>
                                <option value="assign_team">Asignar a equipo</option>
                                <option value="add_label">Añadir etiqueta</option>
                                <option value="remove_label">Quitar etiqueta</option>
                                <option value="change_status">Cambiar estado</option>
                                <option value="change_priority">Cambiar prioridad</option>
                                <option value="send_message">Enviar mensaje</option>
                                <option value="add_private_note">Añadir nota privada</option>
                                <option value="resolve_conversation">Resolver conversación</option>
                                <option value="reopen_conversation">Reabrir conversación</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">Valor</label>
                            <div class="action-value-container"></div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-danger remove-action d-block w-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#actions-container').append(actionHtml);
        actionIndex++;
    });

    $(document).on('change', '.condition-attribute', function() {
        const conditionItem = $(this).closest('.condition-item');
        const conditionType = $(this).val();
        const index = conditionItem.data('index');
        const container = conditionItem.find('.condition-value-container');
        container.html(createConditionValueInput(conditionType, index));
    });

    $(document).on('change', '.action-type', function() {
        const actionItem = $(this).closest('.action-item');
        const actionType = $(this).val();
        const index = actionItem.data('index');
        const container = actionItem.find('.action-value-container');
        container.html(createActionValueInput(actionType, index));
    });

    $(document).on('click', '.remove-condition', function() {
        $(this).closest('.condition-item').remove();
    });

    $(document).on('click', '.remove-action', function() {
        $(this).closest('.action-item').remove();
    });

    $('#add-condition').click();
    $('#add-action').click();

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

@extends('layouts.theme')

@section('title', 'Editar automatización')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .form-section {
        background: #fff;
        border-radius: 8px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .action-item {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        background: #f8f9fa;
        position: relative;
    }
    .action-item .remove-action {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
    }
    .action-type-select {
        border-left: 4px solid #90bb13;
    }
    .sticky-sidebar {
        position: sticky;
        top: 20px;
    }
    .stats-card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        height: 100%;
    }
    .stats-card .card-body {
        padding: 1.5rem;
    }
    .stats-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1;
    }
    .stats-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <a href="{{ route('settings.mailrelay.automations.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <h1 class="mb-0">Editar automatización</h1>
        </div>
        <p class="text-muted mb-0">Modifica la configuración de tu flujo de trabajo automatizado</p>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Errores de validación</h6>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stats-card card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-value text-primary">
                                <span class="badge bg-primary fs-4">{{ $automation->executions_count ?? 0 }}</span>
                            </div>
                            <div class="stats-label">Veces ejecutada</div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-sync fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-value text-info small">
                                {{ $automation->last_executed_at?->diffForHumans() ?? 'Nunca' }}
                            </div>
                            <div class="stats-label">Última ejecución</div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-clock fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stats-value text-success">
                                {{ $automation->success_rate ?? 0 }}%
                            </div>
                            <div class="stats-label">Tasa de éxito</div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-chart-line fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <p class="text-muted text-center mb-0">Los logs de ejecución se muestran en el panel de automations.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('settings.mailrelay.automations.update', $automation) }}" method="POST" id="automationForm">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">

                <!-- Sección 1: Información básica -->
                <div class="form-section">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Información básica
                    </h6>

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $automation->nombre) }}"
                               placeholder="ej. Bienvenida a nuevos suscriptores" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Nombre identificativo para esta automatización</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3"
                                  placeholder="Describe el propósito de esta automatización">{{ old('description', $automation->descripcion) }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="is_active" name="is_active" value="1"
                               {{ old('is_active', $automation->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Automatización activa
                        </label>
                        <small class="d-block text-muted">La automatización se ejecutará automáticamente cuando se cumplan las condiciones</small>
                    </div>
                </div>

                <!-- Sección 2: Trigger (disparador) -->
                <div class="form-section">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-bolt me-2"></i>Disparador
                    </h6>

                    <div class="mb-3">
                        <label for="trigger_type" class="form-label">
                            Tipo de evento <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('trigger_type') is-invalid @enderror"
                                id="trigger_type" name="trigger_type" required>
                            <option value="">Seleccionar evento...</option>
                            <option value="nuevo_suscriptor" {{ old('trigger_type', $automation->trigger_type) === 'nuevo_suscriptor' ? 'selected' : '' }}>
                                Nuevo suscriptor
                            </option>
                            <option value="suscriptor_actualizado" {{ old('trigger_type', $automation->trigger_type) === 'suscriptor_actualizado' ? 'selected' : '' }}>
                                Suscriptor actualizado
                            </option>
                            <option value="click_campana" {{ old('trigger_type', $automation->trigger_type) === 'click_campana' ? 'selected' : '' }}>
                                Click en campaña
                            </option>
                            <option value="apertura_email" {{ old('trigger_type', $automation->trigger_type) === 'apertura_email' ? 'selected' : '' }}>
                                Apertura de email
                            </option>
                            <option value="cancelacion_suscripcion" {{ old('trigger_type', $automation->trigger_type) === 'cancelacion_suscripcion' ? 'selected' : '' }}>
                                Cancelación de suscripción
                            </option>
                        </select>
                        @error('trigger_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="trigger_conditions" class="form-label">
                            Condiciones adicionales (opcional)
                        </label>
                        <textarea class="form-control @error('trigger_conditions') is-invalid @enderror"
                                  id="trigger_conditions" name="trigger_conditions" rows="3"
                                  placeholder='ej. {"grupo": "clientes-premium", "etiqueta": "interesado"}'
                        >{{ old('trigger_conditions', is_array($automation->trigger_conditions) ? json_encode($automation->trigger_conditions) : $automation->trigger_conditions) }}</textarea>
                        @error('trigger_conditions')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Formato JSON con condiciones específicas para activar la automatización</small>
                    </div>
                </div>

                <!-- Sección 3: Acciones -->
                <div class="form-section">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-tasks me-2"></i>Acciones
                    </h6>

                    <div id="actionsContainer">
                        @if($automation->actions)
                            @foreach($automation->actions as $index => $action)
                                <div class="action-item" id="action{{ $index }}" data-action-index="{{ $index }}">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-action" onclick="removeAction({{ $index }})">
                                        <i class="fas fa-times"></i>
                                    </button>

                                    <div class="mb-3">
                                        <label class="form-label">Tipo de acción</label>
                                        <select class="form-select action-type-select" name="actions[{{ $index }}][type]" required onchange="updateActionConfig({{ $index }}, this.value)">
                                            <option value="">Seleccionar...</option>
                                            <option value="enviar_email" {{ ($action['type'] ?? '') === 'enviar_email' ? 'selected' : '' }}>Enviar email</option>
                                            <option value="agregar_grupo" {{ ($action['type'] ?? '') === 'agregar_grupo' ? 'selected' : '' }}>Agregar a grupo</option>
                                            <option value="actualizar_campo" {{ ($action['type'] ?? '') === 'actualizar_campo' ? 'selected' : '' }}>Actualizar campo</option>
                                            <option value="esperar" {{ ($action['type'] ?? '') === 'esperar' ? 'selected' : '' }}>Esperar X días</option>
                                            <option value="webhook" {{ ($action['type'] ?? '') === 'webhook' ? 'selected' : '' }}>Webhook</option>
                                        </select>
                                    </div>

                                    <div id="actionConfig{{ $index }}">
                                        @if(($action['type'] ?? '') === 'enviar_email')
                                            <div class="mb-3">
                                                <label class="form-label">Plantilla de email</label>
                                                <select class="form-select" name="actions[{{ $index }}][config][template_id]" required>
                                                    <option value="">Seleccionar plantilla...</option>
                                                    <option value="{{ $action['config']['template_id'] ?? '' }}" selected>Plantilla seleccionada</option>
                                                </select>
                                            </div>
                                        @elseif(($action['type'] ?? '') === 'agregar_grupo')
                                            <div class="mb-3">
                                                <label class="form-label">Grupo destino</label>
                                                <select class="form-select" name="actions[{{ $index }}][config][group_id]" required>
                                                    <option value="">Seleccionar grupo...</option>
                                                    <option value="{{ $action['config']['group_id'] ?? '' }}" selected>Grupo seleccionado</option>
                                                </select>
                                            </div>
                                        @elseif(($action['type'] ?? '') === 'actualizar_campo')
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Campo</label>
                                                    <input type="text" class="form-control" name="actions[{{ $index }}][config][field]" required placeholder="ej. estado" value="{{ $action['config']['field'] ?? '' }}">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Valor</label>
                                                    <input type="text" class="form-control" name="actions[{{ $index }}][config][value]" required placeholder="ej. activo" value="{{ $action['config']['value'] ?? '' }}">
                                                </div>
                                            </div>
                                        @elseif(($action['type'] ?? '') === 'esperar')
                                            <div class="mb-3">
                                                <label class="form-label">Días de espera</label>
                                                <input type="number" class="form-control" name="actions[{{ $index }}][config][days]" required min="1" value="{{ $action['config']['days'] ?? 1 }}">
                                            </div>
                                        @elseif(($action['type'] ?? '') === 'webhook')
                                            <div class="mb-3">
                                                <label class="form-label">URL del webhook</label>
                                                <input type="url" class="form-control" name="actions[{{ $index }}][config][url]" required placeholder="https://ejemplo.com/webhook" value="{{ $action['config']['url'] ?? '' }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Método HTTP</label>
                                                <select class="form-select" name="actions[{{ $index }}][config][method]" required>
                                                    <option value="POST" {{ ($action['config']['method'] ?? 'POST') === 'POST' ? 'selected' : '' }}>POST</option>
                                                    <option value="GET" {{ ($action['config']['method'] ?? '') === 'GET' ? 'selected' : '' }}>GET</option>
                                                    <option value="PUT" {{ ($action['config']['method'] ?? '') === 'PUT' ? 'selected' : '' }}>PUT</option>
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <button type="button" class="btn btn-outline-primary" id="addActionBtn">
                        <i class="fas fa-plus-circle me-2"></i>Agregar acción
                    </button>
                </div>

                <!-- Sección 4: Configuración avanzada -->
                <div class="form-section">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-cog me-2"></i>Configuración avanzada
                    </h6>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="delay_minutes" class="form-label">Retraso inicial (minutos)</label>
                            <input type="number" class="form-control @error('delay_minutes') is-invalid @enderror"
                                   id="delay_minutes" name="delay_minutes"
                                   value="{{ old('delay_minutes', $automation->delay_minutes ?? 0) }}" min="0">
                            @error('delay_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Tiempo de espera antes de ejecutar la primera acción</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="max_executions" class="form-label">Máximo de ejecuciones</label>
                            <input type="number" class="form-control @error('max_executions') is-invalid @enderror"
                                   id="max_executions" name="max_executions"
                                   value="{{ old('max_executions', $automation->max_executions ?? 0) }}" min="0">
                            @error('max_executions')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">0 = ilimitado</small>
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="detailed_logging" name="detailed_logging" value="1"
                               {{ old('detailed_logging', $automation->detailed_logging) ? 'checked' : '' }}>
                        <label class="form-check-label" for="detailed_logging">
                            Registrar log detallado
                        </label>
                        <small class="d-block text-muted">Guarda información completa de cada ejecución (puede ocupar más espacio)</small>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <!-- Ayuda -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-lightbulb me-2"></i>Guía rápida
                        </div>
                        <div class="card-body">
                            <ul class="small mb-0 ps-3">
                                <li class="mb-2">Selecciona el evento que activará la automatización</li>
                                <li class="mb-2">Agrega las acciones que se ejecutarán</li>
                                <li class="mb-2">Las acciones se ejecutan en orden</li>
                                <li class="mb-2">Puedes usar retrasos entre acciones</li>
                                <li class="mb-2">Activa el logging detallado para debugging</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Tipos de acción -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-info-circle me-2"></i>Tipos de acción
                        </div>
                        <div class="card-body">
                            <small class="d-block mb-2"><strong>Enviar email:</strong> Envía un correo personalizado</small>
                            <small class="d-block mb-2"><strong>Agregar a grupo:</strong> Añade el suscriptor a un grupo</small>
                            <small class="d-block mb-2"><strong>Actualizar campo:</strong> Modifica información del suscriptor</small>
                            <small class="d-block mb-2"><strong>Esperar X días:</strong> Pausa antes de la siguiente acción</small>
                            <small class="d-block"><strong>Webhook:</strong> Envía datos a una URL externa</small>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Guardar cambios
                                </button>
                                <a href="{{ route('settings.mailrelay.automations.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let actionCounter = {{ count($automation->actions ?? []) }};
const existingActions = @json($automation->actions ?? []);

document.getElementById('addActionBtn').addEventListener('click', function() {
    addAction();
});

function addAction(actionData = null) {
    actionCounter++;
    const container = document.getElementById('actionsContainer');
    const actionType = actionData ? actionData.type : '';
    const actionConfig = actionData ? actionData.config : {};

    const actionHtml = `
        <div class="action-item" id="action${actionCounter}">
            <button type="button" class="btn btn-sm btn-outline-danger remove-action" onclick="removeAction(${actionCounter})">
                <i class="fas fa-times"></i>
            </button>

            <div class="mb-3">
                <label class="form-label">Tipo de acción</label>
                <select class="form-select action-type-select" name="actions[${actionCounter}][type]" required onchange="updateActionConfig(${actionCounter}, this.value)">
                    <option value="">Seleccionar...</option>
                    <option value="enviar_email" ${actionType === 'enviar_email' ? 'selected' : ''}>Enviar email</option>
                    <option value="agregar_grupo" ${actionType === 'agregar_grupo' ? 'selected' : ''}>Agregar a grupo</option>
                    <option value="actualizar_campo" ${actionType === 'actualizar_campo' ? 'selected' : ''}>Actualizar campo</option>
                    <option value="esperar" ${actionType === 'esperar' ? 'selected' : ''}>Esperar X días</option>
                    <option value="webhook" ${actionType === 'webhook' ? 'selected' : ''}>Webhook</option>
                </select>
            </div>

            <div id="actionConfig${actionCounter}">
                <!-- La configuración específica aparecerá aquí -->
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', actionHtml);

    if (actionType) {
        updateActionConfig(actionCounter, actionType, actionConfig);
    }
}

function removeAction(actionId) {
    const element = document.getElementById('action' + actionId);
    element.remove();
}

function updateActionConfig(actionId, type, existingConfig = {}) {
    const configDiv = document.getElementById('actionConfig' + actionId);
    let configHtml = '';

    switch(type) {
        case 'enviar_email':
            configHtml = `
                <div class="mb-3">
                    <label class="form-label">Plantilla de email</label>
                    <select class="form-select" name="actions[${actionId}][config][template_id]" required>
                        <option value="">Seleccionar plantilla...</option>
                        <!-- Las plantillas se cargarían dinámicamente -->
                    </select>
                </div>
            `;
            break;
        case 'agregar_grupo':
            configHtml = `
                <div class="mb-3">
                    <label class="form-label">Grupo destino</label>
                    <select class="form-select" name="actions[${actionId}][config][group_id]" required>
                        <option value="">Seleccionar grupo...</option>
                        <!-- Los grupos se cargarían dinámicamente -->
                    </select>
                </div>
            `;
            break;
        case 'actualizar_campo':
            configHtml = `
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Campo</label>
                        <input type="text" class="form-control" name="actions[${actionId}][config][field]" required placeholder="ej. estado" value="${existingConfig.field || ''}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor</label>
                        <input type="text" class="form-control" name="actions[${actionId}][config][value]" required placeholder="ej. activo" value="${existingConfig.value || ''}">
                    </div>
                </div>
            `;
            break;
        case 'esperar':
            configHtml = `
                <div class="mb-3">
                    <label class="form-label">Días de espera</label>
                    <input type="number" class="form-control" name="actions[${actionId}][config][days]" required min="1" value="${existingConfig.days || 1}">
                </div>
            `;
            break;
        case 'webhook':
            configHtml = `
                <div class="mb-3">
                    <label class="form-label">URL del webhook</label>
                    <input type="url" class="form-control" name="actions[${actionId}][config][url]" required placeholder="https://ejemplo.com/webhook" value="${existingConfig.url || ''}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Método HTTP</label>
                    <select class="form-select" name="actions[${actionId}][config][method]" required>
                        <option value="POST" ${existingConfig.method === 'POST' ? 'selected' : ''}>POST</option>
                        <option value="GET" ${existingConfig.method === 'GET' ? 'selected' : ''}>GET</option>
                        <option value="PUT" ${existingConfig.method === 'PUT' ? 'selected' : ''}>PUT</option>
                    </select>
                </div>
            `;
            break;
    }

    configDiv.innerHTML = configHtml;
}

// Las acciones existentes ya están renderizadas en Blade, no es necesario cargarlas con JavaScript
</script>
@endsection

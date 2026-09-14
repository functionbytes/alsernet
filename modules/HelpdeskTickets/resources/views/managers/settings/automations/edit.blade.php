@extends('layouts.theme')

@section('title', 'Editar automatizacion')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar automatizacion'])
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <form action="{{ route('manager.helpdesk.settings.automations.update', $automation) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    @include('core::components.alerts')

                    <h6 class="fw-semibold mb-1">Informacion basica</h6>
                    <p class="text-muted small mb-3">Nombre y descripcion de la automatizacion</p>

                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $automation->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripcion</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $automation->description) }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Trigger</h6>
                    <p class="text-muted small mb-3">Evento que dispara esta automatizacion</p>

                    <div class="mb-3">
                        <label class="form-label">Evento <span class="text-danger">*</span></label>
                        <select name="trigger_event" class="form-select select2" required>
                            @foreach($triggerEvents as $key => $label)
                                <option value="{{ $key }}" {{ old('trigger_event', $automation->trigger_event) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Condiciones</h6>
                    <p class="text-muted small mb-3">JSON array con condiciones. Ej: <code>[{"field": "priority", "op": "equals", "value": "high"}]</code></p>

                    <div class="mb-3">
                        <textarea name="conditions" class="form-control font-monospace" rows="4" required>{{ old('conditions', json_encode($automation->conditions ?? [])) }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Acciones</h6>
                    <p class="text-muted small mb-3">JSON array con acciones. Ej: <code>[{"type": "assign_group", "value": 1}]</code></p>

                    <div class="mb-3">
                        <textarea name="actions" class="form-control font-monospace" rows="4" required>{{ old('actions', json_encode($automation->actions ?? [])) }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Configuracion</h6>
                    <p class="text-muted small mb-3">Estado y prioridad de ejecucion</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Orden</label>
                            <input type="number" name="order" class="form-control" value="{{ old('order', $automation->order) }}">
                            <small class="text-muted">Menor = se ejecuta primero</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="is_active" class="form-select select2">
                                <option value="1" {{ old('is_active', $automation->is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Activa</option>
                                <option value="0" {{ old('is_active', $automation->is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactiva</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar</button>
                    <a href="{{ route('manager.helpdesk.settings.automations.index') }}" class="btn btn-light w-100">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Sobre las automatizaciones</h6>
            </div>
            <div class="card-body">
                <p class="card-text text-muted mb-0">
                    Una automatizacion vigila los tickets y, cuando se cumplen sus condiciones,
                    ejecuta las acciones definidas sin intervencion del agente.
                </p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Buenas practicas</h6>
            </div>
            <div class="card-body">
                <ul class="text-muted mb-0">
                    <li class="mb-2">Empieza por una condicion concreta y amplia despues</li>
                    <li class="mb-2">Comprueba el orden: se aplican de la primera a la ultima</li>
                    <li class="mb-2">Evita dos reglas que cambien el mismo campo</li>
                    <li class="mb-0">Desactivala en vez de borrarla mientras la pruebas</li>
                </ul>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Operadores de condicion</h6>
            </div>
            <div class="card-body">
                <ul class="small text-muted mb-0">
                    <li><code>equals</code> — igual</li>
                    <li><code>not_equals</code> — distinto</li>
                    <li><code>contains</code> — contiene</li>
                    <li><code>greater_than</code> — mayor que</li>
                    <li><code>less_than</code> — menor que</li>
                    <li><code>is_null</code> / <code>is_not_null</code></li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Tipos de accion</h6>
            </div>
            <div class="card-body">
                <ul class="small text-muted mb-0">
                    <li><code>assign_group</code> — asignar grupo (value: group_id)</li>
                    <li><code>assign_user</code> — asignar agente (value: user_id)</li>
                    <li><code>set_priority</code> — establecer prioridad</li>
                    <li><code>set_status</code> — cambiar estado (value: status_id)</li>
                    <li><code>add_tag</code> — añadir etiqueta</li>
                    <li><code>close</code> — cerrar ticket</li>
                    <li><code>add_internal_note</code> — añadir nota interna (value: texto)</li>
                    <li><code>notify_agent</code> — avisar al agente asignado</li>
                    <li><code>ai_route</code> — enrutar con IA: aplica la categoría sugerida y asigna agente por carga e idioma. No pisa nada puesto a mano y, por debajo del umbral de confianza, deja el ticket sin asignar</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush

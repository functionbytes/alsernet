@extends('layouts.theme')

@section('title', 'Nueva automatizacion')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva automatizacion'])
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <form action="{{ route('manager.helpdesk.settings.automations.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    @include('core::components.alerts')

                    <h6 class="fw-semibold mb-1">Informacion basica</h6>
                    <p class="text-muted small mb-3">Nombre y descripcion de la automatizacion</p>

                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripcion</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Trigger</h6>
                    <p class="text-muted small mb-3">Evento que dispara esta automatizacion</p>

                    <div class="mb-3">
                        <label class="form-label">Evento <span class="text-danger">*</span></label>
                        <select name="trigger_event" class="form-select" required>
                            @foreach($triggerEvents as $key => $label)
                                <option value="{{ $key }}" {{ old('trigger_event') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Condiciones</h6>
                    <p class="text-muted small mb-3">JSON array con condiciones. Ej: <code>[{"field": "priority", "op": "equals", "value": "high"}]</code></p>

                    <div class="mb-3">
                        <textarea name="conditions" class="form-control font-monospace" rows="4" required>{{ old('conditions', '[]') }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Acciones</h6>
                    <p class="text-muted small mb-3">JSON array con acciones. Ej: <code>[{"type": "assign_group", "value": 1}]</code></p>

                    <div class="mb-3">
                        <textarea name="actions" class="form-control font-monospace" rows="4" required>{{ old('actions', '[]') }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Configuracion</h6>
                    <p class="text-muted small mb-3">Estado y prioridad de ejecucion</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Orden</label>
                            <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}">
                            <small class="text-muted">Menor = se ejecuta primero</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Activa</option>
                                <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactiva</option>
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
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-2"><i class="fas fa-lightbulb me-1 text-warning"></i> Operadores de condicion</h6>
                <ul class="small text-muted mb-3">
                    <li><code>equals</code> — igual</li>
                    <li><code>not_equals</code> — distinto</li>
                    <li><code>contains</code> — contiene</li>
                    <li><code>greater_than</code> — mayor que</li>
                    <li><code>less_than</code> — menor que</li>
                    <li><code>is_null</code> / <code>is_not_null</code></li>
                </ul>

                <h6 class="fw-semibold mb-2 mt-3">Tipos de accion</h6>
                <ul class="small text-muted mb-0">
                    <li><code>assign_group</code> — asignar grupo (value: group_id)</li>
                    <li><code>assign_user</code> — asignar agente (value: user_id)</li>
                    <li><code>set_priority</code> — establecer prioridad</li>
                    <li><code>set_status</code> — cambiar estado (value: status_id)</li>
                    <li><code>add_tag</code> — añadir etiqueta</li>
                    <li><code>close</code> — cerrar ticket</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

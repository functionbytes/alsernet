@extends('layouts.theme')

@section('title', 'Nueva automatización')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva automatización'])
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <form action="{{ route('manager.helpdesk.settings.automations.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    @include('core::components.alerts')

                    <h6 class="fw-semibold mb-1">Información básica</h6>
                    <p class="text-muted small mb-3">Nombre y descripción de la automatización</p>

                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-brand">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Disparador</h6>
                    <p class="text-muted small mb-3">Evento que dispara esta automatización</p>

                    <div class="mb-3">
                        <label class="form-label">Evento <span class="text-brand">*</span></label>
                        <select name="trigger_event" class="form-select select2" required>
                            @foreach($triggerEvents as $key => $label)
                                <option value="{{ $key }}" {{ old('trigger_event') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Condiciones</h6>
                    <p class="text-muted small mb-3">JSON array con condiciones. Ej: <code>[{"field": "priority", "op": "equals", "value": "high"}]</code></p>

                    <div class="mb-3">
                        <label class="form-label" for="automation-match-mode">Se aplica cuando se cumplen</label>
                        <select name="match_mode" id="automation-match-mode" class="form-select">
                            <option value="all" @selected(old('match_mode', 'all') === 'all')>Todas las condiciones</option>
                            <option value="any" @selected(old('match_mode', 'all') === 'any')>Cualquiera de las condiciones</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <textarea name="conditions" class="form-control font-monospace" rows="4" required>{{ old('conditions', '[]') }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Acciones</h6>
                    <p class="text-muted small mb-3">JSON array con acciones. Ej: <code>[{"type": "assign_group", "value": 1}]</code></p>

                    <div class="mb-3">
                        <textarea name="actions" class="form-control font-monospace" rows="4" required>{{ old('actions', '[]') }}</textarea>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Configuración</h6>
                    <p class="text-muted small mb-3">Estado y prioridad de ejecución</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Orden</label>
                            <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}">
                            <small class="text-muted">Menor = se ejecuta primero</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="is_active" class="form-select select2">
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
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Sobre las automatizaciones</h6>
            </div>
            <div class="card-body">
                <p class="card-text text-muted mb-0">
                    Una automatización vigila los tickets y, cuando se cumplen sus condiciones,
                    ejecuta las acciones definidas sin intervención del agente.
                </p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Buenas prácticas</h6>
            </div>
            <div class="card-body">
                <ul class="text-muted mb-0">
                    <li class="mb-2">Empieza por una condición concreta y amplía después</li>
                    <li class="mb-2">Comprueba el orden: se aplican de la primera a la última</li>
                    <li class="mb-2">Evita dos reglas que cambien el mismo campo</li>
                    <li class="mb-0">Desactívala en vez de borrarla mientras la pruebas</li>
                </ul>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Operadores de condición</h6>
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
                <h6 class="mb-0 fw-bold">Tipos de acción</h6>
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
<script src="{{ asset('modules/helpdesktickets/js/select2-init.js') }}"></script>
@endpush

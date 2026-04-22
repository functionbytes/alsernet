@extends('layouts.theme')

@section('title', 'Editar macro')

@section('content')
@include('core::components.card', ['title' => 'Editar macro'])

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <form action="{{ route('manager.helpdesk.settings.macros.update', $macro) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    @include('core::components.alerts')

                    <h6 class="fw-semibold mb-1">Informacion basica</h6>
                    <p class="text-muted small mb-3">Nombre y descripcion de la macro</p>

                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $macro->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripcion</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="2">{{ old('description', $macro->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Acciones</h6>
                    <p class="text-muted small mb-3">
                        JSON array con las acciones a ejecutar.
                        Ej: <code>[{"type": "set_status", "value": 2}, {"type": "reply", "body": "Hola {{customer_name}}"}]</code>
                    </p>

                    <div class="mb-3">
                        <textarea name="actions" class="form-control font-monospace @error('actions') is-invalid @enderror"
                                  rows="6" required>{{ old('actions', json_encode($macro->actions ?? [], JSON_PRETTY_PRINT)) }}</textarea>
                        @error('actions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Configuracion</h6>
                    <p class="text-muted small mb-3">Visibilidad y estado de la macro</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Visibilidad</label>
                            <select name="is_shared" class="form-select">
                                <option value="1" {{ old('is_shared', $macro->is_shared ? 1 : 0) == 1 ? 'selected' : '' }}>Compartida (equipo)</option>
                                <option value="0" {{ old('is_shared', $macro->is_shared ? 1 : 0) == 0 ? 'selected' : '' }}>Personal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ old('is_active', $macro->is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Activa</option>
                                <option value="0" {{ old('is_active', $macro->is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fas fa-chart-bar me-1"></i>
                            Usada {{ $macro->usage_count }} veces.
                            @if($macro->last_used_at)
                                Ultimo uso: {{ $macro->last_used_at->diffForHumans() }}.
                            @endif
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar</button>
                    <a href="{{ route('manager.helpdesk.settings.macros.index') }}" class="btn btn-light w-100">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-2"><i class="fas fa-lightbulb me-1 text-warning"></i> Tipos de accion disponibles</h6>
                <ul class="small text-muted mb-3">
                    @foreach($actionTypes as $key => $label)
                        <li><code>{{ $key }}</code> — {{ $label }}</li>
                    @endforeach
                </ul>

                <h6 class="fw-semibold mb-2 mt-3">Variables de interpolacion</h6>
                <ul class="small text-muted mb-3">
                    <li><code>{{ticket_number}}</code> — numero de ticket</li>
                    <li><code>{{ticket_title}}</code> — asunto del ticket</li>
                    <li><code>{{customer_name}}</code> — nombre del cliente</li>
                    <li><code>{{agent_name}}</code> — nombre del agente</li>
                </ul>

                <h6 class="fw-semibold mb-2 mt-3">Ejemplo de acciones</h6>
                <pre class="small bg-light p-2 rounded mb-0">[
  {"type": "set_status", "value": 2},
  {"type": "assign_user", "value": 5},
  {"type": "reply", "body": "Hola {{customer_name}}, hemos recibido tu solicitud."}
]</pre>
            </div>
        </div>
    </div>
</div>
@endsection

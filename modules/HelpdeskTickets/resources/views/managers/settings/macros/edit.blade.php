@extends('layouts.theme')

@section('title', 'Editar macro')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar macro'])
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <form action="{{ route('manager.helpdesk.settings.macros.update', $macro) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    @include('core::components.alerts')

                    <h6 class="fw-semibold mb-1">Informacion basica</h6>
                    <p class="text-muted small mb-3">El nombre es lo que el agente ve en el selector de macros de la ficha del ticket; la descripcion le ayuda a elegir la correcta.</p>

                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-brand">*</span></label>
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
                        Lista JSON de acciones. Se ejecutan en orden, de arriba abajo, y todas dentro de la misma
                        operacion: si una falla no se aplica ninguna, asi el ticket nunca queda a medias.
                    </p>

                    <div class="mb-3">
                        <textarea name="actions" class="form-control font-monospace @error('actions') is-invalid @enderror"
                                  rows="6" required>{{ old('actions', json_encode($macro->actions ?? [], JSON_PRETTY_PRINT)) }}</textarea>
                        @error('actions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">
                            Cada accion es un objeto con su <code>type</code> y el dato que necesite. Tienes los tipos
                            disponibles y las variables <code>@{{...}}</code> en el panel de la derecha.
                        </small>
                    </div>

                    <h6 class="fw-semibold mb-1 mt-4">Configuracion</h6>
                    <p class="text-muted small mb-3">Una macro compartida esta disponible para todo el equipo; una personal, solo para ti. Las inactivas no aparecen en la ficha del ticket.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Visibilidad</label>
                            <select name="is_shared" class="form-select select2">
                                <option value="1" {{ old('is_shared', $macro->is_shared ? 1 : 0) == 1 ? 'selected' : '' }}>Compartida (equipo)</option>
                                <option value="0" {{ old('is_shared', $macro->is_shared ? 1 : 0) == 0 ? 'selected' : '' }}>Personal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="is_active" class="form-select select2">
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
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Sobre las macros</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-0">
                    Una macro encadena varias acciones sobre un ticket para aplicarlas de un clic desde la
                    ficha del ticket. Se definen como una lista JSON: cada elemento es una accion con su
                    <code>type</code> y el dato que necesite.
                </p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Tipos de accion</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Que espera cada accion ademas de su <code>type</code>.</p>
                @foreach($actionTypes as $key => $label)
                    @php($spec = \Modules\HelpdeskTickets\Models\Macro::actionSpecs()[$key] ?? null)
                    <div class="d-flex justify-content-between small mb-1">
                        <code>{{ $key }}</code>
                        <span class="text-muted text-end ms-2">{{ $label }}</span>
                    </div>
                    <div class="small text-muted mb-2 ps-2">
                        @if($spec && $spec['key'])
                            <code>{{ $spec['key'] }}</code>: {{ $spec['hint'] }}
                        @else
                            {{ $spec['hint'] ?? '' }}
                        @endif
                        @foreach($spec['optional'] ?? [] as $optKey => $optHint)
                            <br><code>{{ $optKey }}</code> (opcional): {{ $optHint }}
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Variables disponibles</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Se sustituyen al aplicar la macro, dentro de <code>body</code> (y de <code>subject</code> en la accion <code>reply</code>).</p>
                @foreach(\Modules\HelpdeskTickets\Services\TicketVariableInterpolator::availableVariables() as $group => $vars)
                    <div class="mb-3">
                        <div class="small fw-semibold mb-1">{{ $group }}</div>
                        @foreach($vars as $var => $desc)
                            <div class="d-flex justify-content-between small mb-1">
                                <code>{{ $var }}</code>
                                <span class="text-muted text-end ms-2">{{ $desc }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Ejemplo</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">Responder al cliente, asignar el ticket y cerrarlo.</p>
                                <pre class="small bg-light p-2 rounded mb-0 overflow-auto">[
                  {"type": "reply", "subject": "Re: @{{ticket_subject}}", "body": "Hola @{{customer_name}}, ya esta resuelto."},
                  {"type": "assign_user", "value": 5},
                  {"type": "close"}
                ]</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('modules/helpdesktickets/js/select2-init.js') }}"></script>
@endpush

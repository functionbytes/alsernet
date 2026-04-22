@extends('layouts.theme')

@section('title', 'Nueva vista guardada')

@section('content')

    @include('core::components.card', ['title' => 'Nueva vista guardada'])

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="viewForm" action="{{ route('manager.helpdesk.settings.ticket-views.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva vista guardada</h5>
                        <small class="text-muted">Crea una vista personalizada para el listado de tickets</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Informacion basica --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre y descripcion visible de la vista guardada</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Tickets abiertos de hoy"
                                       required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion</label>
                                <textarea name="description"
                                          class="form-control @error('description') is-invalid @enderror"
                                          rows="2"
                                          placeholder="Describe que tickets muestra esta vista">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Ordenacion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Ordenacion</h6>
                        <p class="text-muted small mb-3">Criterio y direccion por defecto al aplicar la vista</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Ordenar por</label>
                                <select name="sort_by" class="form-select @error('sort_by') is-invalid @enderror">
                                    <option value="">Sin ordenacion predeterminada</option>
                                    <option value="created_at" {{ old('sort_by') === 'created_at' ? 'selected' : '' }}>Fecha de creacion</option>
                                    <option value="updated_at" {{ old('sort_by') === 'updated_at' ? 'selected' : '' }}>Ultima actualizacion</option>
                                    <option value="priority" {{ old('sort_by') === 'priority' ? 'selected' : '' }}>Prioridad</option>
                                    <option value="status" {{ old('sort_by') === 'status' ? 'selected' : '' }}>Estado</option>
                                    <option value="assignee_id" {{ old('sort_by') === 'assignee_id' ? 'selected' : '' }}>Agente asignado</option>
                                </select>
                                @error('sort_by')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Direccion</label>
                                <select name="sort_direction" class="form-select @error('sort_direction') is-invalid @enderror">
                                    <option value="desc" {{ old('sort_direction', 'desc') === 'desc' ? 'selected' : '' }}>Descendente (mas recientes primero)</option>
                                    <option value="asc" {{ old('sort_direction') === 'asc' ? 'selected' : '' }}>Ascendente (mas antiguos primero)</option>
                                </select>
                                @error('sort_direction')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Filtros --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Filtros</h6>
                        <p class="text-muted small mb-3">Condiciones que debe cumplir un ticket para aparecer en esta vista</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Filtros en formato JSON</label>
                                <textarea name="filters_json" id="filtersJson"
                                          class="form-control font-monospace @error('filters') is-invalid @enderror"
                                          rows="6"
                                          placeholder='{"status":"open","priority":"high"}'></textarea>
                                <small class="form-text text-muted">
                                    Ingresa los filtros como objeto JSON. Ej: <code>{"status":"open","priority":"high"}</code>
                                </small>
                                @error('filters')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Configuracion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
                        <p class="text-muted small mb-3">Visibilidad y marca como predeterminada</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Visibilidad</label>
                                <select name="is_shared" class="form-select @error('is_shared') is-invalid @enderror">
                                    <option value="0" {{ old('is_shared', '0') === '0' ? 'selected' : '' }}>Privada — solo visible para mi</option>
                                    <option value="1" {{ old('is_shared') === '1' ? 'selected' : '' }}>Compartida — visible para todo el equipo</option>
                                </select>
                                @error('is_shared')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Vista predeterminada</label>
                                <select name="is_default" class="form-select @error('is_default') is-invalid @enderror">
                                    <option value="0" {{ old('is_default', '0') === '0' ? 'selected' : '' }}>No — usar otra vista por defecto</option>
                                    <option value="1" {{ old('is_default') === '1' ? 'selected' : '' }}>Si — aplicar como vista predeterminada</option>
                                </select>
                                @error('is_default')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar vista</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-views.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las vistas guardadas</h6>
                    <p class="card-text text-muted">
                        Las vistas guardadas permiten acceder rapidamente a un listado filtrado y ordenado de tickets sin tener que configurar los filtros cada vez.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check text-success me-2"></i> Usa nombres descriptivos que indiquen que muestra la vista</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check text-success me-2"></i> Combina filtros como <code>status</code>, <code>priority</code> o <code>assignee_id</code></li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check text-success me-2"></i> Comparte la vista con el equipo para estandarizar flujos</li>
                        <li class="text-muted small"><i class="fas fa-check text-success me-2"></i> Marca una vista como predeterminada para que sea la vista inicial</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#viewForm').on('submit', function () {
        $('.filters-hidden').remove();
        try {
            const obj = JSON.parse($('#filtersJson').val() || '{}');
            const form = this;
            Object.entries(obj).forEach(function ([k, v]) {
                $(form).append($('<input>', {
                    type: 'hidden',
                    class: 'filters-hidden',
                    name: 'filters[' + k + ']',
                    value: v,
                }));
            });
        } catch (e) {
            // JSON invalido — no se agregan filtros
        }
    });
});
</script>
@endpush

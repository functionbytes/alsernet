@extends('layouts.theme')

@section('title', 'Editar prioridad: ' . $priority->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Editar prioridad'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="priorityForm" action="{{ route('manager.helpdesk.settings.ticket-priorities.update', $priority) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar: {{ $priority->name }}</h5>
                        <small class="text-muted">Modifica las propiedades de esta prioridad</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre y slug de la prioridad</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $priority->name) }}"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    @include('core::components.slug-field', [
                                        'value' => old('slug', $priority->slug),
                                        'from' => 'input[name=name]',
                                    ])
                                    @error('slug')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Apariencia y nivel</h6>
                        <p class="text-muted small mb-3">Color en los listados y nivel de prioridad frente a las demas (a mayor numero, mas urgente)</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Color <span class="text-danger">*</span></label>
                                    @include('core::components.color-field', [
                                        'name' => 'color',
                                        'value' => old('color', $priority->color),
                                        'preview' => old('name', $priority->name),
                                        'previewFrom' => 'input[name=name]',
                                    ])
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="level" class="form-label">Nivel de prioridad <span class="text-danger">*</span></label>
                                    <input type="number" name="level" id="level" min="1" max="100"
                                           class="form-control @error('level') is-invalid @enderror"
                                           value="{{ old('level', $priority->level) }}"
                                           required>
                                    <small class="form-text text-muted">Ordena las prioridades entre si: 1 es la mas baja y el numero mas alto, la mas urgente</small>
                                    @error('level')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Tiempos de referencia (horas)</h6>
                        <p class="text-muted small mb-3">Valores informativos del catalogo — el motor de SLA usa sus propias politicas, no estos valores</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Primera respuesta <span class="text-danger">*</span></label>
                                    <input type="number" name="response_time_hours" min="1"
                                           class="form-control @error('response_time_hours') is-invalid @enderror"
                                           value="{{ old('response_time_hours', $priority->response_time_hours) }}"
                                           required>
                                    @error('response_time_hours')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Resolucion <span class="text-danger">*</span></label>
                                    <input type="number" name="resolution_time_hours" min="1"
                                           class="form-control @error('resolution_time_hours') is-invalid @enderror"
                                           value="{{ old('resolution_time_hours', $priority->resolution_time_hours) }}"
                                           required>
                                    @error('resolution_time_hours')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Visibilidad</h6>
                        <p class="text-muted small mb-3">Si aparece disponible en el catalogo</p>
                        <div class="row g-3">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Estado</label>
                                    <select class="form-select select2 @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $priority->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('is_active', $priority->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                    @error('is_active')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-priorities.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las prioridades</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Este catalogo alimenta la API publica del helpdesk (<code>api/v1/helpdesk/priorities</code>).
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Un nivel mas alto significa mas urgente</li>
                        <li class="mb-2">Usa colores que se distingan entre si en los listados</li>
                        <li class="mb-2">Los tiempos de referencia son informativos: el SLA usa sus politicas</li>
                        <li class="mb-0">El slug lo usan la API y las automatizaciones: cambialo con cuidado</li>
                    </ul>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Que tener en cuenta</h6>
                </div>
                <div class="card-body">
                    <p class="card-text small text-muted mb-0">
                        <i class="fas fa-circle-info me-1"></i> Todavia no controla el campo de prioridad de los
                        tickets ni las politicas de SLA, que usan sus propios valores fijos (urgente/alta/normal/baja).
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Informacion del registro</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">
                            <span class="fw-semibold">Creada:</span> {{ $priority->created_at?->format('d/m/Y H:i') ?? '—' }}
                        </li>
                        <li class="mb-0">
                            <span class="fw-semibold">Actualizada:</span> {{ $priority->updated_at?->format('d/m/Y H:i') ?? '—' }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });});
</script>
@endpush

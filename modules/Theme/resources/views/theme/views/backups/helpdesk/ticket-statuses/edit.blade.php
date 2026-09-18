@extends('layouts.theme')

@section('title', 'Editar estado: ' . $status->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Editar estado'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="statusForm" action="{{ route('manager.helpdesk.settings.ticket-statuses.update', $status) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar: {{ $status->name }}</h5>
                        <small class="text-muted">Modifica las propiedades del estado</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre, slug y descripcion visible del estado</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $status->name) }}"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    @include('core::components.slug-field', [
                                        'value' => old('slug', $status->slug),
                                        'from' => '#name',
                                        'url' => route('manager.helpdesk.settings.ticket-statuses.ajax-slug'),
                                        'ignoreId' => $status->id,
                                    ])
                                    @error('slug')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripcion</label>
                                    <textarea name="description"
                                              class="form-control @error('description') is-invalid @enderror"
                                              rows="3">{{ old('description', $status->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Apariencia</h6>
                        <p class="text-muted small mb-3">Color que identifica al estado en los listados de tickets</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="cc-color-picker" class="form-label">Color <span class="text-danger">*</span></label>
                                    @include('core::components.color-field', [
                                        'name' => 'color',
                                        'value' => old('color', $status->color),
                                        'preview' => old('name', $status->name),
                                        'previewFrom' => '#name',
                                    ])
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Comportamiento</h6>
                        <p class="text-muted small mb-3">Como afecta este estado a los tickets y a su SLA</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="is_open" class="form-label">Tipo de estado</label>
                                    <select class="form-select select2 @error('is_open') is-invalid @enderror" id="is_open" name="is_open">
                                        <option value="1" {{ old('is_open', $status->is_open ? '1' : '0') == '1' ? 'selected' : '' }}>Abierto — el ticket sigue activo</option>
                                        <option value="0" {{ old('is_open', $status->is_open ? '1' : '0') == '0' ? 'selected' : '' }}>Cerrado — el ticket queda resuelto</option>
                                    </select>
                                    @error('is_open')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="stops_sla_timer" class="form-label">Temporizador SLA</label>
                                    <select class="form-select select2 @error('stops_sla_timer') is-invalid @enderror" id="stops_sla_timer" name="stops_sla_timer">
                                        <option value="0" {{ old('stops_sla_timer', $status->stops_sla_timer ? '1' : '0') == '0' ? 'selected' : '' }}>Sigue corriendo</option>
                                        <option value="1" {{ old('stops_sla_timer', $status->stops_sla_timer ? '1' : '0') == '1' ? 'selected' : '' }}>Se detiene mientras dure el estado</option>
                                    </select>
                                    @error('stops_sla_timer')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="is_default" class="form-label">Estado por defecto</label>
                                    <select class="form-select select2 @error('is_default') is-invalid @enderror" id="is_default" name="is_default">
                                        <option value="0" {{ old('is_default', $status->is_default ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('is_default', $status->is_default ? '1' : '0') == '1' ? 'selected' : '' }}>Si — asignado a los tickets nuevos</option>
                                    </select>
                                    @error('is_default')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-statuses.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Este estado</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Su uso actual y que se puede hacer con el</p>

                    <dl class="row small mb-0">
                        <dt class="col-6 fw-normal text-muted">Tickets con este estado</dt>
                        <dd class="col-6 mb-2 text-end fw-semibold">{{ number_format($ticketsCount) }}</dd>

                        <dt class="col-6 fw-normal text-muted">Cuenta como activo</dt>
                        <dd class="col-6 mb-2 text-end fw-semibold">{{ $status->is_open ? 'Si' : 'No' }}</dd>

                        <dt class="col-6 fw-normal text-muted">Temporizador SLA</dt>
                        <dd class="col-6 mb-2 text-end fw-semibold">{{ $status->stops_sla_timer ? 'Detenido' : 'En marcha' }}</dd>

                        <dt class="col-6 fw-normal text-muted">Se puede eliminar</dt>
                        <dd class="col-6 mb-0 text-end fw-semibold">
                            @if($status->is_default)
                                No, es el predeterminado
                            @elseif($ticketsCount > 0)
                                No, esta en uso
                            @else
                                Si
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Que decide cada campo</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Los estados definen por que fases pasa un ticket</p>

                    <p class="small mb-2">
                        <span class="fw-semibold">Tipo de estado.</span>
                        Un estado abierto mantiene el ticket en la carga de trabajo del equipo; uno cerrado lo saca de las bandejas y de los contadores de pendientes.
                    </p>
                    <p class="small mb-2">
                        <span class="fw-semibold">Temporizador SLA.</span>
                        Detenlo en las esperas que no dependen del agente, como esperar respuesta del cliente: ese tiempo deja de contar contra el plazo de la politica SLA.
                    </p>
                    <p class="small mb-2">
                        <span class="fw-semibold">Estado por defecto.</span>
                        Es el que reciben los tickets nuevos. Solo puede haber uno, y no se puede eliminar mientras lo sea.
                    </p>
                    <p class="small mb-2">
                        <span class="fw-semibold">Color.</span>
                        Identifica el estado en los listados de tickets y en el portal del cliente.
                    </p>
                    <p class="small mb-0">
                        <span class="fw-semibold">Slug.</span>
                        Es el identificador estable del estado. Cambialo con cuidado: las automatizaciones, las vistas guardadas y la API lo usan para referirse a el.
                    </p>
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

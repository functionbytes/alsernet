@extends('layouts.theme')

@section('title', 'Nuevo estado de ticket')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo estado de ticket'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="statusForm" action="{{ route('manager.helpdesk.settings.ticket-statuses.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo estado</h5>
                        <small class="text-muted">Define un estado para el ciclo de vida de los tickets</small>
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
                                           value="{{ old('name') }}"
                                           placeholder="Ej: En espera del cliente"
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
                                        'value' => old('slug', ''),
                                        'from' => '#name',
                                        'url' => route('manager.helpdesk.settings.ticket-statuses.ajax-slug'),
                                        'placeholder' => 'se-genera-desde-el-nombre',
                                    ])
                                    <small class="form-text text-muted">Sigue al nombre mientras no lo edites a mano</small>
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
                                              rows="3"
                                              placeholder="Cuando debe usarse este estado">{{ old('description') }}</textarea>
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
                                        'value' => old('color', '#90bb13'),
                                        'preview' => old('name', 'Estado'),
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
                                        <option value="1" {{ old('is_open', '1') == '1' ? 'selected' : '' }}>Abierto — el ticket sigue activo</option>
                                        <option value="0" {{ old('is_open', '1') == '0' ? 'selected' : '' }}>Cerrado — el ticket queda resuelto</option>
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
                                        <option value="0" {{ old('stops_sla_timer', '0') == '0' ? 'selected' : '' }}>Sigue corriendo</option>
                                        <option value="1" {{ old('stops_sla_timer', '0') == '1' ? 'selected' : '' }}>Se detiene mientras dure el estado</option>
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
                                        <option value="0" {{ old('is_default', '0') == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('is_default', '0') == '1' ? 'selected' : '' }}>Si — asignado a los tickets nuevos</option>
                                    </select>
                                    @error('is_default')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar estado</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-statuses.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre los estados</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Los estados definen por que fases pasa un ticket. Cada estado puede marcarse como abierto o cerrado y decidir si pausa el temporizador de SLA.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Solo un estado puede ser el predeterminado</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Usa "Se detiene" en estados de espera del cliente</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Marca como "Cerrado" solo los estados finales</li>
                        <li class="mb-0"><i class="fas fa-check-circle text-success me-2"></i> El slug se genera automaticamente desde el nombre</li>
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

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
                <form id="statusForm" action="{{ route('settings.helpdesk.statuses.update', $status) }}" method="POST">
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
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
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
                                    <label class="form-label">Slug</label>
                                    @include('core::components.slug-field', [
                                        'value' => old('slug', $status->slug),
                                        'from' => 'input[name=name]',
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
                                              rows="3">{{ old('description', $status->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Color</h6>
                        <p class="text-muted small mb-3">Color identificador del estado en listados y badges</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Color <span class="text-danger">*</span></label>
                                    @include('core::components.color-field', [
                                        'name' => 'color',
                                        'value' => old('color', $status->color),
                                        'preview' => old('name', $status->name),
                                        'previewFrom' => 'input[name=name]',
                                    ])
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-semibold mb-1">Comportamiento</h6>
                        <p class="text-muted small mb-3">Define como se comporta este estado dentro del flujo y SLA</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="is_open" class="form-label">Tipo de estado</label>
                                    <select class="form-select @error('is_open') is-invalid @enderror" id="is_open" name="is_open" required>
                                        <option value="1" {{ old('is_open', $status->is_open ? 1 : 0) == 1 ? 'selected' : '' }}>Abierto — el ticket sigue activo</option>
                                        <option value="0" {{ old('is_open', $status->is_open ? 1 : 0) == 0 ? 'selected' : '' }}>Cerrado — el ticket se considera finalizado</option>
                                    </select>
                                    @error('is_open')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="is_default" class="form-label">Estado por defecto</label>
                                    <select class="form-select @error('is_default') is-invalid @enderror" id="is_default" name="is_default">
                                        <option value="0" {{ old('is_default', $status->is_default ? 1 : 0) == 0 ? 'selected' : '' }}>No — asignacion manual</option>
                                        <option value="1" {{ old('is_default', $status->is_default ? 1 : 0) == 1 ? 'selected' : '' }}>Si — asignado a tickets nuevos</option>
                                    </select>
                                    @error('is_default')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="stops_sla_timer" class="form-label">Temporizador SLA</label>
                                    <select class="form-select @error('stops_sla_timer') is-invalid @enderror" id="stops_sla_timer" name="stops_sla_timer">
                                        <option value="0" {{ old('stops_sla_timer', $status->stops_sla_timer ? 1 : 0) == 0 ? 'selected' : '' }}>No pausa — SLA sigue corriendo</option>
                                        <option value="1" {{ old('stops_sla_timer', $status->stops_sla_timer ? 1 : 0) == 1 ? 'selected' : '' }}>Si pausa — SLA se detiene</option>
                                    </select>
                                    @error('stops_sla_timer')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('settings.helpdesk.statuses.index') }}" class="btn btn-light w-100">Cancelar</a>
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
                        Los estados definen el ciclo de vida de un ticket y permiten controlar el flujo de trabajo y el cumplimiento del SLA.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres cortos que reflejen la etapa del ticket</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Asigna colores distintos para facilitar la identificacion visual</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Solo un estado debe ser el predeterminado para tickets nuevos</li>
                        <li class="mb-0"><i class="fas fa-check-circle text-success me-2"></i> Usa "Pausar SLA" en estados de espera por el cliente</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Informacion del registro</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">
                            <span class="fw-semibold">Creado:</span> {{ $status->created_at->format('d/m/Y H:i') }}
                        </li>
                        <li class="mb-0">
                            <span class="fw-semibold">Actualizado:</span> {{ $status->updated_at->format('d/m/Y H:i') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection


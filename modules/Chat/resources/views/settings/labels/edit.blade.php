@extends('layouts.theme')

@section('title', 'Editar etiqueta')

@section('content')
    <div class="card w-100">
        <form id="formLabel" action="{{ route('settings.chat.labels.update', $label) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar etiqueta</h5>
                </div>
                <p class="mb-3 mt-1">
                    Modifica la información de esta etiqueta para conversaciones del chat.
                </p>

                @include('core::components.alerts')

                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y apariencia de la etiqueta.</p>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre de la etiqueta
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $label->name) }}" required
                                   placeholder="Ej: Problema técnico">
                            <small class="form-text text-muted">Nombre descriptivo para identificar la etiqueta</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Color
                                <span class="text-danger">*</span>
                            </label>
                            <input type="color" name="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                                   value="{{ old('color', $label->color) }}" required
                                   style="height: 38px; width: 100%;">
                            <small class="form-text text-muted">Color distintivo para identificar visualmente la etiqueta</small>
                            @error('color')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="3" placeholder="Descripción de la etiqueta">{{ old('description', $label->description) }}</textarea>
                            <small class="form-text text-muted">Información adicional sobre el uso de esta etiqueta</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones</h6>
                        <p class="text-muted small mb-3">Configura la visibilidad y comportamiento de la etiqueta.</p>
                    </div>

                    <div class="col-12">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="show_on_sidebar" value="0">
                                <input type="checkbox" name="show_on_sidebar" class="form-check-input" id="showOnSidebarCheck" value="1" {{ old('show_on_sidebar', $label->show_on_sidebar) ? 'checked' : '' }}>
                                <label class="form-check-label" for="showOnSidebarCheck">
                                    <strong>Mostrar en barra lateral</strong>
                                    <small class="d-block text-muted">Muestra esta etiqueta en el panel lateral para acceso rápido al filtrar conversaciones.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.labels.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Volver
                </a>
            </div>
        </form>
    </div>
@endsection

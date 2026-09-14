@extends('layouts.theme')

@section('title', 'Nueva etiqueta')

@push('styles')
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva etiqueta'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="tagForm" action="{{ route('settings.helpdesk.tags.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva etiqueta</h5>
                        <small class="text-muted">Crea una etiqueta para clasificar los tickets</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre, slug y descripcion visible de la etiqueta</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}"
                                           placeholder="Ej: Urgente"
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
                                        'value' => old('slug', ''),
                                        'from' => 'input[name=name]',
                                        'placeholder' => 'urgente',
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
                                              placeholder="Describe el uso de esta etiqueta">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Apariencia</h6>
                        <p class="text-muted small mb-3">Color identificador de la etiqueta en conversaciones y listados</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Color</label>
                                    @include('core::components.color-field', [
                                        'name' => 'color',
                                        'value' => old('color', '#90bb13'),
                                        'preview' => old('name', 'Etiqueta'),
                                        'previewFrom' => 'input[name=name]',
                                    ])
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-semibold mb-1">Configuracion</h6>
                        <p class="text-muted small mb-3">Disponibilidad de la etiqueta para asignacion en tickets</p>
                        <div class="row g-3">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Estado</label>
                                    <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
                                        <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Activa — disponible para asignar</option>
                                        <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactiva — no disponible</option>
                                    </select>
                                    @error('is_active')
                                        <div class="field-validation-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar etiqueta</button>
                        <a href="{{ route('settings.helpdesk.tags.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las etiquetas</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Las etiquetas permiten clasificar los tickets para facilitar su busqueda, filtrado y organizacion por el equipo de soporte.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Usa nombres cortos y descriptivos</li>
                        <li class="mb-2">Asigna un color distinto por etiqueta</li>
                        <li class="mb-2">Evita duplicados revisando las existentes</li>
                        <li class="mb-0">El slug se genera automaticamente desde el nombre</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.form-select').select2({ width: '100%' });
});
</script>
@endpush


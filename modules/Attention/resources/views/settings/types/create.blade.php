@extends('layouts.theme')

@section('title', 'Crear tipo de peticiones')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear tipo de peticiones'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="formAttentionType" action="{{ route('settings.attention.types.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo tipo de peticiones</h5>
                        <small class="text-muted">Complete la información para registrar un nuevo tipo de solicitud.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           name="name" value="{{ old('name') }}"
                                           placeholder="ej: Petición" required>
                                    <small class="form-text text-muted">Nombre del tipo de solicitud</small>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Código <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                           name="code" value="{{ old('code') }}"
                                           placeholder="ej: P, Q, R, S, F"
                                           pattern="[A-Z0-9_\-]+" required>
                                    <small class="form-text text-muted">Código único, mayúsculas</small>
                                    @error('code')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description" rows="3"
                                              placeholder="Descripción del tipo de solicitud">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                           <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Color</label>
                                    <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                                           name="color" value="{{ old('color', '#3b82f6') }}"
                                           style="height: 38px; width: 100%;">
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                           <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Orden de visualización</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           name="sort_order" value="{{ old('sort_order', 0) }}"
                                           min="0" placeholder="0">
                                    <small class="form-text text-muted">Menor número aparece primero</small>
                                    @error('sort_order')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                           <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select select2 @error('is_active') is-invalid @enderror" name="is_active">
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('is_active')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Días SLA para respuesta</label>
                                    <input type="number" class="form-control @error('sla_response_days') is-invalid @enderror"
                                           name="sla_response_days" value="{{ old('sla_response_days', 15) }}"
                                           min="1" placeholder="15">
                                    <small class="form-text text-muted">Días máximos para dar respuesta</small>
                                    @error('sla_response_days')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Icono</label>
                                    <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                           id="icon" name="icon" value="{{ old('icon', 'fa fa-file-lines') }}"
                                           placeholder="fa fa-file-lines">
                                    <small class="form-text text-muted">Vista previa: <i id="iconPreview" class="{{ old('icon', 'fa fa-file-lines') }} ms-1"></i></small>
                                    @error('icon')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('settings.attention.types.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es un tipo de peticiones?</h6>
                    <p class="card-text text-muted">
                        Cada tipo representa una modalidad de solicitud ciudadana: Petición (P), Queja (Q), Reclamo (R), Sugerencia (S) o Felicitación (F).
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Código</h6>
                    <p class="card-text text-muted mb-0">
                        El código identifica el tipo de forma abreviada. Debe estar en mayúsculas y ser único (ej: <code>P</code>, <code>Q</code>, <code>RECLAMO</code>).
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">SLA</h6>
                    <p class="card-text text-muted mb-0">
                        El plazo SLA define el número de días hábiles para dar respuesta a este tipo de solicitud, según la normativa vigente.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Icono</h6>
                    <p class="card-text text-muted mb-0">
                        Usa clases de <strong>Font Awesome 6</strong> (ej: <code>fas fa-file-lines</code>). El icono se muestra en listados y etiquetas del sistema.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#icon').on('input', function() {
        $('#iconPreview').attr('class', $(this).val() + ' ms-1');
    });

    $('.select2').select2({
        minimumResultsForSearch: Infinity
    });
});
</script>
@endpush

@extends('layouts.theme')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formAttentionType" action="{{ route('settings.attention.types.update', $type->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Editar tipo de PQRSF</h5>
                                <p class="mb-0 text-muted small">Actualice la información del tipo de solicitud.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           name="name" value="{{ old('name', $type->name) }}"
                                           placeholder="ej: Petición" required>
                                    <small class="form-text text-muted">Nombre del tipo de solicitud</small>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Código <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                           name="code" value="{{ old('code', $type->code) }}"
                                           pattern="[A-Z0-9_\-]+" required>
                                    <small class="form-text text-muted">Código único, mayúsculas</small>
                                    @error('code')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description" rows="3"
                                              placeholder="Descripción del tipo de solicitud">{{ old('description', $type->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Color</label>
                                    <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                                           name="color" value="{{ old('color', $type->color) }}"
                                           style="height: 38px; width: 100%;">
                                    @error('color')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Orden de visualización</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           name="sort_order" value="{{ old('sort_order', $type->sort_order) }}"
                                           min="0" placeholder="0">
                                    <small class="form-text text-muted">Menor número aparece primero</small>
                                    @error('sort_order')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <select class="form-select select2 @error('is_active') is-invalid @enderror" name="is_active">
                                        <option value="1" {{ old('is_active', $type->is_active) == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('is_active', $type->is_active) == 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('is_active')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Días SLA para respuesta</label>
                                    <input type="number" class="form-control @error('sla_response_days') is-invalid @enderror"
                                           name="sla_response_days" value="{{ old('sla_response_days', $type->sla_response_days) }}"
                                           min="1" placeholder="15">
                                    <small class="form-text text-muted">Días máximos para dar respuesta</small>
                                    @error('sla_response_days')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Icono (Font Awesome)</label>
                                    <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                           id="icon" name="icon" value="{{ old('icon', $type->icon) }}"
                                           placeholder="fa fa-file-lines">
                                    <small class="form-text text-muted">Vista previa: <i id="iconPreview" class="{{ old('icon', $type->icon) }} ms-1"></i></small>
                                    @error('icon')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer border-top">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar
                        </button>
                        <a href="{{ route('settings.attention.types.index') }}" class="btn btn-secondary w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Icon preview
    $('#icon').on('input', function() {
        $('#iconPreview').attr('class', $(this).val() + ' ms-1');
    });

    // Initialize Select2
    $('.select2').select2({
        minimumResultsForSearch: Infinity
    });
});
</script>
@endpush

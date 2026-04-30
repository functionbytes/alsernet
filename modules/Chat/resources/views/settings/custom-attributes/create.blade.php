@extends('layouts.theme')

@section('title', 'Nuevo atributo personalizado')

@section('content')

    <div class="card w-100">

        <form id="formCustomAttribute" method="POST" action="{{ route('settings.chat.custom-attributes.store') }}">

            @csrf

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear atributo personalizado</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Define un nuevo campo personalizado para almacenar información adicional sobre clientes o conversaciones.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Especifica el nombre, clave y descripción del atributo.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre para mostrar
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="attribute_display_name" class="form-control @error('attribute_display_name') is-invalid @enderror" value="{{ old('attribute_display_name') }}" required placeholder="Ej: Número de Cliente">
                            <small class="form-text text-muted">Nombre legible que aparecerá en la interfaz</small>
                            @error('attribute_display_name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Clave
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="attribute_key" class="form-control @error('attribute_key') is-invalid @enderror" value="{{ old('attribute_key') }}" required placeholder="ej: customer_id">
                            <small class="form-text text-muted">Identificador único (minúsculas, guiones bajos permitidos)</small>
                            @error('attribute_key')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="attribute_description" class="form-control @error('attribute_description') is-invalid @enderror" rows="2" placeholder="Descripción opcional del atributo">{{ old('attribute_description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este atributo</small>
                            @error('attribute_description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Type and Model -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Tipo y modelo</h6>
                        <p class="text-muted small mb-3">Selecciona el tipo de dato y el modelo al que pertenece el atributo.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Modelo
                                <span class="text-danger">*</span>
                            </label>
                            <select name="attribute_model" class="form-control select2 @error('attribute_model') is-invalid @enderror" required>
                                <option value="0" {{ old('attribute_model') == 0 ? 'selected' : '' }}>Clientes</option>
                                <option value="1" {{ old('attribute_model') == 1 ? 'selected' : '' }}>Conversación</option>
                            </select>
                            <small class="form-text text-muted">El modelo no puede cambiarse después de la creación</small>
                            @error('attribute_model')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tipo de visualización
                                <span class="text-danger">*</span>
                            </label>
                            <select name="attribute_display_type" id="attribute_display_type" class="form-control select2 @error('attribute_display_type') is-invalid @enderror" required>
                                <option value="0" {{ old('attribute_display_type') == 0 ? 'selected' : '' }}>Texto</option>
                                <option value="1" {{ old('attribute_display_type') == 1 ? 'selected' : '' }}>Número</option>
                                <option value="2" {{ old('attribute_display_type') == 2 ? 'selected' : '' }}>Moneda</option>
                                <option value="3" {{ old('attribute_display_type') == 3 ? 'selected' : '' }}>Porcentaje</option>
                                <option value="4" {{ old('attribute_display_type') == 4 ? 'selected' : '' }}>Enlace</option>
                                <option value="5" {{ old('attribute_display_type') == 5 ? 'selected' : '' }}>Fecha</option>
                                <option value="6" {{ old('attribute_display_type') == 6 ? 'selected' : '' }}>Lista</option>
                                <option value="7" {{ old('attribute_display_type') == 7 ? 'selected' : '' }}>Checkbox</option>
                            </select>
                            <small class="form-text text-muted">Define cómo se mostrará y validará el valor</small>
                            @error('attribute_display_type')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- List Values Section -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Valores de lista</h6>
                        <p class="text-muted small mb-3">Define los valores disponibles cuando el tipo es Lista (solo aplica si seleccionaste tipo Lista).</p>
                    </div>

                    <div class="col-12" id="list-values-section" style="display: none;">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Valores de la lista</label>
                            <textarea name="attribute_values" id="attribute_values_input" class="form-control" rows="3" placeholder="Ingresa un valor por línea">{{ old('attribute_values') }}</textarea>
                            <small class="form-text text-muted">Para tipo Lista: ingresa una opción por línea</small>
                        </div>
                    </div>

                    <!-- Validation -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Validación</h6>
                        <p class="text-muted small mb-3">Configura reglas de validación opcionales para el atributo.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Patrón de validación (regex)</label>
                            <input type="text" name="regex_pattern" class="form-control @error('regex_pattern') is-invalid @enderror" value="{{ old('regex_pattern') }}" placeholder="Ej: ^[A-Z0-9]+$">
                            <small class="form-text text-muted">Expresión regular para validar la entrada (opcional)</small>
                            @error('regex_pattern')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Pista de validación</label>
                            <input type="text" name="regex_cue" class="form-control @error('regex_cue') is-invalid @enderror" value="{{ old('regex_cue') }}" placeholder="Ej: Debe ser un email válido">
                            <small class="form-text text-muted">Mensaje de ayuda para el usuario</small>
                            @error('regex_cue')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.custom-attributes.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle list values section based on display type
        $('#attribute_display_type').on('change', function() {
            $('#list-values-section').toggle(this.value === '6');
        });

        // Trigger on page load in case of validation errors
        if ($('#attribute_display_type').val() === '6') {
            $('#list-values-section').show();
        }

        @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif
    });
</script>
@endpush

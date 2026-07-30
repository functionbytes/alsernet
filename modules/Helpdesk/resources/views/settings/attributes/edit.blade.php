@extends('layouts.theme')

@section('title', 'Editar atributo')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar atributo'])
@endsection

@section('content')

    <div class="card w-100">

        <form id="formAttribute" method="POST" action="{{ route('settings.helpdesk.attributes.update', $attribute->id) }}">

            {{ csrf_field() }}
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar atributo</h5>
                </div>
                <p class="card-subtitle mb-3 mt-3">
                    Modifica la configuración de este atributo personalizado. Ten en cuenta que cambiar el tipo de campo puede afectar los datos existentes en las conversaciones.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted mb-3">Modifica el nombre y descripción del atributo. La clave única no puede ser modificada una vez creado el atributo para mantener la integridad de los datos existentes.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del Atributo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $attribute->name) }}" required placeholder="Ej: Prioridad del Cliente">
                            <small class="form-text text-muted">Nombre visible para los usuarios</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Clave Única
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="key" class="form-control bg-light" value="{{ old('key', $attribute->key) }}" readonly>
                            <small class="form-text text-muted">La clave no se puede cambiar una vez creada</small>
                            @error('key')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción opcional del atributo">{{ old('description', $attribute->description) }}</textarea>
                            <small class="form-text text-muted">Ayuda a otros usuarios a entender el propósito de este atributo</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Field Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración del Campo</h6>
                        <p class="text-muted mb-3">Ajusta el tipo de campo y los permisos de acceso. Ten en cuenta que cambiar el tipo de campo puede afectar la visualización de datos existentes en las conversaciones.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tipo de atributo
                                <span class="text-danger">*</span>
                            </label>
                            <select name="model_type" class="form-select" required>
                                <option value="contact" @selected(old('model_type', $attribute->model_type ?? 'contact') === 'contact')>Atributo del contacto</option>
                                <option value="conversation" @selected(old('model_type', $attribute->model_type ?? '') === 'conversation')>Atributo de la conversación</option>
                            </select>
                            @error('model_type')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tipo de campo
                                <span class="text-danger">*</span>
                            </label>
                            <select name="format" class="form-select select2" id="formatSelect" required>
                                <option value="text" {{ old('format', $attribute->format) === 'text' ? 'selected' : '' }}>Texto</option>
                                <option value="textarea" {{ old('format', $attribute->format) === 'textarea' ? 'selected' : '' }}>Área de Texto</option>
                                <option value="number" {{ old('format', $attribute->format) === 'number' ? 'selected' : '' }}>Número</option>
                                <option value="switch" {{ old('format', $attribute->format) === 'switch' ? 'selected' : '' }}>Interruptor (Sí/No)</option>
                                <option value="rating" {{ old('format', $attribute->format) === 'rating' ? 'selected' : '' }}>Calificación (Estrellas)</option>
                                <option value="select" {{ old('format', $attribute->format) === 'select' ? 'selected' : '' }}>Lista de Selección</option>
                                <option value="checkboxGroup" {{ old('format', $attribute->format) === 'checkboxGroup' ? 'selected' : '' }}>Grupo de Checkboxes</option>
                                <option value="date" {{ old('format', $attribute->format) === 'date' ? 'selected' : '' }}>Fecha</option>
                            </select>
                            <small class="form-text text-muted">Cambiar el tipo puede afectar datos existentes</small>
                            @error('format')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Permiso
                                <span class="text-danger">*</span>
                            </label>
                            <select name="permission" class="form-select select2" required>
                                <option value="agentCanEdit" {{ old('permission', $attribute->permission) === 'agentCanEdit' ? 'selected' : '' }}>
                                    Agente puede editar
                                </option>
                                <option value="userCanEdit" {{ old('permission', $attribute->permission) === 'userCanEdit' ? 'selected' : '' }}>
                                    Usuario puede editar
                                </option>
                                <option value="userCanView" {{ old('permission', $attribute->permission) === 'userCanView' ? 'selected' : '' }}>
                                    Usuario solo puede ver
                                </option>
                            </select>
                            <small class="form-text text-muted">Define quién puede modificar este campo</small>
                            @error('permission')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Options Configuration (for select/checkbox types) -->
                    <div class="col-12 d-none" id="optionsContainer">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Opciones</label>
                            <div id="optionsList">
                                @php
                                    $existingOptions = old('options', $attribute->config['options'] ?? []);
                                @endphp
                                @if(!empty($existingOptions))
                                    @foreach($existingOptions as $index => $option)
                                        <div class="option-item mb-2">
                                            <div class="input-group">
                                                <input type="text" name="options[]" class="form-control" value="{{ $option }}" placeholder="Opción {{ $index + 1 }}">
                                                <button type="button" class="btn btn-outline-danger remove-option">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="option-item mb-2">
                                        <div class="input-group">
                                            <input type="text" name="options[]" class="form-control" placeholder="Opción 1">
                                            <button type="button" class="btn btn-outline-danger remove-option">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addOption">
                                <i class="fa fa-plus"></i> Agregar Opción
                            </button>
                            <small class="form-text text-muted d-block mt-2">
                                Define las opciones disponibles para este campo
                            </small>
                            @error('options')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Number Range Configuration -->
                    <div class="col-12 d-none" id="numberRangeContainer">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Valor Mínimo</label>
                                    <input type="number" name="min_value" class="form-control"
                                           value="{{ old('min_value', $attribute->config['min'] ?? '') }}"
                                           placeholder="Sin límite">
                                    <small class="form-text text-muted">Valor mínimo permitido (opcional)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Valor Máximo</label>
                                    <input type="number" name="max_value" class="form-control"
                                           value="{{ old('max_value', $attribute->config['max'] ?? '') }}"
                                           placeholder="Sin límite">
                                    <small class="form-text text-muted">Valor máximo permitido (opcional)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones</h6>
                        <p class="text-muted mb-3">Modifica el comportamiento del atributo. Puedes cambiar si el campo es obligatorio y si está activo para su uso en el sistema.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="required" value="0">
                                <input type="checkbox" name="required" class="form-check-input" id="requiredCheck" value="1" {{ old('required', $attribute->required) ? 'checked' : '' }}>
                                <label class="form-check-label" for="requiredCheck">
                                    <strong>Campo Requerido</strong>
                                    <small class="d-block text-muted">El campo debe tener un valor para guardar</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" class="form-check-input" id="activeCheck" value="1" {{ old('active', $attribute->active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">
                                    <strong>Activo</strong>
                                    <small class="d-block text-muted">El atributo está visible y disponible</small>
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
                <a href="{{ route('settings.helpdesk.attributes.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let optionCounter = {{ !empty($existingOptions) ? count($existingOptions) : 1 }};

    // Initialize Select2
    $('.select2').select2({
        allowClear: false,
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });

    // Format select handler
    $('#formatSelect').on('change', function() {
        const format = $(this).val();

        // Hide all config sections
        $('#optionsContainer').addClass('d-none');
        $('#numberRangeContainer').addClass('d-none');

        // Show relevant config section
        if (format === 'select' || format === 'checkboxGroup') {
            $('#optionsContainer').removeClass('d-none');
        } else if (format === 'number') {
            $('#numberRangeContainer').removeClass('d-none');
        }
    }).trigger('change');

    // Add option
    $('#addOption').on('click', function() {
        optionCounter++;
        const optionHtml = `
            <div class="option-item mb-2">
                <div class="input-group">
                    <input type="text" name="options[]" class="form-control" placeholder="Opción ${optionCounter}">
                    <button type="button" class="btn btn-outline-danger remove-option">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#optionsList').append(optionHtml);
    });

    // Remove option
    $(document).on('click', '.remove-option', function() {
        if ($('.option-item').length > 1) {
            $(this).closest('.option-item').remove();
        } else {
            toastr.warning('Debe mantener al menos una opción', 'Advertencia');
        }
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endsection

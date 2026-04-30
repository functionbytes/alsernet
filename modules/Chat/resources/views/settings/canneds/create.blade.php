@extends('layouts.theme')

@section('title', 'Crear respuesta predefinida')

@section('content')

    @include('core::components.alerts')

    <div class="card w-100">

        <form id="cannedForm" method="POST" action="{{ route('settings.chat.canneds.store') }}">

            @csrf

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear respuesta predefinida</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Crea una plantilla de respuesta rápida para conversaciones. Las variables se sustituyen automáticamente al usar la respuesta.
                </p>

                <div class="row">

                    <!-- Información básica -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el código, título y configuración de la respuesta predefinida.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Título
                            </label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="Ej: Saludo de bienvenida">
                            <small class="form-text text-muted">Nombre descriptivo para identificar la respuesta (opcional)</small>
                            @error('title')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Código corto
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">/</span>
                                <input type="text" name="short_code" class="form-control @error('short_code') is-invalid @enderror" value="{{ old('short_code') }}" placeholder="saludo" required>
                            </div>
                            <small class="form-text text-muted">Comando para insertar la respuesta (sin espacios, minúsculas)</small>
                            @error('short_code')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Visibilidad
                                <span class="text-danger">*</span>
                            </label>
                            <select name="visibility" id="visibility" class="form-control select2 @error('visibility') is-invalid @enderror" required>
                                <option value="personal" {{ old('visibility') === 'personal' ? 'selected' : '' }}>Personal (solo tú)</option>
                                <option value="team" {{ old('visibility') === 'team' ? 'selected' : '' }}>Equipo</option>
                                <option value="everyone" {{ old('visibility', 'everyone') === 'everyone' ? 'selected' : '' }}>Todos (toda la cuenta)</option>
                            </select>
                            <small class="form-text text-muted">Quién puede ver y usar esta respuesta</small>
                            @error('visibility')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Selector de equipos (visible solo cuando visibility = 'team') -->
                    <div class="col-12" id="team-selector" style="display: none;">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Equipos que pueden ver esta respuesta
                                <span class="text-danger">*</span>
                            </label>
                            <select name="team_ids[]" id="team_ids" class="form-control select2 select2" multiple>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}"
                                        {{ in_array($team->id, old('team_ids', [])) ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Selecciona los equipos que pueden acceder a esta respuesta</small>
                            @error('team_ids')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Selector de usuarios (visible solo cuando visibility = 'personal') -->
                    <div class="col-12" id="user-selector" style="display: none;">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Compartir con usuarios específicos
                            </label>
                            <select name="user_ids[]" id="user_ids" class="form-control select2 select2" multiple>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ in_array($user->id, old('user_ids', [])) ? 'selected' : '' }}>
                                        {{ $user->firstname }} {{ $user->lastname }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Opcional: Comparte esta respuesta personal con otros usuarios</small>
                            @error('user_ids')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" placeholder="Descripción breve (opcional)">
                            <small class="form-text text-muted">Proporciona contexto adicional sobre esta respuesta</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Contenido</h6>
                        <p class="text-muted small mb-3">Escribe el mensaje de la respuesta. Usa las variables dinámicas para personalizar el contenido.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Contenido
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror" rows="6" placeholder="Escribe el contenido de la respuesta predefinida..." required>{{ old('content') }}</textarea>
                            <small class="form-text text-muted">El mensaje que se insertará en las conversaciones. Puedes usar variables dinámicas.</small>
                            @error('content')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Variable Buttons -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label d-block">
                                Variables disponibles
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($availableVariables as $variable => $description)
                                    <button type="button" class="btn btn-sm btn-outline-primary insert-var-btn" data-variable="{{ $variable }}" title="{{ $description }}">
                                        <i class="fa fa-code"></i> {{ $variable }}
                                    </button>
                                @endforeach
                            </div>
                            <small class="form-text text-muted">Haz clic en una variable para insertarla en el cursor del contenido. Las variables se reemplazarán automáticamente con datos reales al usar la respuesta.</small>
                        </div>
                    </div>

                    <!-- Variable Reference (Collapsible) -->
                    <div class="col-12">
                        <div class="accordion" id="variableAccordion">
                            <div class="accordion-item border">
                                <h2 class="accordion-header" id="headingVariables">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVariables" aria-expanded="false" aria-controls="collapseVariables">
                                        <i class="fa fa-info-circle me-2"></i> Referencia de variables
                                    </button>
                                </h2>
                                <div id="collapseVariables" class="accordion-collapse collapse" aria-labelledby="headingVariables" data-bs-parent="#variableAccordion">
                                    <div class="accordion-body">
                                        <p class="small text-muted mb-2">Las variables se escriben entre llaves dobles: <code>@{{ variable }}</code></p>
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Variable</th>
                                                    <th>Descripción</th>
                                                    <th>Ejemplo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($availableVariables as $variable => $description)
                                                    <tr>
                                                        <td><code>@{{ {{ $variable }} }}</code></td>
                                                        <td>{{ $description }}</td>
                                                        <td class="text-muted small">
                                                            @if(str_contains($variable, 'name'))
                                                                Juan Pérez
                                                            @elseif(str_contains($variable, 'email'))
                                                                cliente@ejemplo.com
                                                            @elseif(str_contains($variable, 'phone'))
                                                                +34 600 123 456
                                                            @elseif(str_contains($variable, 'id'))
                                                                #12345
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.canneds.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar Select2 para selects múltiples
    $('.select2').select2({
        placeholder: 'Seleccionar...',
        allowClear: true,
        width: '100%'
    });

    // Visibility handler
    function updateVisibilityFields() {
        const visibility = $('#visibility').val();

        // Ocultar todos los selectores primero
        $('#team-selector').hide();
        $('#user-selector').hide();

        // Deshabilitar campos para que no se envíen si están ocultos
        $('#team_ids').prop('required', false);

        // Mostrar el selector apropiado
        if (visibility === 'team') {
            $('#team-selector').show();
            $('#team_ids').prop('required', true);
        } else if (visibility === 'personal') {
            $('#user-selector').show();
        }
    }

    // Ejecutar al cargar la página
    updateVisibilityFields();

    // Ejecutar cuando cambie la visibilidad
    $('#visibility').on('change', updateVisibilityFields);

    // Insert variable at cursor position
    $('.insert-var-btn').on('click', function() {
        const variable = $(this).data('variable');
        const textarea = $('#content')[0];
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);

        textarea.value = before + '{{' + variable + '}}' + after;
        textarea.selectionStart = textarea.selectionEnd = start + variable.length + 4;
        textarea.focus();

        // Trigger change event for any validation
        $(textarea).trigger('change');
    });

    // Form validation
    $('#cannedForm').validate({
        rules: {
            short_code: {
                required: true,
                maxlength: 255,
                pattern: /^[a-z0-9_-]+$/
            },
            content: {
                required: true
            },
            visibility: {
                required: true
            },
            title: {
                maxlength: 255
            },
            description: {
                maxlength: 500
            }
        },
        messages: {
            short_code: {
                required: 'El código corto es obligatorio',
                maxlength: 'El código corto no puede superar 255 caracteres',
                pattern: 'Solo minúsculas, números, guiones y guiones bajos'
            },
            content: {
                required: 'El contenido es obligatorio'
            },
            visibility: {
                required: 'La visibilidad es obligatoria'
            },
            title: {
                maxlength: 'El título no puede superar 255 caracteres'
            },
            description: {
                maxlength: 'La descripción no puede superar 500 caracteres'
            }
        },
        errorClass: 'field-validation-error',
        errorElement: 'span',
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        }
    });

    // Add custom pattern validator
    $.validator.addMethod('pattern', function(value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        if (typeof param === 'string') {
            param = new RegExp('^(?:' + param + ')$');
        }
        return param.test(value);
    }, 'Formato inválido');

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

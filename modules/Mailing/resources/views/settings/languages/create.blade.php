@extends('layouts.theme')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formLanguage" action="{{ route('settings.mailing.templates.languages.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Crear nuevo idioma</h5>
                                <p class="mb-0 text-muted small">Complete la información para crear un nuevo idioma de campaña.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Sección 1: Información del idioma --}}
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Información del idioma</h6>

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        Código del idioma <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('language_code') is-invalid @enderror"
                                           name="language_code"
                                           value="{{ old('language_code') }}"
                                           maxlength="10"
                                           placeholder="Ej: es, en, fr, de"
                                           required>
                                    <small class="form-text text-muted">Código ISO del idioma (ej: es para español, en para inglés)</small>
                                    @error('language_code')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        Nombre del idioma <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('language_name') is-invalid @enderror"
                                           name="language_name"
                                           value="{{ old('language_name') }}"
                                           maxlength="100"
                                           placeholder="Ej: Español, English, Français"
                                           required>
                                    <small class="form-text text-muted">Nombre descriptivo del idioma</small>
                                    @error('language_name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Región (opcional)</label>
                                    <input type="text"
                                           class="form-control @error('region') is-invalid @enderror"
                                           name="region"
                                           value="{{ old('region') }}"
                                           maxlength="50"
                                           placeholder="Ej: España, Estados Unidos, Canadá">
                                    <small class="form-text text-muted">Región específica del idioma</small>
                                    @error('region')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description"
                                              rows="2"
                                              maxlength="255"
                                              placeholder="Descripción adicional del idioma">{{ old('description') }}</textarea>
                                    <small class="form-text text-muted">Información adicional sobre este idioma</small>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Sección 2: Traducciones iniciales --}}
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Traducciones iniciales</h6>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-light border">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Formato de traducciones:</strong>
                                    <p class="mb-0 small mt-2">
                                        Ingresa un objeto JSON válido con las traducciones. Las claves serán los identificadores de traducción y los valores serán las cadenas de texto traducidas.
                                    </p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Traducciones (JSON)</label>
                                    <div id="translationsEditor" style="height: 300px; border: 1px solid #dee2e6; border-radius: 0.25rem;"></div>
                                    <textarea name="translations"
                                              id="translationsData"
                                              class="d-none @error('translations') is-invalid @enderror"
                                              required>{{ old('translations', '{}') }}</textarea>
                                    <small class="form-text text-muted">
                                        Puedes dejar este campo vacío y agregar traducciones después. Mínimo JSON válido: <code>{}</code>
                                    </small>
                                    @error('translations')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">Ejemplo de formato JSON:</h6>
                                        <pre style="background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;"><code>{
  "welcome_email_subject": "¡Bienvenido a nuestra comunidad!",
  "welcome_email_body": "Hola {{nombre}}, gracias por registrarte...",
  "password_reset": "Recuperar contraseña",
  "confirm_email": "Confirmar correo electrónico"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Sección 3: Opciones --}}
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Opciones</h6>

                        <div class="row">
                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="is_active"
                                           id="is_active"
                                           value="1"
                                           {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Idioma activo</strong>
                                        <br>
                                        <small class="text-muted">Estará disponible para su uso en campañas</small>
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="is_default"
                                           id="is_default"
                                           value="1"
                                           {{ old('is_default', '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_default">
                                        <strong>Idioma predeterminado</strong>
                                        <br>
                                        <small class="text-muted">Se usará como idioma por defecto</small>
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="is_rtl"
                                           id="is_rtl"
                                           value="1"
                                           {{ old('is_rtl', '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_rtl">
                                        <strong>Dirección derecha a izquierda</strong>
                                        <br>
                                        <small class="text-muted">Para idiomas RTL como árabe</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="card-footer border-top">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-2"></i>Crear idioma
                        </button>
                        <a href="{{ route('settings.mailing.templates.languages.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ace/1.30.0/ace.min.css">
<style>
    .ace_editor {
        font-size: 13px !important;
        font-family: 'Courier New', monospace !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.30.0/ace.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.30.0/mode-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.30.0/theme-chrome.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Ace editor for JSON
    var editor = ace.edit('translationsEditor');
    editor.setTheme('ace/theme/chrome');
    editor.session.setMode('ace/mode/json');
    editor.setOptions({
        maxLines: 20,
        minLines: 15,
        fontSize: 13,
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true
    });

    // Load initial data from textarea
    var initialData = $('#translationsData').val();
    try {
        editor.setValue(initialData);
    } catch(e) {
        editor.setValue('{}');
    }

    // Sync editor to textarea on form submission
    $('#formLanguage').on('submit', function(e) {
        var jsonData = editor.getValue();

        // Validate JSON
        try {
            JSON.parse(jsonData);
            $('#translationsData').val(jsonData);
        } catch(error) {
            e.preventDefault();
            alert('El formato JSON no es válido. Por favor, revisa tu entrada.\n\nError: ' + error.message);
            editor.focus();
            return false;
        }
    });

    // Form validation
    $('#formLanguage').validate({
        rules: {
            language_code: {
                required: true,
                maxlength: 10,
                pattern: /^[a-z]{2,10}(-[a-z]{2})?$/i
            },
            language_name: {
                required: true,
                maxlength: 100
            },
            region: {
                maxlength: 50
            },
            description: {
                maxlength: 255
            },
            translations: {
                required: function() {
                    return false; // Make it optional
                }
            }
        },
        messages: {
            language_code: {
                required: 'El código del idioma es obligatorio',
                maxlength: 'El código no puede superar 10 caracteres',
                pattern: 'El código debe ser un código ISO válido (ej: es, en, fr)'
            },
            language_name: {
                required: 'El nombre del idioma es obligatorio',
                maxlength: 'El nombre no puede superar 100 caracteres'
            },
            region: {
                maxlength: 'La región no puede superar 50 caracteres'
            },
            description: {
                maxlength: 'La descripción no puede superar 255 caracteres'
            }
        },
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        },
        errorPlacement: function(error, element) {
            error.addClass('field-validation-error');
            error.insertAfter(element);
        }
    });

    // Auto-format JSON button
    var autoFormatBtn = $('<button type="button" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-align-left me-1"></i>Formatear JSON</button>');

    autoFormatBtn.on('click', function(e) {
        e.preventDefault();
        try {
            var jsonData = editor.getValue();
            var formatted = JSON.stringify(JSON.parse(jsonData), null, 2);
            editor.setValue(formatted);
        } catch(error) {
            alert('Error al formatear JSON: ' + error.message);
        }
    });
});
</script>
@endpush

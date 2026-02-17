@extends('layouts.theme')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formTemplate" action="{{ route('settings.mailrelay.templates.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Crear plantilla de email</h5>
                                <p class="mb-0 text-muted small">Complete la información para crear una nueva plantilla de email.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Sección 1: Información básica --}}
                        <h6 class="fw-bold mb-3 border-bottom pb-2">
                            <i class="fas fa-info-circle me-2"></i>Información básica
                        </h6>

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Nombre de plantilla <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           name="name"
                                           value="{{ old('name') }}"
                                           maxlength="100"
                                           placeholder="Ej: Bienvenida nuevos suscriptores"
                                           required>
                                    <small class="form-text text-muted">Nombre interno para identificar la plantilla</small>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Tipo de plantilla <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2 @error('type') is-invalid @enderror"
                                            name="type"
                                            data-placeholder="Seleccionar tipo..."
                                            required>
                                        <option value=""></option>
                                        <option value="welcome" {{ old('type') == 'welcome' ? 'selected' : '' }}>Bienvenida</option>
                                        <option value="newsletter" {{ old('type') == 'newsletter' ? 'selected' : '' }}>Newsletter</option>
                                        <option value="transactional" {{ old('type') == 'transactional' ? 'selected' : '' }}>Transaccional</option>
                                        <option value="custom" {{ old('type') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                                    </select>
                                    @error('type')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description"
                                              rows="3"
                                              maxlength="255"
                                              placeholder="Descripción breve de la plantilla">{{ old('description') }}</textarea>
                                    <small class="form-text text-muted">Información adicional sobre el propósito de esta plantilla</small>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Sección 2: Configuración de email --}}
                        <h6 class="fw-bold mb-3 border-bottom pb-2">
                            <i class="fas fa-envelope me-2"></i>Configuración de email
                        </h6>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Asunto del email <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('subject') is-invalid @enderror"
                                           id="subject-input"
                                           name="subject"
                                           value="{{ old('subject') }}"
                                           maxlength="200"
                                           placeholder="Ej: ¡Bienvenido a nuestra comunidad!"
                                           required>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">Este texto aparecerá en la bandeja de entrada</small>
                                        <small id="char-count" class="text-muted">0/200</small>
                                    </div>
                                    @error('subject')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-info">
                                    <strong><i class="fas fa-lightbulb me-2"></i>Vista previa del asunto:</strong>
                                    <div id="subject-preview" class="mt-2 p-2 bg-white rounded border">
                                        <span class="text-muted fst-italic">El asunto aparecerá aquí...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Sección 3: Contenido HTML --}}
                        <h6 class="fw-bold mb-3 border-bottom pb-2">
                            <i class="fas fa-code me-2"></i>Contenido HTML
                        </h6>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-light border mb-3">
                                    <strong><i class="fas fa-tags me-2"></i>Variables disponibles:</strong>
                                    <div class="mt-2">
                                        <span class="badge bg-light text-dark me-2 mb-2 variable-tag" data-variable="{{nombre}}">
                                            <i class="fas fa-plus me-1"></i>{{nombre}}
                                        </span>
                                        <span class="badge bg-light text-dark me-2 mb-2 variable-tag" data-variable="{{email}}">
                                            <i class="fas fa-plus me-1"></i>{{email}}
                                        </span>
                                        <span class="badge bg-light text-dark me-2 mb-2 variable-tag" data-variable="{{fecha}}">
                                            <i class="fas fa-plus me-1"></i>{{fecha}}
                                        </span>
                                        <span class="badge bg-light text-dark me-2 mb-2 variable-tag" data-variable="{{unsubscribe_link}}">
                                            <i class="fas fa-plus me-1"></i>{{unsubscribe_link}}
                                        </span>
                                    </div>
                                    <small class="text-muted">Haz clic en una variable para insertarla en el contenido</small>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Contenido del email <span class="text-danger">*</span>
                                    </label>
                                    <div id="email-content-editor" style="height: 400px;"></div>
                                    <textarea name="content"
                                              id="email-content"
                                              class="d-none @error('content') is-invalid @enderror"
                                              required>{{ old('content') }}</textarea>
                                    <small class="form-text text-muted">Usa el editor para dar formato al contenido del email</small>
                                    @error('content')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#previewModal">
                                    <i class="fas fa-eye me-2"></i>Vista previa
                                </button>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Sección 4: Opciones --}}
                        <h6 class="fw-bold mb-3 border-bottom pb-2">
                            <i class="fas fa-cog me-2"></i>Opciones
                        </h6>

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="is_active"
                                           id="is_active"
                                           value="1"
                                           {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Plantilla activa</strong>
                                        <br>
                                        <small class="text-muted">La plantilla estará disponible para su uso</small>
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="use_layout"
                                           id="use_layout"
                                           value="1"
                                           {{ old('use_layout', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="use_layout">
                                        <strong>Usar layout base</strong>
                                        <br>
                                        <small class="text-muted">Incluir encabezado y pie de página predeterminados</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="card-footer border-top">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-2"></i>Guardar plantilla
                        </button>
                        <a href="{{ route('settings.mailrelay.templates.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal de vista previa --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">
                        <i class="fas fa-eye me-2"></i>Vista previa de la plantilla
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Asunto:</strong>
                        <div id="preview-subject" class="p-2 bg-light rounded border mt-1">
                            <span class="text-muted fst-italic">Sin asunto</span>
                        </div>
                    </div>
                    <div>
                        <strong>Contenido:</strong>
                        <div id="preview-content" class="p-3 border rounded mt-1" style="min-height: 200px; background: #fff;">
                            <span class="text-muted fst-italic">Sin contenido</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    .variable-tag {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #dee2e6;
    }
    .variable-tag:hover {
        background-color: #90bb13 !important;
        color: white !important;
        border-color: #90bb13;
    }
    .ql-editor {
        min-height: 350px;
        font-size: 14px;
    }
    .ql-container {
        font-family: inherit;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar Select2
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

    // Inicializar Quill editor
    var quill = new Quill('#email-content-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'font': [] }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'align': [] }],
                ['blockquote', 'code-block'],
                ['link', 'image', 'video'],
                ['clean']
            ]
        },
        placeholder: 'Escribe aquí el contenido de tu email...'
    });

    // Cargar contenido antiguo si hay errores de validación
    @if(old('content'))
    quill.root.innerHTML = {!! json_encode(old('content')) !!};
    @endif

    // Character counter para el asunto
    function updateCharCount() {
        var length = $('#subject-input').val().length;
        $('#char-count').text(length + '/200');

        // Cambiar color según proximidad al límite
        if (length > 180) {
            $('#char-count').removeClass('text-muted text-warning').addClass('text-danger');
        } else if (length > 150) {
            $('#char-count').removeClass('text-muted text-danger').addClass('text-warning');
        } else {
            $('#char-count').removeClass('text-warning text-danger').addClass('text-muted');
        }
    }

    // Preview del asunto en tiempo real
    function updateSubjectPreview() {
        var subject = $('#subject-input').val();
        if (subject.trim() === '') {
            $('#subject-preview').html('<span class="text-muted fst-italic">El asunto aparecerá aquí...</span>');
        } else {
            $('#subject-preview').html('<strong>' + escapeHtml(subject) + '</strong>');
        }
    }

    // Escape HTML para prevenir XSS
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Eventos para el asunto
    $('#subject-input').on('input', function() {
        updateCharCount();
        updateSubjectPreview();
    });

    // Inicializar contador
    updateCharCount();
    updateSubjectPreview();

    // Insertar variables en el editor
    $('.variable-tag').on('click', function() {
        var variable = $(this).data('variable');
        var range = quill.getSelection(true);
        if (range) {
            quill.insertText(range.index, variable);
            quill.setSelection(range.index + variable.length);
        } else {
            // Si no hay selección, insertar al final
            var length = quill.getLength();
            quill.insertText(length - 1, variable);
            quill.setSelection(length + variable.length - 1);
        }

        // Focus en el editor
        quill.focus();
    });

    // Actualizar vista previa del modal
    $('#previewModal').on('show.bs.modal', function() {
        var subject = $('#subject-input').val();
        var content = quill.root.innerHTML;

        if (subject.trim() === '') {
            $('#preview-subject').html('<span class="text-muted fst-italic">Sin asunto</span>');
        } else {
            $('#preview-subject').html('<strong>' + escapeHtml(subject) + '</strong>');
        }

        if (content.trim() === '' || content === '<p><br></p>') {
            $('#preview-content').html('<span class="text-muted fst-italic">Sin contenido</span>');
        } else {
            $('#preview-content').html(content);
        }
    });

    // Sincronizar contenido del editor con el textarea antes de enviar
    $('#formTemplate').on('submit', function(e) {
        var content = quill.root.innerHTML;
        $('#email-content').val(content);

        // Validación adicional: verificar que no esté vacío
        if (content.trim() === '' || content === '<p><br></p>') {
            e.preventDefault();
            alert('Por favor, ingresa contenido para la plantilla.');
            return false;
        }
    });

    // Form validation con jQuery Validate
    $('#formTemplate').validate({
        rules: {
            name: {
                required: true,
                maxlength: 100
            },
            type: {
                required: true
            },
            description: {
                maxlength: 255
            },
            subject: {
                required: true,
                maxlength: 200
            },
            content: {
                required: true
            }
        },
        messages: {
            name: {
                required: 'El nombre de la plantilla es obligatorio',
                maxlength: 'El nombre no puede superar los 100 caracteres'
            },
            type: {
                required: 'Selecciona un tipo de plantilla'
            },
            description: {
                maxlength: 'La descripción no puede superar los 255 caracteres'
            },
            subject: {
                required: 'El asunto del email es obligatorio',
                maxlength: 'El asunto no puede superar los 200 caracteres'
            },
            content: {
                required: 'El contenido del email es obligatorio'
            }
        },
        highlight: function(element) {
            $(element).addClass('is-invalid');

            // Para Select2
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container')
                    .find('.select2-selection')
                    .addClass('is-invalid');
            }
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');

            // Para Select2
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container')
                    .find('.select2-selection')
                    .removeClass('is-invalid');
            }
        },
        errorPlacement: function(error, element) {
            error.addClass('field-validation-error');

            // Colocar error después del contenedor Select2
            if ($(element).hasClass('select2')) {
                error.insertAfter(element.next('.select2-container'));
            } else {
                error.insertAfter(element);
            }
        }
    });

    // Validar Select2 al cambiar
    $('.select2').on('change', function() {
        $(this).valid();
    });
});
</script>
@endpush

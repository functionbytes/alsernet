@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración Global de Documentos'])


        @if ($message = session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($message = session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-exclamation me-2"></i> Por favor, corrige los siguientes errores:
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif



                <form action="{{ route('settings.documents.configurations.update') }}"
                      method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PATCH')

                    <div class="card">
                        <div class="card-body">

                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                   Solicitud de documentos
                                </h6>
                                <p class="text-muted small mb-3">
                                    Se envía cuando se detecta una orden pagada y se crea un documento que requiere ser cargado.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableInitialRequest"
                                           name="enable_initial_request" value="1"
                                           {{ ($globalSettings['enable_initial_request'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableInitialRequest">
                                        <strong>Habilitar</strong> solicitud inicial de documentos
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template select2" id="mail_template_initial_request_id"
                                            name="mail_template_initial_request_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        @foreach ($globalSettings['available_templates'] as $template)
                                            <option value="{{ $template['id'] }}"
                                                    @if ((string)$globalSettings['mail_template_initial_request_id'] === (string)$template['id']) selected @endif>
                                                {{ $template['text'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Busca y selecciona una plantilla personalizada. Si dejas vacío, se usará la plantilla Por defecto del sistema.
                                    </small>
                                </div>

                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                  Recordatorio automático
                                </h6>
                                <p class="text-muted small mb-3">
                                    Se envía automáticamente a los clientes que no han cargado los documentos después del número de días especificado.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableReminder"
                                           name="enable_reminder" value="1"
                                           {{ ($globalSettings['enable_reminder'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableReminder">
                                        <strong>Habilitar</strong> recordatorios automáticos
                                    </label>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Días antes de enviar recordatorio</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="reminder_days"
                                                   min="1" max="90"
                                                   value="{{ $globalSettings['reminder_days'] ?? 7 }}" required>
                                            <span class="input-group-text">días</span>
                                        </div>
                                        <small class="text-muted">Mínimo: 1 día, Máximo: 90 días</small>
                                    </div>

                                    <div class="col-md-12 mt-1">
                                        <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                        <select class="form-select select2 select2-template select2" id="mail_template_reminder_id"
                                                name="mail_template_reminder_id"
                                                data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                            @foreach ($globalSettings['available_templates'] as $template)
                                                <option value="{{ $template['id'] }}"
                                                        @if ((string)$globalSettings['mail_template_reminder_id'] === (string)$template['id']) selected @endif>
                                                    {{ $template['text'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>

                            <hr class="my-4">

                            <!-- Configuración de Documentos Específicos -->
                            <div class="mb-0">
                                <h6 class="mb-1 fw-bold text-dark">
                                     Solicitud de documentos específicos
                                </h6>
                                <p class="text-muted small mb-3">
                                    Permite al administrador solicitar documentos específicos que el cliente debe reenviar o corregir.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableMissingDocs"
                                           name="enable_missing_docs" value="1"
                                           {{ ($globalSettings['enable_missing_docs'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableMissingDocs">
                                        <strong>Habilitar</strong> solicitud de documentos específicos
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template select2" id="mail_template_missing_docs_id"
                                            name="mail_template_missing_docs_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        @foreach ($globalSettings['available_templates'] as $template)
                                            <option value="{{ $template['id'] }}"
                                                    @if ((string)$globalSettings['mail_template_missing_docs_id'] === (string)$template['id']) selected @endif>
                                                {{ $template['text'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>

                            <hr class="my-4">

                            <!-- Correo Personalizado -->
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Correo personalizado
                                </h6>
                                <p class="text-muted small mb-3">
                                    Opción para enviar correos personalizados manualmente desde la gestión de documentos.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableCustomEmail"
                                           name="enable_custom_email" value="1"
                                           {{ ($globalSettings['enable_custom_email'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableCustomEmail">
                                        <strong>Habilitar</strong> correo personalizado
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template select2" id="mail_template_custom_email_id"
                                            name="mail_template_custom_email_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        <option value=""></option>
                                        @foreach ($globalSettings['available_templates'] as $template)
                                            <option value="{{ $template['id'] }}"
                                                    @if ((string)$globalSettings['mail_template_custom_email_id'] === (string)$template['id']) selected @endif>
                                                {{ $template['text'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Selecciona una plantilla personalizada o deja vacío para usar la plantilla Por defecto.
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Configuración de Confirmación de Subida -->
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificación de confirmación de subida
                                </h6>
                                <p class="text-muted small mb-3">
                                    Se envía automáticamente cuando el cliente carga documentos en el portal.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableUploadConfirmation"
                                           name="enable_upload_confirmation" value="1"
                                           {{ ($globalSettings['enable_upload_confirmation'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableUploadConfirmation">
                                        <strong>Habilitar</strong> notificación de confirmación de subida
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template select2" id="mail_template_upload_confirmation_id"
                                            name="mail_template_upload_confirmation_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        @foreach ($globalSettings['available_templates'] as $template)
                                            <option value="{{ $template['id'] }}"
                                                    @if ((string)$globalSettings['mail_template_upload_confirmation_id'] === (string)$template['id']) selected @endif>
                                                {{ $template['text'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Notifica al cliente que sus documentos han sido recibidos correctamente.
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Configuración de Aprobación -->
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificación de aprobación
                                </h6>
                                <p class="text-muted small mb-3">
                                    Se envía cuando los documentos son aprobados y el proceso continúa.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableApproval"
                                           name="enable_approval" value="1"
                                           {{ ($globalSettings['enable_approval'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableApproval">
                                        <strong>Habilitar</strong> notificación de aprobación
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template select2" id="mail_template_approval_id"
                                            name="mail_template_approval_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        @foreach ($globalSettings['available_templates'] as $template)
                                            <option value="{{ $template['id'] }}"
                                                    @if ((string)$globalSettings['mail_template_approval_id'] === (string)$template['id']) selected @endif>
                                                {{ $template['text'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Notifica al cliente que sus documentos han sido aprobados.
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Configuración de Rechazo -->
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificación de rechazo
                                </h6>
                                <p class="text-muted small mb-3">
                                    Se envía cuando los documentos son rechazados y se requiere que se reenvíen.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableRejection"
                                           name="enable_rejection" value="1"
                                           {{ ($globalSettings['enable_rejection'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableRejection">
                                        <strong>Habilitar</strong> notificación de rechazo
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template select2" id="mail_template_rejection_id"
                                            name="mail_template_rejection_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        @foreach ($globalSettings['available_templates'] as $template)
                                            <option value="{{ $template['id'] }}"
                                                    @if ((string)$globalSettings['mail_template_rejection_id'] === (string)$template['id']) selected @endif>
                                                {{ $template['text'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Notifica al cliente que sus documentos fueron rechazados e indica qué debe corregir.
                                    </small>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                                <button type="submit" class="btn btn-primary w-100 mb-1">
                                    Guardar
                                </button>
                                <a href="{{ route('settings.documents.configurations.global') }}" class="btn btn-secondary w-100">
                                    Volver
                                </a>
                        </div>

                    </div>

                </form>



@endsection

@section('scripts')
<script>
(function() {
    // Initialize character counters using jQuery
    function initCharCounters() {
        // Find all textareas with char counter class
        $('.char-count-textarea').each(function() {
            var $textarea = $(this);
            var textareaId = $textarea.attr('name');

            // Find the counter by looking for parent container and then the span
            var $mbDiv = $textarea.closest('div.mb-0');
            var $counter = $mbDiv.find('span.char-count');

            if ($counter.length > 0) {
                // Update function
                var updateCounter = function() {
                    var length = $textarea.val().length;
                    $counter.text(length);
                };

                // Initialize on page load
                updateCounter();

                // Bind to all input events
                $textarea.on('input change keyup paste', updateCounter);
            }
        });
    }

    // Run on document ready
    $(document).ready(function() {
        initCharCounters();
    });

    // Obtener token CSRF using jQuery
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val() || '';

    // Templates disponibles desde el servidor
    var availableTemplates = {!! json_encode($globalSettings['available_templates'] ?? []) !!};

    // Configuración compartida para todos los selects de templates
    var selectConfig = {
        placeholder: 'Selecciona un template o deja vacío para usar el predefinido',
        allowClear: true,
        width: '100%',
        ajax: {
            url: '{{ route("settings.documents.configurations.search-templates") }}',
            dataType: 'json',
            delay: 250,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            data: function(params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function(data) {
                // Si no hay búsqueda, usar templates del servidor
                if (!data.results || data.results.length === 0) {
                    return {
                        results: availableTemplates,
                        pagination: { more: false }
                    };
                }
                return {
                    results: data.results || [],
                    pagination: data.pagination || { more: false }
                };
            },
            cache: true
        },
        templateResult: function(data) {
            if (!data.id) return data.text;
            return $('<span>' + data.text + '</span>');
        },
        templateSelection: function(data) {
            return data.text || data.name;
        },
        minimumInputLength: 0,
        allowHtml: true,
        dropdownParent: $('body'),
        language: {
            noResults: function() {
                return 'No se encontraron templates';
            },
            searching: function() {
                return 'Buscando...';
            },
            inputTooShort: function() {
                return 'Comienza a escribir para buscar templates';
            }
        }
    };

    // Lista de selectores
    var templateSelects = [
        '#mail_template_initial_request_id',
        '#mail_template_reminder_id',
        '#mail_template_missing_docs_id',
        '#mail_template_custom_email_id',
        '#mail_template_upload_confirmation_id',
        '#mail_template_approval_id',
        '#mail_template_rejection_id'
    ];

    function initializeSelect2() {
        // Verificar que jQuery y Select2 estén disponibles
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(initializeSelect2, 100);
            return;
        }

        for (var i = 0; i < templateSelects.length; i++) {
            var selector = templateSelects[i];
            var $el = $(selector);

            if ($el.length === 0) {
                console.warn('Element not found:', selector);
                continue;
            }

            // Destruir Select2 anterior si existe
            if ($el.data('select2')) {
                $el.select2('destroy');
            }

            try {
                $el.select2(selectConfig);

                // Trigger AJAX load when dropdown is opened
                $el.on('select2:opening', function(e) {
                    var searchField = $(this).data('select2').dropdown.$search ||
                                      $(this).data('select2').selection.$search;

                    if (searchField) {
                        // Simulate a search with empty term to load all templates
                        setTimeout(function() {
                            searchField.val('').trigger('input');
                        }, 100);
                    }
                });
            } catch (error) {
                console.error('Error initializing Select2 for ' + selector + ':', error);
            }
        }
    }

    // Ejecutar cuando jQuery esté disponible
    if (typeof $ !== 'undefined') {
        $(document).ready(initializeSelect2);
    } else {
        document.addEventListener('DOMContentLoaded', initializeSelect2);
    }
})();
</script>
@endsection

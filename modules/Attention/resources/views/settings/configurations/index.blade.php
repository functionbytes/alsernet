@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración Global de Atención'])


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

        <form action="{{ route('settings.attention.configurations.update') }}"
              method="POST" class="needs-validation" novalidate>
            @csrf
            @method('PATCH')

            <div class="card">
                <!-- Tabs Navigation -->
                <div class="card-header border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab"
                                    data-bs-target="#general" type="button" role="tab">
                                General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="email-tab" data-bs-toggle="tab"
                                    data-bs-target="#email" type="button" role="tab">
                                Email
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sla-tab" data-bs-toggle="tab"
                                    data-bs-target="#sla" type="button" role="tab">
                                SLA
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab"
                                    data-bs-target="#notifications" type="button" role="tab">
                                Notificaciones
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="configTabsContent">

                        <!-- Tab 1: General -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Configuración de radicado
                                </h6>
                                <p class="text-muted mb-3">
                                    Define el formato del número de radicado para las solicitudes PQRSF.
                                </p>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Prefijo de radicado</label>
                                        <input type="text" class="form-control" name="radicado_prefix"
                                               value="{{ old('radicado_prefix', $configurations['radicado_prefix'] ?? 'PQRSF') }}"
                                               placeholder="PQRSF" required>
                                        <small class="text-muted">Ejemplo: PQRSF-2024-0001</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Dígitos del consecutivo</label>
                                        <input type="number" class="form-control" name="radicado_digits"
                                               value="{{ old('radicado_digits', $configurations['radicado_digits'] ?? 6) }}"
                                               min="4" max="10" required>
                                        <small class="text-muted">Cantidad de dígitos (ej: 6 = 000001)</small>
                                    </div>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="includeYear"
                                           name="radicado_include_year" value="1"
                                           {{ old('radicado_include_year', $configurations['radicado_include_year'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="includeYear">
                                        Incluir año en el radicado
                                    </label>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Archivos adjuntos
                                </h6>
                                <p class="text-muted mb-3">
                                    Configura los límites para archivos adjuntos en las solicitudes.
                                </p>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tamaño máximo por archivo (MB)</label>
                                        <input type="number" class="form-control" name="max_file_size"
                                               value="{{ old('max_file_size', $configurations['max_file_size'] ?? 10) }}"
                                               min="1" max="100" required>
                                        <small class="text-muted">Límite en megabytes</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Cantidad máxima de archivos</label>
                                        <input type="number" class="form-control" name="max_attachments"
                                               value="{{ old('max_attachments', $configurations['max_attachments'] ?? 5) }}"
                                               min="1" max="20" required>
                                        <small class="text-muted">Archivos permitidos por solicitud</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Extensiones permitidas</label>
                                    <input type="text" class="form-control" name="allowed_extensions"
                                           value="{{ old('allowed_extensions', $configurations['allowed_extensions'] ?? 'pdf,doc,docx,jpg,jpeg,png') }}"
                                           placeholder="pdf,doc,docx,jpg,jpeg,png">
                                    <small class="text-muted">Separadas por comas</small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-0">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Opciones generales
                                </h6>
                                <p class="text-muted mb-3">
                                    Configuraciones adicionales del sistema.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="allowAnonymous"
                                           name="allow_anonymous" value="1"
                                           {{ old('allow_anonymous', $configurations['allow_anonymous'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allowAnonymous">
                                        Permitir solicitudes anónimas
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="requireAttachment"
                                           name="require_attachment" value="1"
                                           {{ old('require_attachment', $configurations['require_attachment'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requireAttachment">
                                        Requerir al menos un archivo adjunto
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="autoAssign"
                                           name="auto_assign" value="1"
                                           {{ old('auto_assign', $configurations['auto_assign'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="autoAssign">
                                        Asignación automática por departamento
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Email -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificación de recepción
                                </h6>
                                <p class="text-muted mb-3">
                                    Se envía automáticamente cuando el ciudadano crea una nueva solicitud.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailReceived"
                                           name="email_received_enabled" value="1"
                                           {{ old('email_received_enabled', $configurations['email_received_enabled'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emailReceived">
                                        <strong>Habilitar</strong> notificación de recepción
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template" id="email_template_received_id"
                                            name="email_template_received_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        <option value=""></option>
                                        @if(isset($availableTemplates))
                                            @foreach ($availableTemplates as $template)
                                                <option value="{{ $template['id'] }}"
                                                        @if ((string)($configurations['email_template_received_id'] ?? '') === (string)$template['id']) selected @endif>
                                                    {{ $template['text'] }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Notifica al ciudadano que su solicitud fue recibida con el número de radicado.
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificación de asignación
                                </h6>
                                <p class="text-muted mb-3">
                                    Se envía cuando la solicitud es asignada a un funcionario o departamento.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailAssigned"
                                           name="email_assigned_enabled" value="1"
                                           {{ old('email_assigned_enabled', $configurations['email_assigned_enabled'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emailAssigned">
                                        <strong>Habilitar</strong> notificación de asignación
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template" id="email_template_assigned_id"
                                            name="email_template_assigned_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        <option value=""></option>
                                        @if(isset($availableTemplates))
                                            @foreach ($availableTemplates as $template)
                                                <option value="{{ $template['id'] }}"
                                                        @if ((string)($configurations['email_template_assigned_id'] ?? '') === (string)$template['id']) selected @endif>
                                                    {{ $template['text'] }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificación en proceso
                                </h6>
                                <p class="text-muted mb-3">
                                    Se envía cuando la solicitud cambia a estado "En proceso".
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailInProcess"
                                           name="email_in_process_enabled" value="1"
                                           {{ old('email_in_process_enabled', $configurations['email_in_process_enabled'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emailInProcess">
                                        <strong>Habilitar</strong> notificación en proceso
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template" id="email_template_in_process_id"
                                            name="email_template_in_process_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        <option value=""></option>
                                        @if(isset($availableTemplates))
                                            @foreach ($availableTemplates as $template)
                                                <option value="{{ $template['id'] }}"
                                                        @if ((string)($configurations['email_template_in_process_id'] ?? '') === (string)$template['id']) selected @endif>
                                                    {{ $template['text'] }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificación de resolución
                                </h6>
                                <p class="text-muted mb-3">
                                    Se envía cuando la solicitud es resuelta con una respuesta oficial.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailResolved"
                                           name="email_resolved_enabled" value="1"
                                           {{ old('email_resolved_enabled', $configurations['email_resolved_enabled'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emailResolved">
                                        <strong>Habilitar</strong> notificación de resolución
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template" id="email_template_resolved_id"
                                            name="email_template_resolved_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        <option value=""></option>
                                        @if(isset($availableTemplates))
                                            @foreach ($availableTemplates as $template)
                                                <option value="{{ $template['id'] }}"
                                                        @if ((string)($configurations['email_template_resolved_id'] ?? '') === (string)$template['id']) selected @endif>
                                                    {{ $template['text'] }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-0">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificación de cierre
                                </h6>
                                <p class="text-muted mb-3">
                                    Se envía cuando la solicitud es cerrada definitivamente.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailClosed"
                                           name="email_closed_enabled" value="1"
                                           {{ old('email_closed_enabled', $configurations['email_closed_enabled'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emailClosed">
                                        <strong>Habilitar</strong> notificación de cierre
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Plantilla de Email (Opcional)</label>
                                    <select class="form-select select2 select2-template" id="email_template_closed_id"
                                            name="email_template_closed_id"
                                            data-placeholder="Selecciona un template o deja vacío para usar el predefinido">
                                        <option value=""></option>
                                        @if(isset($availableTemplates))
                                            @foreach ($availableTemplates as $template)
                                                <option value="{{ $template['id'] }}"
                                                        @if ((string)($configurations['email_template_closed_id'] ?? '') === (string)$template['id']) selected @endif>
                                                    {{ $template['text'] }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: SLA -->
                        <div class="tab-pane fade" id="sla" role="tabpanel">
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Tiempos de respuesta (SLA)
                                </h6>
                                <p class="text-muted mb-3">
                                    Define los tiempos máximos para cada etapa del proceso de atención.
                                </p>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tiempo para asignación (horas)</label>
                                        <input type="number" class="form-control" name="sla_assignment_hours"
                                               value="{{ old('sla_assignment_hours', $configurations['sla_assignment_hours'] ?? 4) }}"
                                               min="1" required>
                                        <small class="text-muted">Tiempo máximo para asignar la solicitud</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tiempo para primera respuesta (horas)</label>
                                        <input type="number" class="form-control" name="sla_first_response_hours"
                                               value="{{ old('sla_first_response_hours', $configurations['sla_first_response_hours'] ?? 24) }}"
                                               min="1" required>
                                        <small class="text-muted">Tiempo para dar una primera respuesta</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tiempo para resolución (días)</label>
                                        <input type="number" class="form-control" name="sla_resolution_days"
                                               value="{{ old('sla_resolution_days', $configurations['sla_resolution_days'] ?? 15) }}"
                                               min="1" required>
                                        <small class="text-muted">Tiempo máximo para resolver la solicitud</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tiempo para cierre (días)</label>
                                        <input type="number" class="form-control" name="sla_closure_days"
                                               value="{{ old('sla_closure_days', $configurations['sla_closure_days'] ?? 3) }}"
                                               min="1" required>
                                        <small class="text-muted">Tiempo para cerrar después de resolver</small>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-0">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Alertas SLA
                                </h6>
                                <p class="text-muted mb-3">
                                    Configura cuándo se envían alertas de vencimiento.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableSlaAlerts"
                                           name="enable_sla_alerts" value="1"
                                           {{ old('enable_sla_alerts', $configurations['enable_sla_alerts'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableSlaAlerts">
                                        Habilitar alertas automáticas de SLA
                                    </label>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold">Porcentaje de alerta temprana (%)</label>
                                    <input type="number" class="form-control" name="sla_warning_threshold"
                                           value="{{ old('sla_warning_threshold', $configurations['sla_warning_threshold'] ?? 75) }}"
                                           min="10" max="100">
                                    <small class="text-muted">Enviar alerta al alcanzar este porcentaje del tiempo SLA (ej: 75% = alerta al 75% del tiempo)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Notificaciones -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificaciones internas
                                </h6>
                                <p class="text-muted mb-3">
                                    Configura las notificaciones que reciben los funcionarios.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyNewAttention"
                                           name="notify_new_attention" value="1"
                                           {{ old('notify_new_attention', $configurations['notify_new_attention'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notifyNewAttention">
                                        Notificar nueva solicitud recibida
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyAssignment"
                                           name="notify_assignment" value="1"
                                           {{ old('notify_assignment', $configurations['notify_assignment'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notifyAssignment">
                                        Notificar asignaciones al responsable
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyReassignment"
                                           name="notify_reassignment" value="1"
                                           {{ old('notify_reassignment', $configurations['notify_reassignment'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notifyReassignment">
                                        Notificar reasignaciones
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="notifySlaWarning"
                                           name="notify_sla_warning" value="1"
                                           {{ old('notify_sla_warning', $configurations['notify_sla_warning'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notifySlaWarning">
                                        Notificar alertas de SLA próximas a vencer
                                    </label>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-0">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Notificaciones al ciudadano
                                </h6>
                                <p class="text-muted mb-3">
                                    Configura las notificaciones que reciben los ciudadanos.
                                </p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyCitizenUpdates"
                                           name="notify_citizen_updates" value="1"
                                           {{ old('notify_citizen_updates', $configurations['notify_citizen_updates'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notifyCitizenUpdates">
                                        Notificar cambios de estado
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyCitizenComments"
                                           name="notify_citizen_comments" value="1"
                                           {{ old('notify_citizen_comments', $configurations['notify_citizen_comments'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notifyCitizenComments">
                                        Notificar nuevos comentarios internos
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="enableSmsNotifications"
                                           name="enable_sms_notifications" value="1"
                                           {{ old('enable_sms_notifications', $configurations['enable_sms_notifications'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableSmsNotifications">
                                        Habilitar notificaciones SMS (requiere configuración adicional)
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">
                        Guardar configuración
                    </button>
                    <a href="{{ route('settings.attention.index') }}" class="btn btn-secondary w-100">
                        Volver
                    </a>
                </div>
            </div>

        </form>


@endsection

@section('scripts')
<script>
(function() {
    // Obtener token CSRF
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val() || '';

    // Templates disponibles desde el servidor
    var availableTemplates = {!! json_encode($availableTemplates ?? []) !!};

    // Configuración compartida para todos los selects de templates
    var selectConfig = {
        placeholder: 'Selecciona un template o deja vacío para usar el predefinido',
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,
        allowHtml: true,
        dropdownParent: $('body')
    };

    // Lista de selectores
    var templateSelects = [
        '#email_template_received_id',
        '#email_template_assigned_id',
        '#email_template_in_process_id',
        '#email_template_resolved_id',
        '#email_template_closed_id'
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

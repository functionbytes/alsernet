@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración general de Mailrelay'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Configuration Form Card -->
        <div class="card">
            <form id="formMailrelayGeneral" method="POST" action="{{ route('settings.mailrelay.general.update') }}">
                @csrf
                @method('PATCH')

                <!-- Card Header -->
                <div class="card-header p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Configuración general</h5>
                            <p class="small mb-0 text-muted">Configura los parámetros generales de Mailrelay para tu aplicación.</p>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body">

                    <!-- Sección 1: Remitente predeterminado -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-user-circle me-2"></i>Remitente predeterminado
                    </h6>

                    <div class="row">
                        <!-- Nombre del remitente -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="sender_name">
                                    Nombre del remitente
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('sender_name') is-invalid @enderror"
                                       id="sender_name"
                                       name="sender_name"
                                       value="{{ old('sender_name', $settings->sender_name ?? '') }}"
                                       required
                                       placeholder="Mi Empresa">
                                @error('sender_name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Email del remitente -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="sender_email">
                                    Email del remitente
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="email"
                                       class="form-control @error('sender_email') is-invalid @enderror"
                                       id="sender_email"
                                       name="sender_email"
                                       value="{{ old('sender_email', $settings->sender_email ?? '') }}"
                                       required
                                       placeholder="noreply@miempresa.com">
                                @error('sender_email')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Email de respuesta -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="reply_to_email">Email de respuesta</label>
                                <input type="email"
                                       class="form-control @error('reply_to_email') is-invalid @enderror"
                                       id="reply_to_email"
                                       name="reply_to_email"
                                       value="{{ old('reply_to_email', $settings->reply_to_email ?? '') }}"
                                       placeholder="soporte@miempresa.com">
                                <small class="form-text text-muted">Email que recibirá las respuestas de los suscriptores</small>
                                @error('reply_to_email')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Sección 2: Opciones de sincronización -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-sync me-2"></i>Opciones de sincronización
                    </h6>

                    <div class="row">
                        <!-- Sincronización automática -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="auto_sync_enabled">Sincronización automática</label>
                                <select class="form-select" name="auto_sync_enabled" id="auto_sync_enabled">
                                    <option value="1" {{ old('auto_sync_enabled', $settings->auto_sync_enabled ?? false) == 1 ? 'selected' : '' }}>Activado</option>
                                    <option value="0" {{ old('auto_sync_enabled', $settings->auto_sync_enabled ?? false) == 0 ? 'selected' : '' }}>Desactivado</option>
                                </select>
                                <small class="form-text text-muted">Sincroniza automáticamente los suscriptores con Mailrelay</small>
                            </div>
                        </div>

                        <!-- Frecuencia de sincronización -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="sync_frequency">Frecuencia de sincronización</label>
                                <select class="form-select select2 @error('sync_frequency') is-invalid @enderror"
                                        id="sync_frequency"
                                        name="sync_frequency"
                                        data-placeholder="Seleccionar frecuencia...">
                                    <option value=""></option>
                                    <option value="15" {{ old('sync_frequency', $settings->sync_frequency ?? '') == '15' ? 'selected' : '' }}>Cada 15 minutos</option>
                                    <option value="30" {{ old('sync_frequency', $settings->sync_frequency ?? '') == '30' ? 'selected' : '' }}>Cada 30 minutos</option>
                                    <option value="60" {{ old('sync_frequency', $settings->sync_frequency ?? '') == '60' ? 'selected' : '' }}>Cada 1 hora</option>
                                    <option value="360" {{ old('sync_frequency', $settings->sync_frequency ?? '') == '360' ? 'selected' : '' }}>Cada 6 horas</option>
                                    <option value="1440" {{ old('sync_frequency', $settings->sync_frequency ?? '') == '1440' ? 'selected' : '' }}>Cada 24 horas</option>
                                </select>
                                @error('sync_frequency')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Sincronizar suscriptores eliminados -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="sync_deleted">Sincronizar suscriptores eliminados</label>
                                <select class="form-select" name="sync_deleted" id="sync_deleted">
                                    <option value="1" {{ old('sync_deleted', $settings->sync_deleted ?? false) == 1 ? 'selected' : '' }}>Activado</option>
                                    <option value="0" {{ old('sync_deleted', $settings->sync_deleted ?? false) == 0 ? 'selected' : '' }}>Desactivado</option>
                                </select>
                                <small class="form-text text-muted">Eliminar de Mailrelay los suscriptores borrados en el sistema</small>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Sección 3: Límites y restricciones -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-shield-alt me-2"></i>Límites y restricciones
                    </h6>

                    <div class="row">
                        <!-- Emails por campaña -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="emails_per_campaign">Emails por campaña</label>
                                <input type="number"
                                       class="form-control @error('emails_per_campaign') is-invalid @enderror"
                                       id="emails_per_campaign"
                                       name="emails_per_campaign"
                                       value="{{ old('emails_per_campaign', $settings->emails_per_campaign ?? 1000) }}"
                                       min="1"
                                       placeholder="1000">
                                <small class="form-text text-muted">Número máximo de emails a enviar por campaña</small>
                                @error('emails_per_campaign')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Reintentos en caso de error -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="retry_attempts">Reintentos en caso de error</label>
                                <input type="number"
                                       class="form-control @error('retry_attempts') is-invalid @enderror"
                                       id="retry_attempts"
                                       name="retry_attempts"
                                       value="{{ old('retry_attempts', $settings->retry_attempts ?? 3) }}"
                                       min="0"
                                       max="5"
                                       placeholder="3">
                                <small class="form-text text-muted">Número de reintentos (0-5) en caso de fallo</small>
                                @error('retry_attempts')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Tiempo de espera -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="timeout">Tiempo de espera</label>
                                <input type="number"
                                       class="form-control @error('timeout') is-invalid @enderror"
                                       id="timeout"
                                       name="timeout"
                                       value="{{ old('timeout', $settings->timeout ?? 30) }}"
                                       min="10"
                                       max="300"
                                       placeholder="30">
                                <small class="form-text text-muted">Tiempo de espera en segundos para las peticiones API</small>
                                @error('timeout')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Sección 4: Opciones de privacidad -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-lock me-2"></i>Opciones de privacidad
                    </h6>

                    <div class="row">
                        <!-- Doble opt-in -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="double_optin">Doble opt-in requerido</label>
                                <select class="form-select" name="double_optin" id="double_optin">
                                    <option value="1" {{ old('double_optin', $settings->double_optin ?? false) == 1 ? 'selected' : '' }}>Activado</option>
                                    <option value="0" {{ old('double_optin', $settings->double_optin ?? false) == 0 ? 'selected' : '' }}>Desactivado</option>
                                </select>
                                <small class="form-text text-muted">Enviar email de confirmación a nuevos suscriptores</small>
                            </div>
                        </div>

                        <!-- Permitir cancelación -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="allow_unsubscribe">Permitir cancelación de suscripción</label>
                                <select class="form-select" name="allow_unsubscribe" id="allow_unsubscribe">
                                    <option value="1" {{ old('allow_unsubscribe', $settings->allow_unsubscribe ?? true) == 1 ? 'selected' : '' }}>Activado</option>
                                    <option value="0" {{ old('allow_unsubscribe', $settings->allow_unsubscribe ?? true) == 0 ? 'selected' : '' }}>Desactivado</option>
                                </select>
                                <small class="form-text text-muted">Incluir enlace de baja en los emails</small>
                            </div>
                        </div>

                        <!-- Link de cancelación -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label" for="unsubscribe_footer">Link de cancelación en footer</label>
                                <input type="text"
                                       class="form-control @error('unsubscribe_footer') is-invalid @enderror"
                                       id="unsubscribe_footer"
                                       name="unsubscribe_footer"
                                       value="{{ old('unsubscribe_footer', $settings->unsubscribe_footer ?? '') }}"
                                       placeholder="Si no deseas recibir más emails, [cancela tu suscripción aquí]">
                                <small class="form-text text-muted">Texto del footer con el enlace de cancelación</small>
                                @error('unsubscribe_footer')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Sección 5: Configuraciones avanzadas (colapsable) -->
                    <div class="mb-3">
                        <a class="btn btn-light w-100 text-start d-flex justify-content-between align-items-center"
                           data-bs-toggle="collapse"
                           href="#advancedSettings"
                           role="button"
                           aria-expanded="false"
                           aria-controls="advancedSettings">
                            <span>
                                <i class="fas fa-cog me-2"></i>
                                <strong>Configuraciones avanzadas</strong>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                    </div>

                    <div class="collapse" id="advancedSettings">
                        <div class="card card-body bg-light-secondary">
                            <div class="row">
                                <!-- Logging detallado -->
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="detailed_logging">Logging detallado</label>
                                        <select class="form-select" name="detailed_logging" id="detailed_logging">
                                            <option value="1" {{ old('detailed_logging', $settings->detailed_logging ?? false) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('detailed_logging', $settings->detailed_logging ?? false) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                        <small class="form-text text-muted">Registrar todas las operaciones con Mailrelay en logs</small>
                                    </div>
                                </div>

                                <!-- Retención de logs -->
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="log_retention_days">Retención de logs</label>
                                        <input type="number"
                                               class="form-control @error('log_retention_days') is-invalid @enderror"
                                               id="log_retention_days"
                                               name="log_retention_days"
                                               value="{{ old('log_retention_days', $settings->log_retention_days ?? 30) }}"
                                               min="1"
                                               max="365"
                                               placeholder="30">
                                        <small class="form-text text-muted">Días de retención de logs (1-365)</small>
                                        @error('log_retention_days')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Modo de prueba -->
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="sandbox_mode">Modo de prueba (sandbox)</label>
                                        <select class="form-select" name="sandbox_mode" id="sandbox_mode">
                                            <option value="1" {{ old('sandbox_mode', $settings->sandbox_mode ?? false) == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('sandbox_mode', $settings->sandbox_mode ?? false) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                        <small class="form-text text-muted">Simular operaciones sin ejecutar llamadas reales a la API</small>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning border-0 bg-warning-subtle mb-0">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-triangle-exclamation text-warning"></i>
                                    <div>
                                        <small class="fw-semibold">Advertencia:</small>
                                        <small class="d-block">Estas configuraciones son para usuarios avanzados. Cambiarlas incorrectamente puede afectar el rendimiento del sistema.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Card Footer -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary px-4 w-100">
                        <i class="fas fa-save me-2"></i>Guardar configuración
                    </button>
                </div>

            </form>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {

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

        // Validate Select2 on change
        $('.select2').on('change', function() {
            $(this).valid();
        });

        // Form Validation
        $('#formMailrelayGeneral').validate({
            rules: {
                sender_name: {
                    required: true,
                    minlength: 2,
                    maxlength: 255
                },
                sender_email: {
                    required: true,
                    email: true
                },
                reply_to_email: {
                    email: true
                },
                sync_frequency: {
                    required: function() {
                        return $('#auto_sync_enabled').is(':checked');
                    }
                },
                emails_per_campaign: {
                    required: true,
                    number: true,
                    min: 1
                },
                retry_attempts: {
                    required: true,
                    number: true,
                    min: 0,
                    max: 5
                },
                timeout: {
                    required: true,
                    number: true,
                    min: 10,
                    max: 300
                },
                log_retention_days: {
                    required: true,
                    number: true,
                    min: 1,
                    max: 365
                }
            },
            messages: {
                sender_name: {
                    required: 'El nombre del remitente es obligatorio',
                    minlength: 'Debe contener al menos 2 caracteres',
                    maxlength: 'No debe exceder 255 caracteres'
                },
                sender_email: {
                    required: 'El email del remitente es obligatorio',
                    email: 'Debe ser un email válido'
                },
                reply_to_email: {
                    email: 'Debe ser un email válido'
                },
                sync_frequency: {
                    required: 'Selecciona una frecuencia de sincronización'
                },
                emails_per_campaign: {
                    required: 'Este campo es obligatorio',
                    number: 'Debe ser un número',
                    min: 'El mínimo es 1'
                },
                retry_attempts: {
                    required: 'Este campo es obligatorio',
                    number: 'Debe ser un número',
                    min: 'El mínimo es 0',
                    max: 'El máximo es 5'
                },
                timeout: {
                    required: 'Este campo es obligatorio',
                    number: 'Debe ser un número',
                    min: 'El mínimo es 10 segundos',
                    max: 'El máximo es 300 segundos'
                },
                log_retention_days: {
                    required: 'Este campo es obligatorio',
                    number: 'Debe ser un número',
                    min: 'El mínimo es 1 día',
                    max: 'El máximo es 365 días'
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
            },
            submitHandler: function(form) {
                var $submitButton = $('button[type="submit"]');
                $submitButton.prop('disabled', true);
                $submitButton.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

                form.submit();
            }
        });

        // Toggle sync frequency requirement based on auto_sync_enabled
        $('#auto_sync_enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#sync_frequency').prop('required', true);
            } else {
                $('#sync_frequency').prop('required', false);
            }
        });

    });
</script>
@endpush

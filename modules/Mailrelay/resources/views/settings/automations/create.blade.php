@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Crear automatización'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Configuration Form Card -->
        <div class="card">
            <form id="formAutomation" method="POST" action="{{ route('settings.mailrelay.automations.store') }}">
                @csrf

                <!-- Card Header -->
                <div class="card-header p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Nueva automatización</h5>
                            <p class="small mb-0 text-muted">Configura una automatización para ejecutar acciones cuando ocurran eventos específicos.</p>
                        </div>
                        <a href="{{ route('settings.mailrelay.automations.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body">

                    <!-- Sección 1: Información básica -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Información básica
                    </h6>

                    <div class="row">
                        <!-- Nombre -->
                        <div class="col-12 col-md-8">
                            <div class="mb-3">
                                <label class="form-label" for="nombre">
                                    Nombre
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('nombre') is-invalid @enderror"
                                       id="nombre"
                                       name="nombre"
                                       value="{{ old('nombre') }}"
                                       required
                                       placeholder="Ej: Bienvenida a nuevos suscriptores">
                                @error('nombre')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Toggle: Automatización activa -->
                        <div class="col-12 col-md-4">
                            <div class="mb-3">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="activa"
                                           name="activa"
                                           value="1"
                                           {{ old('activa', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="activa">
                                        Automatización activa
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label" for="descripcion">Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                          id="descripcion"
                                          name="descripcion"
                                          rows="3"
                                          placeholder="Describe el propósito de esta automatización">{{ old('descripcion') }}</textarea>
                                @error('descripcion')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Sección 2: Trigger (disparador) -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-bolt me-2"></i>Disparador
                    </h6>

                    <div class="alert alert-info border-0 bg-info-subtle mb-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-info-circle text-info"></i>
                            <div>
                                <small class="fw-semibold">El trigger determina cuándo se ejecuta</small>
                                <small class="d-block">Selecciona el evento que activará esta automatización.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Select tipo_evento -->
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="trigger-type">
                                    Tipo de evento
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select select2 @error('tipo_evento') is-invalid @enderror"
                                        id="trigger-type"
                                        name="tipo_evento"
                                        required
                                        data-placeholder="Seleccionar evento...">
                                    <option value=""></option>
                                    <option value="nuevo_suscriptor" {{ old('tipo_evento') === 'nuevo_suscriptor' ? 'selected' : '' }}>Nuevo suscriptor</option>
                                    <option value="suscriptor_actualizado" {{ old('tipo_evento') === 'suscriptor_actualizado' ? 'selected' : '' }}>Suscriptor actualizado</option>
                                    <option value="click_campana" {{ old('tipo_evento') === 'click_campana' ? 'selected' : '' }}>Click en campaña</option>
                                    <option value="apertura_email" {{ old('tipo_evento') === 'apertura_email' ? 'selected' : '' }}>Apertura de email</option>
                                    <option value="cancelacion" {{ old('tipo_evento') === 'cancelacion' ? 'selected' : '' }}>Cancelación de suscripción</option>
                                    <option value="fecha_especifica" {{ old('tipo_evento') === 'fecha_especifica' ? 'selected' : '' }}>Fecha específica</option>
                                    <option value="campo_cambia" {{ old('tipo_evento') === 'campo_cambia' ? 'selected' : '' }}>Campo personalizado cambia</option>
                                </select>
                                @error('tipo_evento')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Trigger conditions (mostrar/ocultar según evento) -->
                    <div id="trigger-conditions" style="display: none;">
                        <div class="row">
                            <!-- Select grupo (si evento afecta grupos) -->
                            <div class="col-12 col-md-6 condition-field" id="condition-grupo" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label" for="trigger-grupo">Grupo</label>
                                    <select class="form-select select2"
                                            id="trigger-grupo"
                                            name="trigger_grupo"
                                            data-placeholder="Seleccionar grupo...">
                                        <option value=""></option>
                                        <option value="1">Clientes VIP</option>
                                        <option value="2">Newsletter semanal</option>
                                        <option value="3">Promociones</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Select campaña (si evento es de campaña) -->
                            <div class="col-12 col-md-6 condition-field" id="condition-campana" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label" for="trigger-campana">Campaña</label>
                                    <select class="form-select select2"
                                            id="trigger-campana"
                                            name="trigger_campana"
                                            data-placeholder="Seleccionar campaña...">
                                        <option value=""></option>
                                        <option value="1">Campaña de verano 2026</option>
                                        <option value="2">Black Friday</option>
                                        <option value="3">Newsletter mensual</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Input date (si evento es fecha) -->
                            <div class="col-12 col-md-6 condition-field" id="condition-fecha" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label" for="trigger-fecha">Fecha específica</label>
                                    <input type="date"
                                           class="form-control"
                                           id="trigger-fecha"
                                           name="trigger_fecha"
                                           value="{{ old('trigger_fecha') }}">
                                </div>
                            </div>

                            <!-- Select campo (si evento es campo_cambia) -->
                            <div class="col-12 col-md-6 condition-field" id="condition-campo" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label" for="trigger-campo">Campo personalizado</label>
                                    <select class="form-select select2"
                                            id="trigger-campo"
                                            name="trigger_campo"
                                            data-placeholder="Seleccionar campo...">
                                        <option value=""></option>
                                        <option value="empresa">Empresa</option>
                                        <option value="cargo">Cargo</option>
                                        <option value="telefono">Teléfono</option>
                                        <option value="pais">País</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Sección 3: Acciones -->
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-tasks me-2"></i>Acciones
                    </h6>

                    <div class="alert alert-success border-0 bg-success-subtle mb-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-check-circle text-success"></i>
                            <div>
                                <small class="fw-semibold">Define qué sucede cuando se activa</small>
                                <small class="d-block">Las acciones se ejecutan en el orden en que se agregan.</small>
                            </div>
                        </div>
                    </div>

                    <div id="actions-container">
                        <!-- Las acciones se agregan dinámicamente aquí -->
                    </div>

                    <button type="button" class="btn btn-success btn-sm" id="add-action-btn">
                        <i class="fas fa-plus-circle me-2"></i>Agregar acción
                    </button>

                    <hr class="my-4">

                    <!-- Sección 4: Configuración avanzada (collapse) -->
                    <div class="mb-3">
                        <a class="btn btn-light w-100 text-start d-flex justify-content-between align-items-center"
                           data-bs-toggle="collapse"
                           href="#advancedConfig"
                           role="button"
                           aria-expanded="false"
                           aria-controls="advancedConfig">
                            <span>
                                <i class="fas fa-cog me-2"></i>
                                <strong>Configuración avanzada</strong>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                    </div>

                    <div class="collapse" id="advancedConfig">
                        <div class="card card-body bg-light-secondary">
                            <div class="row">
                                <!-- Retraso inicial -->
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="retraso_inicial">Retraso inicial (minutos)</label>
                                        <input type="number"
                                               class="form-control @error('retraso_inicial') is-invalid @enderror"
                                               id="retraso_inicial"
                                               name="retraso_inicial"
                                               value="{{ old('retraso_inicial', 0) }}"
                                               min="0"
                                               placeholder="0">
                                        <small class="form-text text-muted">Tiempo de espera antes de ejecutar la primera acción</small>
                                        @error('retraso_inicial')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Máximo ejecuciones -->
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="max_ejecuciones">Máximo ejecuciones</label>
                                        <input type="number"
                                               class="form-control @error('max_ejecuciones') is-invalid @enderror"
                                               id="max_ejecuciones"
                                               name="max_ejecuciones"
                                               value="{{ old('max_ejecuciones', 0) }}"
                                               min="0"
                                               placeholder="0">
                                        <small class="form-text text-muted">0 = ilimitado. Limita cuántas veces se puede ejecutar esta automatización.</small>
                                        @error('max_ejecuciones')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Toggle: Log detallado -->
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="log_detallado"
                                                   name="log_detallado"
                                                   value="1"
                                                   {{ old('log_detallado') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="log_detallado">
                                                Log detallado
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">Registrar información detallada de cada ejecución</small>
                                    </div>
                                </div>

                                <!-- Toggle: Notificar settings en errores -->
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="notificar_admin"
                                                   name="notificar_admin"
                                                   value="1"
                                                   {{ old('notificar_admin') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="notificar_admin">
                                                Notificar admin en errores
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">Enviar notificación al administrador cuando ocurra un error</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Card Footer -->
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('settings.mailrelay.automations.index') }}" class="btn btn-secondary px-4">
                            <i class="fas fa-times-circle me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </div>

            </form>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let actionIndex = 0;

    // Initialize Select2
    $('.select2').select2({
        allowClear: true,
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
    $('#formAutomation').validate({
        rules: {
            nombre: {
                required: true,
                minlength: 3,
                maxlength: 255
            },
            tipo_evento: {
                required: true
            },
            retraso_inicial: {
                number: true,
                min: 0
            },
            max_ejecuciones: {
                number: true,
                min: 0
            }
        },
        messages: {
            nombre: {
                required: 'El nombre es obligatorio',
                minlength: 'Debe contener al menos 3 caracteres',
                maxlength: 'No debe exceder 255 caracteres'
            },
            tipo_evento: {
                required: 'Selecciona un tipo de evento'
            },
            retraso_inicial: {
                number: 'Debe ser un número válido',
                min: 'El valor mínimo es 0'
            },
            max_ejecuciones: {
                number: 'Debe ser un número válido',
                min: 'El valor mínimo es 0'
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

    // Trigger type change handler
    $('#trigger-type').on('change', function() {
        const selectedType = $(this).val();

        // Hide all condition fields
        $('.condition-field').hide();
        $('#trigger-conditions').hide();

        // Show relevant condition fields based on trigger type
        if (selectedType === 'nuevo_suscriptor' || selectedType === 'suscriptor_actualizado') {
            $('#condition-grupo').show();
            $('#trigger-conditions').show();
        } else if (selectedType === 'click_campana' || selectedType === 'apertura_email') {
            $('#condition-campana').show();
            $('#trigger-conditions').show();
        } else if (selectedType === 'fecha_especifica') {
            $('#condition-fecha').show();
            $('#trigger-conditions').show();
        } else if (selectedType === 'campo_cambia') {
            $('#condition-campo').show();
            $('#trigger-conditions').show();
        }
    });

    // Add action button handler
    $('#add-action-btn').on('click', function() {
        addAction();
    });

    // Function to add action
    function addAction() {
        actionIndex++;

        const actionHtml = `
            <div class="action-item border rounded p-3 mb-3 bg-light position-relative" id="action-${actionIndex}">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 remove-action" data-action-id="${actionIndex}">
                    <i class="fas fa-times"></i>
                </button>

                <input type="hidden" name="acciones[${actionIndex}][orden]" value="${actionIndex}">

                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Tipo de acción
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select action-type-select select2"
                                    name="acciones[${actionIndex}][tipo_accion]"
                                    id="action-type-${actionIndex}"
                                    required>
                                <option value="">Seleccionar...</option>
                                <option value="enviar_email">Enviar email</option>
                                <option value="agregar_grupo">Agregar a grupo</option>
                                <option value="actualizar_campo">Actualizar campo</option>
                                <option value="esperar">Esperar</option>
                                <option value="webhook">Llamar webhook</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="action-config" id="action-config-${actionIndex}">
                    <!-- Dynamic fields will be inserted here -->
                </div>
            </div>
        `;

        $('#actions-container').append(actionHtml);

        // Add change handler for action type
        $(`#action-type-${actionIndex}`).on('change', function() {
            updateActionConfig(actionIndex, $(this).val());
        });

        // Add remove handler
        $(`.remove-action[data-action-id="${actionIndex}"]`).on('click', function() {
            $(`#action-${actionIndex}`).remove();
        });
    }

    // Function to update action configuration based on type
    function updateActionConfig(index, type) {
        const configDiv = $(`#action-config-${index}`);
        let configHtml = '';

        switch(type) {
            case 'enviar_email':
                configHtml = `
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Template de email <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="acciones[${index}][template]" required>
                                    <option value="">Seleccionar template...</option>
                                    <option value="1">Bienvenida</option>
                                    <option value="2">Confirmación</option>
                                    <option value="3">Newsletter</option>
                                    <option value="4">Promoción</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'agregar_grupo':
                configHtml = `
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Grupo <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="acciones[${index}][grupo]" required>
                                    <option value="">Seleccionar grupo...</option>
                                    <option value="1">Clientes VIP</option>
                                    <option value="2">Newsletter semanal</option>
                                    <option value="3">Promociones</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'actualizar_campo':
                configHtml = `
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Campo <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="acciones[${index}][campo]" required>
                                    <option value="">Seleccionar campo...</option>
                                    <option value="empresa">Empresa</option>
                                    <option value="cargo">Cargo</option>
                                    <option value="telefono">Teléfono</option>
                                    <option value="pais">País</option>
                                    <option value="estado">Estado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Valor <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       name="acciones[${index}][valor]"
                                       required
                                       placeholder="Nuevo valor">
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'esperar':
                configHtml = `
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Días de espera <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control"
                                       name="acciones[${index}][dias]"
                                       required
                                       min="1"
                                       value="1"
                                       placeholder="1">
                                <small class="form-text text-muted">Número de días a esperar antes de la siguiente acción</small>
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'webhook':
                configHtml = `
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">URL del webhook <span class="text-danger">*</span></label>
                                <input type="url"
                                       class="form-control"
                                       name="acciones[${index}][url]"
                                       required
                                       placeholder="https://ejemplo.com/webhook">
                                <small class="form-text text-muted">URL completa del endpoint webhook</small>
                            </div>
                        </div>
                    </div>
                `;
                break;

            default:
                configHtml = '';
        }

        configDiv.html(configHtml);
    }

    // Trigger change event if there's an old value (for validation errors)
    const oldTriggerType = $('#trigger-type').val();
    if (oldTriggerType) {
        $('#trigger-type').trigger('change');
    }
});
</script>
@endpush

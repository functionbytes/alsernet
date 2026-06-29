@extends('layouts.theme')

@section('title', 'Crear campo personalizado')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formCustomField" action="{{ route('mailrelay.settings.custom-fields.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Crear campo personalizado</h5>
                                <p class="mb-0 text-muted">Configure un nuevo campo personalizado para sus suscriptores.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs mb-4" id="customFieldTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active"
                                        id="basic-info-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#basic-info"
                                        type="button" role="tab">
                                    Información básica
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link"
                                        id="field-options-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#field-options"
                                        type="button" role="tab">
                                    Opciones del campo
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link"
                                        id="validation-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#validation"
                                        type="button" role="tab">
                                    Validación
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link"
                                        id="integration-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#integration"
                                        type="button" role="tab">
                                    Integración Mailrelay
                                </button>
                            </li>
                        </ul>

                        <!-- Tabs Content -->
                        <div class="tab-content" id="customFieldTabsContent">
                            <!-- Tab 1: Información básica -->
                            <div class="tab-pane fade show active"
                                 id="basic-info"
                                 role="tabpanel">
                                <div class="card-body">

                                    <div class="col-12">
                                        <h5 class="fw-bold mb-0">
                                            Información básica
                                        </h5>
                                        <p class="text-muted mb-4">Configure el nombre y tipo de campo personalizado.</p>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 col-md-6">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">
                                                    Nombre del campo <span class="text-danger">*</span>
                                                </label>
                                                <input type="text"
                                                       class="form-control @error('name') is-invalid @enderror"
                                                       id="name"
                                                       name="name"
                                                       value="{{ old('name') }}"
                                                       placeholder="Ej: Teléfono"
                                                       required>
                                                <small class="form-text text-muted">Nombre visible para los usuarios</small>
                                                @error('name')
                                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">
                                                    Clave interna <span class="text-danger">*</span>
                                                </label>
                                                <input type="text"
                                                       class="form-control @error('key') is-invalid @enderror"
                                                       id="key"
                                                       name="key"
                                                       value="{{ old('key') }}"
                                                       pattern="[a-z0-9_-]+"
                                                       placeholder="Ej: telefono"
                                                       required>
                                                <small class="form-text text-muted">Solo minúsculas, números, guiones y guiones bajos</small>
                                                @error('key')
                                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Descripción</label>
                                                <textarea class="form-control @error('description') is-invalid @enderror"
                                                          id="description"
                                                          name="description"
                                                          rows="3"
                                                          placeholder="Descripción del campo (opcional)">{{ old('description') }}</textarea>
                                                <small class="form-text text-muted">Información adicional sobre el campo</small>
                                                @error('description')
                                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">
                                                    Tipo de campo <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select @error('type') is-invalid @enderror select2"
                                                        id="type"
                                                        name="type"
                                                        required>
                                                    <option value="">Seleccionar tipo...</option>
                                                    <option value="text" {{ old('type') == 'text' ? 'selected' : '' }}>Texto (text)</option>
                                                    <option value="email" {{ old('type') == 'email' ? 'selected' : '' }}>Email (email)</option>
                                                    <option value="number" {{ old('type') == 'number' ? 'selected' : '' }}>Número (number)</option>
                                                    <option value="date" {{ old('type') == 'date' ? 'selected' : '' }}>Fecha (date)</option>
                                                    <option value="select" {{ old('type') == 'select' ? 'selected' : '' }}>Selección única (select)</option>
                                                    <option value="checkbox" {{ old('type') == 'checkbox' ? 'selected' : '' }}>Casilla de verificación (checkbox)</option>
                                                    <option value="radio" {{ old('type') == 'radio' ? 'selected' : '' }}>Selección múltiple (radio)</option>
                                                </select>
                                                @error('type')
                                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-3">
                                                <label class="control-label col-form-label">Orden de visualización</label>
                                                <input type="number"
                                                       class="form-control @error('order') is-invalid @enderror"
                                                       id="order"
                                                       name="order"
                                                       value="{{ old('order', 0) }}"
                                                       min="0"
                                                       placeholder="0">
                                                <small class="form-text text-muted">Menor número aparece primero</small>
                                                @error('order')
                                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- End Tab 1: Información básica -->

                            <!-- Tab 2: Opciones del campo -->
                            <div class="tab-pane fade"
                                 id="field-options"
                                 role="tabpanel">
                                <div class="card-body">

                                    <div class="col-12">
                                        <h5 class="fw-bold mb-0">
                                            Opciones del campo
                                        </h5>
                                        <p class="text-muted mb-4">Configure las opciones disponibles para campos de tipo select o radio.</p>
                                    </div>

                                    <div class="alert bg-light border-0 mb-4">
                                        <i class="fas fa-info-circle text-primary"></i>
                                        <strong>Información:</strong> Las opciones solo se aplican a campos de tipo "Selección única (select)" o "Selección múltiple (radio)".
                                    </div>

                                    <div id="field-options-container" style="display: none;">
                                        <div class="mb-3">
                                            <label class="control-label col-form-label">Opciones disponibles</label>
                                            <div id="options-list">
                                                <!-- Dynamic options will be added here -->
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary mt-2" id="addOptionBtn">
                                                <i class="fas fa-plus"></i> Agregar opción
                                            </button>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning" id="no-options-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Este tipo de campo no requiere opciones.
                                    </div>

                                </div>
                            </div>
                            <!-- End Tab 2: Opciones del campo -->

                            <!-- Tab 3: Validación -->
                            <div class="tab-pane fade"
                                 id="validation"
                                 role="tabpanel">
                                <div class="card-body">

                                    <div class="col-12">
                                        <h5 class="fw-bold mb-0">
                                            Reglas de validación
                                        </h5>
                                        <p class="text-muted mb-4">Configure las reglas de validación para este campo.</p>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="is_required">Campo requerido</label>
                                            <select class="form-select" name="is_required" id="is_required">
                                                <option value="1" {{ old('is_required') == 1 ? 'selected' : '' }}>Activado</option>
                                                <option value="0" {{ old('is_required', 0) == 0 ? 'selected' : '' }}>Desactivado</option>
                                            </select>
                                            <small class="text-muted">Los usuarios deben completar este campo</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="is_unique">Valor único</label>
                                            <select class="form-select" name="is_unique" id="is_unique">
                                                <option value="1" {{ old('is_unique') == 1 ? 'selected' : '' }}>Activado</option>
                                                <option value="0" {{ old('is_unique', 0) == 0 ? 'selected' : '' }}>Desactivado</option>
                                            </select>
                                            <small class="text-muted">No permitir valores duplicados</small>
                                        </div>
                                    </div>

                                    <div id="text-validation-options" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="control-label col-form-label">Longitud mínima</label>
                                                <input type="number"
                                                       class="form-control @error('min_length') is-invalid @enderror"
                                                       id="min_length"
                                                       name="min_length"
                                                       value="{{ old('min_length') }}"
                                                       min="0"
                                                       placeholder="Ej: 3">
                                                <small class="form-text text-muted">Número mínimo de caracteres</small>
                                                @error('min_length')
                                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="control-label col-form-label">Longitud máxima</label>
                                                <input type="number"
                                                       class="form-control @error('max_length') is-invalid @enderror"
                                                       id="max_length"
                                                       name="max_length"
                                                       value="{{ old('max_length') }}"
                                                       min="1"
                                                       placeholder="Ej: 100">
                                                <small class="form-text text-muted">Número máximo de caracteres</small>
                                                @error('max_length')
                                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="control-label col-form-label">Patrón regex (opcional)</label>
                                            <input type="text"
                                                   class="form-control @error('regex_pattern') is-invalid @enderror"
                                                   id="regex_pattern"
                                                   name="regex_pattern"
                                                   value="{{ old('regex_pattern') }}"
                                                   placeholder="Ej: ^[0-9]{9}$">
                                            <small class="form-text text-muted">Expresión regular para validación personalizada (ej: ^[0-9]{9}$ para teléfonos de 9 dígitos)</small>
                                            @error('regex_pattern')
                                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="control-label col-form-label">Mensaje de error personalizado</label>
                                            <input type="text"
                                                   class="form-control @error('regex_cue') is-invalid @enderror"
                                                   id="regex_cue"
                                                   name="regex_cue"
                                                   value="{{ old('regex_cue') }}"
                                                   placeholder="Ej: El teléfono debe tener 9 dígitos">
                                            <small class="form-text text-muted">Mensaje que verán los usuarios si la validación falla</small>
                                            @error('regex_cue')
                                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- End Tab 3: Validación -->

                            <!-- Tab 4: Integración Mailrelay -->
                            <div class="tab-pane fade"
                                 id="integration"
                                 role="tabpanel">
                                <div class="card-body">

                                    <div class="col-12">
                                        <h5 class="fw-bold mb-0">
                                            Configuración de sincronización
                                        </h5>
                                        <p class="text-muted mb-4">Configure la integración de este campo con Mailrelay.</p>
                                    </div>

                                    <div class="alert alert-info mb-4">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Sincronización con Mailrelay:</strong>
                                        La sincronización permite usar este campo en Mailrelay. Si activas la sincronización, este campo se enviará automáticamente cuando los usuarios se suscriban o actualicen sus datos.
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="sync_with_mailrelay">Sincronizar automáticamente</label>
                                        <select class="form-select" name="sync_with_mailrelay" id="sync_with_mailrelay">
                                            <option value="1" {{ old('sync_with_mailrelay') == 1 ? 'selected' : '' }}>Activado</option>
                                            <option value="0" {{ old('sync_with_mailrelay', 0) == 0 ? 'selected' : '' }}>Desactivado</option>
                                        </select>
                                        <small class="text-muted">Enviar este campo a Mailrelay automáticamente</small>
                                    </div>

                                    <div id="mailrelay-options" style="display: none;">
                                        <div class="mb-3">
                                            <label class="control-label col-form-label">ID de campo en Mailrelay</label>
                                            <input type="text"
                                                   class="form-control @error('mailrelay_field_id') is-invalid @enderror"
                                                   id="mailrelay_field_id"
                                                   name="mailrelay_field_id"
                                                   value="{{ old('mailrelay_field_id') }}"
                                                   placeholder="Dejar vacío para crear nuevo">
                                            <small class="form-text text-muted">
                                                Dejar vacío para crear nuevo
                                            </small>
                                            @error('mailrelay_field_id')
                                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- End Tab 4: Integración Mailrelay -->

                        </div>
                        <!-- End Tab Content -->

                    </div>

                    <!-- Footer -->
                    <div class="card-footer border-top">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            <i class="fas fa-save"></i> Guardar campo
                        </button>
                        <a href="{{ route('mailrelay.settings.custom-fields.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i> Cancelar
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
    let optionIndex = 0;

    // Auto-generate key from name
    $('#name').on('input', function() {
        const key = $(this).val()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '_')
            .replace(/-+/g, '_')
            .substring(0, 50);

        $('#key').val(key);
    });

    // Toggle options section based on field type
    $('#type').on('change', function() {
        const type = $(this).val();
        const optionsContainer = $('#field-options-container');
        const noOptionsWarning = $('#no-options-warning');
        const textValidation = $('#text-validation-options');

        if (type === 'select' || type === 'radio') {
            optionsContainer.show();
            noOptionsWarning.hide();

            // Add initial option if none exist
            if ($('#options-list .option-item').length === 0) {
                addOption();
            }
        } else {
            optionsContainer.hide();
            noOptionsWarning.show();
        }

        // Show/hide text validation options
        if (type === 'text' || type === 'email') {
            textValidation.show();
        } else {
            textValidation.hide();
        }
    });

    // Toggle Mailrelay options
    $('#sync_with_mailrelay').on('change', function() {
        const mailrelayOptions = $('#mailrelay-options');
        if ($(this).is(':checked')) {
            mailrelayOptions.show();
        } else {
            mailrelayOptions.hide();
        }
    });

    // Add option function
    $('#addOptionBtn').on('click', function() {
        addOption();
    });

    function addOption() {
        const index = optionIndex++;
        const optionHtml = `
            <div class="card mb-2 option-item">
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="options[${index}][value]" placeholder="Valor" required />
                        </div>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="options[${index}][label]" placeholder="Etiqueta" required />
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-primary w-100 remove-option-btn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#options-list').append(optionHtml);
    }

    // Remove option (delegated event)
    $(document).on('click', '.remove-option-btn', function() {
        const optionsList = $('#options-list');

        // Keep at least one option for select/radio fields
        if (optionsList.find('.option-item').length > 1) {
            $(this).closest('.option-item').remove();
        } else {
            alert('Debe haber al menos una opción disponible');
        }
    });

    // Initialize visibility on page load
    const typeSelect = $('#type');
    if (typeSelect.val()) {
        typeSelect.trigger('change');
    }

    const syncCheckbox = $('#sync_with_mailrelay');
    if (syncCheckbox.is(':checked')) {
        $('#mailrelay-options').show();
    } else {
        $('#mailrelay-options').hide();
    }

    // Fix for HTML5 validation: Manage 'required' attribute in tabs
    function updateRequiredInTabs() {
        // Remove required from all hidden tab inputs
        $('.tab-pane:not(.active) input, .tab-pane:not(.active) select, .tab-pane:not(.active) textarea').each(function() {
            if ($(this).attr('required') !== undefined) {
                $(this).removeAttr('required').attr('data-was-required', 'true');
            }
        });

        // Restore required to active tab inputs
        $('.tab-pane.active input[data-was-required="true"], .tab-pane.active select[data-was-required="true"], .tab-pane.active textarea[data-was-required="true"]').each(function() {
            $(this).attr('required', 'required').removeAttr('data-was-required');
        });
    }

    // Run on page load
    updateRequiredInTabs();

    // Run whenever a tab is shown
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
        updateRequiredInTabs();
    });
});
</script>
@endpush

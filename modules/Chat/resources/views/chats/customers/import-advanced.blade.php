@extends('layouts.theme')

@section('title', 'Importación Avanzada de Clientes')

@section('content')

    @include('core::components.card', ['title' => 'Importación Avanzada de Clientes', 'icon' => 'fa-file-import'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Importación avanzada de clientes</h5>
                        <p class="small mb-0 text-muted">Sube archivos CSV con mapeo de campos personalizado y opciones de configuración</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('chat.customers.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <button type="button" class="btn btn-secondary" onclick="location.reload()">
                            <i class="fas fa-sync me-1"></i> Reiniciar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="card-body border-bottom bg-light">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div id="step-1-indicator" class="d-flex align-items-center gap-2 step-indicator active">
                            <div class="step-number">
                                <span class="badge bg-primary rounded-circle">1</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">Cargar archivo</h6>
                                <small class="text-muted">Seleccionar CSV</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div id="step-2-indicator" class="d-flex align-items-center gap-2 step-indicator">
                            <div class="step-number">
                                <span class="badge bg-secondary rounded-circle">2</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">Mapear campos</h6>
                                <small class="text-muted">Asignar columnas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div id="step-3-indicator" class="d-flex align-items-center gap-2 step-indicator">
                            <div class="step-number">
                                <span class="badge bg-secondary rounded-circle">3</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">Previsualizar</h6>
                                <small class="text-muted">Verificar datos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div id="step-4-indicator" class="d-flex align-items-center gap-2 step-indicator">
                            <div class="step-number">
                                <span class="badge bg-secondary rounded-circle">4</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">Confirmar</h6>
                                <small class="text-muted">Ejecutar importación</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Upload CSV -->
            <div id="step-1" class="import-step">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-upload me-2"></i>Cargar archivo CSV
                    </h6>

                    <!-- Instructions -->
                    <div class="alert alert-info border-0 bg-info-subtle mb-4">
                        <h6 class="alert-heading fw-bold mb-2">Requisitos del archivo CSV</h6>
                        <ul class="mb-0 small">
                            <li>El archivo debe tener una <strong>fila de encabezados</strong> con los nombres de las columnas</li>
                            <li>Columnas requeridas: <strong>name</strong> (nombre) y <strong>email</strong> (correo electrónico)</li>
                            <li>Columnas opcionales: phone, country, language, state, city, timezone</li>
                            <li>Formato: CSV con separador de coma (,) o punto y coma (;)</li>
                            <li>Codificación: UTF-8 (recomendado)</li>
                            <li>Tamaño máximo: 10 MB</li>
                        </ul>
                    </div>

                    <!-- Example CSV Format -->
                    <div class="card mb-4 border">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-semibold">Ejemplo de formato CSV</h6>
                        </div>
                        <div class="card-body">
                            <pre class="bg-dark text-light p-3 rounded mb-0"><code>name,email,phone,country,language
Juan Pérez,juan.perez@ejemplo.com,+34600123456,ES,es
María García,maria.garcia@ejemplo.com,+34600654321,ES,es
John Smith,john.smith@example.com,+1234567890,US,en</code></pre>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <div class="mb-3">
                                <label for="csv_file" class="form-label fw-semibold">
                                    Seleccionar archivo CSV <span class="text-danger">*</span>
                                </label>
                                <input type="file"
                                       id="csv_file"
                                       name="csv_file"
                                       class="form-control"
                                       accept=".csv,.txt"
                                       required>
                                <small class="text-muted">Formatos aceptados: .csv, .txt (máx. 10 MB)</small>
                            </div>

                            <div class="mb-3">
                                <label for="csv_delimiter" class="form-label fw-semibold">
                                    Delimitador
                                </label>
                                <select id="csv_delimiter" name="csv_delimiter" class="form-control select2">
                                    <option value=",">Coma (,)</option>
                                    <option value=";">Punto y coma (;)</option>
                                    <option value="\t">Tabulador</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="csv_encoding" class="form-label fw-semibold">
                                    Codificación
                                </label>
                                <select id="csv_encoding" name="csv_encoding" class="form-control select2">
                                    <option value="UTF-8" selected>UTF-8</option>
                                    <option value="ISO-8859-1">ISO-8859-1 (Latin-1)</option>
                                    <option value="Windows-1252">Windows-1252</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" id="upload-btn" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Cargar y Analizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Field Mapping -->
            <div id="step-2" class="import-step" style="display: none;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-columns me-2"></i>Mapeo de campos
                    </h6>

                    <div class="alert alert-warning border-0 bg-warning-subtle mb-4">
                        <small class="fw-semibold">Importante:</small>
                        <small class="d-block">Asigna las columnas de tu CSV a los campos del sistema. Los campos marcados con (*) son obligatorios.</small>
                    </div>

                    <div id="field-mapping-container">
                        <!-- Dynamic field mapping will be inserted here -->
                    </div>

                    <div class="d-flex gap-2 justify-content-between mt-4">
                        <button type="button" id="back-to-step-1" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Anterior
                        </button>
                        <button type="button" id="proceed-to-preview" class="btn btn-primary">
                            Siguiente <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Preview Data -->
            <div id="step-3" class="import-step" style="display: none;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-eye me-2"></i>Previsualización de datos
                    </h6>

                    <div class="alert alert-info border-0 bg-info-subtle mb-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-info-circle mt-1"></i>
                            <div>
                                <small class="fw-semibold">Revisa los datos antes de importar</small>
                                <small class="d-block">Se mostrarán las primeras 10 filas. Verifica que los datos estén correctamente mapeados.</small>
                            </div>
                        </div>
                    </div>

                    <div id="preview-stats" class="row g-3 mb-4">
                        <!-- Stats will be inserted here -->
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="preview-table">
                            <thead class="table-light">
                                <!-- Headers will be inserted dynamically -->
                            </thead>
                            <tbody>
                                <!-- Preview data will be inserted here -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 justify-content-between mt-4">
                        <button type="button" id="back-to-step-2" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Anterior
                        </button>
                        <button type="button" id="proceed-to-config" class="btn btn-primary">
                            Siguiente <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Configuration and Import -->
            <div id="step-4" class="import-step" style="display: none;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-cog me-2"></i>Opciones de importación
                    </h6>

                    <form id="import-form">
                        <!-- Import Options -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Comportamiento de datos</h6>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="update_existing" name="update_existing" checked>
                                            <label class="form-check-label" for="update_existing">
                                                Actualizar clientes existentes
                                            </label>
                                            <small class="d-block text-muted">Si el email ya existe, actualiza sus datos</small>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates">
                                            <label class="form-check-label" for="skip_duplicates">
                                                Omitir duplicados
                                            </label>
                                            <small class="d-block text-muted">No importar emails duplicados en el archivo</small>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="validate_emails" name="validate_emails" checked>
                                            <label class="form-check-label" for="validate_emails">
                                                Validar emails
                                            </label>
                                            <small class="d-block text-muted">Verificar formato de correos electrónicos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Etiquetas y categorización</h6>

                                        <div class="mb-3">
                                            <label for="import_labels" class="form-label fw-semibold">
                                                Etiquetas a asignar
                                            </label>
                                            <select id="import_labels" name="labels[]" class="form-control select2 select2-tags" multiple>
                                                <option value="imported">Importado</option>
                                                <option value="new_customer">Nuevo Cliente</option>
                                                <option value="bulk_import">Importación Masiva</option>
                                            </select>
                                            <small class="text-muted">Puedes crear nuevas etiquetas escribiendo y presionando Enter</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="default_language" class="form-label fw-semibold">
                                                Idioma por defecto
                                            </label>
                                            <select id="default_language" name="default_language" class="form-control select2">
                                                <option value="">— No establecer —</option>
                                                <option value="es" selected>🇪🇸 Español</option>
                                                <option value="en">🇬🇧 English</option>
                                                <option value="fr">🇫🇷 Français</option>
                                                <option value="pt">🇵🇹 Português</option>
                                            </select>
                                            <small class="text-muted">Para clientes sin idioma especificado</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Import Summary -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Resumen de importación</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-file-csv fs-4 text-primary"></i>
                                            <div>
                                                <small class="text-muted d-block">Total de registros</small>
                                                <strong id="total-records">0</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-check-circle fs-4 text-success"></i>
                                            <div>
                                                <small class="text-muted d-block">Válidos</small>
                                                <strong id="valid-records">0</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-exclamation-triangle fs-4 text-warning"></i>
                                            <div>
                                                <small class="text-muted d-block">Con advertencias</small>
                                                <strong id="warning-records">0</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-times-circle fs-4 text-danger"></i>
                                            <div>
                                                <small class="text-muted d-block">Errores</small>
                                                <strong id="error-records">0</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-between">
                            <button type="button" id="back-to-step-3" class="btn btn-light">
                                <i class="fas fa-arrow-left me-1"></i> Anterior
                            </button>
                            <button type="button" id="execute-import" class="btn btn-success">
                                <i class="fas fa-check me-1"></i> Ejecutar Importación
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Progress Section (Hidden initially) -->
            <div id="import-progress" class="card-body border-top" style="display: none;">
                <h6 class="fw-bold mb-3">Progreso de importación</h6>
                <div class="progress mb-2" style="height: 25px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width: 0%">
                        <span id="progress-text">0%</span>
                    </div>
                </div>
                <small id="progress-status" class="text-muted">Iniciando importación...</small>
            </div>

        </div>

    </div>

@endsection

@push('styles')
<style>
.step-indicator {
    transition: all 0.3s ease;
    opacity: 0.5;
}

.step-indicator.active {
    opacity: 1;
}

.step-indicator.active .badge {
    background-color: #b10100 !important;
}

.step-indicator.completed .badge {
    background-color: #13C672 !important;
}

.import-step {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.field-mapping-row {
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 10px;
    background: #fafafa;
    transition: all 0.2s ease;
}

.field-mapping-row:hover {
    background: #f5f5f5;
    border-color: #b10100;
}

.csv-column-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

#preview-table th {
    white-space: nowrap;
    font-weight: 600;
}

#preview-table td {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let csvData = null;
    let csvHeaders = [];
    let fieldMapping = {};
    let currentStep = 1;

    // System field definitions
    const systemFields = [
        { key: 'name', label: 'Nombre', required: true, icon: 'fas fa-user' },
        { key: 'email', label: 'Email', required: true, icon: 'fas fa-envelope' },
        { key: 'phone', label: 'Teléfono', required: false, icon: 'fas fa-phone' },
        { key: 'country', label: 'País', required: false, icon: 'fas fa-globe' },
        { key: 'language', label: 'Idioma', required: false, icon: 'fas fa-language' },
        { key: 'state', label: 'Estado/Región', required: false, icon: 'fas fa-map' },
        { key: 'city', label: 'Ciudad', required: false, icon: 'fas fa-city' },
        { key: 'timezone', label: 'Zona Horaria', required: false, icon: 'fas fa-clock' }
    ];

    // Initialize Select2 for tags
    $('.select2-tags').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Seleccionar o crear etiquetas',
        allowClear: true
    });

    // Step navigation functions
    function showStep(step) {
        $('.import-step').hide();
        $('#step-' + step).show();

        $('.step-indicator').removeClass('active completed');
        for (let i = 1; i < step; i++) {
            $('#step-' + i + '-indicator').addClass('completed');
        }
        $('#step-' + step + '-indicator').addClass('active');

        currentStep = step;
    }

    // Upload and analyze CSV
    $('#upload-btn').on('click', function() {
        const fileInput = $('#csv_file')[0];

        if (!fileInput.files.length) {
            toastr.error('Por favor selecciona un archivo CSV', 'Error');
            return;
        }

        const file = fileInput.files[0];
        const delimiter = $('#csv_delimiter').val();
        const encoding = $('#csv_encoding').val();

        // Show loading
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Analizando...');

        // Read file
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const content = e.target.result;
                parseCSV(content, delimiter);

                $('#upload-btn').prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Cargar y Analizar');
                showStep(2);
                renderFieldMapping();
                toastr.success('Archivo cargado correctamente', 'Éxito');
            } catch (error) {
                toastr.error('Error al procesar el archivo: ' + error.message, 'Error');
                $('#upload-btn').prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Cargar y Analizar');
            }
        };
        reader.readAsText(file, encoding);
    });

    // Parse CSV content
    function parseCSV(content, delimiter) {
        const lines = content.trim().split('\n');
        csvHeaders = lines[0].split(delimiter).map(h => h.trim().replace(/['"]/g, ''));

        csvData = [];
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(delimiter).map(v => v.trim().replace(/['"]/g, ''));
            const row = {};
            csvHeaders.forEach((header, index) => {
                row[header] = values[index] || '';
            });
            csvData.push(row);
        }
    }

    // Render field mapping interface
    function renderFieldMapping() {
        const container = $('#field-mapping-container');
        container.empty();

        systemFields.forEach(field => {
            const autoMapped = csvHeaders.find(h =>
                h.toLowerCase() === field.key.toLowerCase() ||
                h.toLowerCase() === field.label.toLowerCase()
            );

            if (autoMapped) {
                fieldMapping[field.key] = autoMapped;
            }

            const html = `
                <div class="field-mapping-row">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="fw-semibold mb-0">
                                <i class="${field.icon} me-2 text-primary"></i>
                                ${field.label}
                                ${field.required ? '<span class="text-danger">*</span>' : ''}
                            </label>
                            <small class="text-muted d-block">Campo del sistema</small>
                        </div>
                        <div class="col-md-1 text-center">
                            <i class="fas fa-arrow-right text-muted"></i>
                        </div>
                        <div class="col-md-7">
                            <select class="form-control select2 field-mapping" data-field="${field.key}" ${field.required ? 'required' : ''}>
                                <option value="">— No mapear —</option>
                                ${csvHeaders.map(header =>
                                    `<option value="${header}" ${autoMapped === header ? 'selected' : ''}>
                                        <span class="csv-column-badge">${header}</span>
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
        });

        // Update field mapping on change
        $('.field-mapping').on('change', function() {
            const field = $(this).data('field');
            const value = $(this).val();
            if (value) {
                fieldMapping[field] = value;
            } else {
                delete fieldMapping[field];
            }
        });
    }

    // Proceed to preview
    $('#proceed-to-preview').on('click', function() {
        // Validate required fields
        const requiredFields = systemFields.filter(f => f.required);
        const missingFields = requiredFields.filter(f => !fieldMapping[f.key]);

        if (missingFields.length > 0) {
            toastr.error('Por favor mapea todos los campos obligatorios: ' +
                missingFields.map(f => f.label).join(', '), 'Error');
            return;
        }

        renderPreview();
        showStep(3);
    });

    // Render preview
    function renderPreview() {
        const previewLimit = 10;
        const previewData = csvData.slice(0, previewLimit);

        // Stats
        const statsHtml = `
            <div class="col-md-3">
                <div class="card bg-light-primary">
                    <div class="card-body">
                        <h6 class="text-primary">Total de registros</h6>
                        <h4 class="mb-0">${csvData.length}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light-info">
                    <div class="card-body">
                        <h6 class="text-info">Campos mapeados</h6>
                        <h4 class="mb-0">${Object.keys(fieldMapping).length}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light-success">
                    <div class="card-body">
                        <h6 class="text-success">Vista previa</h6>
                        <h4 class="mb-0">${previewData.length} filas</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light-warning">
                    <div class="card-body">
                        <h6 class="text-warning">Columnas CSV</h6>
                        <h4 class="mb-0">${csvHeaders.length}</h4>
                    </div>
                </div>
            </div>
        `;
        $('#preview-stats').html(statsHtml);

        // Table headers
        const mappedFields = Object.keys(fieldMapping);
        const theadHtml = `
            <tr>
                <th class="text-center">#</th>
                ${mappedFields.map(field => {
                    const fieldDef = systemFields.find(f => f.key === field);
                    return `<th><i class="${fieldDef.icon} me-1"></i>${fieldDef.label}</th>`;
                }).join('')}
            </tr>
        `;
        $('#preview-table thead').html(theadHtml);

        // Table body
        const tbodyHtml = previewData.map((row, index) => {
            return `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    ${mappedFields.map(field => {
                        const csvColumn = fieldMapping[field];
                        const value = row[csvColumn] || '-';
                        return `<td title="${value}">${value}</td>`;
                    }).join('')}
                </tr>
            `;
        }).join('');
        $('#preview-table tbody').html(tbodyHtml);

        // Update summary stats
        $('#total-records').text(csvData.length);
        $('#valid-records').text(csvData.length);
        $('#warning-records').text(0);
        $('#error-records').text(0);
    }

    // Execute import
    let importInterval = null;

    $('#execute-import').on('click', function() {
        const formData = new FormData();

        // Add CSV file
        formData.append('file', $('#csv_file')[0].files[0]);

        // Add field mapping
        formData.append('field_mapping', JSON.stringify(fieldMapping));

        // Add options
        formData.append('update_existing', $('#update_existing').is(':checked') ? 1 : 0);
        formData.append('skip_duplicates', $('#skip_duplicates').is(':checked') ? 1 : 0);
        formData.append('validate_emails', $('#validate_emails').is(':checked') ? 1 : 0);
        formData.append('labels', JSON.stringify($('#import_labels').val() || []));
        formData.append('default_language', $('#default_language').val());
        formData.append('delimiter', $('#csv_delimiter').val());
        formData.append('encoding', $('#csv_encoding').val());

        // Show progress
        $('#import-progress').show();
        $('#execute-import').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando...');

        // Simulate progress (replace with actual AJAX call)
        let progress = 0;
        importInterval = setInterval(function() {
            progress += 10;
            $('#progress-bar').css('width', progress + '%');
            $('#progress-text').text(progress + '%');

            if (progress >= 100) {
                clearInterval(importInterval);
                importInterval = null;
                executeActualImport(formData);
            }
        }, 300);
    });

    function executeActualImport(formData) {
        axios.post('{{ route("chat.customers.import.advanced.process") }}', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(function(response) {
            if (importInterval) {
                clearInterval(importInterval);
                importInterval = null;
            }

            $('#progress-status').html('<i class="fas fa-check-circle text-success me-1"></i> Importación completada');
            toastr.success(response.data.message || 'Importación completada exitosamente', 'Éxito');

            setTimeout(function() {
                window.location.href = '{{ route("chat.customers.index") }}';
            }, 2000);
        })
        .catch(function(error) {
            if (importInterval) {
                clearInterval(importInterval);
                importInterval = null;
            }

            $('#progress-bar').removeClass('progress-bar-animated').addClass('bg-danger');
            $('#progress-status').html('<i class="fas fa-times-circle text-danger me-1"></i> Error en la importación');
            $('#execute-import').prop('disabled', false).html('<i class="fas fa-check me-1"></i> Reintentar Importación');

            const errorMsg = error.response?.data?.message || 'Error al procesar la importación';
            toastr.error(errorMsg, 'Error');
        });
    }

    // Clear interval on page unload
    $(window).on('beforeunload', function() {
        if (importInterval) {
            clearInterval(importInterval);
            importInterval = null;
        }
    });

    // Navigation buttons
    $('#back-to-step-1').on('click', () => showStep(1));
    $('#back-to-step-2').on('click', () => showStep(2));
    $('#back-to-step-3').on('click', () => showStep(3));
    $('#proceed-to-config').on('click', () => showStep(4));

    // Toastr notifications
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

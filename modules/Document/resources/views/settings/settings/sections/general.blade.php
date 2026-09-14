{{-- General Settings Section --}}
<div class="general-settings">

    <!-- Section Header -->
    <div class="mb-4">
        <h5 class="fw-bold mb-2">
            <i class="ti ti-settings text-primary me-2"></i>
            Configuracion General
        </h5>
        <p class="text-muted mb-0">Configura el comportamiento basico del sistema de documentos, limites de archivos y opciones de expiracion.</p>
    </div>

    <!-- File Handling Section -->
    <div class="setting-section mb-4">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-file me-1"></i> Manejo de Archivos
        </h6>

        <div class="row g-3">
            <!-- Max File Size -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <label class="form-label fw-semibold mb-1">
                        Tamano Maximo de Archivo
                        <span class="text-danger">*</span>
                    </label>
                    <p class="text-muted small mb-2">Tamano maximo permitido para cada documento cargado.</p>
                    <div class="input-group">
                        <input type="number"
                               class="form-control"
                               name="max_file_size"
                               value="{{ $settings['file_handling']['max_file_size'] ?? 10 }}"
                               min="1"
                               max="100"
                               required>
                        <span class="input-group-text">MB</span>
                    </div>
                    <small class="text-muted">Recomendado: 10-25 MB</small>
                </div>
            </div>

            <!-- Total Max Size -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <label class="form-label fw-semibold mb-1">
                        Tamano Maximo Total
                    </label>
                    <p class="text-muted small mb-2">Tamano total maximo de todos los documentos por solicitud.</p>
                    <div class="input-group">
                        <input type="number"
                               class="form-control"
                               name="max_total_size"
                               value="{{ $settings['file_handling']['max_total_size'] ?? 50 }}"
                               min="10"
                               max="500">
                        <span class="input-group-text">MB</span>
                    </div>
                    <small class="text-muted">Dejar en 0 para sin limite</small>
                </div>
            </div>

            <!-- Allowed Extensions -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <label class="form-label fw-semibold mb-1">
                        Extensiones Permitidas
                        <span class="text-danger">*</span>
                    </label>
                    <p class="text-muted small mb-2">Tipos de archivo permitidos (separados por coma).</p>
                    <input type="text"
                           class="form-control"
                           name="allowed_extensions"
                           value="{{ $settings['file_handling']['allowed_extensions'] ?? 'pdf,jpg,jpeg,png,doc,docx' }}"
                           placeholder="pdf,jpg,jpeg,png,doc,docx"
                           required>
                    <small class="text-muted">Ejemplo: pdf,jpg,jpeg,png,doc,docx</small>
                </div>
            </div>

            <!-- Max Files Per Request -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <label class="form-label fw-semibold mb-1">
                        Archivos Maximos por Solicitud
                    </label>
                    <p class="text-muted small mb-2">Cantidad maxima de archivos que se pueden cargar por solicitud.</p>
                    <input type="number"
                           class="form-control"
                           name="max_files_per_request"
                           value="{{ $settings['file_handling']['max_files_per_request'] ?? 10 }}"
                           min="1"
                           max="50">
                    <small class="text-muted">Recomendado: 5-15 archivos</small>
                </div>
            </div>

            <!-- Auto Compress Images -->
            <div class="col-12">
                <div class="p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-photo me-1 text-muted"></i>
                                Comprimir Imagenes Automaticamente
                            </label>
                            <p class="text-muted small mb-0">Reduce automaticamente el tamano de las imagenes para optimizar el almacenamiento.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="auto_compress_images"
                                   id="autoCompressImages"
                                   value="1"
                                   {{ ($settings['file_handling']['auto_compress_images'] ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="mt-3 compression-options {{ ($settings['file_handling']['auto_compress_images'] ?? false) ? '' : 'd-none' }}">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Calidad de compresion (%)</label>
                                <input type="number"
                                       class="form-control form-control-sm"
                                       name="compression_quality"
                                       value="{{ $settings['file_handling']['compression_quality'] ?? 85 }}"
                                       min="50"
                                       max="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Ancho maximo (px)</label>
                                <input type="number"
                                       class="form-control form-control-sm"
                                       name="max_image_width"
                                       value="{{ $settings['file_handling']['max_image_width'] ?? 2048 }}"
                                       min="800"
                                       max="4096">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- Document Expiration Section -->
    <div class="setting-section mb-4">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-calendar-event me-1"></i> Expiracion de Documentos
        </h6>

        <div class="row g-3">
            <!-- Enable Expiration -->
            <div class="col-12">
                <div class="p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-clock-off me-1 text-muted"></i>
                                Habilitar Expiracion de Documentos
                            </label>
                            <p class="text-muted small mb-0">Permite marcar documentos con fecha de vencimiento y notificar antes de que expiren.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="enable_expiration"
                                   id="enableExpiration"
                                   value="1"
                                   {{ ($settings['expiration']['enabled'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expiration Warning Days -->
            <div class="col-md-6 expiration-options {{ ($settings['expiration']['enabled'] ?? true) ? '' : 'd-none' }}">
                <div class="p-3 bg-light rounded h-100">
                    <label class="form-label fw-semibold mb-1">
                        Dias de Aviso Previo
                    </label>
                    <p class="text-muted small mb-2">Dias antes de la expiracion para enviar aviso.</p>
                    <input type="number"
                           class="form-control"
                           name="expiration_warning_days"
                           value="{{ $settings['expiration']['warning_days'] ?? 30 }}"
                           min="1"
                           max="90">
                    <small class="text-muted">Recomendado: 15-30 dias</small>
                </div>
            </div>

            <!-- Auto Archive Expired -->
            <div class="col-md-6 expiration-options {{ ($settings['expiration']['enabled'] ?? true) ? '' : 'd-none' }}">
                <div class="p-3 bg-light rounded h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                Archivar Documentos Expirados
                            </label>
                            <p class="text-muted small mb-0">Mover automaticamente documentos expirados a archivo.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="auto_archive_expired"
                                   id="autoArchiveExpired"
                                   value="1"
                                   {{ ($settings['expiration']['auto_archive'] ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small">Dias despues de expiracion</label>
                        <input type="number"
                               class="form-control form-control-sm"
                               name="archive_after_days"
                               value="{{ $settings['expiration']['archive_after_days'] ?? 7 }}"
                               min="1"
                               max="30">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- Support Request Section -->
    <div class="setting-section mb-4">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-help me-1"></i> Solicitudes de Soporte
        </h6>

        <div class="row g-3">
            <!-- Allow Re-upload -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-refresh me-1 text-muted"></i>
                                Permitir Recarga de Documentos
                            </label>
                            <p class="text-muted small mb-0">Permite a los clientes recargar documentos rechazados.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="allow_reupload"
                                   id="allowReupload"
                                   value="1"
                                   {{ ($settings['support']['allow_reupload'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Max Reupload Attempts -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100">
                    <label class="form-label fw-semibold mb-1">
                        Intentos Maximos de Recarga
                    </label>
                    <p class="text-muted small mb-2">Cantidad maxima de veces que se puede recargar un documento.</p>
                    <input type="number"
                           class="form-control"
                           name="max_reupload_attempts"
                           value="{{ $settings['support']['max_reupload_attempts'] ?? 3 }}"
                           min="1"
                           max="10">
                    <small class="text-muted">0 = Sin limite</small>
                </div>
            </div>

            <!-- Require Rejection Reason -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-message-circle me-1 text-muted"></i>
                                Requerir Motivo de Rechazo
                            </label>
                            <p class="text-muted small mb-0">Obliga a especificar el motivo al rechazar documentos.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="require_rejection_reason"
                                   id="requireRejectionReason"
                                   value="1"
                                   {{ ($settings['support']['require_rejection_reason'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auto Close Completed -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-check me-1 text-muted"></i>
                                Cerrar Automaticamente al Completar
                            </label>
                            <p class="text-muted small mb-0">Cierra la solicitud cuando todos los documentos estan aprobados.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="auto_close_completed"
                                   id="autoCloseCompleted"
                                   value="1"
                                   {{ ($settings['support']['auto_close_completed'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- Display Options Section -->
    <div class="setting-section mb-4">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted small">
            <i class="ti ti-layout me-1"></i> Opciones de Visualizacion
        </h6>

        <div class="row g-3">
            <!-- Show Progress Bar -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-progress me-1 text-muted"></i>
                                Mostrar Barra de Progreso
                            </label>
                            <p class="text-muted small mb-0">Muestra el progreso de documentos completados al cliente.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="show_progress_bar"
                                   id="showProgressBar"
                                   value="1"
                                   {{ ($settings['display']['show_progress_bar'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Show Document History -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold mb-1">
                                <i class="ti ti-history me-1 text-muted"></i>
                                Mostrar Historial al Cliente
                            </label>
                            <p class="text-muted small mb-0">Permite al cliente ver el historial de cambios de documentos.</p>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="show_document_history"
                                   id="showDocumentHistory"
                                   value="1"
                                   {{ ($settings['display']['show_document_history'] ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Default List View -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100">
                    <label class="form-label fw-semibold mb-1">
                        Vista Por defecto
                    </label>
                    <p class="text-muted small mb-2">Tipo de vista predeterminada para la lista de documentos.</p>
                    <select class="form-select select2" name="default_view">
                        <option value="grid" {{ ($settings['display']['default_view'] ?? 'grid') == 'grid' ? 'selected' : '' }}>
                            Cuadricula
                        </option>
                        <option value="list" {{ ($settings['display']['default_view'] ?? 'grid') == 'list' ? 'selected' : '' }}>
                            Lista
                        </option>
                        <option value="table" {{ ($settings['display']['default_view'] ?? 'grid') == 'table' ? 'selected' : '' }}>
                            Tabla
                        </option>
                    </select>
                </div>
            </div>

            <!-- Items Per Page -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100">
                    <label class="form-label fw-semibold mb-1">
                        Elementos por Pagina
                    </label>
                    <p class="text-muted small mb-2">Cantidad de documentos a mostrar por pagina.</p>
                    <select class="form-select select2" name="items_per_page">
                        <option value="10" {{ ($settings['display']['items_per_page'] ?? 20) == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ ($settings['display']['items_per_page'] ?? 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ ($settings['display']['items_per_page'] ?? 20) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($settings['display']['items_per_page'] ?? 20) == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- Advanced Options Section -->
    <div class="setting-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0 text-uppercase text-muted small">
                <i class="ti ti-adjustments me-1"></i> Opciones Avanzadas
            </h6>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
                <i class="ti ti-chevron-down me-1"></i> Mostrar
            </button>
        </div>

        <div class="collapse" id="advancedOptions">
            <div class="row g-3">
                <!-- Enable Watermark -->
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <label class="form-label fw-semibold mb-1">
                                    <i class="ti ti-droplet me-1 text-muted"></i>
                                    Agregar Marca de Agua
                                </label>
                                <p class="text-muted small mb-0">Agrega marca de agua a los documentos descargados.</p>
                            </div>
                            <div class="form-check form-switch ms-3">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="enable_watermark"
                                       id="enableWatermark"
                                       value="1"
                                       {{ ($settings['advanced']['enable_watermark'] ?? false) ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="mt-2 watermark-options {{ ($settings['advanced']['enable_watermark'] ?? false) ? '' : 'd-none' }}">
                            <input type="text"
                                   class="form-control form-control-sm"
                                   name="watermark_text"
                                   value="{{ $settings['advanced']['watermark_text'] ?? 'CONFIDENCIAL' }}"
                                   placeholder="Texto de marca de agua">
                        </div>
                    </div>
                </div>

                <!-- Enable Audit Log -->
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <label class="form-label fw-semibold mb-1">
                                    <i class="ti ti-list-details me-1 text-muted"></i>
                                    Registro de Auditoria
                                </label>
                                <p class="text-muted small mb-0">Registra todas las acciones sobre documentos.</p>
                            </div>
                            <div class="form-check form-switch ms-3">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="enable_audit_log"
                                       id="enableAuditLog"
                                       value="1"
                                       {{ ($settings['advanced']['enable_audit_log'] ?? true) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Retention Period -->
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded h-100">
                        <label class="form-label fw-semibold mb-1">
                            Periodo de Retencion
                        </label>
                        <p class="text-muted small mb-2">Tiempo que se conservan los documentos archivados.</p>
                        <div class="input-group">
                            <input type="number"
                                   class="form-control"
                                   name="retention_period"
                                   value="{{ $settings['advanced']['retention_period'] ?? 365 }}"
                                   min="30"
                                   max="3650">
                            <span class="input-group-text">dias</span>
                        </div>
                        <small class="text-muted">0 = Conservar indefinidamente</small>
                    </div>
                </div>

                <!-- Storage Path -->
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded h-100">
                        <label class="form-label fw-semibold mb-1">
                            Ruta de Almacenamiento
                        </label>
                        <p class="text-muted small mb-2">Directorio donde se guardan los documentos.</p>
                        <input type="text"
                               class="form-control"
                               name="storage_path"
                               value="{{ $settings['advanced']['storage_path'] ?? 'documents' }}"
                               placeholder="documents">
                        <small class="text-muted">Relativo a storage/app/</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle compression options
    $('#autoCompressImages').on('change', function() {
        $('.compression-options').toggleClass('d-none', !this.checked);
    });

    // Toggle expiration options
    $('#enableExpiration').on('change', function() {
        $('.expiration-options').toggleClass('d-none', !this.checked);
    });

    // Toggle watermark options
    $('#enableWatermark').on('change', function() {
        $('.watermark-options').toggleClass('d-none', !this.checked);
    });

    // Toggle advanced options button text
    $('#advancedOptions').on('show.bs.collapse', function() {
        $(this).prev().find('button').html('<i class="ti ti-chevron-up me-1"></i> Ocultar');
    }).on('hide.bs.collapse', function() {
        $(this).prev().find('button').html('<i class="ti ti-chevron-down me-1"></i> Mostrar');
    });
});
</script>
@endpush

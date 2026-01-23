<div id="uploadSectionContainer">

    <!-- Upload Section - Ocultar si ya está gestionado y tiene documentos, o si todos están cargados -->
    @if(!($document->proccess == 1 && $document->media->count() > 0) && !$allUploaded)
        <div class="card mb-3" id="documentsUploadCard">
            <div class="card-header p-3 bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Carga de documentos</h5>
                        <p class="small mb-0 text-muted">Sube los documentos requeridos</p>
                    </div>
                    <div>
                        @php
                            $totalDocs = count($requiredDocuments);
                            $uploadedCount = count($uploadedDocs);
                        @endphp
                        <span id="documentCounter">
                        {{ $uploadedCount }}/{{ $totalDocs }} cargados
                    </span>
                    </div>
                </div>
            </div>
            <div class="card-body">

                <!-- Formulario de carga múltiple -->
                <form id="adminUploadForm" enctype="multipart/form-data">
                    @foreach($requiredDocuments as $docKey => $docLabel)
                        <div class="mb-3 document-upload-item" data-doc-type="{{ $docKey }}">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label mb-0 fw-semibold">
                                    {{ $docLabel }}
                                </label>
                                @if(!isset($uploadedDocs[$docKey]))
                                    <span class="badge bg-danger-subtle text-white">
                                    Pendiente
                                </span>
                                @endif
                            </div>

                            @if(isset($uploadedDocs[$docKey]))
                                <!-- Documento ya cargado -->
                                <div class="uploaded-doc-info p-3 bg-light-secondary border rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <div>
                                                <p class="mb-0 fw-semibold">{{ $uploadedDocs[$docKey]->file_name }}</p>
                                                <small class="text-muted">{{ formatBytes($uploadedDocs[$docKey]->size) }} • {{ $uploadedDocs[$docKey]->created_at->format('d/m/Y H:i') }}</small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ parse_url($uploadedDocs[$docKey]->getUrl(), PHP_URL_PATH) }}" class="btn btn-sm btn-primary" target="_blank" title="Descargar">
                                                <i class="fa fa-download"></i>
                                            </a>
                                            @if(auth()->user()->canDocument('delete-documents'))
                                                <button type="button" class="btn btn-sm btn-danger btn-delete-single-doc" data-media-id="{{ $uploadedDocs[$docKey]->id }}" data-doc-type="{{ $docKey }}" title="Eliminar">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-danger" disabled title="No tienes permiso para eliminar documentos">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Input para cargar documento -->
                                <input
                                    type="file"
                                    class="form-control document-file-input"
                                    name="documents[{{ $docKey }}]"
                                    data-doc-type="{{ $docKey }}"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @if(!auth()->user()->canDocument('upload-documents')) disabled @endif
                                >
                                <small class="text-muted d-block mt-1">
                                    <i class="fa fa-info-circle"></i> PDF, JPG, PNG, DOC (máximo 10MB)
                                </small>
                            @endif
                        </div>
                    @endforeach

                    <div id="uploadProgress" style="display: none;" class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="fw-semibold">Cargando documentos...</small>
                            <small class="text-muted" id="uploadStatus">0%</small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" id="uploadProgressBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-upload-document" @if($allUploaded || !auth()->user()->canDocument('upload-documents')) disabled @endif>
                        @if($allUploaded)
                            Documentos Completos
                        @else
                            Cargar documentos
                        @endif
                    </button>
                </form>

                @if($allUploaded && $document->media->count() > 0)
                    <div class="border-top mt-3 pt-3">
                        <a href="{{ route('documents.summary', $document->uid) }}" target="_blank" class="btn btn-outline-primary w-100">
                            <i class="fa fa-file-archive"></i> Ver todos los documentos comprimidos
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Mensaje de éxito cuando todos los documentos están cargados -->
    @if($allUploaded)
        <div class="card mb-3">
            <div class="card-header p-3 bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold text-success">Documentos cargados</h5>
                        <p class="small mb-0 text-muted">Todos los documentos requeridos han sido recibidos</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Lista de documentos con opciones -->
                @foreach($uploadedDocs as $docType => $media)
                    <div class="mb-3 document-upload-item" data-doc-type="{{ $docType }}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0 fw-semibold">
                                {{ $media->file_name }}
                            </label>
                        </div>

                        <!-- Documento ya cargado -->
                        <div class="uploaded-doc-info p-3 bg-light-secondary border rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <div>
                                        <p class="mb-0 fw-semibold">{{ $media->file_name }}</p>
                                        <small class="text-muted">{{ formatBytes($media->size) }} • {{ $media->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ parse_url($media->getUrl(), PHP_URL_PATH) }}" class="btn btn-sm btn-primary" target="_blank" title="Descargar">
                                        <i class="fa fa-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete-single-doc" data-media-id="{{ $media->id }}" data-doc-type="{{ $docType }}" title="Eliminar">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($document->media->count() > 1 && $document->confirmed_at != null)
                    <div class="border-top mt-3 pt-3">
                        <a href="{{ route('documents.summary', $document->uid) }}" target="_blank" class="btn btn-primary w-100">
                            Ver todos los documentos
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- Modal: Confirmar carga con documentos faltantes --}}
<div class="modal fade" id="confirmMissingDocumentsModal" tabindex="-1" role="dialog" aria-labelledby="confirmMissingDocumentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmMissingDocumentsModalLabel">
                    Documentos faltantes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    <strong>Aún faltarán por cargar los siguientes documentos:</strong>
                </p>
                <ul id="missingDocsList" class="list-unstyled ms-3">
                    <!-- Se rellena dinámicamente con JavaScript -->
                </ul>
                <p class="mt-3 mb-0 text-muted">
                    <small>¿Deseas continuar con la carga de todas formas?</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 mb-1" id="confirmUploadBtn">
                    Sí, continuar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmar eliminación de documento --}}
<div class="modal fade" id="confirmDeleteDocumentModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteDocumentModalLabel">
                    Confirmar eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    <strong>¿Estás seguro de que deseas eliminar este documento?</strong>
                </p>
                <p class="text-muted small mt-2 mb-0">
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 mb-1" id="confirmDeleteBtn">
                    Eliminar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Upload Section Scripts --}}
@push('scripts')
    <script>
        // Global document UID and type for use in modals and external scripts
        window.documentUid = '{{ $document->uid }}';
        window.documentType = '{{ $document->documentType?->slug ?? "general" }}';

        $(document).ready(function() {
            const documentUid = window.documentUid;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Utility function to format bytes
            function formatBytes(bytes, decimals = 2) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            }

            // Utility function to escape HTML
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }

            // ===== Cargar Documentos Múltiples =====
            $(document).on('submit', '#adminUploadForm', function(e) {
                e.preventDefault();

                const $form = $(this);
                const formData = new FormData(this);
                const $submitBtn = $form.find('button[type="submit"]');
                const $progressBar = $('#uploadProgress');
                const $uploadStatus = $('#uploadStatus');

                // Verificar que al menos un archivo esté seleccionado
                let hasFiles = false;
                const uploadedByType = {};
                const filesBeingUploaded = [];

                $('.document-file-input').each(function() {
                    const $input = $(this);
                    const docType = $input.data('doc-type');

                    if ($input[0].files && $input[0].files.length > 0) {
                        hasFiles = true;
                        uploadedByType[docType] = true;

                        const $item = $input.closest('.document-upload-item');
                        let docLabel = $item.find('.form-label').text().trim();

                        // Validar que se encontró el label
                        if (!docLabel) {
                            docLabel = `Documento (${docType})`;
                        }

                        filesBeingUploaded.push({
                            type: docType,
                            label: docLabel
                        });
                    }
                });

                if (!hasFiles) {
                    toastr.warning('Por favor selecciona al menos un documento', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                // Validar que lo que se está cargando existe en los requeridos
                let allFilesValid = true;
                filesBeingUploaded.forEach(file => {
                    const $item = $(`.document-upload-item[data-doc-type="${file.type}"]`);
                    if ($item.length === 0) {
                        toastr.error(`Documento tipo "${file.label}" no es válido`, 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                        allFilesValid = false;
                    }
                });

                if (!allFilesValid) {
                    return;
                }

                // Advertir si aún hay documentos faltantes después de esta carga
                const missingAfterUpload = [];
                $('.document-upload-item').each(function() {
                    const docType = $(this).data('doc-type');
                    const isAlreadyUploaded = $(this).find('.uploaded-doc-info').length > 0;

                    // Verificar si hay archivo seleccionado en el input
                    const $fileInput = $(this).find('input.document-file-input');
                    const hasFileSelected = $fileInput.length > 0 && $fileInput[0].files && $fileInput[0].files.length > 0;

                    // También verificar en uploadedByType por compatibilidad
                    const isBeingUploaded = uploadedByType[docType] || hasFileSelected;

                    if (!isAlreadyUploaded && !isBeingUploaded) {
                        const docLabel = $(this).find('.form-label').text().trim();
                        missingAfterUpload.push({
                            type: docType,
                            label: docLabel
                        });
                    }
                });

                if (missingAfterUpload.length > 0) {
                    // Mostrar modal con advertencia clara
                    const missingHtml = missingAfterUpload.map(doc => `<li><strong>${doc.label}</strong></li>`).join('');
                    const uploadingHtml = filesBeingUploaded.map(file => `<li class="text-success"><strong>${file.label}</strong></li>`).join('');

                    let modalBody = `
                        <div class="mb-3">
                            <h6 class="text-success mb-2">Estás cargando:</h6>
                            <ul class="list-unstyled ms-3">
                                ${uploadingHtml}
                            </ul>
                        </div>
                        <div>
                            <h6 class="text-success mb-2">Aún faltarán después de esta carga:</h6>
                            <ul class="list-unstyled ms-3">
                                ${missingHtml}
                            </ul>
                        </div>
                    `;

                    $('#missingDocsList').html(modalBody);
                    $('#confirmMissingDocumentsModal').modal('show');

                    // Guardar formData en variable global para usarla cuando confirme
                    window.pendingFormData = formData;
                    window.pendingUpload = true;
                    return;
                }

                // Si no hay documentos faltantes, proceder directamente
                performUpload($submitBtn, formData, $progressBar, $uploadStatus);
            });

            // ===== Confirmar carga con documentos faltantes =====
            $(document).on('click', '#confirmUploadBtn', function() {
                $('#confirmMissingDocumentsModal').modal('hide');
                performUpload(
                    $('#adminUploadForm').find('button[type="submit"]'),
                    window.pendingFormData,
                    $('#uploadProgress'),
                    $('#uploadStatus')
                );
            });

            // ===== Eliminar Documento Individual =====
            $(document).on('click', '.btn-delete-single-doc', function(e) {
                e.preventDefault();

                const $btn = $(this);
                const mediaId = $btn.data('media-id');
                const docType = $btn.data('doc-type');

                // Guardar datos en window global para usar en el modal
                window.pendingDelete = {
                    btn: $btn,
                    mediaId: mediaId,
                    docType: docType
                };

                // Mostrar modal
                $('#confirmDeleteDocumentModal').modal('show');
            });

            // ===== Confirmar eliminación de documento =====
            $(document).on('click', '#confirmDeleteBtn', function() {
                if (!window.pendingDelete) {
                    return;
                }

                const { btn: $btn, mediaId, docType } = window.pendingDelete;

                $btn.prop('disabled', true);
                $btn.html('<i class="fa fa-spinner fa-spin"></i>');

                $('#confirmDeleteDocumentModal').modal('hide');

                $.ajax({
                    url: "{{ route('api.documents.delete-attachment', ['uid' => 'UID_PLACEHOLDER', 'mediaId' => 'MEDIA_PLACEHOLDER']) }}"
                        .replace('UID_PLACEHOLDER', documentUid)
                        .replace('MEDIA_PLACEHOLDER', mediaId),
                    type: 'DELETE',
                    success: function(response) {
                        console.log('✅ DELETE success:', response);
                        toastr.success('Documento eliminado correctamente', 'Éxito', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });

                        // Actualizar estado sin recargar la página
                        if (typeof reloadDocumentsSection === 'function') {
                            console.log('🔄 Llamando a reloadDocumentsSection');
                            reloadDocumentsSection(documentUid);
                        }

                        // Refrescar la sección de gestión del documento
                        if (typeof refreshManagementSection === 'function') {
                            console.log('🔄 Llamando a refreshManagementSection');
                            refreshManagementSection(documentUid);
                        }
                    },
                    error: function() {
                        toastr.error('Error al procesar la solicitud', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('<i class="fa fa-trash"></i>');
                        window.pendingDelete = null;
                    }
                });
            });

            // ===== Confirmar Carga =====
            $(document).on('click', '.confirm-upload-btn', function(e) {
                e.preventDefault();

                if (!confirm('¿Confirmar carga del documento?')) {
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html('<i class="fa fa-spinner fa-spin"></i> Confirmando...');

                $.ajax({
                    url: "{{ route('api.documents.confirm-upload') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: {
                        uid: documentUid
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Carga confirmada correctamente', 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            toastr.error(response.message || 'No se pudo confirmar', 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    },
                    error: function() {
                        toastr.error('Error al procesar la solicitud', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('<i class="fa fa-check"></i> Confirmar carga');
                    }
                });
            });

            /**
             * Actualiza los elementos visuales de documentos ya cargados
             */
            function updateUploadedDocumentsUI(response) {
                const uploadedKeys = response.uploaded_documents || [];
                const uploadedDetails = response.uploaded_documents_details || {};
                const uploadedDocs = uploadedKeys.reduce((acc, key) => {
                    acc[key] = uploadedDetails[key];
                    return acc;
                }, {});

                // Actualizar contador principal
                const totalDocs = Object.keys(response.stats?.total_required || {}).length || $('.document-upload-item').length;
                const uploadedCount = uploadedKeys.length;
                $('#documentCounter').text(uploadedCount + '/' + totalDocs + ' cargados');

                // Iterar sobre cada item de documento
                $('.document-upload-item').each(function() {
                    const docType = $(this).data('doc-type');
                    const $badge = $(this).find('.badge');
                    const $input = $(this).find('input[type="file"]');
                    const $infoDiv = $(this).find('.uploaded-doc-info');

                    if (uploadedDocs[docType]) {
                        const docInfo = uploadedDocs[docType];

                        // Cambiar badge a "Cargado"
                        $badge.removeClass('bg-danger-subtle text-danger').addClass('bg-success-subtle text-success');
                        $badge.html('<i class="fa fa-check-circle"></i> Cargado');

                        // Si no existe la div de info, crearla
                        if ($infoDiv.length === 0) {
                            const infoDivHtml = `
                                <div class="uploaded-doc-info mt-2 p-3 bg-light border rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="mb-0 fw-semibold text-dark small">
                                                <i class="fa fa-file-pdf text-danger"></i> ${escapeHtml(docInfo.file_name)}
                                            </p>
                                            <small class="text-muted d-block mt-1">
                                                ${formatBytes(docInfo.size)} • ${docInfo.created_at}
                                            </small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="${docInfo.url}" class="btn btn-sm btn-primary" target="_blank" title="Descargar">
                                                <i class="fa fa-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger btn-delete-single-doc" data-media-id="${docInfo.id}" data-doc-type="${docType}" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                            $(this).append(infoDivHtml);
                        } else {
                            // Actualizar contenido existente
                            $infoDiv.find('.fw-semibold').html(`<i class="fa fa-file-pdf text-danger"></i> ${escapeHtml(docInfo.file_name)}`);
                            $infoDiv.find('small.text-muted').html(`${formatBytes(docInfo.size)} • ${docInfo.created_at}`);
                        }

                        // Ocultar input si existe
                        if ($input.length) {
                            $input.hide();
                        }
                    } else {
                        // Documento NO cargado
                        $badge.removeClass('bg-success-subtle text-success').addClass('bg-danger-subtle text-danger');
                        $badge.html('<i class="fa fa-clock"></i> Pendiente');

                        // Eliminar div de info si existe
                        if ($infoDiv.length) {
                            $infoDiv.remove();
                        }

                        // Si no existe input, recrearlo
                        if ($input.length === 0) {
                            const inputHtml = `
                                <input
                                    type="file"
                                    class="form-control document-file-input"
                                    name="documents[${docType}]"
                                    data-doc-type="${docType}"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                >
                                <small class="text-muted d-block mt-1">
                                    <i class="fa fa-info-circle"></i> PDF, JPG, PNG, DOC (máximo 10MB)
                                </small>
                            `;
                            $(this).append(inputHtml);
                        } else {
                            // Si existe, asegurarse de que esté visible
                            $input.show();
                        }
                    }
                });
            }

            /**
             * Realiza el upload de documentos via AJAX
             */
            function performUpload($submitBtn, formData, $progressBar, $uploadStatus) {
                $submitBtn.prop('disabled', true);
                $submitBtn.html('Cargando...');
                $progressBar.show();
                $uploadStatus.text('Cargando documentos...');

                // Agregar token CSRF al FormData para compatibilidad con multipart/form-data
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                $.ajax({
                    url: "{{ route('api.documents.upload', ['uid' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', documentUid),
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percentComplete = (e.loaded / e.total) * 100;
                                $('#uploadProgressBar').css('width', percentComplete + '%');
                                $uploadStatus.text('Cargando... ' + Math.round(percentComplete) + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        if (response.success) {
                            const uploadedCount = response.uploaded_count || 1;
                            toastr.success('Se cargaron ' + uploadedCount + ' documento(s) correctamente', 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                            // Actualizar estado del documento sin recargar la página
                            updateDocumentState(documentUid);
                        } else {
                            toastr.error(response.message || 'No se pudo cargar', 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error al procesar la solicitud';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toastr.error(errorMsg, 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                        $submitBtn.html('Cargar documentos');
                        $progressBar.hide();
                    }
                });
            }



            /**
             * Recarga completamente la sección de carga de documentos vía AJAX
             * Global para que los modales puedan llamarla
             */
            window.reloadDocumentsSection = function(uid = window.documentUid) {
                $.ajax({
                    url: "{{ route('api.documents.refresh-section', ['uid' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', uid),
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {

                        if (response.success && response.html) {
                            var $container = $('#uploadSectionContainer');

                            if ($container.length > 0) {
                                // El servidor devuelve HTML completo que incluye el wrapper <div id="uploadSectionContainer">
                                // Extraemos solo el contenido interno para evitar DIVs anidados
                                var html = response.html;

                                // Si el HTML comienza con <div id="uploadSectionContainer">, extraer contenido
                                if (html.indexOf('<div id="uploadSectionContainer">') === 0) {
                                    // Encontrar el cierre del div
                                    var startTag = '<div id="uploadSectionContainer">';
                                    var endTag = '</div>';
                                    var startIndex = html.indexOf(startTag) + startTag.length;
                                    var endIndex = html.lastIndexOf(endTag);

                                    if (startIndex > startTag.length - 1 && endIndex > startIndex) {
                                        var innerHtml = html.substring(startIndex, endIndex);
                                        $container.html(innerHtml);
                                    } else {
                                        // Si no se puede extraer, reemplazar todo
                                        $container.html(html);
                                    }
                                } else {
                                    // Si no tiene el wrapper, usar el HTML tal cual
                                    $container.html(html);
                                }
                            }
                        } else {
                            toastr.error('No se pudo actualizar la sección', 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = 'Error al refrescar la sección de documentos';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.status === 404) {
                            errorMsg = 'La ruta de actualización no existe (404)';
                        }

                        toastr.error(errorMsg, 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    }
                });
            }

            /**
             * DEPRECATED: Mantener por compatibilidad, usar reloadDocumentsSection()
             */
            function updateDocumentState(uid = documentUid) {
                reloadDocumentsSection(uid);
            }

            /**
             * Refrescar la sección de gestión del documento (document-management)
             * Global para que los modales y otros componentes puedan llamarla
             */
            window.refreshManagementSection = function(uid = window.documentUid) {
                const refreshUrl = '/api/documents/' + uid + '/refresh-management';
                console.log('🔄 refreshManagementSection:', refreshUrl);

                $.ajax({
                    url: refreshUrl,
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(refreshResponse) {
                        console.log('✅ refresh-management response:', refreshResponse);
                        if (refreshResponse.success && refreshResponse.html) {
                            const $managementCard = $('#documentManagementCard');
                            if ($managementCard.length) {
                                $managementCard.replaceWith(refreshResponse.html);
                                console.log('✅ Sección de gestión actualizada');
                            } else {
                                console.warn('⚠️ Elemento #documentManagementCard no encontrado');
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('❌ Error refresh-management:', xhr);
                    }
                });
            }

            /**
             * Recarga el historial de acciones
             * Global para que los modales puedan llamarla
             */
            window.reloadActionHistory = function(uid = window.documentUid) {
                console.log('[reloadActionHistory] Iniciando recarga para uid:', uid);

                $.ajax({
                    url: "{{ route('api.documents.refresh-action-history', ['uid' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', uid),
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('[reloadActionHistory] Response:', response);

                        if (response.success && response.html) {
                            const $container = $('#actionHistoryContainer');
                            $container.html(response.html);
                            console.log('[reloadActionHistory] Recarga completada');
                        } else {
                            console.error('[reloadActionHistory] Respuesta sin success o html:', response);
                        }
                    },
                    error: function(xhr) {
                        console.error('[reloadActionHistory] Error AJAX:', xhr);
                        let errorMsg = 'Error al refrescar el historial de acciones';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        console.error(errorMsg);
                    }
                });
            }

            /**
             * Actualiza la lista de documentos faltantes
             */
            function updateMissingDocumentsUI(response) {
                const missingDocs = response.missing_documents || {};
                const totalMissing = response.stats?.total_missing || 0;

                // Actualizar contador de documentos faltantes en el modal
                const $missingBadge = $('.missing-count-badge');
                if ($missingBadge.length) {
                    if (totalMissing > 0) {
                        $missingBadge.removeClass('d-none').text(totalMissing);
                    } else {
                        $missingBadge.addClass('d-none');
                    }
                }

                // Actualizar lista de documentos faltantes si existe
                const $missingList = $('#missingDocsList');
                if ($missingList.length) {
                    $missingList.empty();
                    if (Object.keys(missingDocs).length > 0) {
                        Object.entries(missingDocs).forEach(([docType, docLabel]) => {
                            $missingList.append(`
                            <li class="list-group-item">
                                <i class="fa fa-warning text-warning"></i> ${escapeHtml(docLabel)}
                            </li>
                        `);
                        });
                    } else {
                        $missingList.html('<li class="list-group-item text-success">Todos los documentos están cargados</li>');
                    }
                }

                // Si está completo, mostrar mensaje de éxito
                if (response.all_uploaded) {
                    toastr.success('¡Todos los documentos han sido cargados correctamente!', 'Completado', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                }
            }

            /**
             * Función auxiliar para escapar HTML (usada en múltiples lugares)
             */
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }

            // ===== Global Event Listeners =====
            // Llamar a updateDocumentState después de cargar documentos exitosamente
            $(document).on('ajax-upload-success', function() {
                updateDocumentState();
            });

            // También actualizar cuando se elimina un documento
            $(document).on('document-deleted', function() {
                updateDocumentState();
            });

        });
    </script>
@endpush

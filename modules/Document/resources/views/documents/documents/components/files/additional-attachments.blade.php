{{-- Additional Attachments Section --}}
@php
    $attachments = $document->getMedia('additional_attachments');
    $attachmentDetails = $document->getAdditionalAttachmentsWithDetails();
    $totalAttachments = $attachments->count();
@endphp

<div class="card mb-3" id="additionalAttachmentsSection">
    <div class="card-header p-3 bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Documentación adicional</h5>
                <p class="small mb-0 text-muted">Adjuntar documentos extra para el expediente</p>
            </div>
            <span class="badge bg-primary-subtle text-primary" id="attachmentCount">
                {{ $totalAttachments }} {{ $totalAttachments === 1 ? 'adjunto' : 'adjuntos' }}
            </span>
        </div>
    </div>
    <div class="card-body">
        {{-- Upload Form --}}
        <form id="uploadAdditionalAttachmentForm" class="mb-4">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label for="additionalFile" class="form-label fw-semibold small">
                        Seleccionar archivo
                    </label>
                    <input type="file"
                           class="form-control"
                           id="additionalFile"
                           name="file"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @if(!auth()->user()->canDocument('upload-attachments')) disabled @endif>
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-info-circle me-1"></i>
                        PDF, JPG, PNG, DOC, DOCX (máx. 10MB)
                    </small>
                </div>
                <div class="col-12">
                    <label for="attachmentNotes" class="form-label fw-semibold small">
                        Notas <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="attachmentNotes"
                           name="notes"
                           placeholder="Ej: Factura proforma, Contrato firmado..."
                           maxlength="500" @if(!auth()->user()->canDocument('upload-attachments')) disabled @endif>
                </div>
                <div class="col-12">
                    <button type="submit"
                            class="btn btn-primary w-100"
                            id="uploadAttachmentBtn" @if(!auth()->user()->canDocument('upload-attachments')) disabled title="No tienes permiso para subir adjuntos" @endif>
                        Subir documento
                    </button>
                </div>
            </div>
        </form>

        <div class="border-top my-3"></div>

        {{-- Attachments List --}}
        <div id="attachmentsList">
            @if($totalAttachments > 0)
                <div class="attachments-scroll">
                    @foreach($attachmentDetails as $attachment)
                        <div class="mb-3" data-attachment-id="{{ $attachment['id'] }}">
                            <div class="uploaded-doc-info p-3 bg-light-secondary border rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <div>
                                            <p class="mb-0 fw-semibold">{{ $attachment['name'] }}</p>
                                            @if($attachment['notes'])
                                                <small class="text-muted d-block mt-1">
                                                    {{ $attachment['notes'] }}
                                                </small>
                                            @endif
                                            <small class="text-muted">{{ number_format($attachment['size'] / 1024, 1) }} KB • {{ $attachment['uploaded_at'] }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ parse_url($attachment['url'], PHP_URL_PATH) }}" class="btn btn-sm btn-primary" target="_blank" title="Descargar">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        @if(auth()->user()->canDocument('delete-attachments'))
                                            <button type="button" class="btn btn-sm btn-danger delete-attachment-btn" data-media-id="{{ $attachment['id'] }}" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-danger" disabled title="No tienes permiso para eliminar adjuntos">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($totalAttachments > 0)
                    <div class="border-top mt-3 pt-3">
                        <a href="{{ route('documents.summary', $document->uid) }}?type=attachments" target="_blank" class="btn btn-primary w-100">
                            Ver todos los documentos comprimidos
                        </a>
                    </div>
                @endif
            @else
                <div class="alert bg-light-subtle py-3 px-3 mb-0" role="alert" id="noAttachmentsAlert">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-circle-info text-muted me-2 mt-1" style="font-size: 0.9rem;"></i>
                        <div>
                            <small class="fw-semibold d-block">Sin documentos adicionales</small>
                            <small class="text-muted">Aún no hay documentos adicionales adjuntos.</small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal: Confirmar eliminación de adjunto --}}
<div class="modal fade" id="confirmDeleteAttachmentModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteAttachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteAttachmentModalLabel">
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
                <button type="button" class="btn btn-primary w-100 mb-1" id="confirmDeleteAttachmentBtn">
                    Eliminar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .attachments-scroll {
            overflow-y: auto;
            max-height: 250px;
        }

        @media (min-width: 576px) { .attachments-scroll { max-height: 280px; } }
        @media (min-width: 768px) { .attachments-scroll { max-height: 320px; } }
        @media (min-width: 992px) { .attachments-scroll { max-height: 350px; } }

        .attachments-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .attachments-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .attachments-scroll::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 2px;
        }

        .attachments-scroll::-webkit-scrollbar-thumb:hover {
            background: #adb5bd;
        }

        #uploadAdditionalAttachmentForm .form-control {
            border-color: #dee2e6;
        }

        #uploadAdditionalAttachmentForm .form-control:focus {
            border-color: #90bb13;
            box-shadow: 0 0 0 0.2rem rgba(144, 187, 19, 0.15);
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Función para extraer la ruta de una URL (similar a parse_url PHP_URL_PATH)
        function getPathFromUrl(url) {
            try {
                const urlObj = new URL(url);
                return urlObj.pathname;
            } catch (e) {
                // Si ya es una ruta relativa, devolverla como está
                return url;
            }
        }

        $(document).ready(function() {
            const documentUid = '{{ $document->uid }}';

            // ===== Upload Additional Attachment =====
            $('#uploadAdditionalAttachmentForm').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $('#uploadAttachmentBtn');
                const $fileInput = $('#additionalFile');
                const file = $fileInput[0].files[0];

                if (!file) {
                    toastr.warning('Por favor selecciona un archivo', 'Aviso', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                // Validate size (10MB)
                if (file.size > 10485760) {
                    toastr.error('El archivo es demasiado grande. Máximo 10MB', 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                const formData = new FormData($form[0]);
                const notes = $('#attachmentNotes').val();
                if (notes) {
                    formData.append('notes', notes);
                }

                // Agregar token CSRF al FormData para compatibilidad con multipart/form-data
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Subiendo...');

                $.ajax({
                    url: "{{ route('api.documents.upload-attachment', $document->uid) }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || 'Documento subido correctamente', 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });

                            // Clear form
                            $fileInput.val('');
                            $('#attachmentNotes').val('');

                            // Refresh attachments list
                            refreshAttachmentsList();
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Error al cargar el archivo';
                        toastr.error(message, 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('Subir documento');
                    }
                });
            });

            // ===== Refresh Attachments List =====
            function refreshAttachmentsList() {
                $.ajax({
                    url: "{{ route('api.documents.attachments', $document->uid) }}",
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const attachments = response.attachments;
                            const total = response.total;
                            const $container = $('#attachmentsList');

                            // Update badge
                            $('#attachmentCount').text(total + (total === 1 ? ' adjunto' : ' adjuntos'));

                            if (attachments.length === 0) {
                                $container.html(`
                                <div class="alert bg-light-subtle py-3 px-3 mb-0" role="alert" id="noAttachmentsAlert">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-circle-info text-muted me-2 mt-1" style="font-size: 0.9rem;"></i>
                                        <div>
                                            <small class="fw-semibold d-block">Sin documentos adicionales</small>
                                            <small class="text-muted">Aún no hay documentos adicionales adjuntos.</small>
                                        </div>
                                    </div>
                                </div>
                            `);
                            } else {
                                let html = '<div class="attachments-scroll">';
                                attachments.forEach(function(attachment) {
                                    const sizeKB = (attachment.size / 1024).toFixed(1);

                                    const notesHtml = attachment.notes ?
                                        `<small class="text-muted d-block fst-italic mt-1">
                                        <i class="fas fa-sticky-note me-1"></i>${attachment.notes}
                                    </small>` : '';

                                    html += `
                                    <div class="mb-3" data-attachment-id="${attachment.id}">
                                        <div class="uploaded-doc-info p-3 bg-light-secondary border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center flex-grow-1">
                                                    <div>
                                                        <p class="mb-0 fw-semibold">${attachment.name}</p>
                                                        ${notesHtml}
                                                        <small class="text-muted">${sizeKB} KB • ${attachment.uploaded_at}</small>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a href="${getPathFromUrl(attachment.url)}" class="btn btn-sm btn-primary" target="_blank" title="Descargar">
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger delete-attachment-btn" data-media-id="${attachment.id}" title="Eliminar">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                });
                                html += '</div>';

                                // Agregar botón de descarga si hay más de 1 attachment
                                if (total > 0) {
                                    html += `
                                    <div class="border-top mt-3 pt-3">
                                        <a href="{{ route('documents.summary', $document->uid) }}?type=attachments" target="_blank" class="btn btn-primary w-100">
                                            Ver todos los documentos comprimidos
                                        </a>
                                    </div>
                                `;
                                }

                                $container.html(html);
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('Error refreshing attachments:', xhr);
                    }
                });
            }

            // ===== Delete Attachment =====
            var pendingDeleteMediaId = null;
            var pendingDeleteBtn = null;

            $(document).on('click', '.delete-attachment-btn', function(e) {
                e.preventDefault();

                pendingDeleteBtn = $(this);
                pendingDeleteMediaId = pendingDeleteBtn.data('media-id');

                // Mostrar modal
                var confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteAttachmentModal'));
                confirmModal.show();
            });

            // ===== Confirmar eliminación de adjunto =====
            $(document).on('click', '#confirmDeleteAttachmentBtn', function() {
                console.log('🔴 Click en confirmDeleteAttachmentBtn');
                if (!pendingDeleteMediaId || !pendingDeleteBtn) {
                    console.warn('⚠️ pendingDeleteMediaId o pendingDeleteBtn no existen');
                    return;
                }

                console.log('📝 mediaId:', pendingDeleteMediaId, 'uid:', documentUid);
                pendingDeleteBtn.prop('disabled', true);
                pendingDeleteBtn.html('<i class="fas fa-spinner fa-spin"></i>');

                $('#confirmDeleteAttachmentModal').modal('hide');

                const deleteUrl = "{{ route('api.documents.delete-attachment', ['uid' => 'UID_PLACEHOLDER', 'mediaId' => 'MEDIA_PLACEHOLDER']) }}"
                    .replace('UID_PLACEHOLDER', documentUid)
                    .replace('MEDIA_PLACEHOLDER', pendingDeleteMediaId);

                console.log('🌐 DELETE URL:', deleteUrl);

                $.ajax({
                    url: deleteUrl,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log('✅ DELETE success callback ejecutado. Response:', response);

                        toastr.success('Adjunto eliminado', 'Éxito', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });

                        // Refrescar la sección de gestión del documento
                        if (typeof refreshManagementSection === 'function') {
                            console.log('🔄 Llamando a refreshManagementSection');
                            refreshManagementSection(documentUid);
                        }

                        // Refresh list
                        refreshAttachmentsList();
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Error DELETE:', {
                            status: status,
                            statusCode: xhr.status,
                            error: error,
                            response: xhr.responseJSON
                        });
                        var message = 'Error al eliminar el archivo';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        toastr.error(message, 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function() {
                        console.log('🔚 AJAX complete');
                        if (pendingDeleteBtn) {
                            pendingDeleteBtn.prop('disabled', false);
                            pendingDeleteBtn.html('<i class="fas fa-trash"></i>');
                        }
                        pendingDeleteMediaId = null;
                        pendingDeleteBtn = null;
                    }
                });
            });
        });
    </script>
@endpush

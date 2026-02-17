<div id="uploadSectionContainer">
    <div class="card mb-3">
        <div class="card-header p-3 bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Archivos adjuntos</h5>
                    <p class="small mb-0 text-muted">Documentos relacionados con la solicitud</p>
                </div>
                <div>
                    @php
                        $attachmentsCount = $attention->getMedia('attachments')->count();
                    @endphp
                    <span class="badge bg-primary">
                        {{ $attachmentsCount }} archivo(s)
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Lista de archivos existentes --}}
            @if($attachmentsCount > 0)
                <div class="mb-3">
                    <label class="form-label fw-semibold">Archivos adjuntos</label>
                    <div class="list-group">
                        @foreach($attention->getMedia('attachments') as $media)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>
                                    <div>
                                        <p class="mb-0 fw-semibold">{{ $media->file_name }}</p>
                                        <small class="text-muted">{{ number_format($media->size / 1024, 2) }} KB • {{ $media->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ $media->getUrl() }}" class="btn btn-sm btn-primary" target="_blank" title="Descargar">
                                        <i class="fa fa-download"></i>
                                    </a>
                                    @if(auth()->user()->can('attention.delete-files'))
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-attachment" data-media-id="{{ $media->id }}" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Formulario de carga --}}
            @if(auth()->user()->can('attention.upload-files'))
                <form id="uploadAttachmentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cargar nuevo archivo</label>
                        <input type="file" class="form-control" name="attachment" id="attachmentInput"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="text-muted d-block mt-1">
                            <i class="fa fa-info-circle"></i> PDF, JPG, PNG, DOC (máximo 10MB)
                        </small>
                    </div>

                    <div id="uploadProgress" style="display: none;" class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="fw-semibold">Cargando archivo...</small>
                            <small class="text-muted" id="uploadStatus">0%</small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                 role="progressbar" id="uploadProgressBar" style="width: 0%"
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="uploadBtn">
                        Cargar archivo
                    </button>
                </form>
            @else
                <div class="alert alert-info py-2 px-3 mb-0" role="alert">
                    <small>No tienes permiso para cargar archivos</small>
                </div>
            @endif

        </div>
    </div>
</div>

{{-- Modal: Confirmar eliminación de archivo --}}
<div class="modal fade" id="confirmDeleteAttachmentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    <strong>¿Estás seguro de que deseas eliminar este archivo?</strong>
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

@push('scripts')
<script>
$(document).ready(function() {
    const attentionUid = '{{ $attention->uid }}';
    let pendingDeleteMediaId = null;

    // Cargar archivo
    $('#uploadAttachmentForm').on('submit', function(e) {
        e.preventDefault();

        const fileInput = $('#attachmentInput')[0];
        if (!fileInput.files || fileInput.files.length === 0) {
            toastr.warning('Por favor selecciona un archivo', 'Atención');
            return;
        }

        const file = fileInput.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB

        if (file.size > maxSize) {
            toastr.error('El archivo excede el tamaño máximo de 10MB', 'Error');
            return;
        }

        const formData = new FormData(this);
        const $btn = $('#uploadBtn');
        const $progressBar = $('#uploadProgress');
        const $uploadStatus = $('#uploadStatus');

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cargando...');
        $progressBar.show();

        $.ajax({
            url: `/attentions/${attentionUid}/upload-attachment`,
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        $('#uploadProgressBar').css('width', percentComplete + '%');
                        $uploadStatus.text(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Archivo cargado correctamente', 'Éxito');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'No se pudo cargar el archivo', 'Error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al cargar el archivo';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg, 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Cargar archivo');
                $progressBar.hide();
                $('#uploadProgressBar').css('width', '0%');
                $uploadStatus.text('0%');
                $('#attachmentInput').val('');
            }
        });
    });

    // Eliminar archivo
    $(document).on('click', '.btn-delete-attachment', function() {
        pendingDeleteMediaId = $(this).data('media-id');
        $('#confirmDeleteAttachmentModal').modal('show');
    });

    $('#confirmDeleteAttachmentBtn').on('click', function() {
        if (!pendingDeleteMediaId) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Eliminando...');

        $.ajax({
            url: `/attentions/${attentionUid}/delete-attachment/${pendingDeleteMediaId}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    toastr.success('Archivo eliminado correctamente', 'Éxito');
                    $('#confirmDeleteAttachmentModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'No se pudo eliminar el archivo', 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Error al eliminar el archivo', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Eliminar');
                pendingDeleteMediaId = null;
            }
        });
    });
});
</script>
@endpush

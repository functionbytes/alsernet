{{-- Modal: Confirmar eliminación de documento --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-danger-subtle">
                <div>
                    <h5 class="modal-title fw-bold mb-1 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar eliminación
                    </h5>
                    <p class="text-muted small mb-0">Esta acción no se puede deshacer</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning border-warning">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-exclamation-circle text-warning me-3 mt-1"></i>
                        <div>
                            <h6 class="mb-2 fw-semibold">¿Estás seguro de eliminar este documento?</h6>
                            <p class="mb-2 small">Se eliminará permanentemente:</p>
                            <ul class="small mb-0">
                                <li>Documento #{{ $document->uid }}</li>
                                <li>Tipo: {{ $document->type->label ?? 'N/A' }}</li>
                                <li>Cliente: {{ $document->client->name ?? 'N/A' }}</li>
                                <li>Todos los archivos adjuntos asociados</li>
                                <li>Todo el historial de acciones</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold">Motivo de eliminación</label>
                    <textarea
                        class="form-control"
                        id="deleteReason"
                        rows="3"
                        placeholder="Describe brevemente por qué se elimina este documento..."
                        required></textarea>
                    <small class="text-muted">Este motivo quedará registrado en el log del sistema</small>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-danger w-100 mb-1" id="btnConfirmDelete">
                    <i class="fas fa-trash-alt me-2"></i>Eliminar documento
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
        $('#btnConfirmDelete').on('click', function() {
            const $btn = $(this);
            const reason = $('#deleteReason').val().trim();

            if (!reason) {
                toastr.warning('Debes especificar un motivo para eliminar el documento', 'Atención');
                return;
            }

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Eliminando...');

            $.ajax({
                url: "{{ route('api.documents.delete', $document->uid) }}",
                method: 'DELETE',
                data: {
                    reason: reason,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Documento eliminado exitosamente', 'Éxito');
                        $('#confirmDeleteModal').modal('hide');

                        // Redirigir a la lista de documentos después de 1.5 segundos
                        setTimeout(() => {
                            window.location.href = response.redirect_url || '/';
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error al eliminar el documento';
                    toastr.error(message, 'Error');
                    $btn.prop('disabled', false).html('<i class="fas fa-trash-alt me-2"></i>Eliminar documento');
                }
            });
        });

        // Limpiar el formulario cuando se cierra el modal
        $('#confirmDeleteModal').on('hidden.bs.modal', function() {
            $('#deleteReason').val('');
        });
    });
</script>
@endpush

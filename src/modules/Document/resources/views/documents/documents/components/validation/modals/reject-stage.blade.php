{{-- Modal: Rechazar documento --}}
<div class="modal fade" id="rejectStageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-1">
                        Rechazar documento
                    </h5>
                    <p class="text-muted small mb-0">El documento será marcado como rechazado</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Current Stage Info --}}
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded mb-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle" style="width: 48px; height: 48px;">
                        <i class="fas fa-times text-primary fs-5"></i>
                    </div>
                    <div>
                        <p class="mb-0 fw-semibold">Etapa {{ $document->current_stage }} de {{ $document->total_stages }}</p>
                        <small class="text-muted">El flujo de validación se detendrá</small>
                    </div>
                </div>

                {{-- Internal Note Warning --}}
                <div class="alert bg-primary-subtle border-0 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-lock text-primary me-2 mt-1"></i>
                        <div>
                            <small class="fw-semibold d-block text-primary">Nota interna</small>
                            <small class="text-dark">Esta información NO será enviada al cliente</small>
                        </div>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold small">
                        Motivo del rechazo <span class="text-primary">*</span>
                    </label>
                    <textarea class="form-control form-control-sm" id="rejectStageReason" rows="4" required
                              placeholder="Describe el motivo del rechazo (solo visible para el equipo interno)..."></textarea>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>Mínimo 10 caracteres - Solo visible para el equipo interno
                    </small>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary w-100 mb-1" id="btnConfirmRejectStage">
                    Rechazar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // ===== Handler: Rechazar Documento =====
        $('#btnConfirmRejectStage').on('click', function() {
            const $btn = $(this);
            const reason = $('#rejectStageReason').val();

            // Validar que hay una razón
            if (!reason || reason.trim().length < 10) {
                toastr.warning('Debes proporcionar una razón de al menos 10 caracteres', 'Atención', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-bottom-right"
                });
                return;
            }

            // Deshabilitar botón y mostrar loading
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Rechazando...');

            $.ajax({
                url: "{{ route('api.documents.reject-stage', $document->uid) }}",
                method: 'POST',
                data: {
                    reason: reason,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, 'Éxito', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });

                        // Cerrar modal y recargar página después de 1.5s
                        $('#rejectStageModal').modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error al rechazar el documento';
                    toastr.error(message, 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });

                    // Restaurar botón
                    $btn.prop('disabled', false).html('<i class="fas fa-times-circle me-2"></i>Rechazar Documento');
                }
            });
        });

        // Limpiar modal al cerrar
        $('#rejectStageModal').on('hidden.bs.modal', function() {
            $('#rejectStageReason').val('');
            $('#btnConfirmRejectStage')
                .prop('disabled', false)
                .html('<i class="fas fa-times-circle me-2"></i>Rechazar Documento');
        });
    });
</script>
@endpush

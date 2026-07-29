{{-- Modal: Confirmación de recepción de documentos --}}
<div class="modal fade" id="uploadConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Confirmación de recepción</h5>
                    <p class="text-muted small mb-0">Notifica al cliente que sus documentos han sido recibidos</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert bg-light border-0">
                    Se enviará un email confirmando que los documentos han sido recibidos correctamente.
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary w-100 mb-1" id="btnConfirmUpload">
                    Enviar confirmación
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
        $('#btnConfirmUpload').on('click', function() {
            const $btn = $(this);

            $btn.prop('disabled', true).html('Enviando...');

            $.ajax({
                url: "{{ route('api.documents.send-upload-confirmation', ['uid' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', window.documentUid),
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Email de confirmación enviado a: ' + response.recipient, 'Éxito', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                        $('#uploadConfirmationModal').modal('hide');

                        // Recargar historial de acciones
                        if (typeof window.reloadActionHistory === 'function') {
                            window.reloadActionHistory();
                        }
                    }
                },
                error: function(xhr) {
                    const status = xhr.status;
                    const data = xhr.responseJSON || {};

                    if (status === 429) {
                        if (data.error_type === 'rate_limit' || data.error_type === 'send_failed') {
                            showErrorModal(data);
                        } else {
                            toastr.error(data.message || 'Error al procesar la solicitud', 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    } else {
                        const message = data.message || 'Error al enviar el email';
                        toastr.error(message, 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).html('Enviar confirmación');
                }
            });
        });

        // Función para mostrar modal de rate limit o error de envío
        function showErrorModal(response) {
            // Cerrar modal actual
            $('#uploadConfirmationModal').modal('hide');

            // Determinar si es rate limit o error de envío
            const isRateLimit = response.error_type === 'rate_limit';

            let modalContent = '';
            let timerContent = '';

            if (isRateLimit) {
                const retryTime = new Date(response.retry_after);
                const secondsRemaining = Math.ceil(response.seconds_remaining);

                timerContent = `
                    <div class="mb-3">
                        <div class="h2 text-warning fw-bold">${secondsRemaining}s</div>
                        <small class="text-muted">Reintentar en</small>
                    </div>
                    <small class="text-muted d-block">A las ${retryTime.toLocaleTimeString('es-ES')}</small>
                `;

                modalContent = `
                    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-sm">
                                <div class="modal-header bg-light border-bottom">
                                    <h5 class="modal-title fw-bold">
                                        Límite de envío
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <p class="text-muted mb-3">Solo puedes enviar un email del mismo tipo cada 60 segundos</p>
                                    ${timerContent}
                                </div>
                                <div class="modal-footer border-top bg-light">
                                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Entendido</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Error de envío
                modalContent = `
                    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-sm">
                                <div class="modal-header bg-light border-bottom">
                                    <h5 class="modal-title fw-bold">
                                        <i class="fas fa-exclamation-circle text-danger me-2"></i>Error al enviar
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <p class="text-muted">${response.message || 'No se pudo enviar el email. Intenta de nuevo en unos segundos.'}</p>
                                </div>
                                <div class="modal-footer border-top bg-light">
                                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Entendido</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Remover modal anterior si existe
            $('#errorModal').remove();
            $('body').append(modalContent);

            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();

            // Timer que actualiza cada segundo (solo si es rate limit)
            if (isRateLimit) {
                const secondsRemaining = Math.ceil(response.seconds_remaining);
                let remaining = secondsRemaining;
                const timerInterval = setInterval(function() {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(timerInterval);
                        errorModal.hide();
                    } else {
                        $('#errorModal .h2').html(remaining + 's');
                    }
                }, 1000);
            }
        }
    });
</script>
@endpush

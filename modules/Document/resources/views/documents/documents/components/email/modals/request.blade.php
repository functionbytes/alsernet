{{-- Modal: Solicitud inicial de documentos --}}
<div class="modal fade" id="requestUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-1">
                        Solicitud documentación
                    </h5>
                    <p class="text-muted small mb-0">Solicita al cliente que cargue los documentos requeridos</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" role="alert">
                    <div class="d-flex align-items-center">

                            Se enviará un email solicitando que se carguen todos los documentos requeridos.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary w-100 mb-1" id="btnSendRequestUpload">
                    Enviar solicitud
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {

            $(document).on('click', '.send-notification-btn', function(e) {
                e.preventDefault();
                $('#requestUploadModal').modal('show');
            });

            // ===== Handler: Enviar Solicitud Inicial de Documentos =====
            $(document).on('click', '#btnSendRequestUpload', function() {
                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html('Enviando...');

                // Usar fetch en lugar de jQuery AJAX para mayor control
                fetch("{{ route('api.documents.send-notification', ['uid' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', window.documentUid), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({})
                })
                .then(response => {
                    // Parsear JSON sin importar el status code
                    return response.json().then(data => ({
                        status: response.status,
                        data: data
                    }));
                })
                .then(({ status, data }) => {
                    // Manejar 200 (éxito)
                    if (status === 200) {
                        if (data.success) {
                            $('#requestUploadModal').modal('hide');
                            toastr.success('Email enviado a: ' + (data.recipient || 'cliente'), 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                            // Recargar historial de acciones
                            if (typeof window.reloadActionHistory === 'function') {
                                window.reloadActionHistory();
                            }
                        } else if (data.error_type === 'rate_limit' || data.error_type === 'send_failed') {
                            showRateLimitModal(data);
                        } else {
                            toastr.error(data.message || 'No se pudo enviar', 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    }
                    // Manejar 429 (rate limit o send failed)
                    else if (status === 429) {
                        if (data.error_type === 'rate_limit' || data.error_type === 'send_failed') {
                            showRateLimitModal(data);
                        } else {
                            toastr.error(data.message || 'Error al procesar la solicitud', 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    }
                    // Manejar 422 (validación)
                    else if (status === 422) {
                        toastr.error(data.message || 'Error al procesar la solicitud', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    }
                    // Otros errores
                    else {
                        toastr.error('Error al procesar la solicitud', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    }
                })
                .catch(error => {
                    console.error('Error en solicitud:', error);
                    toastr.error('Error al procesar la solicitud', 'Error', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                })
                .finally(() => {
                    $btn.prop('disabled', false);
                    $btn.html('Enviar solicitud');
                });
            });

            // Función para mostrar modal de rate limit o error de envío
            function showRateLimitModal(response) {
                // Cerrar modal actual
                $('#requestUploadModal').modal('hide');

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
                        <div class="modal fade" id="rateLimitModal" tabindex="-1" aria-hidden="true">
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
                        <div class="modal fade" id="rateLimitModal" tabindex="-1" aria-hidden="true">
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
                $('#rateLimitModal').remove();
                $('body').append(modalContent);

                const rateLimitModal = new bootstrap.Modal(document.getElementById('rateLimitModal'));
                rateLimitModal.show();

                // Timer que actualiza cada segundo (solo si es rate limit)
                if (isRateLimit) {
                    const secondsRemaining = Math.ceil(response.seconds_remaining);
                    let remaining = secondsRemaining;
                    const timerInterval = setInterval(function() {
                        remaining--;
                        if (remaining <= 0) {
                            clearInterval(timerInterval);
                            rateLimitModal.hide();
                        } else {
                            $('#rateLimitModal .h2').html(remaining + 's');
                        }
                    }, 1000);
                }
            }
        });
    </script>
@endpush

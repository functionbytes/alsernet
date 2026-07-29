{{-- Modal: Notificación de rechazo --}}
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title">
                        Notificación de rechazo
                    </h5>
                    <p class="text-muted small mb-0">Notifica al cliente que sus documentos fueron rechazados.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert bg-light border-0 mb-3">
                    Se enviará un email notificando que los documentos han sido rechazados.
                </div>

                {{-- Razón del rechazo (requerido) --}}
                <div class="mb-3">
                    <label for="rejectionReason" class="form-label fw-semibold">
                        Razón del rechazo <span class="text-danger">*</span>
                    </label>
                    <textarea
                        class="form-control"
                        id="rejectionReason"
                        rows="4"
                        placeholder="Ejemplo: Los documentos no cumplen con los requisitos de calidad. Por favor, envíe fotografías más claras."
                        minlength="10"
                        required></textarea>
                    <div class="form-text">
                        <i class="fas fa-info-circle me-1"></i>
                        Esta razón será incluida en el email enviado al cliente (mínimo 10 caracteres).
                    </div>
                </div>

                {{-- Documentos rechazados específicos (opcional) --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Documentos rechazados específicos <span class="text-muted">(opcional)</span>
                    </label>
                    <div class="form-text mb-2">
                        Si no selecciona ninguno, se asumirá que todos los documentos fueron rechazados.
                    </div>
                    @if(isset($requiredDocuments) && count($requiredDocuments) > 0)
                        <div class="border rounded p-3 bg-light">
                            @foreach($requiredDocuments as $key => $label)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="rejected_docs[]" value="{{ $key }}" id="rejected_{{ $key }}">
                                    <label class="form-check-label" for="rejected_{{ $key }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="border rounded p-3 bg-light">
                            <p class="text-muted small mb-0">
                                No hay documentos específicos configurados para este tipo de documento.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary w-100 mb-1" id="btnSendRejection">
                    Enviar rechazo
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
            // ===== Handler: Enviar Rechazo =====
            $('#btnSendRejection').on('click', function() {
                const $btn = $(this);
                const reason = $('#rejectionReason').val().trim();

                // Validar que la razón no esté vacía y tenga al menos 10 caracteres
                if (!reason) {
                    toastr.warning('Debes especificar la razón del rechazo', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    $('#rejectionReason').focus();
                    return;
                }

                if (reason.length < 10) {
                    toastr.warning('La razón debe tener al menos 10 caracteres', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    $('#rejectionReason').focus();
                    return;
                }

                // Recoger documentos rechazados seleccionados (opcional)
                const rejectedDocs = [];
                $('input[name="rejected_docs[]"]:checked').each(function() {
                    rejectedDocs.push($(this).val());
                });

                // Deshabilitar botón y mostrar loading
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');

                $.ajax({
                    url: "{{ route('api.documents.send-rejection', ['uid' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', window.documentUid),
                    method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                    data: {
                        reason: reason,
                        rejected_docs: rejectedDocs  // Array vacío si no se seleccionó ninguno
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Email de rechazo enviado a: ' + response.recipient, 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                            $('#rejectionModal').modal('hide');

                            // Limpiar textarea de razón
                            $('#rejectionReason').val('');

                            // Limpiar checkboxes (desmarcar todos)
                            $('input[name="rejected_docs[]"]').prop('checked', false);

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
                        $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Enviar rechazo');
                    }
                });
            });

            // Función para mostrar modal de rate limit o error de envío
            function showErrorModal(response) {
                // Cerrar modal actual
                $('#rejectionModal').modal('hide');

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

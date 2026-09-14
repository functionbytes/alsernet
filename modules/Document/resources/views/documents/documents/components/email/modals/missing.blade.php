{{-- Modal: Solicitar documentos faltantes --}}
<div class="modal fade" id="missingDocsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Solicitar documentos faltantes</h5>
                    <p class="text-muted small mb-0">Selecciona los documentos a solicitar</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if(count($missingDocs) > 0)
                    <p class="text-muted small mb-3">Los siguientes documentos están pendientes de carga:</p>
                    <form id="missingDocsForm">
                        <div class="border rounded p-3 bg-light-secondary mb-3">
                            @foreach($missingDocs as $key => $label)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="missing_docs[]" value="{{ $key }}" id="missing_{{ $key }}" checked>
                                    <label class="form-check-label fw-semibold" for="missing_{{ $key }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mb-0">
                            <label for="additional_notes" class="form-label fw-semibold">Notas adicionales (opcional)</label>
                            <textarea class="form-control" id="additional_notes" name="notes" rows="3" placeholder="Ej: La foto del DNI está borrosa..."></textarea>
                        </div>
                    </form>
                @else
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold">Todos los documentos están cargados</h6>
                                <p class="mb-0 small">No hay documentos faltantes para este tipo de solicitud.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer border-top">
                @if(count($missingDocs) > 0)
                    <button type="button" class="btn btn-primary w-100 mb-1" id="btnSendMissingDocs">
                        Enviar solicitud
                    </button>
                @endif
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            // ===== Handler: Enviar Documentos Faltantes =====
            $('#btnSendMissingDocs').on('click', function() {
                const $btn = $(this);
                const $form = $('#missingDocsForm');
                const selectedDocs = [];

                $form.find('input[name="missing_docs[]"]:checked').each(function() {
                    selectedDocs.push($(this).val());
                });

                if (selectedDocs.length === 0) {
                    toastr.warning('Selecciona al menos un documento faltante', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                // Deshabilitar botón y mostrar estado de carga
                $btn.prop('disabled', true);
                $btn.html('Enviando...');

                // Usar fetch en lugar de jQuery AJAX para mayor control
                fetch("{{ route('api.documents.send-missing', ['uid' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', window.documentUid), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        missing_docs: selectedDocs,
                        notes: $('#additional_notes').val()
                    })
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
                            $('#missingDocsModal').modal('hide');
                            // Limpiar checkboxes
                            $form.find('input[name="missing_docs[]"]:checked').prop('checked', false);
                            // Limpiar textarea de notas
                            $('#additional_notes').val('');

                            toastr.success('Email enviado correctamente', 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });

                            // Recargar historial de acciones
                            if (typeof window.reloadActionHistory === 'function') {
                                window.reloadActionHistory();
                            }
                        } else if (data.error_type === 'rate_limit') {
                            showRateLimitModal(data);
                        } else if (data.error_type === 'send_failed') {
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
                $('#missingDocsModal').modal('hide');

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

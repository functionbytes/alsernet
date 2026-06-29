{{-- Modal: Correo personalizado --}}
<div class="modal fade" id="customEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Redactar correo personalizado</h5>
                    <p class="text-muted small mb-0">Envía un mensaje personalizado al cliente</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="customEmailForm">
                    {{-- Plantilla configurada - Cargado via AJAX --}}
                    <div class="alert alert-info" id="templateInfo" style="display: none;">
                        <h6 class="mb-1 fw-semibold">Plantilla configurada</h6>
                        <p class="mb-0 small">Se usará: <span class="badge bg-primary" id="templateName"></span></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Asunto</label>
                        <input type="text" class="form-control" id="emailSubject" name="subject" placeholder="Asunto del correo" required>
                        <span class="invalid-feedback d-block"></span>
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Mensaje</label>
                            <small class="text-muted"><span id="charCount">0</span>/10 min</small>
                        </div>
                        <textarea class="form-control" id="emailMessage" name="message" rows="5" placeholder="Escribe tu mensaje..." required minlength="10"></textarea>
                        <span class="invalid-feedback d-block"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary w-100 mb-1" id="btnSendCustomEmail">
                    Enviar email
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let customEmailTemplateId = null;

        // Inicializar validación con jQuery Validate
        $('#customEmailForm').validate({
            rules: {
                subject: {
                    required: true,
                    minlength: 3
                },
                message: {
                    required: true,
                    minlength: 10
                }
            },
            messages: {
                subject: {
                    required: 'El asunto es requerido',
                    minlength: 'El asunto debe tener al menos 3 caracteres'
                },
                message: {
                    required: 'El mensaje es requerido',
                    minlength: 'El mensaje debe contener al menos 10 caracteres'
                }
            },
            errorClass: 'is-invalid',
            errorElement: 'span',
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            },
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback d-block');
                error.insertAfter(element);
            }
        });

        // Actualizar contador de caracteres en tiempo real
        $('#emailMessage').on('input', function() {
            const charCount = $(this).val().length;
            $('#charCount').text(charCount);

            // Cambiar color del contador si no hay suficientes caracteres
            if (charCount < 10) {
                $('#charCount').parent().css('color', '#dc3545');
            } else {
                $('#charCount').parent().css('color', '#6c757d');
            }
        });

        // Cargar plantilla de email cuando se abre el modal
        $('#customEmailModal').on('show.bs.modal', function() {
            // Resetear formulario y validación
            $('#customEmailForm').trigger('reset');
            $('#customEmailForm').find('input, textarea').removeClass('is-invalid');
            $('#charCount').text('0').parent().css('color', '#6c757d');

            $.ajax({
                url: "{{ route('api.documents.custom-email-template') }}",
                method: 'GET',
                success: function(response) {
                    if (response.success && response.template) {
                        customEmailTemplateId = response.template.id;
                        $('#templateName').text(response.template.name);
                        $('#templateInfo').show();
                    }
                }
            });
        });

        $('#btnSendCustomEmail').on('click', function() {
            // Validar formulario
            if (!$('#customEmailForm').valid()) {
                return;
            }

            const $btn = $(this);
            const subject = $('#emailSubject').val().trim();
            const message = $('#emailMessage').val().trim();

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

            $.ajax({
                url: "{{ route('api.documents.send-custom-email', $document->uid) }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    subject: subject,
                    message: message,
                    template_id: customEmailTemplateId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Email enviado a: ' + response.recipient, 'Éxito');
                        $('#customEmailModal').modal('hide');
                        $('#customEmailForm').trigger('reset');
                        $('#customEmailForm').find('input, textarea').removeClass('is-invalid');
                        $('#charCount').text('0');
                    }
                },
                error: function(xhr) {
                    const status = xhr.status;
                    const data = xhr.responseJSON || {};

                    if (status === 429) {
                        if (data.error_type === 'rate_limit' || data.error_type === 'send_failed') {
                            showErrorModal(data);
                        } else {
                            toastr.error(data.message || 'Error al procesar la solicitud', 'Error');
                        }
                    } else {
                        toastr.error(data.message || 'Error al enviar', 'Error');
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).html('Enviar email');
                }
            });
        });

        // Función para mostrar modal de rate limit o error de envío
        function showErrorModal(response) {
            // Cerrar modal actual
            $('#customEmailModal').modal('hide');

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

{{-- Modal: Enviar correo personalizado --}}
<div class="modal fade" id="customEmailModal" tabindex="-1" aria-labelledby="customEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="customEmailModalLabel">
                    Redactar correo personalizado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendCustomEmailForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Destinatario <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="recipient"
                               value="{{ $attention->customer_email }}" required
                               placeholder="correo@ejemplo.com">
                        <small class="text-muted">Email del destinatario</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Asunto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="subject" required
                               placeholder="Asunto del correo..."
                               value="RE: {{ $attention->radicado }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mensaje <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="message" rows="6" required
                                  placeholder="Escribir el mensaje..."></textarea>
                        <small class="text-muted">Contenido del email</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="send_copy" value="1" id="sendCopyCheck">
                            <label class="form-check-label" for="sendCopyCheck">
                                Enviarme una copia de este correo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="submit" class="btn btn-primary w-100 mb-1" id="sendCustomEmailBtn">
                        Enviar correo
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#sendCustomEmailForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#sendCustomEmailBtn');
        const formData = $(this).serialize();

        $btn.prop('disabled', true).html('Enviando...');

        $.ajax({
            url: '{{ route("attention.emails.send-custom", $attention->uid) }}',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Correo enviado correctamente', 'Éxito');
                    $('#customEmailModal').modal('hide');
                    $('#sendCustomEmailForm')[0].reset();

                    // Reload page after 1.5 seconds
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'No se pudo enviar el correo', 'Error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al enviar el correo';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMsg = errors.join('<br>');
                }
                toastr.error(errorMsg, 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Enviar correo');
            }
        });
    });
});
</script>
@endpush

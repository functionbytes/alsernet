{{-- Modal: Enviar notificación de resolución --}}
<div class="modal fade" id="resolvedModal" tabindex="-1" aria-labelledby="resolvedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="resolvedModalLabel">
                   Enviar notificación de resolución
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendResolvedForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info" role="alert">
                        Se enviará un email al ciudadano notificando que su peticiones ha sido resuelto.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Destinatario</label>
                        <input type="email" class="form-control" value="{{ $attention->customer_email }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mensaje adicional (opcional)</label>
                        <textarea class="form-control" name="additional_message" rows="4"
                                  placeholder="Agregar un mensaje personalizado..."></textarea>
                        <small class="text-muted">Este mensaje se incluirá en el email junto con la resolución</small>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="submit" class="btn btn-primary w-100 mb-1" id="sendResolvedBtn">
                        Enviar notificación
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
    $('#sendResolvedForm').on('submit', function(e) {
        e.preventDefault();

        const attentionUid = '{{ $attention->uid }}';
        const $btn = $('#sendResolvedBtn');
        const formData = $(this).serialize();

        $btn.prop('disabled', true).html('Enviando...');

        $.ajax({
            url: `/attentions/${attentionUid}/emails/send-resolution`,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Notificación enviada correctamente', 'Éxito');
                    $('#resolvedModal').modal('hide');
                    $('#sendResolvedForm')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'No se pudo enviar la notificación', 'Error');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Error al enviar la notificación';
                toastr.error(msg, 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Enviar notificación');
            }
        });
    });
});
</script>
@endpush

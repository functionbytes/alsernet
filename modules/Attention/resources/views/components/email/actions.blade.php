{{-- Componente: Tarjeta de Acciones de Email --}}
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Acciones de email</h5>
        <p class="small mb-0 text-muted">Comunicación con el ciudadano</p>
    </div>
    <div class="card-body">
        @if(auth()->user()->can('attention.send-confirmation'))
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Confirmación de recepción
                </label>
                <p class="text-muted mb-2">
                    Enviar confirmación al ciudadano de que su PQRSF fue recibido
                </p>
                <button type="button" class="btn btn-outline-primary w-100 send-confirmation-btn"
                        data-uid="{{ $attention->uid }}">
                    Enviar confirmación
                </button>
            </div>
        @endif

        @if(auth()->user()->can('attention.send-in-process'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Notificación de trámite
                </label>
                <p class="text-muted mb-2">
                    Notificar al ciudadano que su PQRSF está en proceso
                </p>
                <button type="button" class="btn btn-outline-primary w-100 send-in-process-btn"
                        data-uid="{{ $attention->uid }}">
                    Enviar notificación
                </button>
            </div>
        @endif

        @if(auth()->user()->can('attention.send-resolved'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                    Notificación de resolución
                </label>
                <p class="text-muted mb-2">
                    Notificar al ciudadano que su PQRSF fue resuelto
                </p>
                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#resolvedModal">
                    Enviar resolución
                </button>
            </div>
        @endif

        @if(auth()->user()->can('attention.send-custom'))
            <hr class="my-3">
            <div class="mb-3">
                <label class="form-label fw-semibold mb-1">
                   Correo personalizado
                </label>
                <p class="text-muted mb-2">
                    Enviar email con contenido personalizado al ciudadano
                </p>
                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#customEmailModal">
                    Redactar correo
                </button>
            </div>
        @endif
    </div>
</div>

{{-- MODALES DE EMAIL (Condicionales por permisos) --}}
@if(auth()->user()->can('attention.send-resolved'))
    @include('attention::components.email.modals.resolved')
@endif

@if(auth()->user()->can('attention.send-custom'))
    @include('attention::components.email.modals.custom')
@endif

@push('scripts')
<script>
$(document).ready(function() {
    const attentionUid = '{{ $attention->uid }}';
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Enviar confirmación de recepción
    $('.send-confirmation-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');

        $.ajax({
            url: `/attentions/${attentionUid}/emails/send-confirmation`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Confirmación enviada correctamente', 'Éxito');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'No se pudo enviar la confirmación', 'Error');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Error al enviar la confirmación';
                toastr.error(msg, 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Enviar confirmación');
            }
        });
    });

    // Enviar notificación de proceso
    $('.send-in-process-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');

        $.ajax({
            url: `/attentions/${attentionUid}/emails/send-in-process`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Notificación enviada correctamente', 'Éxito');
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

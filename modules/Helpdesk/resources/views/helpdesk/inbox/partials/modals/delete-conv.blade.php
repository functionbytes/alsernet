{{-- Modal: Confirmar eliminar conversación --}}
<div class="bv-modal" data-bv-modal-name="delete-conv">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box danger"><i class="fas fa-trash"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CONFIRMACIÓN</span>
                <div class="bv-modal-title">Eliminar conversación</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <div class="bv-warn-box">
                <div class="bv-warn-box__body">
                    <strong class="bv-warn-box__title">¿Eliminar esta conversación? Esta acción no se puede deshacer.</strong>
                    <span class="bv-warn-box__desc">Todos los mensajes, adjuntos y notas internas se perderán permanentemente.</span>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-danger-solid" id="bv-delete-conv-confirm">Eliminar conversación</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '#bv-delete-conv-confirm', function () {
    var url = $('#bv-btn-delete-conv').data('delete-url');
    if (!url) {
        if (window.toastr) toastr.warning('No hay conversación activa');
        return;
    }

    var $btn = $(this).prop('disabled', true);

    $.ajax({
        url: url,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    })
    .done(function () {
        $('[data-bv-modal-name="delete-conv"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) $('body').css('overflow', '');
        if (window.toastr) toastr.success('Conversación eliminada.');
        $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
    })
    .fail(function (xhr) {
        var msg = xhr?.responseJSON?.message || 'Error al eliminar la conversación';
        if (window.toastr) toastr.error(msg);
    })
    .always(function () {
        $btn.prop('disabled', false);
    });
});
</script>
@endpush
@endonce

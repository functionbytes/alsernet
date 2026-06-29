{{-- Modal: Confirmar bloqueo de contacto --}}
<div class="bv-modal" data-bv-modal-name="block-contact">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box danger"><i class="fas fa-ban"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · ACCIÓN IRREVERSIBLE</span>
                <div class="bv-modal-title">Bloquear contacto</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <div class="bv-warn-box">
                <div class="bv-warn-box__body">
                    <strong class="bv-warn-box__title">El contacto no podrá enviar nuevos mensajes.</strong>
                    <span class="bv-warn-box__desc">Esta acción bloquea al cliente de todos los canales. Puedes desbloquearlo desde su ficha de cliente más adelante.</span>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-danger-solid" id="bv-block-contact-confirm">Bloquear contacto</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '#bv-block-contact-confirm', function () {
    var url = $('#bv-btn-block-contact').data('block-url');
    if (!url) {
        if (window.toastr) toastr.warning('No hay conversación activa');
        return;
    }

    var $btn = $(this).prop('disabled', true);

    $.ajax({
        url: url,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    })
    .done(function () {
        $('[data-bv-modal-name="block-contact"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) $('body').css('overflow', '');
        if (window.toastr) toastr.success('Contacto bloqueado.');
        $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
    })
    .fail(function (xhr) {
        var msg = xhr?.responseJSON?.message || 'Error al bloquear el contacto';
        if (window.toastr) toastr.error(msg);
    })
    .always(function () {
        $btn.prop('disabled', false);
    });
});
</script>
@endpush
@endonce

{{-- Modal: Confirmar marcar como spam --}}
<div class="bv-modal" data-bv-modal-name="mark-spam">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box danger"><i class="far fa-circle-xmark"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.mark_spam_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.mark_spam_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <div class="bv-warn-box">
                <div class="bv-warn-box__body">
                    <strong class="bv-warn-box__title">{{ __('helpdesk::helpdesk.inbox.modals.mark_spam_warning_title') }}</strong>
                    <span class="bv-warn-box__desc">{{ __('helpdesk::helpdesk.inbox.modals.mark_spam_warning_desc') }}</span>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-danger-solid" id="bv-mark-spam-confirm">{{ __('helpdesk::helpdesk.inbox.modals.mark_spam_confirm') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '#bv-mark-spam-confirm', function () {
    var url = $('#bv-btn-mark-spam').data('spam-url');
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
        $('[data-bv-modal-name="mark-spam"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) $('body').css('overflow', '');
        if (window.toastr) toastr.success('Conversación marcada como spam.');
        $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
    })
    .fail(function (xhr) {
        var msg = xhr?.responseJSON?.message || 'Error al marcar como spam';
        if (window.toastr) toastr.error(msg);
    })
    .always(function () {
        $btn.prop('disabled', false);
    });
});
</script>
@endpush
@endonce

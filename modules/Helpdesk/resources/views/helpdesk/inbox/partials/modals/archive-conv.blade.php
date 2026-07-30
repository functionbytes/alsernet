{{-- Modal: Archivar / Eliminar conversación (#08 ve-archive) --}}
<div class="bv-modal" data-bv-modal-name="archive-conv">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box danger"><i class="fas fa-trash"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.archive_conv_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.archive_conv_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body bv-x11">

            <div class="bv-modal-danger-icon">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <p class="bv-modal-danger-text">
                {{ __('helpdesk::helpdesk.inbox.modals.archive_conv_warning_1') }} <strong>{{ __('helpdesk::helpdesk.inbox.modals.archive_conv_warning_strong') }}</strong>. {{ __('helpdesk::helpdesk.inbox.modals.archive_conv_warning_2') }}
            </p>
            <div class="bv-warn-box bv-x12">
                <div class="bv-warn-box__body">
                    <i class="fas fa-lightbulb bv-x13"></i>
                    {{ __('helpdesk::helpdesk.inbox.modals.archive_conv_hint_1') }} <strong>{{ __('helpdesk::helpdesk.inbox.modals.archive_conv_hint_strong') }}</strong>? {{ __('helpdesk::helpdesk.inbox.modals.archive_conv_hint_2') }}
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-danger" id="bv-archive-conv-delete">{{ __('helpdesk::helpdesk.inbox.modals.archive_conv_delete_permanently') }}</button>
            <button class="btn-secondary" id="bv-archive-conv-archive">{{ __('helpdesk::helpdesk.inbox.modals.archive_conv_archive_instead') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('click', '#bv-archive-conv-archive', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/archive',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('archive-conv');
            if (window.toastr) { toastr.success('Conversación archivada'); }
            $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al archivar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '#bv-archive-conv-delete', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('archive-conv');
            if (window.toastr) { toastr.success('Conversación eliminada'); }
            $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al eliminar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

}(window.jQuery));
</script>
@endpush
@endonce

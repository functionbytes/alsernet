{{-- Modal: Archivar / Eliminar conversación (#08 ve-archive) --}}
<div class="bv-modal" data-bv-modal-name="archive-conv">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box danger"><i class="fas fa-trash"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CONFIRMACIÓN</span>
                <div class="bv-modal-title">Eliminar conversación</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body" style="text-align:center">

            <div class="bv-modal-danger-icon">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <p class="bv-modal-danger-text">
                Esta acción <strong>no se puede deshacer</strong>. Se eliminarán todos los mensajes, adjuntos y registros de esta conversación.
            </p>
            <div class="bv-warn-box" style="text-align:left;margin-top:12px">
                <div class="bv-warn-box__body">
                    <i class="fas fa-lightbulb" style="color:var(--warning,#fec90f);margin-right:6px"></i>
                    ¿Prefieres <strong>archivar</strong>? La conversación quedará oculta pero los datos se conservan.
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-danger" id="bv-archive-conv-delete">Eliminar definitivamente</button>
            <button class="btn-secondary" id="bv-archive-conv-archive">Archivar en su lugar</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
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

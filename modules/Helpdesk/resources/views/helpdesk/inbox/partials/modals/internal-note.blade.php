{{-- Modal: Nota interna avanzada (#71 ve-internal-note) --}}
<div class="bv-modal" data-bv-modal-name="internal-note">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-note-sticky"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · NOTA INTERNA</span>
                <div class="bv-modal-title">Crear nota para el equipo</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Mencionar a <span class="bv-form-hint">@ para agentes</span></label>
                <input id="internalNoteMentions" type="text" class="bv-form-input" placeholder="@nombre.agente" autocomplete="off">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Nota <span class="bv-req">*</span></label>
                <textarea id="internalNoteText" class="bv-form-input" rows="4" placeholder="Escribe la nota…"></textarea>
            </div>

            <div class="bv-frow">
                <div class="bv-form-field">
                    <label class="bv-form-label">Prioridad</label>
                    <select id="internalNotePriority" class="bv-form-input">
                        <option value="info">Informativa</option>
                        <option value="important" selected>Importante</option>
                        <option value="critical">Crítica</option>
                    </select>
                </div>
                <div class="bv-form-field">
                    <label class="bv-form-label">Caduca</label>
                    <select id="internalNoteExpiry" class="bv-form-input">
                        <option value="">No caduca</option>
                        <option value="7" selected>En 7 días</option>
                        <option value="30">En 30 días</option>
                    </select>
                </div>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="internalNotePinned" checked>
                Anclar en la parte superior del thread
            </label>
            <label class="bv-check">
                <input type="checkbox" id="internalNotePush">
                Enviar notificación push al equipo
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-internal-note-save">Guardar nota</button>
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

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'internal-note') { return; }
        $('#internalNoteText').val('').focus();
        $('#internalNoteMentions').val('');
        $('#internalNotePriority').val('important');
        $('#internalNoteExpiry').val('7');
        $('#internalNotePinned').prop('checked', true);
        $('#internalNotePush').prop('checked', false);
    });

    $(document).on('click', '#bv-internal-note-save', function () {
        var text = $('#internalNoteText').val().trim();
        if (!text) {
            if (window.toastr) { toastr.warning('La nota no puede estar vacía'); }
            $('#internalNoteText').focus();
            return;
        }
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/notes',
            method: 'POST',
            data: {
                content:    text,
                mentions:   $('#internalNoteMentions').val().trim(),
                priority:   $('#internalNotePriority').val(),
                expires_in: $('#internalNoteExpiry').val(),
                pinned:     $('#internalNotePinned').is(':checked') ? 1 : 0,
                push_team:  $('#internalNotePush').is(':checked') ? 1 : 0,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('internal-note');
            if (window.toastr) { toastr.success('Nota guardada'); }
            $(document).trigger('bv:note:created', [convId, resp]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al guardar la nota';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Guardar nota');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce

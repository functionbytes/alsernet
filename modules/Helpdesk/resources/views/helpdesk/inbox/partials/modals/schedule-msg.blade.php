{{-- Modal: Programar mensaje (#68 ve-schedule-msg) --}}
<div class="bv-modal" data-bv-modal-name="schedule-msg">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-paper-plane"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">COMPOSER · PROGRAMAR</span>
                <div class="bv-modal-title">Programar envío</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Mensaje <span class="bv-req">*</span></label>
                <textarea id="scheduleMsgText" class="bv-form-input" rows="3" placeholder="Escribe el mensaje a programar…" style="resize:vertical"></textarea>
            </div>

            <div class="bv-frow" style="display:flex;gap:10px">
                <div class="bv-form-field" style="flex:1">
                    <label class="bv-form-label">Fecha <span class="bv-req">*</span></label>
                    <input id="scheduleMsgDate" type="date" class="bv-form-input">
                </div>
                <div class="bv-form-field" style="flex:1">
                    <label class="bv-form-label">Hora <span class="bv-req">*</span></label>
                    <input id="scheduleMsgTime" type="time" class="bv-form-input" value="09:00">
                </div>
            </div>

            <label class="bv-check" style="margin-bottom:10px">
                <input type="checkbox" id="scheduleMsgRepeat">
                Repetir envío automáticamente
            </label>

            {{-- Mensajes programados --}}
            <div class="bv-form-field">
                <div class="bv-form-label" style="margin-bottom:6px">Programados pendientes <span class="bv-chip-id" id="scheduledCount" style="display:none"></span></div>
                <div id="scheduledList" style="display:flex;flex-direction:column;gap:5px">
                    <div class="bv-cv-loading-msg" style="padding:10px"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-schedule-msg-go">Programar envío</button>
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

    function setDefaultDate() {
        var d = new Date();
        d.setDate(d.getDate() + 1);
        var yyyy = d.getFullYear();
        var mm   = String(d.getMonth() + 1).padStart(2, '0');
        var dd   = String(d.getDate()).padStart(2, '0');
        $('#scheduleMsgDate').val(yyyy + '-' + mm + '-' + dd);
    }

    function loadScheduled(convId) {
        if (!convId) {
            $('#scheduledList').html('<div style="font-size:12px;color:var(--bv-text-muted,#6b7280)">Selecciona una conversación para ver mensajes programados.</div>');
            return;
        }
        $('#scheduledList').html('<div class="bv-cv-loading-msg" style="padding:10px"><i class="fas fa-spinner fa-spin"></i></div>');
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/messages/scheduled',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var items = resp.data || resp.messages || resp || [];
            if (!items.length) {
                $('#scheduledCount').hide();
                $('#scheduledList').html('<div style="font-size:12px;color:var(--bv-text-muted,#6b7280)">Sin mensajes programados pendientes.</div>');
                return;
            }
            $('#scheduledCount').text(items.length).show();
            var html = items.map(function (m) {
                var preview = (m.content || m.body || '').slice(0, 60) + (m.content && m.content.length > 60 ? '…' : '');
                return '<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 8px;background:var(--bv-bg-subtle,#f9fafb);border-radius:5px;font-size:11.5px">' +
                    '<div><span style="font-weight:600;color:var(--bv-text,#111827)">' + (m.scheduled_at || m.send_at || '') + '</span>' +
                    ' <span style="color:var(--bv-text-muted,#6b7280)">·</span>' +
                    ' <span style="color:var(--bv-text-muted,#6b7280)">' + preview + '</span></div>' +
                    '<button class="bv-sched-cancel" data-sched-id="' + m.id + '" style="background:none;border:none;cursor:pointer;color:var(--bv-text-muted,#6b7280);font-size:11px"><i class="fas fa-xmark"></i></button>' +
                    '</div>';
            }).join('');
            $('#scheduledList').html(html);
        }).fail(function () {
            $('#scheduledList').html('<div style="font-size:12px;color:var(--bv-text-muted,#6b7280)">Error al cargar.</div>');
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'schedule-msg') { return; }
        setDefaultDate();
        $('#scheduleMsgText').val('').focus();
        $('#scheduleMsgRepeat').prop('checked', false);
        loadScheduled(getConvId());
    });

    $(document).on('click', '.bv-sched-cancel', function () {
        var id = $(this).data('sched-id');
        var $el = $(this).closest('div');
        $.ajax({
            url: '/panel/helpdesk/conversations/scheduled-messages/' + id,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            $el.slideUp(150, function () { $(this).remove(); });
        }).fail(function () {
            if (window.toastr) { toastr.error('Error al cancelar'); }
        });
    });

    $(document).on('click', '#bv-schedule-msg-go', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var msg  = $('#scheduleMsgText').val().trim();
        var date = $('#scheduleMsgDate').val();
        var time = $('#scheduleMsgTime').val();
        if (!msg) { if (window.toastr) { toastr.warning('Escribe el mensaje'); } return; }
        if (!date || !time) { if (window.toastr) { toastr.warning('Especifica fecha y hora'); } return; }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Programando…');
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/messages/scheduled',
            method: 'POST',
            data: {
                content:    msg,
                send_at:    date + 'T' + time + ':00',
                repeat:     $('#scheduleMsgRepeat').is(':checked') ? 1 : 0,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('schedule-msg');
            if (window.toastr) { toastr.success('Mensaje programado correctamente'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al programar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Programar envío');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce

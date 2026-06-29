{{-- Modal: Silenciar notificaciones del chat (#51 ve-mute-chat) --}}
<div class="bv-modal" data-bv-modal-name="mute-chat">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box bv-modal-icon-box--danger"><i class="fas fa-bell-slash"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · NOTIFICACIONES</span>
                <div class="bv-modal-title">Silenciar notificaciones</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-info-table" id="muteInfoTable">
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">Cliente</span>
                    <span class="bv-info-table__v" id="muteCustomerName">—</span>
                </div>
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">Canal</span>
                    <span class="bv-info-table__v" id="muteChannel">—</span>
                </div>
            </div>

            <div class="bv-warn-box">
                <div class="bv-warn-box__body">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div><b>No recibirás notificaciones de esta conversación.</b> Los mensajes seguirán llegando pero sin alertas. Puedes reactivar las notificaciones desde la ficha del cliente.</div>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label" style="text-transform:uppercase;font-size:9.5px;letter-spacing:.06em">Silenciar durante</label>
                <div class="bv-opt-list" id="muteDurationList">
                    <button type="button" class="bv-opt on" data-mute-duration="60">
                        <div class="bv-opt__body"><span class="bv-opt__t">1 hora</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-mute-duration="480">
                        <div class="bv-opt__body"><span class="bv-opt__t">8 horas</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-mute-duration="1440">
                        <div class="bv-opt__body"><span class="bv-opt__t">24 horas</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-mute-duration="0">
                        <div class="bv-opt__body"><span class="bv-opt__t">Hasta que reactive manualmente</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-danger" id="bv-mute-confirm">Silenciar notificaciones</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _muteDuration = 60;

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'mute-chat') { return; }
        _muteDuration = 60;
        $('#muteDurationList .bv-opt').removeClass('on');
        $('#muteDurationList .bv-opt[data-mute-duration="60"]').addClass('on');

        var customerName = $('[data-customer-name]').first().attr('data-customer-name') || '—';
        var channel      = $('[data-conversation-channel]').first().attr('data-conversation-channel') || '—';
        $('#muteCustomerName').text(customerName);
        $('#muteChannel').text(channel);
    });

    $(document).on('click', '#muteDurationList .bv-opt', function () {
        $('#muteDurationList .bv-opt').removeClass('on');
        $(this).addClass('on');
        _muteDuration = parseInt($(this).data('mute-duration'), 10);
    });

    $(document).on('click', '#bv-mute-confirm', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Silenciando…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/mute',
            method: 'POST',
            data: { duration_minutes: _muteDuration },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('mute-chat');
            if (window.toastr) { toastr.success('Notificaciones silenciadas'); }
            $(document).trigger('bv:conversation:muted', [convId, _muteDuration]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al silenciar notificaciones';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Silenciar notificaciones');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce

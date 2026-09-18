/*!
 * Helpdesk · modal "mute-chat" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/mute-chat.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
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

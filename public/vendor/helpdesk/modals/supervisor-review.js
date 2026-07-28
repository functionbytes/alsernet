/*!
 * Helpdesk · modal "{name}" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/{name}.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';

    var _supRevType = 'approve_response';

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'supervisor-review') { return; }
        _supRevType = 'approve_response';
        $('#supRevTypeList .bv-opt').removeClass('on');
        $('#supRevTypeList .bv-opt[data-rev-type="approve_response"]').addClass('on');
        $('#supRevComment').val('').focus();

        var convId = getConvId();
        $('#supRevConvRef').text(convId ? '#' + convId : '—');
        $('#supRevUserRole').text($('[data-user-role]').first().attr('data-user-role') || '—');
    });

    $(document).on('click', '#supRevTypeList .bv-opt', function () {
        $('#supRevTypeList .bv-opt').removeClass('on');
        $(this).addClass('on');
        _supRevType = $(this).data('rev-type');
    });

    $(document).on('click', '#bv-sup-rev-submit', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/supervisor-review',
            method: 'POST',
            data: {
                review_type: _supRevType,
                comment:     $('#supRevComment').val().trim(),
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('supervisor-review');
            if (window.toastr) { toastr.success('Solicitud enviada al supervisor'); }
            $(document).trigger('bv:supervisor:review:requested', [convId, _supRevType]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al enviar la solicitud';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Enviar solicitud');
        });
    });

}(window.jQuery));

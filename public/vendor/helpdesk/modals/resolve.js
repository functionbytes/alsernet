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

    var _resolveScore = 5;

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'resolve') { return; }
        $('#resolveNotes').val('').focus();
        _resolveScore = 5;
        $('#bvRatingRow .bv-rating-btn').removeClass('on');
        $('#bvRatingRow .bv-rating-btn[data-score="5"]').addClass('on');

        var email = $('[data-customer-email]').first().attr('data-customer-email') || '';
        if (email) {
            $('#resolveEmailAddr').text(email);
            $('#resolveEmailHint').show();
        } else {
            $('#resolveEmailHint').hide();
        }
    });

    $(document).on('click', '#bvRatingRow .bv-rating-btn', function () {
        $('#bvRatingRow .bv-rating-btn').removeClass('on');
        $(this).addClass('on');
        _resolveScore = parseInt($(this).data('score'), 10);
    });

    function doResolve(sendCsat) {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var notes = $('#resolveNotes').val().trim();
        var $btn = sendCsat ? $('#bv-resolve-with-csat') : $('#bv-resolve-without-csat');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Resolviendo…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/resolve',
            method: 'POST',
            data: {
                notes:           notes,
                final_status:    $('#resolveFinalStatus').val(),
                category:        $('#resolveCategory').val(),
                send_csat:       sendCsat ? 1 : 0,
                csat_score:      sendCsat ? _resolveScore : null,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('resolve');
            if (window.toastr) { toastr.success('Conversación resuelta'); }
            $(document).trigger('bv:conversation:resolved', [convId]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al resolver la conversación';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    $(document).on('click', '#bv-resolve-with-csat', function () { doResolve(true); });
    $(document).on('click', '#bv-resolve-without-csat', function () { doResolve(false); });

}(window.jQuery));

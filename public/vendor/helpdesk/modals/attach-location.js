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
$(document).on('click', '[data-bv-modal-name="attach-location"] .bv-loc-tab', function () {
    $('[data-bv-modal-name="attach-location"] .bv-loc-tab').removeClass('on');
    $(this).addClass('on');
    var type = $(this).data('loc-type');
    $('#attach-location-search-wrap').toggleClass('bv-hidden', type !== 'search');
    $('#attach-location-saved').toggleClass('bv-hidden', type !== 'saved');
});

$(document).on('click', '[data-bv-modal-name="attach-location"] .bv-loc-opt', function () {
    $('[data-bv-modal-name="attach-location"] .bv-loc-opt').removeClass('on');
    $(this).addClass('on');
});

$(document).on('click', '#attach-location-send', function () {
    var $btn = $(this);
    var convId = $('.bv-composer').data('bv-conversation-id');
    if (!convId) { toastr.error('No hay conversación seleccionada'); return; }

    var activeType = $('[data-bv-modal-name="attach-location"] .bv-loc-tab.on').data('loc-type') || 'saved';
    var payload = { type: activeType };

    if (activeType === 'current') {
        if (!navigator.geolocation) { toastr.error('Geolocalización no disponible'); return; }
        $btn.prop('disabled', true).text('Obteniendo ubicación…');
        navigator.geolocation.getCurrentPosition(function (pos) {
            payload.lat = pos.coords.latitude;
            payload.lng = pos.coords.longitude;
            sendLocation(convId, payload, $btn);
        }, function () {
            toastr.error('No se pudo obtener la ubicación');
            $btn.prop('disabled', false).text('Enviar ubicación');
        });
        return;
    }

    if (activeType === 'search') {
        payload.address = ($('#attach-location-search').val() || '').trim();
        if (!payload.address) { toastr.warning('Escribe una dirección'); return; }
    }

    if (activeType === 'saved') {
        var $opt = $('[data-bv-modal-name="attach-location"] .bv-loc-opt.on');
        payload.saved_id = $opt.data('loc-id') || '';
        payload.address  = $opt.find('.s').text().trim();
    }

    $btn.prop('disabled', true);
    sendLocation(convId, payload, $btn);
});

function sendLocation(convId, payload, $btn) {
    $.ajax({
        url: '/panel/helpdesk/conversations/' + convId + '/location',
        method: 'POST',
        dataType: 'json',
        data: payload,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    }).done(function (resp) {
        $('[data-bv-modal-name="attach-location"]').removeClass('on');
        $('body').css('overflow', '');
        if (resp?.item && typeof window.appendBubbleToThread === 'function') {
            window.appendBubbleToThread(resp.item, false);
        }
    }).fail(function (xhr) {
        var msg = xhr?.responseJSON?.message || 'No se pudo enviar la ubicación';
        toastr.error(msg);
    }).always(function () {
        $btn.prop('disabled', false).text('Enviar ubicación');
    });
}

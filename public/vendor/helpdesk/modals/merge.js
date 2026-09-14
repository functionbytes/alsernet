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
$(document).on('click', '[data-bv-modal-name="merge"] .bv-opt', function () {
    $('[data-bv-modal-name="merge"] .bv-opt').removeClass('on');
    $(this).addClass('on');
});

$(document).on('input', '#merge-search', function () {
    var q = $(this).val().toLowerCase();
    $('[data-bv-modal-name="merge"] .bv-opt').each(function () {
        var text = $(this).find('.name, .sub').text().toLowerCase();
        $(this).toggle(!q || text.includes(q));
    });
});

// Carga real de candidatos al abrir el modal
$(document).on('click', '[data-bv-modal="merge"]', function () {
    var convId = $('.bv-composer').data('bv-conversation-id');
    if (!convId) return;
    $.get('/panel/helpdesk/conversations/' + convId + '/merge-candidates').done(function (resp) {
        var $list = $('#merge-list');
        if (!Array.isArray(resp?.data) || !resp.data.length) {
            $list.html('<div class="bv-empty-hint">No hay otras conversaciones de este contacto.</div>');
            return;
        }
        $list.empty();
        resp.data.forEach(function (c, i) {
            var icon = c.channel === 'email' ? 'fas fa-envelope'
                : c.channel === 'whatsapp' ? 'fab fa-whatsapp'
                : c.channel === 'facebook' ? 'fab fa-facebook-messenger'
                : c.channel === 'instagram' ? 'fab fa-instagram'
                : 'far fa-comment';
            // El subject/preview vienen del email entrante del cliente (no
            // confiable) — escapar antes de insertar como HTML.
            var $opt = $('<div>', { class: 'bv-opt' + (i === 0 ? ' on' : ''), 'data-conv-id': c.id }).append(
                $('<div>', { class: 'bv-av c' + ((c.id % 8) + 1) + ' bv-av-rounded bv-merge-av' }).append($('<i>', { class: icon })),
                $('<div>', { class: 'body' }).append(
                    $('<div>', { class: 'name' }).text('#' + c.id + ' · ' + (c.subject || 'Sin asunto')),
                    $('<div>', { class: 'sub' }).text((c.preview || '—') + ' · ' + (c.time || ''))
                ),
                $('<span>', { class: 'bv-modal-radio-dot flex-shrink-0' })
            );
            $list.append($opt);
        });
    });
});

// Aplicar fusión
$(document).on('click', '#bv-merge-apply', function () {
    var $btn = $(this);
    var convId = $('.bv-composer').data('bv-conversation-id');
    var targetId = $('#merge-list .bv-opt.on').data('conv-id');
    if (!convId || !targetId) {
        toastr.warning('Selecciona la conversación destino');
        return;
    }
    window.__confirm('¿Fusionar esta conversación con #' + targetId + '? Esta acción es irreversible.', function () {
    $btn.prop('disabled', true);
    $.ajax({
        url: '/panel/helpdesk/conversations/' + convId + '/merge',
        method: 'POST',
        dataType: 'json',
        data: { target_id: targetId },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    }).done(function (resp) {
        var redirect = '/panel/helpdesk/conversations?selected=' + targetId;
        setTimeout(function () { window.location.href = redirect; }, 600);
    }).fail(function (xhr) {
        toastr.error(xhr?.responseJSON?.message || 'No se pudo fusionar');
    }).always(function () { $btn.prop('disabled', false); });
    }); // cierra __confirm
}); // cierra click handler

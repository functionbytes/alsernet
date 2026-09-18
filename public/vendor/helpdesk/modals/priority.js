/*!
 * Helpdesk · modal "priority" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/priority.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function () {
    var slaText = {
        low:    'Al cambiar a Baja se ajustará el SLA a 24 horas. El cliente recibirá atención en el siguiente ciclo.',
        normal: 'Al cambiar a Normal se ajustará el SLA a 8 horas. Atención estándar dentro del horario laboral.',
        high:   'Al cambiar a Alta se ajustará el SLA a 4 horas. Se notificará al agente asignado inmediatamente.',
        urgent: 'Al cambiar a Urgente se ajustará el SLA a 1 hora. Se escalará y notificará al equipo de guardia.'
    };

    function updateCallout(val) {
        var txt = slaText[val] || '';
        $('#prio-callout-text').text(txt);
        $('#prio-callout').toggleClass('show', !!txt);
    }

    $(document).on('click', '[data-bv-modal-name="priority"] .prio-opt', function () {
        $(this).closest('.prio-list').find('.prio-opt').removeClass('on');
        $(this).addClass('on');
        updateCallout($(this).data('bv-value'));
    });

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'priority') { return; }
        var cur = $('.bv-th-pill[data-bv-modal="priority"]').attr('data-bv-value') || 'normal';
        $('[data-bv-modal-name="priority"] .prio-opt').removeClass('on');
        $('[data-bv-modal-name="priority"] .prio-opt[data-bv-value="' + cur + '"]').addClass('on');
        $('#prio-reason').val('');
        updateCallout(cur);
    });
}());

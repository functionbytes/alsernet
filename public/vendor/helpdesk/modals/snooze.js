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
(function() {
    function pad(n) { return String(n).padStart(2, '0'); }
    function fmtTime(d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); }
    function fmtDay(d) { return d.getDate() + ' ' + d.toLocaleString('es', {month: 'short'}); }

    function initTimes() {
        var now = new Date();
        var days = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];

        var t1h = new Date(now.getTime() + 60 * 60 * 1000);
        $('#snzTime1h').text('Reaparece a las ' + fmtTime(t1h));
        $('#snzBadge1h').text(fmtTime(t1h));

        var t4h = new Date(now.getTime() + 4 * 60 * 60 * 1000);
        $('#snzTime4h').text('Reaparece a las ' + fmtTime(t4h));
        $('#snzBadge4h').text(fmtTime(t4h));

        var tom = new Date(now);
        tom.setDate(tom.getDate() + 1);
        $('#snzTimeTom').text(days[tom.getDay()].charAt(0).toUpperCase() + days[tom.getDay()].slice(1) + ' 09:00');
        $('#snzBadgeTom').text('09:00');

        var daysUntilMon = (8 - now.getDay()) % 7 || 7;
        var mon = new Date(now);
        mon.setDate(now.getDate() + daysUntilMon);
        $('#snzTimeWeek').text('Lunes ' + fmtDay(mon) + ' 09:00');
        $('#snzBadgeWeek').text(fmtDay(mon));

        var nextMon = new Date(mon);
        nextMon.setDate(mon.getDate() + 7);
        $('#snzTimeNextWeek').text('Lunes ' + fmtDay(nextMon) + ' 09:00');
        $('#snzBadgeNextWeek').text(fmtDay(nextMon));
    }

    $(document).on('click', '.snz-opt', function() {
        var snz = $(this).data('snz');
        $('.snz-opt').removeClass('on');
        $(this).addClass('on');
        if (snz === 'custom') {
            $('#snzCustomForm').addClass('show');
        } else {
            $('#snzCustomForm').removeClass('show');
        }
    });

    function calcSnoozeUntil(opt) {
        var now = new Date();
        if (opt === '1h')  return new Date(now.getTime() + 60 * 60 * 1000);
        if (opt === '4h')  return new Date(now.getTime() + 4 * 60 * 60 * 1000);
        if (opt === 'tom') {
            var t = new Date(now); t.setDate(t.getDate() + 1); t.setHours(9, 0, 0, 0); return t;
        }
        if (opt === 'week') {
            var w = new Date(now);
            var diff = (8 - w.getDay()) % 7 || 7;
            w.setDate(w.getDate() + diff); w.setHours(9, 0, 0, 0); return w;
        }
        if (opt === 'nextweek') {
            var nw = new Date(now);
            var diff2 = (8 - nw.getDay()) % 7 || 7;
            nw.setDate(nw.getDate() + diff2 + 7); nw.setHours(9, 0, 0, 0); return nw;
        }
        if (opt === 'custom') {
            var d = $('#snzCustomDate').val();
            var ti = $('#snzCustomTime').val();
            if (d && ti) return new Date(d + 'T' + ti);
        }
        return null;
    }

    $(document).on('click', '#snzBtnApply', function() {
        var $btn = $(this);
        var opt = $('.snz-opt.on').data('snz');
        var until = calcSnoozeUntil(opt);
        if (!until || isNaN(until.getTime())) {
            toastr.error('Selecciona una fecha y hora válida');
            return;
        }
        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId) {
            toastr.error('No hay conversación seleccionada');
            return;
        }
        $btn.prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/snooze',
            method: 'POST',
            dataType: 'json',
            data: {
                until: until.toISOString(),
                reopen_on_reply: $('#snzReopenOnReply').is(':checked') ? 1 : 0
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        }).done(function(resp) {
            $('[data-bv-modal-name="snooze"] [data-bv-close]').first().trigger('click');
            if (resp && resp.message) toastr.success(resp.message);
        }).fail(function(xhr) {
            var msg = xhr?.responseJSON?.errors
                ? Object.values(xhr.responseJSON.errors)[0]?.[0]
                : (xhr?.responseJSON?.message || 'No se pudo posponer la conversación');
            toastr.error(msg);
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('bv:modal:open', function(e, name) {
        if (name !== 'snooze') return;
        initTimes();
        $('.snz-opt').removeClass('on');
        $('[data-snz="1h"]').addClass('on');
        $('#snzCustomForm').removeClass('show');
    });
}());

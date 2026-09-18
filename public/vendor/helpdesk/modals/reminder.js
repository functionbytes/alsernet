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

    var _reminderPreset = '30m';

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function tomorrowDate() {
        var d = new Date();
        d.setDate(d.getDate() + 1);
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }

    function resolveRemindAt() {
        var now = new Date();
        if (_reminderPreset === '30m') {
            now.setMinutes(now.getMinutes() + 30);
            return now.toISOString();
        }
        if (_reminderPreset === '2h') {
            now.setHours(now.getHours() + 2);
            return now.toISOString();
        }
        if (_reminderPreset === 'tomorrow') {
            var d = new Date();
            d.setDate(d.getDate() + 1);
            d.setHours(9, 0, 0, 0);
            return d.toISOString();
        }
        if (_reminderPreset === 'custom') {
            var date = $('#reminderDate').val();
            var time = $('#reminderTime').val() || '09:00';
            if (!date) { return null; }
            return date + 'T' + time + ':00';
        }
        return null;
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'reminder') { return; }

        _reminderPreset = '30m';
        $('#reminderTitle').val('').focus();
        $('#reminderNotes').val('');
        $('#reminderEmail').prop('checked', true);
        $('#reminderCustomPanel').hide();
        $('#reminderDate').val(tomorrowDate());
        $('#reminderTime').val('09:00');
        $('#reminderWhenList .bv-opt').removeClass('on');
        $('#reminderWhenList .bv-opt[data-remind-preset="30m"]').addClass('on');

        var convId = getConvId();
        if (convId) {
            $('#reminderConvRef').text('#' + convId);
            var custName = $('[data-customer-name]').first().attr('data-customer-name') || '';
            if (custName) {
                $('#reminderCustomerName').text(custName);
                $('#reminderContext').show();
            } else {
                $('#reminderContext').hide();
            }
        } else {
            $('#reminderContext').hide();
        }
    });

    $(document).on('click', '#reminderWhenList .bv-opt', function () {
        $('#reminderWhenList .bv-opt').removeClass('on');
        $(this).addClass('on');
        _reminderPreset = $(this).data('remind-preset');
        if (_reminderPreset === 'custom') {
            $('#reminderCustomPanel').slideDown(150);
        } else {
            $('#reminderCustomPanel').slideUp(150);
        }
    });

    $(document).on('click', '#bv-reminder-create', function () {
        var title = $('#reminderTitle').val().trim();
        if (!title) {
            if (window.toastr) { toastr.warning('El título es obligatorio'); }
            $('#reminderTitle').focus();
            return;
        }

        var remindAt = resolveRemindAt();
        if (!remindAt) {
            if (window.toastr) { toastr.warning('Especifica una fecha válida'); }
            return;
        }

        var convId = getConvId();
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Creando…');

        $.ajax({
            url: convId ? '/panel/helpdesk/conversations/' + convId + '/reminders' : '/panel/helpdesk/reminders',
            method: 'POST',
            data: {
                title:        title,
                remind_at:    remindAt,
                notes:        $('#reminderNotes').val().trim(),
                email_notify: $('#reminderEmail').is(':checked') ? 1 : 0,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('reminder');
            if (window.toastr) { toastr.success('Recordatorio creado correctamente'); }
            $(document).trigger('bv:reminder:created');
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al crear el recordatorio';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Crear recordatorio');
        });
    });

    $(document).on('keydown', '#reminderTitle', function (e) {
        if (e.key === 'Enter') { $('#bv-reminder-create').trigger('click'); }
    });

}(window.jQuery));

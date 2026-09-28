'use strict';

    // ── Modal 02: Programar envío ─────────────────────────────
    function openScheduleModal(t) {
        function slot(hoursFromNow, atHour) {
            var d = new Date();
            if (atHour != null) {
                d.setHours(atHour, 0, 0, 0);
                if (d <= new Date()) d.setDate(d.getDate() + 1);
            } else {
                d.setHours(d.getHours() + hoursFromNow, 0, 0, 0);
            }
            return d;
        }
        var tomorrow = slot(null, 8);
        var endOfDay = slot(null, 18);
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); };
        var human = function (d) { return pad(d.getDate()) + ' ' + MONTH_SHORT[d.getMonth()] + ' · ' + pad(d.getHours()) + ':' + pad(d.getMinutes()); };

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-clock',
            kicker: TKA.t('kicker_ticket_schedule_send', 'Ticket · programar envío'),
            title: TKA.t('modal_title_schedule_send', 'Programar envío'),
            titleChip: t.ticket_number,
            width: 'md',
            body: '' +
                '<div class="tkt-pick-list">' +
                    '<button type="button" class="tkt-pick" data-slot="' + iso(tomorrow) + '">' +
                        '<span class="av light"><i class="fa-solid fa-mug-hot"></i></span>' +
                        '<span class="who"><span class="n">' + TKA.t('modal_02_tomorrow_morning', 'Mañana por la mañana') + '</span><span class="s">' + human(tomorrow) + '</span></span></button>' +
                    '<button type="button" class="tkt-pick" data-slot="' + iso(endOfDay) + '">' +
                        '<span class="av light"><i class="fa-solid fa-business-time"></i></span>' +
                        '<span class="who"><span class="n">' + TKA.t('modal_02_end_of_day', 'Hoy al final del día') + '</span><span class="s">' + human(endOfDay) + '</span></span></button>' +
                    '<button type="button" class="tkt-pick" id="tkt-sched-custom">' +
                        '<span class="av light"><i class="fa-regular fa-calendar"></i></span>' +
                        '<span class="who"><span class="n">' + TKA.t('modal_02_custom_date', 'Fecha personalizada') + '</span><span class="s">' + TKA.t('modal_02_choose_date_time', 'Elegir día y hora') + '</span></span></button>' +
                '</div>' +
                '<div class="tkt-field-row" id="tkt-sched-custom-row" hidden>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-sched-date">' + TKA.t('modal_02_date_label', 'Fecha') + '</label><input type="date" class="tkt-input" id="tkt-sched-date"></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-sched-time">' + TKA.t('modal_02_time_label', 'Hora') + '</label><input type="time" class="tkt-input" id="tkt-sched-time" value="18:00"></div>' +
                '</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> ' + TKA.t('modal_02_schedule_note', 'Se envía exactamente a la fecha y hora elegidas (hora de tu navegador), sin ajustarse al horario laboral.') + '</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-sched-cancel"' + (composeDraft && composeDraft.cancelIfReplies ? ' checked' : '') + '> ' + TKA.t('modal_02_cancel_if_replies', 'Cancelar si el cliente responde antes') + '</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-sched-confirm">' + TKA.t('modal_02_schedule_btn', 'Programar') + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-sched-cancel-btn">' + TKA.t('cancel', 'Cancelar') + '</button>',
        }));

        function pick(value) {
            composeDraft.scheduledAt = value;
            composeDraft.cancelIfReplies = $('#tkt-sched-cancel').is(':checked');
            closeModal();
            openComposeModal(t);
        }

        $backdrop.on('click', '[data-slot]', function () { pick($(this).data('slot')); });
        $backdrop.on('click', '#tkt-sched-custom', function () {
            document.getElementById('tkt-sched-custom-row').hidden = false;
            $('#tkt-sched-date').trigger('focus');
        });
        $backdrop.on('click', '#tkt-sched-confirm', function () {
            var date = $('#tkt-sched-date').val();
            var time = $('#tkt-sched-time').val() || '18:00';
            if (!date) {
                if (window.toastr) toastr.error(TKA.t('schedule_pick_date_required', 'Elige una fecha o uno de los atajos de arriba'));
                return;
            }
            pick(date + 'T' + time);
        });
        $backdrop.on('click', '#tkt-sched-cancel-btn', function () { closeModal(); openComposeModal(t); });
    }



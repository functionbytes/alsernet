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
    var forms = {
        callback: '#schedFormCallback',
        meeting: '#schedFormMeeting',
        task: '#schedFormTask',
        message: '#schedFormMessage'
    };

    $(document).on('click', '.mv4-sched-opt', function() {
        var type = $(this).data('sched-type');
        $('.mv4-sched-opt').removeClass('on');
        $(this).addClass('on');
        $('#schedTypeHidden').val(type);
        $.each(forms, function(k, sel) { $(sel).hide(); });
        $(forms[type]).show();
    });

    var schedAgentsLoaded = false;

    function loadSchedAgents() {
        if (schedAgentsLoaded) { return; }
        $.ajax({
            url: '/panel/helpdesk/api/agents-autocomplete',
            method: 'GET',
            dataType: 'json',
            data: { include_self: 1 },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function(resp) {
            var agents = resp.agents || resp || [];
            var options = '<option value="">Sin asignar</option>';
            $.each(agents, function(i, a) {
                options += '<option value="' + a.id + '">' + $('<span>').text(a.name).html() + '</option>';
            });
            $('.bv-sched-agents-select').html(options);
            schedAgentsLoaded = true;
        }).fail(function() {
            $('.bv-sched-agents-select').html('<option value="">No disponible</option>');
        });
    }

    $(document).on('bv:modal:open', function(e, name) {
        if (name === 'schedule') { loadSchedAgents(); }
    });

    $(document).on('change', '#schedPlatform', function() {
        var links = {
            meet: 'meet.google.com/abc-defg-hij',
            zoom: 'zoom.us/j/12345678901',
            teams: 'teams.microsoft.com/l/meetup/xyz'
        };
        $('#schedMeetLink').text(links[$(this).val()] || '');
    });

    $(document).on('click', '#bv-sched-save', function () {
        var $btn = $(this);
        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId) { toastr.error('No hay conversación seleccionada'); return; }
        var type = $('#schedTypeHidden').val() || 'callback';

        // Sólo "message" tiene endpoint backend (envío programado).
        // Los otros tipos (callback/meeting/task) los registramos como nota interna por ahora.
        if (type === 'message') {
            var date = $('#schedMessageDate').val();
            var time = $('#schedMessageTime').val();
            var body = ($('#schedMessageBody').val() || '').trim();
            if (!date || !time || !body) {
                toastr.warning('Completa fecha, hora y mensaje');
                return;
            }
            var when = new Date(date + 'T' + time);
            $btn.prop('disabled', true);
            $.ajax({
                url: '/panel/helpdesk/conversations/' + convId + '/messages/scheduled',
                method: 'POST',
                dataType: 'json',
                data: { body: body, scheduled_at: when.toISOString() },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            }).done(function (resp) {
                $('[data-bv-modal-name="schedule"]').removeClass('on');
                $('body').css('overflow', '');
                if (window.toastr) toastr.success('Mensaje programado correctamente.');
            }).fail(function (xhr) {
                var msg = xhr?.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors)[0]?.[0]
                    : (xhr?.responseJSON?.message || 'No se pudo programar');
                toastr.error(msg);
            }).always(function () { $btn.prop('disabled', false); });
            return;
        }

        // Para los otros tipos, registramos como nota interna con metadatos
        var noteText = '';
        if (type === 'callback') {
            noteText = '📞 Llamada agendada — ' + ($('#schedCallbackDate').val() || '') + ' ' + ($('#schedCallbackTime').val() || '') +
                ' al ' + ($('#schedCallbackPhone').val() || '') + '\n' + ($('#schedCallbackNotes').val() || '');
        } else if (type === 'meeting') {
            noteText = '🎥 Videollamada — ' + ($('#schedPlatform').val() || '') + ' · ' + $('#schedMeetLink').text();
        } else if (type === 'task') {
            noteText = '✅ Tarea: ' + ($('#schedTaskTitle').val() || '') +
                '\nFecha límite: ' + ($('#schedTaskDate').val() || '') +
                '\n' + ($('#schedTaskNotes').val() || '');
        }
        if (!noteText.trim()) { toastr.warning('Completa los datos'); return; }
        $btn.prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/messages',
            method: 'POST',
            dataType: 'json',
            data: { body: noteText, is_internal: 1, action: 'send' },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        }).done(function (resp) {
            $('[data-bv-modal-name="schedule"]').removeClass('on');
            $('body').css('overflow', '');
            if (resp?.item && typeof window.appendBubbleToThread === 'function') {
                window.appendBubbleToThread(resp.item, true);
            }
            if (window.toastr) toastr.success('Evento agendado correctamente.');
        }).fail(function (xhr) {
            toastr.error(xhr?.responseJSON?.message || 'No se pudo guardar el evento');
        }).always(function () { $btn.prop('disabled', false); });
    });
}());

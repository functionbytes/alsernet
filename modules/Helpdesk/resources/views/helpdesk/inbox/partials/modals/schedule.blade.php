{{-- Modal: Agendar evento --}}
<div class="bv-modal" data-bv-modal-name="schedule">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fad fa-calendar-plus bv-modal-title-icon"></i> Agendar</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Tipo de evento --}}
            <input type="hidden" id="schedTypeHidden" name="type" value="callback">

            <div class="mv4-sched-type">
                <button class="mv4-sched-opt on" data-sched-type="callback">
                    <i class="fas fa-phone-volume"></i>
                    <div><b>Llamada</b><span>Te recordaremos llamar al cliente</span></div>
                </button>
                <button class="mv4-sched-opt" data-sched-type="meeting">
                    <i class="fas fa-video"></i>
                    <div><b>Videollamada</b><span>Genera link y envía invitación</span></div>
                </button>
                <button class="mv4-sched-opt" data-sched-type="task">
                    <i class="fas fa-list-check"></i>
                    <div><b>Tarea</b><span>Pendiente para el equipo</span></div>
                </button>
                <button class="mv4-sched-opt" data-sched-type="message">
                    <i class="fas fa-clock"></i>
                    <div><b>Mensaje programado</b><span>Envío diferido a una fecha y hora</span></div>
                </button>
            </div>

            {{-- Formulario callback/llamada --}}
            <div class="mv4-sched-form" id="schedFormCallback">
                <div class="row">
                    <label>
                        <span>Fecha</span>
                        <input type="date" id="schedCallbackDate" value="{{ date('Y-m-d', strtotime('+2 days')) }}">
                    </label>
                    <label>
                        <span>Hora</span>
                        <input type="time" id="schedCallbackTime" value="10:30">
                    </label>
                    <label>
                        <span>Duración</span>
                        <select>
                            <option>15 minutos</option>
                            <option selected>30 minutos</option>
                            <option>45 minutos</option>
                            <option>1 hora</option>
                        </select>
                    </label>
                </div>
                <div class="row">
                    <label class="bv-sched-label-flex">
                        <span>Teléfono</span>
                        <input type="tel" id="schedCallbackPhone" value="+34 612 345 678">
                    </label>
                    <label class="bv-sched-label-flex">
                        <span>Asignado a</span>
                        <select id="schedCallbackAgent" class="bv-sched-agents-select">
                            <option value="">Cargando…</option>
                        </select>
                    </label>
                </div>
                <label>
                    <span>Notas</span>
                    <textarea id="schedCallbackNotes" placeholder="Agenda o puntos a tratar en la llamada…"></textarea>
                </label>
                <label class="check">
                    <input type="checkbox" checked> Recordarme 15 min antes
                </label>
            </div>

            {{-- Formulario videollamada --}}
            <div class="mv4-sched-form bv-hidden" id="schedFormMeeting">
                <div class="row">
                    <label>
                        <span>Fecha</span>
                        <input type="date" value="{{ date('Y-m-d', strtotime('+2 days')) }}">
                    </label>
                    <label>
                        <span>Hora</span>
                        <input type="time" value="11:00">
                    </label>
                </div>
                <div class="row">
                    <label class="bv-sched-label-flex">
                        <span>Plataforma</span>
                        <select id="schedPlatform">
                            <option value="meet">Google Meet</option>
                            <option value="zoom">Zoom</option>
                            <option value="teams">Microsoft Teams</option>
                        </select>
                    </label>
                </div>
                <div>
                    <label><span>Link generado</span></label>
                    <div class="bv-meet-link-row">
                        <i class="fas fa-link bv-meet-link-icon"></i>
                        <span id="schedMeetLink" class="bv-meet-link-text">meet.google.com/abc-defg-hij</span>
                        <button class="bv-meet-copy-btn"><i class="far fa-copy"></i></button>
                    </div>
                </div>
                <label class="check">
                    <input type="checkbox" checked> Enviar invitación al cliente
                </label>
            </div>

            {{-- Formulario tarea --}}
            <div class="mv4-sched-form bv-hidden" id="schedFormTask">
                <label class="bv-sched-label-flex">
                    <span>Título de la tarea</span>
                    <input type="text" id="schedTaskTitle" placeholder="Ej: Hacer seguimiento del reembolso">
                </label>
                <label>
                    <span>Descripción</span>
                    <textarea id="schedTaskNotes" placeholder="Detalles de la tarea…"></textarea>
                </label>
                <div class="row">
                    <label>
                        <span>Fecha límite</span>
                        <input type="date" id="schedTaskDate" value="{{ date('Y-m-d', strtotime('+3 days')) }}">
                    </label>
                    <label class="bv-sched-label-flex">
                        <span>Asignar a</span>
                        <select id="schedTaskAgent" class="bv-sched-agents-select">
                            <option value="">Cargando…</option>
                        </select>
                    </label>
                </div>
            </div>

            {{-- Formulario mensaje programado --}}
            <div class="mv4-sched-form bv-hidden" id="schedFormMessage">
                <label>
                    <span>Mensaje</span>
                    <textarea id="schedMessageBody" rows="4" placeholder="Escribe el mensaje que se enviará automáticamente…"></textarea>
                </label>
                <div class="row">
                    <label>
                        <span>Fecha de envío</span>
                        <input type="date" id="schedMessageDate" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </label>
                    <label>
                        <span>Hora</span>
                        <input type="time" id="schedMessageTime" value="09:00">
                    </label>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sched-save">Crear evento</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
        }).fail(function (xhr) {
            toastr.error(xhr?.responseJSON?.message || 'No se pudo guardar el evento');
        }).always(function () { $btn.prop('disabled', false); });
    });
}());
</script>
@endpush
@endonce

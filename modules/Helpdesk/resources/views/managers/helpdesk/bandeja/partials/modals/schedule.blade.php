{{-- Modal: Agendar evento --}}
<div class="bv-modal" data-bv-modal-name="schedule">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Agendar</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Tipo de evento --}}
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
                        <input type="date" value="{{ date('Y-m-d', strtotime('+2 days')) }}">
                    </label>
                    <label>
                        <span>Hora</span>
                        <input type="time" value="10:30">
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
                    <label style="flex:1">
                        <span>Teléfono</span>
                        <input type="tel" value="+34 612 345 678">
                    </label>
                    <label style="flex:1">
                        <span>Asignado a</span>
                        <select>
                            <option>María López (yo)</option>
                            <option>Carlos Ruiz</option>
                            <option>Ana Torres</option>
                        </select>
                    </label>
                </div>
                <label>
                    <span>Notas</span>
                    <textarea placeholder="Agenda o puntos a tratar en la llamada…"></textarea>
                </label>
                <label class="check">
                    <input type="checkbox" checked> Recordarme 15 min antes
                </label>
            </div>

            {{-- Formulario videollamada --}}
            <div class="mv4-sched-form" id="schedFormMeeting" style="display:none">
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
                    <label style="flex:1">
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
                    <div style="margin-top:5px;padding:8px 10px;background:var(--bv-bg-subtle);border:1px solid var(--bv-border);border-radius:7px;font-size:12px;display:flex;align-items:center;gap:8px">
                        <i class="fas fa-link" style="color:var(--bv-text-muted)"></i>
                        <span id="schedMeetLink" style="flex:1;color:var(--bv-text-muted);font-family:monospace">meet.google.com/abc-defg-hij</span>
                        <button style="font-size:11px;border:0;background:transparent;cursor:pointer;color:var(--bv-text-muted)"><i class="far fa-copy"></i></button>
                    </div>
                </div>
                <label class="check">
                    <input type="checkbox" checked> Enviar invitación al cliente
                </label>
            </div>

            {{-- Formulario tarea --}}
            <div class="mv4-sched-form" id="schedFormTask" style="display:none">
                <label style="flex:1">
                    <span>Título de la tarea</span>
                    <input type="text" placeholder="Ej: Hacer seguimiento del reembolso">
                </label>
                <label>
                    <span>Descripción</span>
                    <textarea placeholder="Detalles de la tarea…"></textarea>
                </label>
                <div class="row">
                    <label>
                        <span>Fecha límite</span>
                        <input type="date" value="{{ date('Y-m-d', strtotime('+3 days')) }}">
                    </label>
                    <label style="flex:1">
                        <span>Asignar a</span>
                        <select>
                            <option>María López (yo)</option>
                            <option>Carlos Ruiz</option>
                            <option>Ana Torres</option>
                        </select>
                    </label>
                </div>
            </div>

            {{-- Formulario mensaje programado --}}
            <div class="mv4-sched-form" id="schedFormMessage" style="display:none">
                <label>
                    <span>Mensaje</span>
                    <textarea rows="4" placeholder="Escribe el mensaje que se enviará automáticamente…"></textarea>
                </label>
                <div class="row">
                    <label>
                        <span>Fecha de envío</span>
                        <input type="date" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </label>
                    <label>
                        <span>Hora</span>
                        <input type="time" value="09:00">
                    </label>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary" data-bv-close>Cancelar</button>
            <div style="margin-left:auto">
                <button class="btn-primary">
                    <i class="fas fa-check"></i> Crear evento
                </button>
            </div>
        </div>
    </div>
</div>

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
        $.each(forms, function(k, sel) { $(sel).hide(); });
        $(forms[type]).show();
    });

    $(document).on('change', '#schedPlatform', function() {
        var links = {
            meet: 'meet.google.com/abc-defg-hij',
            zoom: 'zoom.us/j/12345678901',
            teams: 'teams.microsoft.com/l/meetup/xyz'
        };
        $('#schedMeetLink').text(links[$(this).val()] || '');
    });
}());
</script>

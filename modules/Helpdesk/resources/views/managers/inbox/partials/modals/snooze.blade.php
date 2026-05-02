{{-- Modal: Posponer conversación --}}
<div class="bv-modal" data-bv-modal-name="snooze">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Chat · Bandeja</span>
                <div class="bv-modal-title"><i class="far fa-clock bv-modal-title-icon"></i> Posponer conversación</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::managers.inbox.partials.modals._context-card')

            <div class="mv4-snz-grid">
                <button class="mv4-snz-opt on" data-snz="1h">
                    <b>1 hora</b>
                    <span id="snzTime1h">Reaparece a las --:--</span>
                </button>
                <button class="mv4-snz-opt" data-snz="4h">
                    <b>4 horas</b>
                    <span id="snzTime4h">Reaparece a las --:--</span>
                </button>
                <button class="mv4-snz-opt" data-snz="tom">
                    <b>Mañana</b>
                    <span id="snzTimeTom">Mañana 09:00</span>
                </button>
                <button class="mv4-snz-opt" data-snz="week">
                    <b>Esta semana</b>
                    <span id="snzTimeWeek">Lunes próximo 09:00</span>
                </button>
                <button class="mv4-snz-opt" data-snz="nextweek">
                    <b>La próxima semana</b>
                    <span id="snzTimeNextWeek">Lunes siguiente 09:00</span>
                </button>
                <button class="mv4-snz-opt" data-snz="custom">
                    <b>Personalizar</b>
                    <span>Elige fecha y hora exacta</span>
                </button>
            </div>

            {{-- Custom datetime picker --}}
            <div id="snzCustomForm" class="bv-hidden bv-mt-12">
                <div class="mv4-sched-form bv-snz-custom-form">
                    <div class="row">
                        <label>
                            <span>Fecha</span>
                            <input type="date" id="snzCustomDate" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </label>
                        <label>
                            <span>Hora</span>
                            <input type="time" id="snzCustomTime" value="09:00">
                        </label>
                    </div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="snzBtnApply">Posponer</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    function pad(n) { return String(n).padStart(2, '0'); }

    function initTimes() {
        var now = new Date();

        var t1h = new Date(now.getTime() + 60 * 60 * 1000);
        $('#snzTime1h').text('Reaparece a las ' + pad(t1h.getHours()) + ':' + pad(t1h.getMinutes()));

        var t4h = new Date(now.getTime() + 4 * 60 * 60 * 1000);
        $('#snzTime4h').text('Reaparece a las ' + pad(t4h.getHours()) + ':' + pad(t4h.getMinutes()));

        var days = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
        var tom = new Date(now);
        tom.setDate(tom.getDate() + 1);
        $('#snzTimeTom').text('Mañana ' + days[tom.getDay()] + ' 09:00');

        var daysUntilMon = (8 - now.getDay()) % 7 || 7;
        var mon = new Date(now);
        mon.setDate(mon.getDate() + daysUntilMon);
        $('#snzTimeWeek').text('Lunes ' + mon.getDate() + ' ' + mon.toLocaleString('es', {month:'short'}) + ' 09:00');

        var nextMon = new Date(mon);
        nextMon.setDate(nextMon.getDate() + 7);
        $('#snzTimeNextWeek').text('Lunes ' + nextMon.getDate() + ' ' + nextMon.toLocaleString('es', {month:'short'}) + ' 09:00');
    }

    $(document).on('click', '.mv4-snz-opt', function() {
        var snz = $(this).data('snz');
        $('.mv4-snz-opt').removeClass('on');
        $(this).addClass('on');
        $('#snzCustomForm').toggle(snz === 'custom');
    });

    function calcSnoozeUntil(opt) {
        var now = new Date();
        if (opt === '1h') return new Date(now.getTime() + 60 * 60 * 1000);
        if (opt === '4h') return new Date(now.getTime() + 4 * 60 * 60 * 1000);
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
            var t = $('#snzCustomTime').val();
            if (d && t) return new Date(d + 'T' + t);
        }
        return null;
    }

    $(document).on('click', '#snzBtnApply', function() {
        var $btn = $(this);
        var opt = $('.mv4-snz-opt.on').data('snz');
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
            data: { snoozed_until: until.toISOString() },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        }).done(function (resp) {
            $('[data-bv-modal-name="snooze"]').removeClass('on');
            $('body').css('overflow', '');
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.errors
                ? Object.values(xhr.responseJSON.errors)[0]?.[0]
                : (xhr?.responseJSON?.message || 'No se pudo posponer la conversación');
            toastr.error(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('bv:modal:open', function(e, name) {
        if (name !== 'snooze') return;
        initTimes();
        $('.mv4-snz-opt').removeClass('on');
        $('[data-snz="1h"]').addClass('on');
        $('#snzCustomForm').hide();
    });
}());
</script>
@endpush

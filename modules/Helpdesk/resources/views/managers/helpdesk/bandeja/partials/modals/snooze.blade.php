{{-- Modal: Posponer conversación --}}
<div class="bv-modal" data-bv-modal-name="snooze">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Posponer conversación</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

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
            <div id="snzCustomForm" style="display:none;margin-top:12px">
                <div class="mv4-sched-form" style="padding-top:12px;border-top:1px solid var(--bv-border)">
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
            <button class="btn-secondary" data-bv-close>Cancelar</button>
            <div style="margin-left:auto">
                <button class="btn-primary" id="snzBtnApply">
                    <i class="fas fa-check"></i> Posponer
                </button>
            </div>
        </div>
    </div>
</div>

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

    $(document).on('click', '#snzBtnApply', function() {
        var opt = $('.mv4-snz-opt.on').data('snz');
        var msg = 'Conversación pospuesta';
        if (opt === 'custom') {
            var d = $('#snzCustomDate').val();
            var t = $('#snzCustomTime').val();
            if (d && t) { msg = 'Pospuesta hasta ' + d + ' ' + t; }
        }
        window.BvModal && window.BvModal.close('snooze');
        toastr.info(msg);
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

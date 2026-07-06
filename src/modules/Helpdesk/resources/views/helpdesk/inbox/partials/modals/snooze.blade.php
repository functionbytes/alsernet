{{-- Modal: Posponer conversación --}}
<div class="bv-modal" data-bv-modal-name="snooze">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="far fa-clock"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.snooze_eyebrow') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.snooze_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <p class="snz-hint">{{ __('helpdesk::helpdesk.inbox.modals.snooze_hint') }}</p>

            <div class="snz-list">
                <button class="snz-opt on" data-snz="1h">
                    <i class="fa-solid fa-stopwatch snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_1h') }}</b>
                        <span id="snzTime1h">{{ __('helpdesk::helpdesk.inbox.modals.snooze_reappears_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadge1h">--:--</span>
                </button>
                <button class="snz-opt" data-snz="4h">
                    <i class="fa-regular fa-clock snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_4h') }}</b>
                        <span id="snzTime4h">{{ __('helpdesk::helpdesk.inbox.modals.snooze_reappears_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadge4h">--:--</span>
                </button>
                <button class="snz-opt" data-snz="tom">
                    <i class="fa-solid fa-sun snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_tomorrow') }}</b>
                        <span id="snzTimeTom">{{ __('helpdesk::helpdesk.inbox.modals.snooze_tomorrow_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadgeTom">09:00</span>
                </button>
                <button class="snz-opt" data-snz="week">
                    <i class="fa-solid fa-calendar-week snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_week') }}</b>
                        <span id="snzTimeWeek">{{ __('helpdesk::helpdesk.inbox.modals.snooze_week_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadgeWeek">--</span>
                </button>
                <button class="snz-opt" data-snz="nextweek">
                    <i class="fa-solid fa-calendar-days snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_next_week') }}</b>
                        <span id="snzTimeNextWeek">{{ __('helpdesk::helpdesk.inbox.modals.snooze_next_week_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadgeNextWeek">--</span>
                </button>
                <button class="snz-opt" data-snz="custom">
                    <i class="fa-solid fa-calendar-plus snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_custom') }}</b>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.snooze_custom_desc') }}</span>
                    </div>
                </button>
            </div>

            <div id="snzCustomForm" class="snz-custom">
                <div class="snz-custom-row">
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.date') }}</span>
                        <input type="date" id="snzCustomDate" class="bv-finput" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </label>
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.snooze_time_label') }}</span>
                        <input type="time" id="snzCustomTime" class="bv-finput" value="09:00">
                    </label>
                </div>
            </div>

            <div class="bv-modal-divider bv-modal-divider--my-14"></div>
            <label class="bv-modal-check">
                <input type="checkbox" id="snzReopenOnReply" checked>
                <span>{{ __('helpdesk::helpdesk.inbox.modals.snooze_reopen_on_reply') }}</span>
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="snzBtnApply">{{ __('helpdesk::helpdesk.inbox.modals.snooze_title') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce

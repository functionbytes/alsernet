{{-- Modal: Crear recordatorio personal (#59 ve-reminder) --}}
<div class="bv-modal" data-bv-modal-name="reminder">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-bell"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · RECORDATORIO</span>
                <div class="bv-modal-title">Crear recordatorio personal</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Contexto --}}
            <div class="bv-info-table" id="reminderContext" style="display:none">
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">Cliente</span>
                    <span class="bv-info-table__v" id="reminderCustomerName">—</span>
                </div>
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">Conversación</span>
                    <span class="bv-info-table__v" id="reminderConvRef" style="font-family:monospace;font-size:11px">—</span>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Título del recordatorio <span class="bv-req">*</span></label>
                <input id="reminderTitle" type="text" class="bv-form-input" placeholder="Ej: Llamar al cliente para confirmar resolución" autocomplete="off">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Cuándo recordar</label>
                <div class="bv-opt-list" id="reminderWhenList">
                    <button type="button" class="bv-opt on" data-remind-preset="30m">
                        <div class="bv-opt__body"><span class="bv-opt__t">En 30 minutos</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-remind-preset="2h">
                        <div class="bv-opt__body"><span class="bv-opt__t">En 2 horas</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-remind-preset="tomorrow">
                        <div class="bv-opt__body"><span class="bv-opt__t">Mañana 09:00</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-remind-preset="custom">
                        <div class="bv-opt__body"><span class="bv-opt__t">Personalizar fecha…</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                </div>
            </div>

            {{-- Custom date picker (hidden by default) --}}
            <div id="reminderCustomPanel" style="display:none">
                <div class="bv-frow">
                    <div class="bv-form-field">
                        <label class="bv-form-label">Fecha</label>
                        <input id="reminderDate" type="date" class="bv-form-input">
                    </div>
                    <div class="bv-form-field">
                        <label class="bv-form-label">Hora</label>
                        <input id="reminderTime" type="time" class="bv-form-input" value="09:00">
                    </div>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Notas <span class="bv-form-hint">opcional</span></label>
                <textarea id="reminderNotes" class="bv-form-input" rows="2" placeholder="Detalles del recordatorio…"></textarea>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="reminderEmail" checked>
                Notificarme por email también
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-reminder-create">Crear recordatorio</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce

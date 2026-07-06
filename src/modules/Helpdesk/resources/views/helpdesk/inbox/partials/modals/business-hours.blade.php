{{-- Modal: Horario de atención (#77 ve-business-hours) --}}
<div class="bv-modal" data-bv-modal-name="business-hours">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-calendar"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_header_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-frow">
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_field_timezone') }}</label>
                    <select id="bhTimezone" class="bv-form-input">
                        <option value="Europe/Madrid">Europe/Madrid (UTC+1)</option>
                        <option value="America/Mexico_City">America/Mexico_City</option>
                        <option value="America/Bogota">America/Bogota</option>
                        <option value="America/Santiago">America/Santiago</option>
                        <option value="America/Buenos_Aires">America/Buenos_Aires</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_field_apply_to') }}</label>
                    <select id="bhApplyTo" class="bv-form-input">
                        <option value="all">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_apply_all') }}</option>
                        <option value="whatsapp">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_apply_whatsapp') }}</option>
                        <option value="email">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_apply_email') }}</option>
                        <option value="webchat">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_apply_webchat') }}</option>
                    </select>
                </div>
            </div>

            <div class="bv-cal-grid" id="bhCalGrid">
                <div class="bv-cal-grid__h"></div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_mon') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_tue') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_wed') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_thu') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_fri') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_sat') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_sun') }}</div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_field_off_message') }}</label>
                <textarea id="bhOffMessage" class="bv-form-input" rows="2" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.business_hours_placeholder_off_message') }}"></textarea>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-bh-save">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_btn_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var HOURS = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'];
    var DAYS  = 7;
    var _slots = {};

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function buildGrid() {
        var html = '<div class="bv-cal-grid__h"></div>';
        ['L','M','X','J','V','S','D'].forEach(function (d) {
            html += '<div class="bv-cal-grid__h">' + d + '</div>';
        });
        HOURS.forEach(function (hr) {
            html += '<div class="bv-cal-grid__hr">' + hr + '</div>';
            for (var d = 0; d < DAYS; d++) {
                var key = hr + '_' + d;
                var on  = _slots[key] ? ' on' : '';
                html += '<div class="bv-cal-grid__slot' + on + '" data-slot="' + key + '"></div>';
            }
        });
        $('#bhCalGrid').html(html);
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'business-hours') { return; }
        _slots = {};
        $('#bhOffMessage').val('Estamos fuera de horario. Te responderemos en cuanto abramos.');

        $.ajax({
            url: '/panel/helpdesk/settings/business-hours',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var d = resp.data || resp;
            _slots = d.slots || {};
            if (d.timezone) { $('#bhTimezone').val(d.timezone); }
            if (d.apply_to) { $('#bhApplyTo').val(d.apply_to); }
            if (d.off_message) { $('#bhOffMessage').val(d.off_message); }
        }).always(function () {
            buildGrid();
        });
    });

    var _dragging = false;
    var _dragOn   = false;

    $(document).on('mousedown', '.bv-cal-grid__slot', function (e) {
        _dragging = true;
        _dragOn   = !$(this).hasClass('on');
        toggleSlot($(this));
        e.preventDefault();
    });

    $(document).on('mouseover', '.bv-cal-grid__slot', function () {
        if (_dragging) { toggleSlot($(this)); }
    });

    $(document).on('mouseup', function () { _dragging = false; });

    function toggleSlot($el) {
        var key = $el.data('slot');
        if (_dragOn) {
            $el.addClass('on');
            _slots[key] = true;
        } else {
            $el.removeClass('on');
            delete _slots[key];
        }
    }

    $(document).on('click', '#bv-bh-save', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');

        $.ajax({
            url: '/panel/helpdesk/settings/business-hours',
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({
                slots:       _slots,
                timezone:    $('#bhTimezone').val(),
                apply_to:    $('#bhApplyTo').val(),
                off_message: $('#bhOffMessage').val().trim(),
            }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('business-hours');
            if (window.toastr) { toastr.success('Horario guardado'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al guardar horario';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Guardar horario');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce

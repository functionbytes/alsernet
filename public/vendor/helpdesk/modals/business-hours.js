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

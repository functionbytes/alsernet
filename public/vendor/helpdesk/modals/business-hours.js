/*!
 * Helpdesk · modal "{name}" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/{name}.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 *
 * Consume las mismas rutas y el mismo modelo de datos que la pagina de ajustes
 * /panel/settings/helpdesk/business-hours (BusinessHoursController): un rango
 * abre/cierra por dia + una zona horaria global. El guardado usa POST (alias
 * .update.ajax) en vez de PUT real por el 405 conocido de PUT via AJAX en Docker.
 */
(function ($) {
    'use strict';

    var _hours = [];
    var _$modal = null;

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function buildTimezoneOptions(timezones, selected) {
        var html = '';
        $.each(timezones, function (value, label) {
            var sel = value === selected ? ' selected' : '';
            html += '<option value="' + escapeHtml(value) + '"' + sel + '>' + escapeHtml(label) + '</option>';
        });
        $('#bhTimezone').html(html);
    }

    function buildDaysList(hours) {
        var html = '';

        hours.forEach(function (hour) {
            var opensAt = (hour.opens_at || '09:00').substring(0, 5);
            var closesAt = (hour.closes_at || '18:00').substring(0, 5);
            var isOpen = !!hour.is_open;

            html += '<div class="bv-bh-day' + (isOpen ? '' : ' bv-bh-day--off') + '" data-day="' + hour.day_of_week + '">'
                + '  <input type="checkbox" class="bv-bh-day__check" data-role="is_open"' + (isOpen ? ' checked' : '') + '>'
                + '  <span class="bv-bh-day__name">' + escapeHtml(hour.day_name) + '</span>'
                + '  <input type="time" class="bv-bh-day__time" data-role="opens_at" value="' + opensAt + '"' + (isOpen ? '' : ' disabled') + '>'
                + '  <span class="bv-bh-day__sep">–</span>'
                + '  <input type="time" class="bv-bh-day__time" data-role="closes_at" value="' + closesAt + '"' + (isOpen ? '' : ' disabled') + '>'
                + '</div>';
        });

        $('#bhDaysList').html(html);
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'business-hours') { return; }

        _$modal = $('[data-bv-modal-name="business-hours"]');
        $('#bhTimezone').empty();
        $('#bhDaysList').html('<div class="bv-bh-days__loading">…</div>');

        $.ajax({
            url: _$modal.data('bh-index-url'),
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _hours = (resp.hours || []).slice().sort(function (a, b) { return a.day_of_week - b.day_of_week; });
            var currentTimezone = (_hours[0] && _hours[0].timezone) || 'America/Mexico_City';
            buildTimezoneOptions(resp.timezones || {}, currentTimezone);
            buildDaysList(_hours);
        }).fail(function () {
            $('#bhDaysList').html('<div class="bv-bh-days__loading">Error al cargar el horario</div>');
        });
    });

    $(document).on('change', '.bv-bh-day__check', function () {
        var $day = $(this).closest('.bv-bh-day');
        var open = $(this).is(':checked');

        $day.toggleClass('bv-bh-day--off', !open);
        $day.find('.bv-bh-day__time').prop('disabled', !open);
    });

    $(document).on('click', '#bv-bh-save', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');
        var timezone = $('#bhTimezone').val();
        var hours = {};

        $('#bhDaysList .bv-bh-day').each(function () {
            var $day = $(this);
            var day = $day.data('day');
            var isOpen = $day.find('[data-role="is_open"]').is(':checked');

            hours[day] = {
                is_open: isOpen,
                opens_at: $day.find('[data-role="opens_at"]').val(),
                closes_at: $day.find('[data-role="closes_at"]').val(),
            };
        });

        $.ajax({
            url: _$modal.data('bh-update-url'),
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ hours: hours, timezone: timezone }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('business-hours');
            if (window.toastr) { toastr.success('Horario guardado'); }
        }).fail(function (xhr) {
            var msg = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al guardar horario';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Guardar horario');
        });
    });

}(window.jQuery));

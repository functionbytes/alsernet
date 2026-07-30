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

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    var _slaData = [];

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'sla-config') { return; }
        _slaData = [];
        $('#slaConfigContent').hide();
        $('#slaConfigLoading').show();
        $('#bv-sla-save').prop('disabled', true);

        $.ajax({
            url: '/panel/helpdesk/settings/sla',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _slaData = resp.data || resp || [];
            var html = _slaData.map(function (row, i) {
                return '<div class="bv-lp-row">' +
                    '<span class="bv-lp-row__k">' + escHtml(row.label) + '</span>' +
                    '<input type="text" class="bv-form-input bv-sla-input bv-x66" data-sla-idx="' + i + '" value="' + escHtml(row.value) + '">' +
                    '</div>';
            }).join('');
            $('#slaConfigRows').html(html);
            $('#slaPauseOffHours').prop('checked', resp.pause_off_hours !== false);
            $('#slaNotifySupervisor').prop('checked', resp.notify_supervisor !== false);
            $('#slaConfigLoading').hide();
            $('#slaConfigContent').show();
            $('#bv-sla-save').prop('disabled', false);
        }).fail(function () {
            $('#slaConfigLoading').hide();
        });
    });

    $(document).on('click', '#bv-sla-save', function () {
        $('.bv-sla-input').each(function () {
            var idx = parseInt($(this).data('sla-idx'), 10);
            if (_slaData[idx]) { _slaData[idx].value = $(this).val().trim(); }
        });

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');

        $.ajax({
            url: '/panel/helpdesk/settings/sla',
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({
                sla:               _slaData,
                pause_off_hours:   $('#slaPauseOffHours').is(':checked'),
                notify_supervisor: $('#slaNotifySupervisor').is(':checked'),
            }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('sla-config');
            if (window.toastr) { toastr.success('Configuración SLA guardada'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al guardar SLA';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Guardar cambios');
        });
    });

}(window.jQuery));

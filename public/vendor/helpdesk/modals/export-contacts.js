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

    var _ecScope = 'all';
    var _ecFmt   = 'csv';

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'export-contacts') { return; }
        _ecScope = 'all';
        _ecFmt   = 'csv';
        $('#exportContactsScope .bv-opt').removeClass('on');
        $('#exportContactsScope .bv-opt[data-scope="all"]').addClass('on');
        $('#exportContactsFormat .bv-export-opt').removeClass('on');
        $('#exportContactsFormat .bv-export-opt[data-fmt="csv"]').addClass('on');
        $('#exportContactsNotes').prop('checked', true);
        $('#exportContactsHistory').prop('checked', false);
    });

    $(document).on('click', '#exportContactsScope .bv-opt', function () {
        $('#exportContactsScope .bv-opt').removeClass('on');
        $(this).addClass('on');
        _ecScope = $(this).data('scope');
    });

    $(document).on('click', '#exportContactsFormat .bv-export-opt', function () {
        $('#exportContactsFormat .bv-export-opt').removeClass('on');
        $(this).addClass('on');
        _ecFmt = $(this).data('fmt');
    });

    $(document).on('click', '#bv-export-contacts-dl', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generando…');

        $.ajax({
            url: '/panel/helpdesk/contacts/export',
            method: 'POST',
            data: {
                scope:            _ecScope,
                format:           _ecFmt,
                include_notes:    $('#exportContactsNotes').is(':checked') ? 1 : 0,
                include_history:  $('#exportContactsHistory').is(':checked') ? 1 : 0,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('export-contacts');
            var url = resp.url || (resp.data && resp.data.url);
            if (url) {
                window.location.href = url;
            } else {
                if (window.toastr) { toastr.success('Exportación en proceso · recibirás un email'); }
            }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al exportar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Descargar archivo');
        });
    });

}(window.jQuery));

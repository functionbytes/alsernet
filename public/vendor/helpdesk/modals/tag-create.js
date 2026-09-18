/*!
 * Helpdesk · modal "tag-create" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/tag-create.blade.php,
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

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'tag-create') { return; }
        $('#tagCreateName').val('').focus();
        $('#tagCreateColor').val('#90bb13');
        $('#tagColorPicker .bv-color-dot').each(function () {
            $(this).css('outline', '').css('outline-offset', '').removeClass('on');
        });
        $('#tagColorPicker .bv-color-dot[data-color="#90bb13"]').addClass('on')
            .css({ outline: '2px solid #18181b', 'outline-offset': '2px' });
    });

    $(document).on('click', '#tagColorPicker .bv-color-dot', function () {
        $('#tagColorPicker .bv-color-dot').css('outline', '').css('outline-offset', '').removeClass('on');
        $(this).addClass('on').css({ outline: '2px solid #18181b', 'outline-offset': '2px' });
        $('#tagCreateColor').val($(this).data('color'));
    });

    $(document).on('click', '#bv-tag-create-confirm', function () {
        var name = $('#tagCreateName').val().trim();
        if (!name) {
            if (window.toastr) { toastr.warning('El nombre es obligatorio'); }
            $('#tagCreateName').focus();
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/settings/helpdesk/tags',
            method: 'POST',
            data: { name: name, color: $('#tagCreateColor').val() },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('tag-create');
            if (window.toastr) { toastr.success('Etiqueta creada'); }
            $(document).trigger('bv:tag:created', [resp]);
        }).fail(function (xhr) {
            var errs = xhr?.responseJSON?.errors;
            var msg = errs ? Object.values(errs)[0]?.[0] : (xhr?.responseJSON?.message || 'Error al crear etiqueta');
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('keydown', '#tagCreateName', function (e) {
        if (e.key === 'Enter') { $('#bv-tag-create-confirm').trigger('click'); }
    });

}(window.jQuery));

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

    var _fbType = 'bug';
    var _fbFile = null;

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'feedback') { return; }
        _fbType = 'bug';
        _fbFile = null;
        $('#feedbackDescription').val('').focus();
        $('#feedbackIncludeTechInfo').prop('checked', true);
        $('#feedbackContactMe').prop('checked', false);
        $('#feedbackTypeBar .bv-prio-card').removeClass('on');
        $('#feedbackTypeBar .bv-prio-card[data-fb-type="bug"]').addClass('on');
        $('#feedbackFile').val('');
        $('#feedbackFilePreview').hide();
    });

    $(document).on('click', '#feedbackTypeBar .bv-prio-card', function () {
        $('#feedbackTypeBar .bv-prio-card').removeClass('on');
        $(this).addClass('on');
        _fbType = $(this).data('fb-type');
    });

    $(document).on('click', '#feedbackDropzone', function () { $('#feedbackFile').trigger('click'); });

    $(document).on('change', '#feedbackFile', function () {
        _fbFile = this.files[0] || null;
        if (_fbFile) {
            $('#feedbackFileName').text(_fbFile.name);
            $('#feedbackFilePreview').show();
        }
    });

    $(document).on('click', '#feedbackFileRemove', function () {
        _fbFile = null;
        $('#feedbackFile').val('');
        $('#feedbackFilePreview').hide();
    });

    $(document).on('click', '#bv-feedback-submit', function () {
        var desc = $('#feedbackDescription').val().trim();
        if (!desc) {
            if (window.toastr) { toastr.warning('La descripción es obligatoria'); }
            $('#feedbackDescription').focus();
            return;
        }

        var fd = new FormData();
        fd.append('type',           _fbType);
        fd.append('description',    desc);
        fd.append('include_tech',   $('#feedbackIncludeTechInfo').is(':checked') ? '1' : '0');
        fd.append('contact_me',     $('#feedbackContactMe').is(':checked') ? '1' : '0');
        if (_fbFile) { fd.append('screenshot', _fbFile); }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando…');

        $.ajax({
            url: '/panel/helpdesk/feedback',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('feedback');
            if (window.toastr) { toastr.success('Gracias por tu feedback'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al enviar feedback';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Enviar feedback');
        });
    });

}(window.jQuery));

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

    var _importFile = null;

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function setFile(file) {
        if (!file) { return; }
        _importFile = file;
        $('#importConvFileName').text(file.name);
        $('#importConvRowCount').text('Calculando…');
        $('#importConvFileRow').show();
        $('#importConvMapping').show();
        $('#bv-import-conv-submit').prop('disabled', false).text('Importar');
        // estimate rows for CSV
        if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
            var reader = new FileReader();
            reader.onload = function (ev) {
                var lines = (ev.target.result.match(/\n/g) || []).length;
                $('#importConvRowCount').text(lines + ' filas aprox.');
                $('#bv-import-conv-submit').text('Importar ' + lines + ' filas');
            };
            reader.readAsText(file.slice(0, 1024 * 64));
        }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'import-conv') { return; }
        _importFile = null;
        $('#importConvFile').val('');
        $('#importConvFileRow').hide();
        $('#importConvMapping').hide();
        $('#bv-import-conv-submit').prop('disabled', true).text('Importar');
        $('#importConvDropzone').removeClass('over');
    });

    $(document).on('click', '#importConvDropzone, #importConvBrowse', function (e) {
        e.stopPropagation();
        $('#importConvFile').trigger('click');
    });

    $(document).on('change', '#importConvFile', function () { setFile(this.files[0]); });

    $('#importConvDropzone').on('dragover dragenter', function (e) {
        e.preventDefault();
        $(this).addClass('over');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $(this).removeClass('over');
        if (e.type === 'drop') { setFile(e.originalEvent.dataTransfer.files[0]); }
    });

    $(document).on('click', '#bv-import-conv-template', function () {
        window.location.href = '/panel/helpdesk/conversations/import/template';
    });

    $(document).on('click', '#bv-import-conv-submit', function () {
        if (!_importFile) { return; }
        var fd = new FormData();
        fd.append('file', _importFile);
        fd.append('create_contacts', $('#importConvCreateContacts').is(':checked') ? '1' : '0');
        fd.append('assign_agent',   $('#importConvAssignAgent').is(':checked') ? '1' : '0');

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando…');

        $.ajax({
            url: '/panel/helpdesk/conversations/import',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('import-conv');
            var count = (resp.data && resp.data.imported) || resp.imported || '';
            if (window.toastr) { toastr.success('Importación completada' + (count ? ' · ' + count + ' conversaciones' : '')); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al importar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Importar');
        });
    });

}(window.jQuery));

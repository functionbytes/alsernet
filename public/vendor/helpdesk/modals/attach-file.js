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

    var _files = [];

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function fmtSize(bytes) {
        if (bytes < 1024) { return bytes + ' B'; }
        if (bytes < 1048576) { return (bytes / 1024).toFixed(0) + ' KB'; }
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function fileIcon(name) {
        var ext = (name.split('.').pop() || '').toLowerCase();
        if (['pdf'].indexOf(ext) !== -1) { return { icon: 'fas fa-file-pdf', bg: '#fef2f2', color: '#dc2626' }; }
        if (['jpg','jpeg','png','gif','webp','svg'].indexOf(ext) !== -1) { return { icon: 'far fa-image', bg: '#dbeafe', color: '#1e3a8a' }; }
        if (['doc','docx'].indexOf(ext) !== -1) { return { icon: 'fas fa-file-word', bg: '#eff6ff', color: '#2563eb' }; }
        if (['xls','xlsx','csv'].indexOf(ext) !== -1) { return { icon: 'fas fa-file-excel', bg: '#f0fdf4', color: '#16a34a' }; }
        if (['zip','rar','7z'].indexOf(ext) !== -1) { return { icon: 'fas fa-file-zipper', bg: '#fef3c7', color: '#d97706' }; }
        return { icon: 'fas fa-file', bg: '#f4f4f5', color: '#52525b' };
    }

    function renderFiles() {
        if (!_files.length) {
            $('#bvAttachList').hide();
            $('#bv-attach-send').prop('disabled', true).text('Adjuntar');
            return;
        }
        var html = _files.map(function (f, i) {
            var ico = fileIcon(f.name);
            return '<div class="bv-attach-file-item">' +
                '<div class="bv-attach-file-item__ico" style="background:' + ico.bg + ';color:' + ico.color + '"><i class="' + ico.icon + '"></i></div>' +
                '<div class="bv-attach-file-item__body">' +
                    '<div class="bv-attach-file-item__name">' + $('<span>').text(f.name).html() + '</div>' +
                    '<div class="bv-attach-file-item__size">' + fmtSize(f.size) + '</div>' +
                '</div>' +
                '<button type="button" class="bv-attach-file-item__remove" data-file-idx="' + i + '"><i class="fas fa-xmark"></i></button>' +
                '</div>';
        }).join('');
        var n = _files.length;
        $('#bvAttachListLabel').text('Archivos seleccionados · ' + n);
        $('#bvAttachItems').html(html);
        $('#bvAttachList').show();
        $('#bv-attach-send').prop('disabled', false).html('<i class="fas fa-paperclip me-1"></i> Adjuntar ' + n + ' ' + (n === 1 ? 'archivo' : 'archivos'));
    }

    function addFiles(fileList) {
        var MAX = 25 * 1024 * 1024;
        var rejected = 0;
        $.each(fileList, function (i, f) {
            if (f.size > MAX) { rejected++; return; }
            var dup = _files.some(function (x) { return x.name === f.name && x.size === f.size; });
            if (!dup) { _files.push(f); }
        });
        if (rejected) { if (window.toastr) { toastr.warning(rejected + ' archivo(s) superan el límite de 25 MB'); } }
        renderFiles();
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'attach-file') { return; }
        _files = [];
        renderFiles();
        $('#bvAttachInput').val('');
        $('#bvAttachDropzone').removeClass('over');
    });

    $(document).on('click', '#bvAttachDropzone, #bvAttachBrowse', function () {
        $('#bvAttachInput').trigger('click');
    });

    $(document).on('change', '#bvAttachInput', function () {
        addFiles(this.files);
    });

    $(document).on('click', '.bv-attach-file-item__remove', function () {
        _files.splice(parseInt($(this).data('file-idx'), 10), 1);
        renderFiles();
    });

    $('#bvAttachDropzone').on('dragover dragenter', function (e) {
        e.preventDefault();
        $(this).addClass('over');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $(this).removeClass('over');
        if (e.type === 'drop') { addFiles(e.originalEvent.dataTransfer.files); }
    });

    $(document).on('click', '.bv-attach-ext', function () {
        var source = $(this).data('source');
        if (window.toastr) { toastr.info('Integración "' + source + '" próximamente disponible'); }
    });

    $(document).on('click', '#bv-attach-send', function () {
        if (!_files.length) { return; }
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }

        var fd = new FormData();
        _files.forEach(function (f) { fd.append('files[]', f); });

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Subiendo…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/attachments',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('attach-file');
            if (window.toastr) { toastr.success('Archivos adjuntados correctamente'); }
            $(document).trigger('bv:attachments:uploaded', [convId]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al subir archivos';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-paperclip me-1"></i> Adjuntar');
        });
    });

}(window.jQuery));

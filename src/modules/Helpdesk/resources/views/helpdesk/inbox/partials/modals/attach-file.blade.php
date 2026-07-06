{{-- Modal: Adjuntar archivo (#13 ve-attach) --}}
<div class="bv-modal" data-bv-modal-name="attach-file">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-paperclip"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.attach_file_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.attach_file_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Drop zone --}}
            <div class="bv-dropzone" id="bvAttachDropzone">
                <i class="fas fa-cloud-arrow-up bv-dropzone__ico"></i>
                <div class="bv-dropzone__title">{{ __('helpdesk::helpdesk.inbox.modals.attach_file_dropzone_title') }}</div>
                <div class="bv-dropzone__hint">{{ __('helpdesk::helpdesk.inbox.modals.attach_file_dropzone_hint_or') }} <span class="bv-dropzone__link" id="bvAttachBrowse">{{ __('helpdesk::helpdesk.inbox.modals.attach_file_dropzone_browse') }}</span></div>
                <div class="bv-dropzone__types">{{ __('helpdesk::helpdesk.inbox.modals.attach_file_dropzone_types') }}</div>
                <input type="file" id="bvAttachInput" multiple style="display:none"
                       accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.zip,.csv,.txt">
            </div>

            {{-- File queue --}}
            <div id="bvAttachList" style="display:none;margin-top:10px">
                <div class="bv-form-label" id="bvAttachListLabel">{{ __('helpdesk::helpdesk.inbox.modals.attach_file_selected_label') }}</div>
                <div class="bv-x15" id="bvAttachItems"></div>
            </div>

            {{-- Extra sources --}}
            <div class="bv-x19">
                <button type="button" class="btn-secondary bv-attach-ext bv-x20" data-source="drive">
                    <i class="fas fa-cloud me-1"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_file_source_drive') }}
                </button>
                <button type="button" class="btn-secondary bv-attach-ext bv-x20" data-source="screenshot">
                    <i class="fas fa-image me-1"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_file_source_screenshot') }}
                </button>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-attach-send" disabled>
                <i class="fas fa-paperclip me-1"></i> {{ __('helpdesk::helpdesk.inbox.modals.attach_file_submit') }}
            </button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
{{-- <style> inline (NO @push): @push('styles') es un stack fantasma en el inbox
     (el layout usa @stack('css'), no 'styles') → estos estilos no se cargaban. --}}
<style>
.bv-dropzone {
    border: 2px dashed var(--bv-border, #e4e4e7);
    border-radius: 10px;
    padding: 28px 16px;
    text-align: center;
    cursor: pointer;
    background: var(--bv-bg-subtle, #fafafa);
    transition: border-color .15s, background .15s;
}
.bv-dropzone.over {
    border-color: var(--bv-primary, #90bb13);
    background: rgba(144,187,19,.06);
}
.bv-dropzone__ico { font-size: 26px; color: var(--bv-text-muted, #a1a1aa); margin-bottom: 8px; }
.bv-dropzone__title { font-size: 13px; font-weight: 600; color: var(--bv-text, #18181b); }
.bv-dropzone__hint { font-size: 11.5px; color: var(--bv-text-muted, #71717a); margin-top: 4px; }
.bv-dropzone__link { color: var(--bv-text, #18181b); text-decoration: underline; cursor: pointer; }
.bv-dropzone__types { font-size: 10.5px; color: var(--bv-text-muted, #a1a1aa); margin-top: 8px; font-family: 'JetBrains Mono', monospace; }
.bv-attach-file-item {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 10px; border: 1px solid var(--bv-border, #e4e4e7);
    border-radius: 6px; background: var(--bv-bg-panel, #fff);
}
.bv-attach-file-item__ico {
    width: 30px; height: 30px; border-radius: 5px;
    display: grid; place-items: center; font-size: 13px; flex-shrink: 0;
}
.bv-attach-file-item__body { flex: 1; min-width: 0; }
.bv-attach-file-item__name { font-size: 12.5px; font-weight: 500; color: var(--bv-text, #18181b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bv-attach-file-item__size { font-size: 10.5px; color: var(--bv-text-muted, #71717a); font-family: 'JetBrains Mono', monospace; }
.bv-attach-file-item__remove {
    width: 22px; height: 22px; border: none; background: none; cursor: pointer;
    color: var(--bv-text-muted, #a1a1aa); border-radius: 4px; display: grid; place-items: center; font-size: 11px;
}
.bv-attach-file-item__remove:hover { background: var(--bv-bg-subtle, #f4f4f5); color: var(--bv-text, #18181b); }
</style>

@push('scripts')
<script>
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
</script>
@endpush
@endonce

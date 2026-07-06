{{-- Modal: Importar conversaciones CSV (#80 ve-import-conv) --}}
<div class="bv-modal" data-bv-modal-name="import-conv">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-file-import"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.import_conv_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.import_conv_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-dropzone" id="importConvDropzone">
                <i class="fas fa-cloud-arrow-up bv-dropzone__ico"></i>
                <div class="bv-dropzone__title">{{ __('helpdesk::helpdesk.inbox.modals.import_conv_dropzone_title') }}</div>
                <div class="bv-dropzone__hint">{{ __('helpdesk::helpdesk.inbox.modals.import_conv_dropzone_hint_or') }} <span class="bv-dropzone__link" id="importConvBrowse">{{ __('helpdesk::helpdesk.inbox.modals.import_conv_dropzone_browse') }}</span> {{ __('helpdesk::helpdesk.inbox.modals.import_conv_dropzone_max_size') }}</div>
                <input type="file" id="importConvFile" accept=".csv,.xlsx" style="display:none">
            </div>

            <div id="importConvFileRow" style="display:none;margin-top:8px" class="bv-lp-row bv-lp-row--active">
                <i class="far fa-file-lines"></i>
                <span id="importConvFileName" class="bv-lp-row__k"></span>
                <span id="importConvRowCount" class="bv-lp-row__v"></span>
            </div>

            <div id="importConvMapping" style="display:none">
                <div class="bv-form-label bv-x29">{{ __('helpdesk::helpdesk.inbox.modals.import_conv_field_mapping') }}</div>
                <div class="bv-lp-row">
                    <span class="bv-lp-row__k">email_cliente →</span>
                    <select class="bv-form-input bv-x30"><option>{{ __('helpdesk::helpdesk.inbox.modals.import_conv_map_email_contact') }}</option></select>
                </div>
                <div class="bv-lp-row">
                    <span class="bv-lp-row__k">canal →</span>
                    <select class="bv-form-input bv-x30"><option>{{ __('helpdesk::helpdesk.inbox.modals.import_conv_map_channel') }}</option></select>
                </div>
                <div class="bv-lp-row">
                    <span class="bv-lp-row__k">fecha →</span>
                    <select class="bv-form-input bv-x30"><option>Created at</option></select>
                </div>
                <div class="bv-lp-row">
                    <span class="bv-lp-row__k">mensaje →</span>
                    <select class="bv-form-input bv-x30"><option>{{ __('helpdesk::helpdesk.inbox.modals.import_conv_map_body') }}</option></select>
                </div>
            </div>

            <label class="bv-check bv-x31">
                <input type="checkbox" id="importConvCreateContacts" checked>
                {{ __('helpdesk::helpdesk.inbox.modals.import_conv_create_contacts') }}
            </label>
            <label class="bv-check">
                <input type="checkbox" id="importConvAssignAgent">
                {{ __('helpdesk::helpdesk.inbox.modals.import_conv_assign_default_agent') }}
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-import-conv-submit" disabled>{{ __('helpdesk::helpdesk.inbox.modals.import_conv_submit') }}</button>
            <button class="btn-secondary" id="bv-import-conv-template">{{ __('helpdesk::helpdesk.inbox.modals.import_conv_download_template') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce

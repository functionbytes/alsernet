{{-- Modal: Exportar conversación (#57 ve-export) --}}
<div class="bv-modal" data-bv-modal-name="export-conv">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-file-export"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_field_format') }}</label>
                <div class="bv-opt-list" id="exportFormatList">
                    <button type="button" class="bv-opt on" data-bv-value="pdf">
                        <div class="bv-opt__ic"><i class="far fa-file-pdf bv-x23"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_format_pdf_title') }}</span>
                            <span class="bv-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_format_pdf_desc') }}</span>
                        </div>
                        <span class="bv-opt__badge">.PDF</span>
                    </button>
                    <button type="button" class="bv-opt" data-bv-value="csv">
                        <div class="bv-opt__ic"><i class="far fa-file-lines bv-x24"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_format_csv_title') }}</span>
                            <span class="bv-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_format_csv_desc') }}</span>
                        </div>
                        <span class="bv-opt__badge">.CSV</span>
                    </button>
                    <button type="button" class="bv-opt" data-bv-value="json">
                        <div class="bv-opt__ic"><i class="fas fa-code bv-x25"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_format_json_title') }}</span>
                            <span class="bv-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_format_json_desc') }}</span>
                        </div>
                        <span class="bv-opt__badge">.JSON</span>
                    </button>
                    <button type="button" class="bv-opt" data-bv-value="eml">
                        <div class="bv-opt__ic"><i class="far fa-envelope bv-x25"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_format_eml_title') }}</span>
                            <span class="bv-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_format_eml_desc') }}</span>
                        </div>
                        <span class="bv-opt__badge">.EML</span>
                    </button>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.export_conv_field_options') }}</label>
                <div class="bv-x26">
                    <label class="bv-check">
                        <input type="checkbox" id="exportNotes" checked>
                        {{ __('helpdesk::helpdesk.inbox.modals.export_conv_option_notes') }}
                    </label>
                    <label class="bv-check">
                        <input type="checkbox" id="exportAttachments" checked>
                        {{ __('helpdesk::helpdesk.inbox.modals.export_conv_option_attachments') }}
                    </label>
                    <label class="bv-check">
                        <input type="checkbox" id="exportMeta">
                        {{ __('helpdesk::helpdesk.inbox.modals.export_conv_option_meta') }}
                    </label>
                    <label class="bv-check">
                        <input type="checkbox" id="exportHeader" checked>
                        {{ __('helpdesk::helpdesk.inbox.modals.export_conv_option_header') }}
                    </label>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-export-conv-go">
                {{ __('helpdesk::helpdesk.inbox.modals.export_conv_submit') }}
            </button>
            <button class="btn-secondary" id="bv-export-conv-email">
                {{ __('helpdesk::helpdesk.inbox.modals.export_conv_submit_email') }}
            </button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('click', '#exportFormatList .bv-opt', function () {
        $('#exportFormatList .bv-opt').removeClass('on');
        $(this).addClass('on');
    });

    function exportPayload(convId) {
        return {
            conversation_id: convId,
            format:          $('#exportFormatList .bv-opt.on').data('bv-value') || 'pdf',
            include_notes:   $('#exportNotes').is(':checked') ? '1' : '0',
            include_meta:    $('#exportMeta').is(':checked') ? '1' : '0',
            include_attachments: $('#exportAttachments').is(':checked') ? '1' : '0',
            include_header:  $('#exportHeader').is(':checked') ? '1' : '0',
        };
    }

    $(document).on('click', '#bv-export-conv-go', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var params = new URLSearchParams(exportPayload(convId));
        window.location.href = '/panel/helpdesk/exports/conversation-transcript?' + params.toString();
        closeBvModal('export-conv');
    });

    $(document).on('click', '#bv-export-conv-email', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando…');
        $.ajax({
            url: '/panel/helpdesk/exports/conversation-transcript/email',
            method: 'POST',
            data: exportPayload(convId),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('export-conv');
            if (window.toastr) { toastr.success(resp.message || 'Enviado por email'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'No se pudo enviar el archivo por email';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('{{ __('helpdesk::helpdesk.inbox.modals.export_conv_submit_email') }}');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce

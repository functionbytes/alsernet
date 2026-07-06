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
                        <input type="checkbox" id="exportMeta">
                        {{ __('helpdesk::helpdesk.inbox.modals.export_conv_option_meta') }}
                    </label>
                    <label class="bv-check">
                        <input type="checkbox" id="exportAttachments">
                        {{ __('helpdesk::helpdesk.inbox.modals.export_conv_option_attachments') }}
                    </label>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-export-conv-go">
                <i class="fas fa-download me-1"></i> {{ __('helpdesk::helpdesk.inbox.modals.export_conv_submit') }}
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

    $(document).on('click', '#bv-export-conv-go', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var format = $('#exportFormatList .bv-opt.on').data('bv-value') || 'pdf';
        var params = new URLSearchParams({
            conversation_id: convId,
            format:          format,
            include_notes:   $('#exportNotes').is(':checked') ? '1' : '0',
            include_meta:    $('#exportMeta').is(':checked') ? '1' : '0',
            include_attachments: $('#exportAttachments').is(':checked') ? '1' : '0',
        });
        window.location.href = '/panel/helpdesk/exports/conversation-transcript?' + params.toString();
        closeBvModal('export-conv');
    });

}(window.jQuery));
</script>
@endpush
@endonce

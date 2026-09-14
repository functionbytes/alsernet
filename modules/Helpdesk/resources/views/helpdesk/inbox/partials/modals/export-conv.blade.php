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
            {{-- data-label-default: el JS restaura este texto tras el envio; lo lee
                 del atributo para no interpolar la traduccion dentro del bloque JS. --}}
            <button class="btn-secondary" id="bv-export-conv-email"
                    data-label-default="{{ __('helpdesk::helpdesk.inbox.modals.export_conv_submit_email') }}">
                {{ __('helpdesk::helpdesk.inbox.modals.export_conv_submit_email') }}
            </button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/export-conv.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/export-conv.js')) }}" defer></script>
@endpush
@endonce

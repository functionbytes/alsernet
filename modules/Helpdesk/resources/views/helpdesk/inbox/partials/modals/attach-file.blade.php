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
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/attach-file.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/attach-file.js')) }}"></script>
@endpush
@endonce

{{-- Modal: Nota interna avanzada (#71 ve-internal-note) --}}
<div class="bv-modal" data-bv-modal-name="internal-note">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-note-sticky"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_mention_label') }} <span class="bv-form-hint">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_mention_hint') }}</span></label>
                <input id="internalNoteMentions" type="text" class="bv-form-input" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.internal_note_mention_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_text_label') }} <span class="bv-req">*</span></label>
                <textarea id="internalNoteText" class="bv-form-input" rows="4" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.internal_note_text_placeholder') }}"></textarea>
            </div>

            <div class="bv-frow">
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_priority_label') }}</label>
                    <select id="internalNotePriority" class="bv-form-input">
                        <option value="info">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_priority_info') }}</option>
                        <option value="important" selected>{{ __('helpdesk::helpdesk.inbox.modals.internal_note_priority_important') }}</option>
                        <option value="critical">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_priority_critical') }}</option>
                    </select>
                </div>
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_expiry_label') }}</label>
                    <select id="internalNoteExpiry" class="bv-form-input">
                        <option value="">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_expiry_never') }}</option>
                        <option value="7" selected>{{ __('helpdesk::helpdesk.inbox.modals.internal_note_expiry_7_days') }}</option>
                        <option value="30">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_expiry_30_days') }}</option>
                    </select>
                </div>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="internalNotePinned" checked>
                {{ __('helpdesk::helpdesk.inbox.modals.internal_note_pin_label') }}
            </label>
            <label class="bv-check">
                <input type="checkbox" id="internalNotePush">
                {{ __('helpdesk::helpdesk.inbox.modals.internal_note_push_label') }}
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-internal-note-save">{{ __('helpdesk::helpdesk.inbox.modals.internal_note_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/internal-note.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/internal-note.js')) }}"></script>
@endpush
@endonce

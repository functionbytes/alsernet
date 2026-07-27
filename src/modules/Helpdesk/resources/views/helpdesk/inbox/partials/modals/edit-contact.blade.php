{{-- Modal: Editar información de contacto --}}
<div class="bv-modal" data-bv-modal-name="edit-contact">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-user-pen"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_label') }}</div>
                <div class="modal-title">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_title') }}</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="field">
                <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_name') }} <span class="req">*</span></div>
                <input id="ec-name" class="finput" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_placeholder_name') }}">
            </div>
            <div class="frow">
                <div class="field">
                    <div class="flabel">Email <span class="req">*</span></div>
                    <input id="ec-email" class="finput" type="email" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_placeholder_email') }}">
                </div>
                <div class="field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_phone') }}</div>
                    <input id="ec-phone" class="finput" type="tel" placeholder="+34 600 000 000">
                </div>
            </div>
            <div class="frow">
                <div class="field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_language') }}</div>
                    <select id="ec-language" class="fselect">
                        <option value="">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_unspecified') }}</option>
                        <option value="es">Español</option>
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="de">Deutsch</option>
                        <option value="pt">Português</option>
                        <option value="it">Italiano</option>
                    </select>
                </div>
                <div class="field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_timezone') }}</div>
                    <select id="ec-timezone" class="fselect">
                        <option value="">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_unspecified') }}</option>
                        <option value="Europe/Madrid">Europe/Madrid</option>
                        <option value="America/Mexico_City">America/Mexico_City</option>
                        <option value="America/Bogota">America/Bogota</option>
                        <option value="America/Lima">America/Lima</option>
                        <option value="America/New_York">America/New_York</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_field_notes') }}</div>
                <textarea id="ec-notes" class="finput" rows="3" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_placeholder_notes') }}"></textarea>
            </div>
            <div id="ec-errors" class="bv-step-hidden field-error"></div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-primary" id="ec-save-btn">{{ __('helpdesk::helpdesk.inbox.modals.edit_contact_btn_save') }}</button>
            <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/edit-contact.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/edit-contact.js')) }}"></script>
@endpush
@endonce

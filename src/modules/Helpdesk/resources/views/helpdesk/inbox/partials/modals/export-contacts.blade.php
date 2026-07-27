{{-- Modal: Exportar contactos (#82 ve-export-contacts) --}}
<div class="bv-modal" data-bv-modal-name="export-contacts">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-file-export"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_scope_label') }}</label>
                <div class="bv-opt-list" id="exportContactsScope">
                    <button type="button" class="bv-opt on" data-scope="all">
                        <div class="bv-opt__ic"><i class="fas fa-users"></i></div>
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_scope_all') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-scope="vip">
                        <div class="bv-opt__ic"><i class="fas fa-star"></i></div>
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_scope_vip') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-scope="filtered">
                        <div class="bv-opt__ic"><i class="fas fa-filter"></i></div>
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_scope_filtered') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-scope="selected">
                        <div class="bv-opt__ic"><i class="far fa-square-check"></i></div>
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_scope_selected') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_format_label') }}</label>
                <div class="bv-export-opt-list" id="exportContactsFormat">
                    <button type="button" class="bv-export-opt on" data-fmt="csv">
                        <div class="bv-export-opt__ic"><i class="far fa-file-lines"></i></div>
                        <div class="bv-export-opt__body">
                            <span class="bv-export-opt__t">CSV</span>
                            <span class="bv-export-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_format_csv_desc') }}</span>
                        </div>
                        <span class="bv-export-opt__ext">.CSV</span>
                    </button>
                    <button type="button" class="bv-export-opt" data-fmt="xlsx">
                        <div class="bv-export-opt__ic"><i class="fas fa-table"></i></div>
                        <div class="bv-export-opt__body">
                            <span class="bv-export-opt__t">Excel</span>
                            <span class="bv-export-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_format_xlsx_desc') }}</span>
                        </div>
                        <span class="bv-export-opt__ext">.XLSX</span>
                    </button>
                    <button type="button" class="bv-export-opt" data-fmt="vcf">
                        <div class="bv-export-opt__ic"><i class="far fa-address-card"></i></div>
                        <div class="bv-export-opt__body">
                            <span class="bv-export-opt__t">vCard</span>
                            <span class="bv-export-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_format_vcf_desc') }}</span>
                        </div>
                        <span class="bv-export-opt__ext">.VCF</span>
                    </button>
                </div>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="exportContactsNotes" checked>
                {{ __('helpdesk::helpdesk.inbox.modals.export_contacts_include_notes') }}
            </label>
            <label class="bv-check">
                <input type="checkbox" id="exportContactsHistory">
                {{ __('helpdesk::helpdesk.inbox.modals.export_contacts_include_history') }}
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-export-contacts-dl">{{ __('helpdesk::helpdesk.inbox.modals.export_contacts_download') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/export-contacts.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/export-contacts.js')) }}"></script>
@endpush
@endonce

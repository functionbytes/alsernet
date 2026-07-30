{{-- Modal: Permisos del rol (#75 ve-role-perms) --}}
<div class="bv-modal" data-bv-modal-name="role-perms">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-shield-halved"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_title') }} <span class="bv-chip" id="rolePermsRoleName"></span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="rolePermsLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>

            <div id="rolePermsContent" style="display:none">
                <table class="bv-perm-matrix" id="rolePermsTable">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_module') }}</th>
                            <th scope="col" class="bv-perm-matrix__c">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_view') }}</th>
                            <th scope="col" class="bv-perm-matrix__c">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_create') }}</th>
                            <th scope="col" class="bv-perm-matrix__c">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_edit') }}</th>
                            <th scope="col" class="bv-perm-matrix__c">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_delete') }}</th>
                        </tr>
                    </thead>
                    <tbody id="rolePermsBody"></tbody>
                </table>

                <label class="bv-check bv-x31">
                    <input type="checkbox" id="rolePermsApplyAll" checked>
                    {{ __('helpdesk::helpdesk.inbox.modals.role_perms_apply_all') }}
                </label>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-role-perms-save" disabled>{{ __('helpdesk::helpdesk.inbox.modals.role_perms_save') }}</button>
            <button class="btn-secondary" id="bv-role-perms-reset">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_reset') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/role-perms.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/role-perms.js')) }}"></script>
@endpush
@endonce

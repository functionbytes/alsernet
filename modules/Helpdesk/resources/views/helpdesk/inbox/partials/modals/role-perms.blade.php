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

            {{-- Paso 0: solo aparece si el modal se abrió sin roleId de contexto
                 (p. ej. desde "Más opciones" del inbox, sin partir de una fila
                 de agente/rol concreta). --}}
            <div id="rolePermsPickRole" class="bv-step-hidden">
                <div class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_pick_role') }}</div>
                <select id="rolePermsRoleSelect" class="fselect"></select>
                <button class="btn-primary bv-x31" id="bv-role-perms-pick-continue">{{ __('helpdesk::helpdesk.inbox.modals.role_perms_continue') }}</button>
            </div>

            <div id="rolePermsLoading" class="bv-cv-loading-msg bv-step-hidden"><i class="fas fa-spinner fa-spin"></i></div>

            <div id="rolePermsContent" class="bv-step-hidden">
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

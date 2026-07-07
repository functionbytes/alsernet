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
<script>
(function ($) {
    'use strict';

    var _roleId   = null;
    var _perms    = {};

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function renderMatrix(modules) {
        var actions = ['view', 'create', 'update', 'delete'];
        var html = modules.map(function (mod) {
            return '<tr>' +
                '<td>' + escHtml(mod.label || mod.name) + '</td>' +
                actions.map(function (act) {
                    var key = mod.name + '.' + act;
                    var on  = _perms[key] ? ' on' : '';
                    return '<td class="bv-perm-matrix__c">' +
                        '<span class="bv-perm-chk' + on + '" data-perm-key="' + key + '">' +
                        (on ? '<i class="fas fa-check"></i>' : '') +
                        '</span></td>';
                }).join('') +
                '</tr>';
        }).join('');
        $('#rolePermsBody').html(html);
    }

    $(document).on('bv:modal:open', function (e, name, data) {
        if (name !== 'role-perms') { return; }
        _roleId = data && data.roleId ? data.roleId : null;
        var roleName = (data && data.roleName) || '';
        $('#rolePermsRoleName').text(roleName);
        $('#rolePermsContent').hide();
        $('#rolePermsLoading').show();
        $('#bv-role-perms-save').prop('disabled', true);

        if (!_roleId) {
            $('#rolePermsLoading').hide();
            $('#rolePermsContent').show();
            $('#bv-role-perms-save').prop('disabled', false);
            return;
        }

        $.ajax({
            url: '/panel/helpdesk/roles/' + _roleId + '/permissions',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _perms = resp.permissions || {};
            renderMatrix(resp.modules || []);
            $('#rolePermsLoading').hide();
            $('#rolePermsContent').show();
            $('#bv-role-perms-save').prop('disabled', false);
        }).fail(function () {
            $('#rolePermsLoading').hide();
        });
    });

    $(document).on('click', '.bv-perm-chk', function () {
        var key = $(this).data('perm-key');
        _perms[key] = !_perms[key];
        $(this).toggleClass('on', !!_perms[key]);
        $(this).html(_perms[key] ? '<i class="fas fa-check"></i>' : '');
    });

    $(document).on('click', '#bv-role-perms-save', function () {
        if (!_roleId) { return; }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');

        $.ajax({
            url: '/panel/helpdesk/roles/' + _roleId + '/permissions',
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ permissions: _perms, apply_to_all: $('#rolePermsApplyAll').is(':checked') }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('role-perms');
            if (window.toastr) { toastr.success('Permisos guardados'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al guardar permisos';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Guardar permisos');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce

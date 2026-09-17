/*!
 * Helpdesk · modal "{name}" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/{name}.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
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

    function loadPermissions(roleId, roleName) {
        _roleId = roleId;
        $('#rolePermsRoleName').text(roleName || '');
        $('#rolePermsPickRole').hide();
        $('#rolePermsContent').hide();
        $('#rolePermsLoading').show();
        $('#bv-role-perms-save, #bv-role-perms-reset').show().prop('disabled', true);

        $.ajax({
            url: '/panel/helpdesk/roles/' + roleId + '/permissions',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _perms = resp.permissions || {};
            renderMatrix(resp.modules || []);
            $('#rolePermsLoading').hide();
            $('#rolePermsContent').show();
            $('#bv-role-perms-save').prop('disabled', false);
        }).fail(function (xhr) {
            $('#rolePermsLoading').hide();
            if (window.toastr) {
                toastr.error(xhr?.responseJSON?.message || 'No se pudieron cargar los permisos del rol');
            }
        });
    }

    function loadRoleOptions() {
        var $sel = $('#rolePermsRoleSelect').prop('disabled', true).html('<option>…</option>');
        $.ajax({
            url: '/panel/helpdesk/roles',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var roles = (resp && resp.roles) || [];
            $sel.html(roles.map(function (r) {
                return '<option value="' + r.id + '">' + escHtml(r.name) + '</option>';
            }).join('')).prop('disabled', false);
        }).fail(function () {
            $sel.html('<option value="">—</option>');
        });
    }

    $(document).on('bv:modal:open', function (e, name, data) {
        if (name !== 'role-perms') { return; }

        var roleId = data && data.roleId ? data.roleId : null;
        var roleName = (data && data.roleName) || '';

        $('#rolePermsLoading').hide();
        $('#rolePermsContent').hide();
        $('#bv-role-perms-save, #bv-role-perms-reset').prop('disabled', true);

        // Sin roleId de contexto (abierto desde "Más opciones" del inbox, no
        // desde una fila de rol/agente concreta): pedirlo primero. Los botones
        // de guardar/descartar no aplican todavía (no hay matriz cargada).
        if (!roleId) {
            $('#rolePermsRoleName').text('');
            $('#bv-role-perms-save, #bv-role-perms-reset').hide();
            $('#rolePermsPickRole').show();
            loadRoleOptions();
            return;
        }

        loadPermissions(roleId, roleName);
    });

    $(document).on('click', '#bv-role-perms-pick-continue', function () {
        var $sel = $('#rolePermsRoleSelect');
        var roleId = $sel.val();
        if (!roleId) { return; }
        loadPermissions(roleId, $sel.find('option:selected').text());
    });

    $(document).on('click', '.bv-perm-chk', function () {
        var key = $(this).data('perm-key');
        _perms[key] = !_perms[key];
        $(this).toggleClass('on', !!_perms[key]);
        $(this).html(_perms[key] ? '<i class="fas fa-check"></i>' : '');
    });

    $(document).on('click', '#bv-role-perms-reset', function () {
        if (!_roleId) { return; }
        loadPermissions(_roleId, $('#rolePermsRoleName').text());
    });

    $(document).on('click', '#bv-role-perms-save', function () {
        if (!_roleId) { return; }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');

        $.ajax({
            url: '/panel/helpdesk/roles/' + _roleId + '/permissions',
            method: 'POST',
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

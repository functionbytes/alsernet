/*!
 * Helpdesk · modal "bulk-actions" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/bulk-actions.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox (el modal se
 * incluye siempre, sin @if, desde partials/modals.blade.php). Sin interpolacion
 * Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';

    var _bulkAction  = null;
    var _bulkPayload = {};

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function getSelectedIds() {
        var ids = [];
        $('[data-bv-bulk-select]:checked').each(function () {
            var id = $(this).closest('.bv-conv').data('bv-conv-id');
            if (id) { ids.push(id); }
        });
        return ids;
    }

    function showSubPanel(type) {
        $('#bulkSubPanel').show();
        $('.bv-bulk-sub').hide();
        if (type === 'assign') {
            $('#bulkSubAssign').show();
            loadBulkAgents();
        } else if (type === 'priority') {
            $('#bulkSubPriority').show();
        } else if (type === 'delete') {
            $('#bulkSubDelete').show();
        } else if (type === 'team') {
            $('#bulkSubTeam').show();
        } else if (type === 'tag') {
            $('#bulkSubTag').show();
        } else if (type === 'snooze') {
            $('#bulkSubSnooze').show();
            // La opción "1h" ya aparece preseleccionada visualmente: fija el
            // payload por defecto para que "Aplicar" funcione sin un click extra.
            $('#bulkSubSnooze .snz-opt').removeClass('on');
            $('#bulkSubSnooze .snz-opt[data-bulk-snooze="1h"]').addClass('on');
            var defaultUntil = calcBulkSnoozeUntil('1h');
            _bulkPayload.until = defaultUntil ? defaultUntil.toISOString() : null;
        } else {
            $('#bulkSubPanel').hide();
        }
    }

    function calcBulkSnoozeUntil(opt) {
        var now = new Date();
        if (opt === '1h') { return new Date(now.getTime() + 60 * 60 * 1000); }
        if (opt === '4h') { return new Date(now.getTime() + 4 * 60 * 60 * 1000); }
        if (opt === 'tom') {
            var t = new Date(now); t.setDate(t.getDate() + 1); t.setHours(9, 0, 0, 0); return t;
        }
        if (opt === 'week') {
            var w = new Date(now);
            var diff = (8 - w.getDay()) % 7 || 7;
            w.setDate(w.getDate() + diff); w.setHours(9, 0, 0, 0); return w;
        }
        return null;
    }

    function loadBulkAgents() {
        $('#bulkAssignList').html('<div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>');
        $.ajax({
            url: '/panel/helpdesk/presence/agents',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var agents = resp.agents || [];
            if (!agents.length) {
                $('#bulkAssignList').html('<div class="bv-cv-loading-msg">Sin agentes</div>');
                return;
            }
            var html = agents.map(function (a, i) {
                var init = ((a.firstname || a.name || '?')[0] + ((a.lastname || '')[0] || '')).toUpperCase();
                return '<button class="asgn-item" data-agent-id="' + a.id + '">' +
                    '<div class="bv-av c' + ((i % 7) + 1) + ' asgn-av">' + init + '<span class="bv-av-dot ' + (a.status || 'offline') + '"></span></div>' +
                    '<div class="asgn-body"><span class="asgn-t">' + (a.name || ((a.firstname || '') + ' ' + (a.lastname || '')).trim()) + '</span></div>' +
                    '<div class="asgn-check"><i class="fas fa-check"></i></div></button>';
            }).join('');
            $('#bulkAssignList').html(html);
        }).fail(function () {
            $('#bulkAssignList').html('<div class="bv-cv-loading-msg"><i class="fas fa-triangle-exclamation"></i></div>');
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'bulk-actions') { return; }
        _bulkAction = null;
        _bulkPayload = {};
        $('.bv-bulk-act').removeClass('on');
        $('#bulkSubPanel').hide();
        $('#bv-bulk-apply').prop('disabled', true).text('Aplicar acción');

        var n = getSelectedIds().length || parseInt($('[data-bulk-count]').data('bulk-count') || '0', 10);
        $('#bulkActionsCount').text(n);
        $('#bulkActionsCountText').text(n + ' conversacion' + (n === 1 ? '' : 'es'));
    });

    $(document).on('click', '.bv-bulk-act', function () {
        $('.bv-bulk-act').removeClass('on');
        $(this).addClass('on');
        _bulkAction = $(this).data('bulk-action');
        _bulkPayload = {};
        $('#bv-bulk-apply').prop('disabled', false);

        var noSubPanel = ['resolve', 'close', 'archive', 'mute'];
        if (noSubPanel.indexOf(_bulkAction) !== -1) {
            $('#bulkSubPanel').hide();
        } else {
            showSubPanel(_bulkAction);
        }
    });

    $(document).on('click', '#bulkAssignList .asgn-item', function () {
        $('#bulkAssignList .asgn-item').removeClass('on');
        $(this).addClass('on');
        _bulkPayload.assignee_id = $(this).data('agent-id');
    });

    $(document).on('input', '#bulkAssignSearch', function () {
        var q = $(this).val().toLowerCase();
        $('#bulkAssignList .asgn-item').each(function () {
            $(this).toggle(!q || $(this).find('.asgn-t').text().toLowerCase().indexOf(q) !== -1);
        });
    });

    $(document).on('click', '#bulkSubPriority .bv-opt', function () {
        $('#bulkSubPriority .bv-opt').removeClass('on');
        $(this).addClass('on');
        _bulkPayload.priority = $(this).data('bv-value');
    });

    $(document).on('click', '#bulkSubTeam .bv-opt', function () {
        $('#bulkSubTeam .bv-opt').removeClass('on');
        $(this).addClass('on');
        _bulkPayload.group_id = $(this).data('bulk-group-id');
    });

    $(document).on('click', '#bulkSubTag .bv-rtag', function () {
        $(this).toggleClass('bv-rtag--on');
        var ids = $('#bulkSubTag .bv-rtag--on').map(function () { return $(this).data('bulk-tag-id'); }).get();
        _bulkPayload.tag_ids = ids;
    });

    $(document).on('click', '#bulkSubSnooze .snz-opt', function () {
        $('#bulkSubSnooze .snz-opt').removeClass('on');
        $(this).addClass('on');
        var until = calcBulkSnoozeUntil($(this).data('bulk-snooze'));
        _bulkPayload.until = until ? until.toISOString() : null;
    });

    $(document).on('click', '#bv-bulk-apply', function () {
        if (!_bulkAction) { return; }

        var ids = getSelectedIds();
        if (!ids.length) {
            if (window.toastr) { toastr.warning('Sin conversaciones seleccionadas'); }
            return;
        }

        // "Resolver" se traduce a la acción "close" del endpoint bulk.
        var action = _bulkAction === 'resolve' ? 'close' : _bulkAction;
        var payload = { action: action, ids: ids, payload: _bulkPayload };
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Procesando…');

        $.ajax({
            url: '/panel/helpdesk/conversations/bulk',
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('bulk-actions');
            var msg = resp.message || 'Acción aplicada a ' + ids.length + ' conversación/es';
            if (window.toastr) { toastr.success(msg); }
            $(document).trigger('bv:bulk:done', [_bulkAction, ids]);
            ids.forEach(function (id) { $('.bv-conv[data-bv-conv-id="' + id + '"] [data-bv-bulk-select]').prop('checked', false); });
            $(document).trigger('bv:selection-changed');
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al aplicar acción masiva';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false).text('Aplicar acción'); });
    });

}(window.jQuery));

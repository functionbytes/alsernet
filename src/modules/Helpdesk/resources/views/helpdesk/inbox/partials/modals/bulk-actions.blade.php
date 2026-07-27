{{-- Modal: Acciones masivas (#58 ve-bulk-actions) --}}
<div class="bv-modal" data-bv-modal-name="bulk-actions">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-list-check"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_title') }} <span class="bv-chip-id" id="bulkActionsCount">0</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-warn-box bv-warn-box--info bv-x21">
                <div class="bv-warn-box__body">
                    {{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_notice_1') }} <strong id="bulkActionsCountText">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_default_count') }}</strong> {{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_notice_2') }}
                </div>
            </div>

            <div class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_available_label') }}</div>
            <div class="bv-bulk-grid">
                <button class="bv-bulk-act" data-bulk-action="assign">
                    <i class="fas fa-user-check"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_assign') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="priority">
                    <i class="fas fa-flag"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_priority') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="resolve">
                    <i class="fas fa-check"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_resolve') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="close">
                    <i class="fas fa-xmark"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_close') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="archive">
                    <i class="fas fa-box-archive"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_archive') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="team">
                    <i class="fas fa-people-group"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_team') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="tag">
                    <i class="fas fa-tag"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_tag') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="snooze">
                    <i class="far fa-clock"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_snooze') }}</span>
                </button>
                <button class="bv-bulk-act" data-bulk-action="mute">
                    <i class="fas fa-bell-slash"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_action_mute') }}</span>
                </button>
            </div>

            {{-- Sub-panel condicional por acción --}}
            <div id="bulkSubPanel" style="display:none;margin-top:14px">

                {{-- Assign sub-panel --}}
                <div id="bulkSubAssign" class="bv-bulk-sub" style="display:none">
                    <div class="bv-modal-search">
                        <i class="fas fa-magnifying-glass"></i>
                        <input id="bulkAssignSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_assign_search_placeholder') }}">
                    </div>
                    <div id="bulkAssignList" class="asgn-list">
                        <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                </div>

                {{-- Priority sub-panel --}}
                <div id="bulkSubPriority" class="bv-bulk-sub" style="display:none">
                    <div class="bv-opt-list">
                        <button class="bv-opt" data-bv-value="low"><div class="bv-opt__ic"><i class="fas fa-chevron-down"></i></div><div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_priority_low') }}</span></div></button>
                        <button class="bv-opt on" data-bv-value="normal"><div class="bv-opt__ic"><i class="fas fa-minus"></i></div><div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_priority_normal') }}</span></div></button>
                        <button class="bv-opt" data-bv-value="high"><div class="bv-opt__ic"><i class="fas fa-chevron-up"></i></div><div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_priority_high') }}</span></div></button>
                        <button class="bv-opt" data-bv-value="urgent"><div class="bv-opt__ic"><i class="fas fa-angles-up"></i></div><div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_priority_urgent') }}</span></div></button>
                    </div>
                </div>

                {{-- Delete confirm sub-panel --}}
                <div id="bulkSubDelete" class="bv-bulk-sub" style="display:none">
                    <div class="bv-warn-box">
                        <div class="bv-warn-box__body">
                            <strong>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_delete_confirm_strong') }}</strong> {{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_delete_confirm_text') }}
                        </div>
                    </div>
                </div>

                {{-- Team sub-panel --}}
                <div id="bulkSubTeam" class="bv-bulk-sub" style="display:none">
                    <div class="bv-opt-list">
                        @forelse($groups ?? [] as $group)
                            <button type="button" class="bv-opt" data-bulk-group-id="{{ $group->id }}">
                                <div class="bv-av c{{ ($loop->index % 8) + 1 }}"><i class="fas fa-users bv-icon-sm"></i></div>
                                <div class="body"><div class="name">{{ $group->name }}</div></div>
                                <i class="fas fa-check check"></i>
                            </button>
                        @empty
                            <div class="bv-empty-msg">{{ __('helpdesk::helpdesk.inbox.modals.move_team_empty') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- Tag sub-panel (multi-select) --}}
                <div id="bulkSubTag" class="bv-bulk-sub" style="display:none">
                    <div class="bv-tags-chip-wrap">
                        @forelse($inboxTags ?? [] as $tag)
                            <span class="bv-rtag" data-bulk-tag-id="{{ $tag->id }}">{{ $tag->name }}</span>
                        @empty
                            <em class="bv-tags-empty">{{ __('helpdesk::helpdesk.inbox.modals.tags_none_available') }}</em>
                        @endforelse
                    </div>
                </div>

                {{-- Snooze sub-panel --}}
                <div id="bulkSubSnooze" class="bv-bulk-sub" style="display:none">
                    <div class="snz-list">
                        <button type="button" class="snz-opt on" data-bulk-snooze="1h">
                            <i class="fa-solid fa-stopwatch snz-ic"></i>
                            <div class="snz-body"><b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_1h') }}</b></div>
                        </button>
                        <button type="button" class="snz-opt" data-bulk-snooze="4h">
                            <i class="fa-regular fa-clock snz-ic"></i>
                            <div class="snz-body"><b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_4h') }}</b></div>
                        </button>
                        <button type="button" class="snz-opt" data-bulk-snooze="tom">
                            <i class="fa-solid fa-sun snz-ic"></i>
                            <div class="snz-body"><b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_tomorrow') }}</b></div>
                        </button>
                        <button type="button" class="snz-opt" data-bulk-snooze="week">
                            <i class="fa-solid fa-calendar-week snz-ic"></i>
                            <div class="snz-body"><b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_week') }}</b></div>
                        </button>
                    </div>
                </div>

            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-bulk-apply" disabled>{{ __('helpdesk::helpdesk.inbox.modals.bulk_actions_apply') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce

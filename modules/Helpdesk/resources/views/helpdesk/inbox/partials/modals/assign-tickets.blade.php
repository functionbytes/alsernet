{{-- Modal: Asignar tickets a la conversación (#49 ve-assign-tickets) --}}
<div class="bv-modal" data-bv-modal-name="assign-tickets">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-ticket"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-modal-search bv-x14">
                <i class="fas fa-magnifying-glass"></i>
                <input id="assignTicketsSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_search_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-hist-pills bv-x14">
                <button class="bv-hist-pill on" data-atk-filter="open">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_filter_open') }} <span class="bv-pill-count" id="atkCountOpen">—</span></button>
                <button class="bv-hist-pill" data-atk-filter="mine">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_filter_mine') }} <span class="bv-pill-count" id="atkCountMine">—</span></button>
                <button class="bv-hist-pill" data-atk-filter="closed">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_filter_closed') }} <span class="bv-pill-count" id="atkCountClosed">—</span></button>
            </div>

            {{-- Tickets vinculados a esta conversación --}}
            <div id="atkLinkedSection" style="display:none;margin-bottom:10px">
                <div class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_linked_label') }}</div>
                <div class="bv-x15" id="atkLinkedList"></div>
            </div>

            {{-- Tickets disponibles --}}
            <div class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_available_label') }}</div>
            <div class="bv-x16" id="atkAvailableList">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-assign-tickets-link" disabled>{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_link_selected') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _atkFilter   = 'open';
    var _atkSearch   = '';
    var _atkSelected = null;
    var _atkAll      = [];
    var _atkLinked   = [];
    var _atkTimer    = null;

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escHtml(s) {
        if (s == null) { return ''; }
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function tkCard(t, selectable) {
        var linked = _atkLinked.some(function (l) { return l.id === t.id; });
        var selClass = selectable ? ' bv-atk-card' : '';
        var disAttr  = selectable && linked ? ' disabled title="Ya vinculado"' : '';
        var onClass  = selectable && _atkSelected === t.id ? ' on' : '';
        return '<button type="button" class="bv-tk-card bv-right-tk' + selClass + onClass + '"' +
            (selectable ? ' data-tk-id="' + t.id + '"' : '') + disAttr + '>' +
            '<div class="bv-tk-card__head">' +
                '<span class="bv-chip-id bv-x17">#' + escHtml(t.number || t.id) + '</span>' +
                '<span class="bv-tk-status">' + escHtml(t.status_label || t.status || '') + '</span>' +
            '</div>' +
            '<div class="bv-tk-card__title">' + escHtml(t.subject || t.title || '') + '</div>' +
            '<div class="bv-tk-card__foot">' +
                (t.assignee_name ? '<span>' + escHtml(t.assignee_name) + '</span>' : '<span class="text-muted bv-x18">Sin asignar</span>') +
                (t.category_name ? '<span><i class="far fa-folder"></i> ' + escHtml(t.category_name) + '</span>' : '') +
                (t.updated_at_human ? '<span class="ms-auto"><i class="far fa-clock"></i> ' + escHtml(t.updated_at_human) + '</span>' : '') +
            '</div>' +
            '</button>';
    }

    function renderLinked() {
        if (!_atkLinked.length) {
            $('#atkLinkedSection').hide();
            return;
        }
        $('#atkLinkedSection').show();
        $('#atkLinkedList').html(_atkLinked.map(function (t) { return tkCard(t, false); }).join(''));
    }

    function filtered() {
        return _atkAll.filter(function (t) {
            var okFilter = true;
            if (_atkFilter === 'open') { okFilter = ['open','pending','in_progress'].indexOf((t.status || '').toLowerCase()) !== -1; }
            if (_atkFilter === 'mine') { okFilter = !!t.is_mine; }
            if (_atkFilter === 'closed') { okFilter = ['closed','resolved','cancelled'].indexOf((t.status || '').toLowerCase()) !== -1; }
            var okSearch = !_atkSearch || (t.subject || '').toLowerCase().indexOf(_atkSearch) !== -1 || String(t.number || t.id).indexOf(_atkSearch) !== -1;
            return okFilter && okSearch;
        });
    }

    function renderAvailable() {
        var list = filtered();
        if (!list.length) {
            $('#atkAvailableList').html('<div class="bv-cv-loading-msg"><i class="fas fa-inbox"></i> Sin tickets disponibles</div>');
            return;
        }
        $('#atkAvailableList').html(list.map(function (t) { return tkCard(t, true); }).join(''));

        var open   = _atkAll.filter(function (t) { return ['open','pending','in_progress'].indexOf((t.status || '').toLowerCase()) !== -1; }).length;
        var mine   = _atkAll.filter(function (t) { return !!t.is_mine; }).length;
        var closed = _atkAll.filter(function (t) { return ['closed','resolved','cancelled'].indexOf((t.status || '').toLowerCase()) !== -1; }).length;
        $('#atkCountOpen').text(open);
        $('#atkCountMine').text(mine);
        $('#atkCountClosed').text(closed);
    }

    function loadTickets() {
        _atkAll      = [];
        _atkLinked   = [];
        _atkSelected = null;
        $('#bv-assign-tickets-link').prop('disabled', true);
        $('#atkAvailableList').html('<div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>');
        $('#atkLinkedSection').hide();

        var convId = getConvId();

        $.ajax({
            url: '/panel/helpdesk/tickets',
            method: 'GET',
            data: { format: 'json', per_page: 50 },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _atkAll = resp.data || resp.tickets || resp || [];

            if (convId) {
                _atkLinked = _atkAll.filter(function (t) {
                    return t.conversation_id === convId || (t.conversation_ids || []).indexOf(convId) !== -1;
                });
            }

            renderLinked();
            renderAvailable();
        }).fail(function () {
            $('#atkAvailableList').html('<div class="bv-cv-loading-msg"><i class="fas fa-triangle-exclamation"></i> Error al cargar</div>');
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'assign-tickets') { return; }
        _atkFilter = 'open';
        _atkSearch = '';
        $('#assignTicketsSearch').val('');
        $('.bv-hist-pill[data-atk-filter]').removeClass('on');
        $('.bv-hist-pill[data-atk-filter="open"]').addClass('on');
        loadTickets();
    });

    $(document).on('click', '.bv-hist-pill[data-atk-filter]', function () {
        $('.bv-hist-pill[data-atk-filter]').removeClass('on');
        $(this).addClass('on');
        _atkFilter = $(this).data('atk-filter');
        _atkSelected = null;
        $('#bv-assign-tickets-link').prop('disabled', true);
        renderAvailable();
    });

    $(document).on('input', '#assignTicketsSearch', function () {
        clearTimeout(_atkTimer);
        var q = $(this).val().toLowerCase().trim();
        _atkTimer = setTimeout(function () {
            _atkSearch   = q;
            _atkSelected = null;
            $('#bv-assign-tickets-link').prop('disabled', true);
            renderAvailable();
        }, 200);
    });

    $(document).on('click', '.bv-atk-card:not([disabled])', function () {
        $('.bv-atk-card').removeClass('on');
        $(this).addClass('on');
        _atkSelected = $(this).data('tk-id');
        $('#bv-assign-tickets-link').prop('disabled', false);
    });

    $(document).on('click', '#bv-assign-tickets-link', function () {
        if (!_atkSelected) { return; }
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Vinculando…');
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/ticket',
            method: 'POST',
            data: { ticket_id: _atkSelected },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('assign-tickets');
            if (window.toastr) { toastr.success('Ticket vinculado a la conversación'); }
            $(document).trigger('bv:ticket:linked', [convId, _atkSelected]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al vincular ticket';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Vincular seleccionado');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce

{{-- Modal: Lista de tickets del cliente (#21 ve-tickets-panel) --}}
<div class="bv-modal" data-bv-modal-name="tickets-panel">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-x67">
            <div class="bv-tk-panel-head">
                <span class="bv-tk-panel-head__count" id="tpCount">0</span>
                <div class="bv-tk-panel-head__meta">
                    <span class="bv-tk-panel-head__lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_label') }}</span>
                    <span class="bv-tk-panel-head__sub" id="tpSubtitle">—</span>
                </div>
                <button type="button" class="bv-tk-panel-head__add" id="bv-tp-new" title="{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_new') }}"><i class="fas fa-plus"></i></button>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-hist-pills" id="ticketsPanelFilter">
                <button class="bv-hist-pill on" data-tpf="all">{{ __('helpdesk::helpdesk.inbox.modals.all_label') }} <span class="bv-pill-count" id="tpCountAll">0</span></button>
                <button class="bv-hist-pill" data-tpf="open">{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_open') }} <span class="bv-pill-count" id="tpCountOpen">0</span></button>
                <button class="bv-hist-pill" data-tpf="closed">{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_closed') }} <span class="bv-pill-count" id="tpCountClosed">0</span></button>
            </div>

            <div id="ticketsPanelLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="ticketsPanelList" class="bv-tk-list" style="display:none"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-tp-new-btn">{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_new') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _tpAll    = [];
    var _tpFilter = 'all';

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function isOpen(t) {
        return ['open','pending','in_progress'].indexOf((t.status || '').toLowerCase()) !== -1;
    }

    function filtered() {
        return _tpAll.filter(function (t) {
            if (_tpFilter === 'open')   { return isOpen(t); }
            if (_tpFilter === 'closed') { return !isOpen(t); }
            return true;
        });
    }

    function renderList() {
        var list = filtered();
        if (!list.length) {
            $('#ticketsPanelList').html('<div class="bv-cv-loading-msg"><i class="fas fa-inbox"></i></div>').show();
            return;
        }
        var html = list.map(function (t) {
            var number = t.number || t.id;
            return '<button type="button" class="bv-tk-card" data-tk-id="' + t.id + '">' +
                '<div class="bv-tk-card__head">' +
                    '<i class="fas fa-bars bv-x68"></i>' +
                    '<span class="bv-tk-card__id">#' + escHtml(number) + '</span>' +
                    '<span class="bv-tk-card__status">' + escHtml(t.status_label || t.status || '') + '</span>' +
                '</div>' +
                '<div class="bv-tk-card__title">' + escHtml(t.subject || t.title || '—') + '</div>' +
                '<div class="bv-tk-card__foot">' +
                    (t.assignee_name ? '<span>' + escHtml(t.assignee_name) + '</span>' : '<span class="bv-tk-card__unassigned">Sin asignar</span>') +
                    (t.category_name ? '<span><i class="far fa-folder"></i> ' + escHtml(t.category_name) + '</span>' : '') +
                    (t.updated_at_human ? '<span class="bv-x69"><i class="far fa-clock"></i> ' + escHtml(t.updated_at_human) + '</span>' : '') +
                '</div>' +
                '</button>';
        }).join('');
        $('#ticketsPanelList').html(html).show();

        var open   = _tpAll.filter(isOpen).length;
        var closed = _tpAll.length - open;
        $('#tpCount').text(_tpAll.length);
        $('#tpSubtitle').text(open + ' abiertos');
        $('#tpCountAll').text(_tpAll.length);
        $('#tpCountOpen').text(open);
        $('#tpCountClosed').text(closed);
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'tickets-panel') { return; }
        _tpAll    = [];
        _tpFilter = 'all';
        $('.bv-hist-pill[data-tpf]').removeClass('on');
        $('.bv-hist-pill[data-tpf="all"]').addClass('on');
        $('#ticketsPanelList').hide().empty();
        $('#ticketsPanelLoading').show().html('<i class="fas fa-spinner fa-spin"></i>');

        var customerId = $('[data-customer-id]').first().attr('data-customer-id');
        if (!customerId) {
            $('#ticketsPanelLoading').html('<i class="fas fa-user-slash"></i>');
            return;
        }

        $.ajax({
            url: '/panel/helpdesk/customers/' + customerId + '/tickets',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _tpAll = resp.data || resp || [];
            $('#ticketsPanelLoading').hide();
            renderList();
        }).fail(function () {
            $('#ticketsPanelLoading').html('<i class="fas fa-triangle-exclamation"></i>');
        });
    });

    $(document).on('click', '.bv-hist-pill[data-tpf]', function () {
        $('.bv-hist-pill[data-tpf]').removeClass('on');
        $(this).addClass('on');
        _tpFilter = $(this).data('tpf');
        renderList();
    });

    $(document).on('click', '.bv-tk-card', function () {
        var id = $(this).data('tk-id');
        $('[data-bv-modal-name="tickets-panel"]').removeClass('on');
        $(document).trigger('bv:modal:open', ['ticket', { ticketId: id }]);
    });

    function openNewTicket() {
        $('[data-bv-modal-name="tickets-panel"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
        $(document).trigger('bv:modal:open', ['create-ticket']);
    }

    $(document).on('click', '#bv-tp-new, #bv-tp-new-btn', openNewTicket);

}(window.jQuery));
</script>
@endpush
@endonce

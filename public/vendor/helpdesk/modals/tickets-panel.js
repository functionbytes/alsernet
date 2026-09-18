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

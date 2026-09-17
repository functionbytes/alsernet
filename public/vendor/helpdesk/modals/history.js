/*!
 * Helpdesk · modal "history" del inbox (historial de conversaciones previas
 * del mismo cliente).
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals.blade.php,
 * donde vivia inline al final del archivo (no en history.blade.php, que solo
 * trae el markup). No esta en window.BvLazyModalScripts (carga lazy de la
 * auditoria 17-sep-2026): sigue cargandose siempre, igual que antes.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';
    if (!window.jQuery) { return; }

    var _histData   = [];
    var _histFilter = 'all';
    var _histSearch = '';

    function getCustomerId() {
        var fromAttr = $('[data-customer-id]').first().attr('data-customer-id');
        if (fromAttr) { return fromAttr; }
        if (window.HDCommerce && typeof window.HDCommerce.customerId === 'function') {
            return window.HDCommerce.customerId();
        }
        return null;
    }

    function escHtml(s) {
        if (s == null) { return ''; }
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function escAttr(s) {
        if (s == null) { return ''; }
        return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function channelIcon(ch) {
        var map = {
            whatsapp: 'fa-brands fa-whatsapp', facebook: 'fa-brands fa-facebook-messenger',
            instagram: 'fa-brands fa-instagram', email: 'fa-regular fa-envelope',
            widget: 'fa-regular fa-comment', webchat: 'fa-regular fa-comment',
        };
        return map[ch] || 'fa-regular fa-comment-dots';
    }

    function renderList(data) {
        var filtered = data.filter(function (c) {
            var okFilter = _histFilter === 'all' ||
                           (_histFilter === 'open' && c.status_open) ||
                           (_histFilter === 'closed' && !c.status_open);
            var okSearch = !_histSearch ||
                           (c.subject || '').toLowerCase().indexOf(_histSearch) !== -1 ||
                           (c.preview || '').toLowerCase().indexOf(_histSearch) !== -1;
            return okFilter && okSearch;
        });

        if (!filtered.length) {
            $('#histList').html('<div class="bv-cv-loading-msg"><i class="fas fa-inbox"></i></div>');
            return;
        }

        var html = filtered.map(function (c) {
            var previewUrl = '/panel/helpdesk/conversations/' + c.id + '/preview';
            var openUrl    = '/panel/helpdesk/conversations?selected=' + c.id;
            var stClass    = c.status_open ? 'open' : '';
            return '<div class="bv-hist-item rp3-prev"' +
                   ' data-preview-url="' + escAttr(previewUrl) + '"' +
                   ' data-open-url="' + escAttr(openUrl) + '"' +
                   ' data-subject="' + escAttr(c.subject) + '">' +
                       '<div class="bv-hist-ch"><i class="' + channelIcon(c.channel) + '"></i></div>' +
                       '<div class="bv-hist-info">' +
                           '<div class="bv-hist-subj">' + escHtml(c.subject || ('#' + c.id)) + '</div>' +
                           '<div class="bv-hist-prev">' + escHtml(c.preview || '') + '</div>' +
                       '</div>' +
                       '<div class="bv-hist-meta">' +
                           '<span class="bv-hist-status ' + stClass + '">' + escHtml(c.status || (c.status_open ? 'Abierta' : 'Cerrada')) + '</span>' +
                           '<span class="bv-hist-time">' + escHtml(c.time) + '</span>' +
                       '</div>' +
                   '</div>';
        }).join('');

        $('#histList').html(html);
    }

    function loadHistory(customerId) {
        _histData   = [];
        _histFilter = 'all';
        _histSearch = '';
        $('#histSearchInput').val('');
        $('.bv-hist-pill').removeClass('on');
        $('.bv-hist-pill[data-bv-hist-filter="all"]').addClass('on');
        $('#histList').html('<div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>');

        $.ajax({
            url: '/panel/helpdesk/customers/' + customerId + '/conversations',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _histData = resp.data || [];
            var open   = _histData.filter(function (c) { return c.status_open; }).length;
            var closed = _histData.length - open;

            $('#histModalCount').text(_histData.length);
            $('#histCountAll').text(_histData.length);
            $('#histCountOpen').text(open);
            $('#histCountClosed').text(closed);

            renderList(_histData);
        }).fail(function () {
            $('#histList').html('<div class="bv-cv-loading-msg"><i class="fas fa-triangle-exclamation"></i></div>');
        });
    }

    // Cargar cuando se abre el modal history
    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'history') { return; }
        var customerId = getCustomerId();
        if (!customerId) {
            $('#histList').html('<div class="bv-cv-loading-msg"><i class="fas fa-user-slash"></i></div>');
            return;
        }
        loadHistory(customerId);
    });

    // Filter pills
    $(document).on('click', '.bv-hist-pill', function () {
        $('.bv-hist-pill').removeClass('on');
        $(this).addClass('on');
        _histFilter = $(this).data('bv-hist-filter') || 'all';
        renderList(_histData);
    });

    // Search
    $(document).on('input', '#histSearchInput', function () {
        _histSearch = $(this).val().toLowerCase().trim();
        renderList(_histData);
    });

}(window.jQuery));

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

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function timeAgo(dateStr) {
        var diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 60)  { return 'hace ' + diff + 's'; }
        if (diff < 3600) { return 'hace ' + Math.floor(diff / 60) + ' min'; }
        if (diff < 86400) { return 'hace ' + Math.floor(diff / 3600) + 'h'; }
        return 'hace ' + Math.floor(diff / 86400) + 'd';
    }

    function renderNotifications(notifs) {
        if (!notifs.length) {
            $('#notifList').html('<div class="bv-cv-loading-msg bv-x46"><i class="fas fa-bell-slash"></i> Sin notificaciones</div>');
            return;
        }

        var unread = notifs.filter(function (n) { return !n.read_at; }).length;
        if (unread > 0) {
            $('#notifCount').text(unread).show();
        } else {
            $('#notifCount').hide();
        }

        var html = notifs.map(function (n) {
            var isUnread = !n.read_at;
            var data = n.data || {};
            var icon = data.icon || 'far fa-bell';
            var title = data.title || n.type || 'Notificación';
            var message = data.message || '';
            var time = n.created_at ? timeAgo(n.created_at) : '';
            return '<div class="bv-notif-item' + (isUnread ? ' unread' : '') + '" data-notif-id="' + n.id + '">' +
                '<div class="bv-notif-ico"><i class="' + icon + '"></i></div>' +
                '<div class="bv-notif-body">' +
                    '<div class="bv-notif-head">' +
                        '<span class="bv-notif-title">' + title + '</span>' +
                        (isUnread ? '<span class="bv-notif-badge">Nuevo</span>' : '') +
                    '</div>' +
                    (message ? '<div class="bv-notif-desc">' + message + '</div>' : '') +
                    (time ? '<div class="bv-notif-meta"><i class="far fa-clock"></i> ' + time + '</div>' : '') +
                '</div>' +
                '<button class="bv-notif-dismiss" data-notif-dismiss="' + n.id + '" title="Descartar"><i class="fas fa-xmark"></i></button>' +
                '</div>';
        }).join('');

        $('#notifList').html(html);
    }

    function loadNotifications() {
        $('#notifList').html('<div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>');
        $('#notifCount').hide();

        $.ajax({
            url: '/api/notifications',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var notifs = resp.data || resp.notifications || resp || [];
            renderNotifications(notifs);
        }).fail(function () {
            $('#notifList').html('<div class="bv-cv-loading-msg"><i class="fas fa-triangle-exclamation"></i> Error al cargar</div>');
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'notifications') { return; }
        loadNotifications();
    });

    $(document).on('click', '#bv-notif-mark-all', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/api/notifications/mark-all-read',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            $('#notifList .bv-notif-item').removeClass('unread');
            $('#notifList .bv-notif-badge').remove();
            $('#notifCount').hide();
            if (window.toastr) { toastr.success('Notificaciones marcadas como leídas'); }
            $(document).trigger('bv:notifications:cleared');
        }).fail(function () {
            if (window.toastr) { toastr.error('Error al actualizar notificaciones'); }
        }).always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.bv-notif-dismiss', function (e) {
        e.stopPropagation();
        var id  = $(this).data('notif-dismiss');
        var $el = $(this).closest('.bv-notif-item');
        $.ajax({
            url: '/api/notifications/' + id,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            $el.slideUp(200, function () { $(this).remove(); });
        });
    });

    $(document).on('click', '.bv-notif-item', function () {
        var id = $(this).data('notif-id');
        if (!$(this).hasClass('unread')) { return; }
        $.ajax({
            url: '/api/notifications/' + id + '/read',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            $('[data-notif-id="' + id + '"]').removeClass('unread').find('.bv-notif-badge').remove();
            var remaining = $('#notifList .bv-notif-item.unread').length;
            if (remaining > 0) { $('#notifCount').text(remaining); } else { $('#notifCount').hide(); }
        });
    });

}(window.jQuery));

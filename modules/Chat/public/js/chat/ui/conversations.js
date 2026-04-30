(function ($) {
    'use strict';

    function init() {
        // Clic en cualquier item de conversación
        $(document).on('click', '.inbox-chat-item[data-conversation-id]', function (e) {
            e.preventDefault();
            var $item = $(this);

            if ($item.hasClass('active') || $item.hasClass('loading')) { return; }

            loadConversation($item.data('conversation-id'), $item);
        });

        // Load-more button
        $(document).on('click', '#btn-load-more', function () {
            loadMore($(this));
        });

        autoLoadFirst();
    }

    function autoLoadFirst() {
        var $first = $('.inbox-chat-item[data-conversation-id]').first();
        if ($first.length) {
            loadConversation($first.data('conversation-id'), $first);
        }
    }

    function loadConversation(conversationId, $item) {
        $('.inbox-chat-item').removeClass('active loading');
        $item.addClass('active loading');

        // Clear cached selectors when switching conversations
        if (window.ChatUtils && window.ChatUtils.clearSelectorCache) {
            window.ChatUtils.clearSelectorCache();
        }

        $('#conversation-detail').html(
            '<div class="d-flex align-items-center justify-content-center h-100">' +
                '<div class="text-center text-muted">' +
                    '<div class="spinner-border text-primary mb-3" role="status">' +
                        '<span class="visually-hidden">Cargando...</span>' +
                    '</div>' +
                    '<p class="mb-0">Cargando conversación...</p>' +
                '</div>' +
            '</div>'
        );

        $.ajax({
            url:     (window.ChatConfig && window.ChatConfig.baseUrl || '/helpdesk/conversations') + '/' + conversationId,
            method:  'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        })
        .done(function (html) {
            $item.removeClass('loading');
            $('#conversation-detail').html(html);

            // Use requestAnimationFrame for smooth scroll
            requestAnimationFrame(function () {
                var chatBox = document.querySelector('.chat-box-inner');
                if (chatBox) {
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            });
        })
        .fail(function (xhr, status, error) {
            $item.removeClass('active loading');

            var errorMsg = 'Error al cargar la conversación';
            if (xhr.status === 404) {
                errorMsg = 'Conversación no encontrada';
            } else if (xhr.status === 403) {
                errorMsg = 'No tienes permiso para ver esta conversación';
            } else if (xhr.status === 0) {
                errorMsg = 'Sin conexión. Comprueba tu red e inténtalo de nuevo.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }

            if (window.toastr) {
                toastr.error(errorMsg);
            }

            $('#conversation-detail').html(
                '<div class="d-flex align-items-center justify-content-center h-100">' +
                    '<div class="text-center text-danger">' +
                        '<i class="fas fa-exclamation-triangle fs-1 mb-3"></i>' +
                        '<p>' + $('<span>').text(errorMsg).html() + '</p>' +
                        '<button class="btn btn-sm btn-outline-primary mt-3" onclick="location.reload()">' +
                            '<i class="fas fa-sync me-1"></i>Reintentar' +
                        '</button>' +
                    '</div>' +
                '</div>'
            );

            console.error('Error loading conversation:', { xhr: xhr, status: status, error: error });
        });
    }

    function loadMore($btn) {
        if ($btn.prop('disabled')) { return; }

        var url      = $btn.data('load-more-url');
        var cursor   = $btn.data('cursor');
        var filter   = $btn.data('filter') || 'all';
        var inboxId  = $btn.data('inbox-id');
        var teamId   = $btn.data('team-id');
        var label    = $btn.data('label');

        var params = { cursor: cursor, filter: filter };
        if (inboxId) { params.inbox_id = inboxId; }
        if (teamId)  { params.team_id  = teamId; }
        if (label)   { params.label    = label; }

        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Cargando...'
        );

        $.ajax({
            url:     url,
            method:  'GET',
            data:    params,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        })
        .done(function (response) {
            var list = document.getElementById('conversation-list-items');
            if (!list) { return; }

            // OPTIMIZADO: Usa DocumentFragment para batch insertion
            var fragment = document.createDocumentFragment();
            var tempDiv = document.createElement('div');

            for (var i = 0; i < response.items.length; i++) {
                tempDiv.innerHTML = renderConversationItem(response.items[i]);
                while (tempDiv.firstChild) {
                    fragment.appendChild(tempDiv.firstChild);
                }
            }

            list.appendChild(fragment);

            if (response.has_more && response.next_cursor) {
                $btn.data('cursor', response.next_cursor)
                    .prop('disabled', false)
                    .html('<i class="fas fa-chevron-down me-1"></i>Cargar mas');
            } else {
                $('#load-more-container').remove();
            }
        })
        .fail(function (xhr) {
            $btn.prop('disabled', false).html('<i class="fas fa-chevron-down me-1"></i>Cargar mas');

            var msg = (xhr.status === 0)
                ? 'Sin conexión. Comprueba tu red e inténtalo de nuevo.'
                : (xhr.responseJSON && xhr.responseJSON.message) || 'Error al cargar más conversaciones.';

            if (window.toastr) {
                toastr.error(msg);
            }

            console.error('Error loading more conversations:', xhr);
        });
    }

    /**
     * Renderiza un item de conversación
     * OPTIMIZADO: Usa array.join() para construcción de HTML más rápida
     */
    function renderConversationItem(item) {
        var priorityIcon = priorityIconHtml(item.priority_slug, item.priority_name);
        var escapedCustomer = $('<span>').text(item.customer_name || '').html();
        var escapedActivity = $('<span>').text(item.last_activity_at).html();
        var escapedMessage = $('<span>').text(item.last_message).html();
        var escapedChannel = $('<span>').text(item.channel_display_name).html();

        var parts = [
            '<li>',
            '<a href="javascript:void(0)" class="inbox-chat-item" data-conversation-id="', item.id, '">',
                '<div class="position-relative w-100 ms-2">',
                    '<div class="d-flex align-items-center justify-content-between mb-2">',
                        '<h6 class="customer">', escapedCustomer, '</h6>',
                        '<p class="mb-0 fs-2 text-muted">', escapedActivity, '</p>',
                    '</div>',
                    '<span class="message">', escapedMessage, '</span>',
                    '<div class="d-flex align-items-center justify-content-between">',
                        '<div class="d-flex align-items-center">',
                            '<span class="d-block">', priorityIcon, '</span>',
                        '</div>',
                        '<span class="badge">', escapedChannel, '</span>',
                    '</div>',
                '</div>',
            '</a>',
            '</li>'
        ];

        return parts.join('');
    }

    function priorityIconHtml(slug, name) {
        var escapedName = $('<span>').text(name || '').html();

        if (slug === 'urgent') {
            return '<i class="fas fa-exclamation-circle text-danger" title="' + escapedName + '"></i>';
        }
        if (slug === 'high') {
            return '<i class="fas fa-exclamation-circle text-warning" title="' + escapedName + '"></i>';
        }
        if (slug) {
            return '<i class="fas fa-exclamation-circle text-muted" title="' + escapedName + '"></i>';
        }
        return '<i class="fas fa-exclamation-circle text-muted"></i>';
    }

    // Exponer API pública
    window.ChatConversations = {
        loadConversation: loadConversation
    };

    $(function () { init(); });

})(jQuery);

/**
 * search.js - Búsqueda global estilo Chatwoot
 * Página dedicada /helpdesk/search
 * Patrón: IIFE + jQuery
 */
(function ($) {
    'use strict';

    var cfg = window.SearchConfig || {};
    var DEBOUNCE_MS = 500;
    var MIN_CHARS = 2;

    var state = {
        query: '',
        activeTab: 'all',
        pages: { contacts: 1, conversations: 1, messages: 1 },
        totals: { contacts: 0, conversations: 0, messages: 0 },
    };

    // ─── Helpers ──────────────────────────────────────────────────────────────

    function highlight(text, q) {
        if (!q || !text) return escapeHtml(text || '');
        var escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return escapeHtml(text).replace(new RegExp('(' + escaped + ')', 'gi'), '<mark class="bg-warning px-0">$1</mark>');
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function channelIcon(channelType) {
        var map = {
            'Channel::Api': 'fa-comment-dots text-secondary',
            'Modules\\Chat\\Models\\Channels\\Whatsapp': 'fab fa-whatsapp text-success',
            'Modules\\Chat\\Models\\Channels\\Facebook': 'fab fa-facebook text-primary',
            'Modules\\Chat\\Models\\Channels\\Instagram': 'fab fa-instagram text-danger',
            'Modules\\Chat\\Models\\Channels\\Email': 'fas fa-envelope text-warning',
            'Modules\\Chat\\Models\\Channels\\Web': 'fas fa-globe text-info',
        };
        return map[channelType] || 'fas fa-comments text-muted';
    }

    function channelName(channelType) {
        var map = {
            'Channel::Api': 'API',
            'Modules\\Chat\\Models\\Channels\\Whatsapp': 'WhatsApp',
            'Modules\\Chat\\Models\\Channels\\Facebook': 'Facebook',
            'Modules\\Chat\\Models\\Channels\\Instagram': 'Instagram',
            'Modules\\Chat\\Models\\Channels\\Email': 'Email',
            'Modules\\Chat\\Models\\Channels\\Web': 'Web',
        };
        return map[channelType] || 'Desconocido';
    }

    function statusBadge(status) {
        var map = {
            'open': '<span class="badge bg-info">Abierta</span>',
            'pending': '<span class="badge bg-warning">Pendiente</span>',
            'resolved': '<span class="badge bg-success">Resuelta</span>',
            'closed': '<span class="badge bg-secondary">Cerrada</span>',
        };
        return map[status] || '<span class="badge bg-light text-dark">' + escapeHtml(status || 'Sin estado') + '</span>';
    }

    // ─── Render functions (table rows) ────────────────────────────────────────

    function renderContact(c) {
        var name = c.name || 'Sin nombre';
        var email = c.email || '-';
        var phone = c.phone_number || '-';
        var convCount = c.conversations_count || 0;
        var url = cfg.contactBase + '/' + c.id;

        return '<tr class="search-result-row" data-url="' + url + '">' +
            '<td>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<div class="avatar avatar-sm rounded-circle bg-light-primary text-primary fw-bold d-flex align-items-center justify-content-center" style="width:32px;height:32px;flex-shrink:0">' +
                        escapeHtml(name.charAt(0).toUpperCase()) +
                    '</div>' +
                    '<span>' + highlight(name, state.query) + '</span>' +
                '</div>' +
            '</td>' +
            '<td>' + highlight(email, state.query) + '</td>' +
            '<td>' + highlight(phone, state.query) + '</td>' +
            '<td class="text-center"><span class="badge bg-light text-dark">' + convCount + '</span></td>' +
            '<td class="text-center">' +
                '<a href="' + url + '" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a>' +
            '</td>' +
        '</tr>';
    }

    function renderConversation(conv) {
        var contact = conv.contact || {};
        var inbox = conv.inbox || {};
        var agent = conv.agent || {};
        var url = cfg.conversationBase + '/' + conv.id;

        return '<tr class="search-result-row" data-url="' + url + '">' +
            '<td>' +
                '<span class="badge bg-light text-dark border">#' + conv.id + '</span>' +
            '</td>' +
            '<td>' + highlight(contact.name || 'Sin nombre', state.query) + '</td>' +
            '<td>' +
                '<i class="fas ' + channelIcon(inbox.channel_type) + ' me-1"></i>' +
                escapeHtml(channelName(inbox.channel_type)) +
            '</td>' +
            '<td class="text-center">' + statusBadge(conv.status) + '</td>' +
            '<td>' +
                '<small class="text-muted">' + escapeHtml(conv.last_activity_at || '-') + '</small>' +
            '</td>' +
            '<td class="text-center">' +
                '<a href="' + url + '" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a>' +
            '</td>' +
        '</tr>';
    }

    function renderMessage(msg) {
        var sender = msg.sender || {};
        var url = cfg.conversationBase + '/' + msg.conversation_id;
        var content = msg.content || '';
        var truncated = content.length > 100 ? content.substring(0, 100) + '...' : content;

        return '<tr class="search-result-row" data-url="' + url + '">' +
            '<td>' +
                '<span class="badge bg-light text-dark border">#' + msg.conversation_id + '</span>' +
            '</td>' +
            '<td>' + escapeHtml(sender.name || 'Sistema') + '</td>' +
            '<td>' +
                '<div class="text-truncate-2">' + highlight(truncated, state.query) + '</div>' +
            '</td>' +
            '<td>' +
                '<small class="text-muted">' + escapeHtml(msg.created_at || '-') + '</small>' +
            '</td>' +
        '</tr>';
    }

    // ─── API calls ────────────────────────────────────────────────────────────

    function fetchContacts(page) {
        return $.get(cfg.urls.contacts, { q: state.query, page: page });
    }

    function fetchConversations(page) {
        return $.get(cfg.urls.conversations, { q: state.query, page: page });
    }

    function fetchMessages(page) {
        return $.get(cfg.urls.messages, { q: state.query, page: page });
    }

    // ─── UI state helpers ─────────────────────────────────────────────────────

    function updateStats() {
        var total = state.totals.contacts + state.totals.conversations + state.totals.messages;

        $('#stat-contacts').text(state.totals.contacts);
        $('#stat-conversations').text(state.totals.conversations);
        $('#stat-messages').text(state.totals.messages);
        $('#stat-total').text(total);

        $('#tab-count-all').text(total);
        $('#tab-count-contacts').text(state.totals.contacts);
        $('#tab-count-conversations').text(state.totals.conversations);
        $('#tab-count-messages').text(state.totals.messages);
    }

    function showLoading() {
        $('#search-empty-state, #search-no-results').addClass('d-none');
        $('#search-tabs-container').addClass('d-none');
        $('#section-contacts, #section-conversations, #section-messages').addClass('d-none');
        $('#search-loading').removeClass('d-none');
    }

    function showEmpty() {
        $('#search-loading, #search-no-results').addClass('d-none');
        $('#search-tabs-container').addClass('d-none');
        $('#section-contacts, #section-conversations, #section-messages').addClass('d-none');
        $('#search-empty-state').removeClass('d-none');

        // Reset stats
        state.totals = { contacts: 0, conversations: 0, messages: 0 };
        updateStats();
    }

    function showNoResults() {
        $('#search-loading, #search-empty-state').addClass('d-none');
        $('#search-tabs-container').addClass('d-none');
        $('#section-contacts, #section-conversations, #section-messages').addClass('d-none');
        $('#search-no-results').removeClass('d-none');
    }

    function applyTabVisibility() {
        var tab = state.activeTab;
        var showContacts = tab === 'all' || tab === 'contacts';
        var showConvs = tab === 'all' || tab === 'conversations';
        var showMsgs = tab === 'all' || tab === 'messages';

        $('#section-contacts').toggleClass('d-none', !showContacts || state.totals.contacts === 0);
        $('#section-conversations').toggleClass('d-none', !showConvs || state.totals.conversations === 0);
        $('#section-messages').toggleClass('d-none', !showMsgs || state.totals.messages === 0);

        var total = state.totals.contacts + state.totals.conversations + state.totals.messages;
        if (total === 0) {
            showNoResults();
        } else {
            $('#search-no-results').addClass('d-none');
            $('#search-tabs-container').removeClass('d-none');
        }
    }

    // ─── Full search ──────────────────────────────────────────────────────────

    function fullSearch() {
        var q = state.query;
        if (q.length < MIN_CHARS) {
            showEmpty();
            return;
        }

        // Reset pages
        state.pages = { contacts: 1, conversations: 1, messages: 1 };
        state.totals = { contacts: 0, conversations: 0, messages: 0 };

        // Clear previous results
        $('#results-contacts tbody, #results-conversations tbody, #results-messages tbody').empty();
        $('#load-more-contacts, #load-more-conversations, #load-more-messages').addClass('d-none').data('page', 1);

        showLoading();

        $.when(fetchContacts(1), fetchConversations(1), fetchMessages(1))
            .done(function (cRes, convRes, msgRes) {
                $('#search-loading').addClass('d-none');

                // Contacts
                var contacts = (cRes[0].payload || {}).contacts || [];
                var cMeta = (cRes[0].payload || {}).meta || {};
                state.totals.contacts = cMeta.total || contacts.length;
                contacts.forEach(function (c) {
                    $('#results-contacts tbody').append(renderContact(c));
                });
                if (state.totals.contacts > contacts.length) {
                    $('#load-more-contacts').removeClass('d-none').data('page', 2);
                }

                // Conversations
                var convs = (convRes[0].payload || {}).conversations || [];
                var convMeta = (convRes[0].payload || {}).meta || {};
                state.totals.conversations = convMeta.total || convs.length;
                convs.forEach(function (conv) {
                    $('#results-conversations tbody').append(renderConversation(conv));
                });
                if (state.totals.conversations > convs.length) {
                    $('#load-more-conversations').removeClass('d-none').data('page', 2);
                }

                // Messages
                var msgs = (msgRes[0].payload || {}).messages || [];
                var msgMeta = (msgRes[0].payload || {}).meta || {};
                state.totals.messages = msgMeta.total || msgs.length;
                msgs.forEach(function (msg) {
                    $('#results-messages tbody').append(renderMessage(msg));
                });
                if (state.totals.messages > msgs.length) {
                    $('#load-more-messages').removeClass('d-none').data('page', 2);
                }

                updateStats();
                applyTabVisibility();
            })
            .fail(function (xhr) {
                $('#search-loading').addClass('d-none');

                var msg = 'Error al buscar. Intenta de nuevo.';
                if (xhr && xhr.status === 0) {
                    msg = 'Sin conexión. Comprueba tu red e inténtalo de nuevo.';
                } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }

                if (window.toastr) {
                    toastr.error(msg);
                }

                console.error('Search failed:', xhr);
                showNoResults();
            });
    }

    // ─── Load more ────────────────────────────────────────────────────────────

    function loadMore(type) {
        var page = parseInt($('#load-more-' + type).data('page'), 10);
        var fetcher = { contacts: fetchContacts, conversations: fetchConversations, messages: fetchMessages }[type];
        if (!fetcher) { return; }

        $('#load-more-' + type).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cargando...');

        fetcher(page).done(function (res) {
            var items = (res.payload || {})[type] || [];
            var renderer = { contacts: renderContact, conversations: renderConversation, messages: renderMessage }[type];
            items.forEach(function (item) {
                $('#results-' + type + ' tbody').append(renderer(item));
            });

            var nextPage = page + 1;
            var loaded = $('#results-' + type + ' tbody tr').length;
            if (loaded < state.totals[type]) {
                $('#load-more-' + type).prop('disabled', false)
                    .data('page', nextPage)
                    .html('<i class="fas fa-chevron-down me-1"></i> Cargar más ' + type);
            } else {
                $('#load-more-' + type).addClass('d-none');
            }
        }).fail(function (xhr) {
            $('#load-more-' + type).prop('disabled', false).html('<i class="fas fa-chevron-down me-1"></i> Cargar más ' + type);

            var msg = 'Error al cargar más resultados.';
            if (xhr && xhr.status === 0) {
                msg = 'Sin conexión. Comprueba tu red e inténtalo de nuevo.';
            } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }

            if (window.toastr) {
                toastr.error(msg);
            }

            console.error('Load more failed for ' + type + ':', xhr);
        });
    }

    // ─── Init ─────────────────────────────────────────────────────────────────

    $(function () {
        var $input = $('#global-search-input');
        var $clear = $('#global-search-clear');
        var $form = $('#search-form');
        var debounceTimer;

        // Form submit handler
        $form.on('submit', function (e) {
            e.preventDefault();
            state.query = $.trim($input.val());
            if (state.query.length >= MIN_CHARS) {
                fullSearch();
            }
        });

        // Debounced search on input
        $input.on('input', function () {
            clearTimeout(debounceTimer);
            state.query = $.trim($(this).val());
            $clear.toggleClass('d-none', !state.query);

            debounceTimer = setTimeout(function () {
                fullSearch();
            }, DEBOUNCE_MS);
        });

        // Clear button
        $clear.on('click', function () {
            $input.val('').trigger('input').focus();
        });

        // Keyboard shortcut: "/" to focus
        $(document).on('keydown', function (e) {
            if (e.key === '/' && !$(e.target).is('input, textarea, select, [contenteditable]')) {
                e.preventDefault();
                $input.focus();
            }
            if (e.key === 'Escape' && $input.is(':focus')) {
                $input.val('').trigger('input');
                $input.blur();
            }
        });

        // Tab switching
        $(document).on('click', '#search-tabs .nav-link', function () {
            $('#search-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            state.activeTab = $(this).data('tab');
            applyTabVisibility();
        });

        // Table row click navigation
        $(document).on('click', '.search-result-row', function (e) {
            if (!$(e.target).closest('a, button').length) {
                var url = $(this).data('url');
                if (url) {
                    window.location.href = url;
                }
            }
        });

        // Load more buttons
        $(document).on('click', '#load-more-contacts', function () { loadMore('contacts'); });
        $(document).on('click', '#load-more-conversations', function () { loadMore('conversations'); });
        $(document).on('click', '#load-more-messages', function () { loadMore('messages'); });

        // Auto-search if ?q= is in URL
        var urlQ = new URLSearchParams(window.location.search).get('q');
        if (urlQ) {
            $input.val(urlQ);
            state.query = $.trim(urlQ);
            $clear.removeClass('d-none');
            fullSearch();
        } else {
            $input.focus();
        }
    });

    window.ChatSearch = { search: fullSearch };

})(jQuery);

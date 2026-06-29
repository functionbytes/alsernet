/**
 * quick-search.js - Búsqueda rápida con dropdown
 * Gestiona el input #conversation-search en la lista de conversaciones
 * Patrón: IIFE + jQuery
 */
(function ($) {
    'use strict';

    var DEBOUNCE_MS = 350;
    var MIN_CHARS = 2;
    var MAX_RESULTS = 5;
    var SEARCH_URL = window.ChatConfig ? (window.ChatConfig.baseUrl || '') + '/chat/conversations/search' : '/chat/conversations/search';
    var SEARCH_PAGE_URL = window.ChatConfig ? (window.ChatConfig.baseUrl || '') + '/helpdesk/search' : '/helpdesk/search';

    var $input, $dropdown;
    var debounceTimer;
    var currentQuery = '';

    // ─── Helpers ──────────────────────────────────────────────────────────────

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function highlight(text, q) {
        if (!q || !text) { return escapeHtml(text); }
        var escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return escapeHtml(text).replace(new RegExp('(' + escaped + ')', 'gi'), '<mark class="bg-warning px-0">$1</mark>');
    }

    function channelIcon(type) {
        var m = {
            whatsapp: 'fab fa-whatsapp text-success',
            facebook: 'fab fa-facebook text-primary',
            instagram: 'fab fa-instagram text-danger',
            email: 'fas fa-envelope text-warning',
            web: 'fas fa-globe text-info',
        };
        var key = (type || '').toLowerCase();
        for (var k in m) {
            if (key.indexOf(k) !== -1) { return m[k]; }
        }
        return 'fas fa-comments text-muted';
    }

    // ─── Dropdown ─────────────────────────────────────────────────────────────

    function createDropdown() {
        $dropdown = $('<div>', {
            id: 'qs-dropdown',
            class: 'position-absolute bg-white border rounded shadow-sm z-3 w-100',
            css: { top: '100%', left: 0, maxHeight: '400px', overflowY: 'auto', display: 'none' },
        });
        $input.closest('.input-group').css('position', 'relative').after($dropdown);

        // Click outside to close
        $(document).on('click.quicksearch', function (e) {
            if (!$(e.target).closest('#qs-dropdown, .input-group:has(#conversation-search)').length) {
                hideDropdown();
            }
        });
    }

    function showDropdown() { $dropdown.show(); }
    function hideDropdown() { $dropdown.hide(); }

    function renderDropdown(results, q) {
        $dropdown.empty();

        if (!results || !results.length) {
            $dropdown.append(
                '<div class="px-3 py-2 text-muted small">Sin resultados para "' + escapeHtml(q) + '"</div>'
            );
            showDropdown();
            return;
        }

        var limited = results.slice(0, MAX_RESULTS);
        limited.forEach(function (item) {
            var icon = channelIcon(item.inbox ? item.inbox.type : '');
            var matchBadge = item.match_type === 'message'
                ? '<span class="badge bg-light text-muted border ms-1" style="font-size:.65rem">mensaje</span>'
                : '';

            var $item = $('<div>', {
                class: 'qs-item px-3 py-2 d-flex align-items-start gap-2 border-bottom cursor-pointer',
                css: { cursor: 'pointer' },
                'data-id': item.id,
                'data-url': item.url || '',
            });

            $item.html(
                '<div class="flex-fill" style="min-width:0">' +
                    '<div class="d-flex align-items-center gap-1 mb-1">' +
                        '<small class="text-muted">#' + item.id + '</small>' +
                        '<i class="' + icon + ' fs-6"></i>' +
                        '<small class="text-muted text-truncate">' + escapeHtml((item.inbox || {}).name || '') + '</small>' +
                        matchBadge +
                        '<small class="text-muted ms-auto">' + escapeHtml((item.last_activity_at || '')) + '</small>' +
                    '</div>' +
                    '<div class="fw-semibold small text-truncate">' + highlight((item.customer || {}).name || '', q) + '</div>' +
                    (item.last_message ? '<div class="text-muted small text-truncate">' + highlight(item.last_message.content || '', q) + '</div>' : '') +
                '</div>'
            );

            $item.on('click', function () {
                var id = $(this).data('id');
                hideDropdown();
                $input.val('');
                if (window.ChatConversations && typeof window.ChatConversations.loadConversation === 'function') {
                    window.ChatConversations.loadConversation(id);
                } else {
                    window.location.href = item.url || '#';
                }
            });

            $dropdown.append($item);
        });

        // "Ver todos" button
        var totalCount = results.length;
        var $viewAll = $('<a>', {
            class: 'qs-view-all d-flex align-items-center justify-content-center gap-2 py-2 text-primary small fw-semibold border-top',
            css: { cursor: 'pointer', textDecoration: 'none' },
            href: SEARCH_PAGE_URL + '?q=' + encodeURIComponent(q),
        }).html('<i class="fas fa-search me-1"></i> Ver todos los resultados' + (totalCount >= MAX_RESULTS ? ' (' + totalCount + ')' : ''));

        $dropdown.append($viewAll);
        showDropdown();
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    function doSearch(q) {
        if (q.length < MIN_CHARS) {
            hideDropdown();
            return;
        }

        $dropdown.html('<div class="px-3 py-2 text-muted small"><i class="fas fa-spinner fa-spin me-1"></i> Buscando...</div>');
        showDropdown();

        $.get(SEARCH_URL, { q: q })
            .done(function (data) {
                if (currentQuery !== q) { return; } // Stale response
                renderDropdown(Array.isArray(data) ? data : [], q);
            })
            .fail(function (xhr) {
                if (currentQuery !== q) { return; }

                var msg = 'Error al buscar. Intenta de nuevo.';
                if (xhr.status === 0) {
                    msg = 'Sin conexión. Verifica tu red.';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }

                $dropdown.html('<div class="px-3 py-2 text-danger small">' + escapeHtml(msg) + '</div>');
                console.error('Quick search failed:', xhr);
            });
    }

    // ─── Init ─────────────────────────────────────────────────────────────────

    $(function () {
        $input = $('#conversation-search');
        if (!$input.length) { return; }

        createDropdown();

        $input.on('input', function () {
            clearTimeout(debounceTimer);
            currentQuery = $.trim($(this).val());

            if (!currentQuery) {
                hideDropdown();
                return;
            }

            debounceTimer = setTimeout(function () {
                doSearch(currentQuery);
            }, DEBOUNCE_MS);
        });

        $input.on('keydown', function (e) {
            if (e.key === 'Escape') {
                $(this).val('');
                currentQuery = '';
                hideDropdown();
            }
            // Enter → navega a página completa de search
            if (e.key === 'Enter' && currentQuery.length >= MIN_CHARS) {
                e.preventDefault();
                window.location.href = SEARCH_PAGE_URL + '?q=' + encodeURIComponent(currentQuery);
            }
        });

        // Click on input icon → navigate to search page
        $input.on('focus', function () {
            if (currentQuery.length >= MIN_CHARS) {
                showDropdown();
            }
        });
    });

    window.ChatQuickSearch = { search: doSearch };

})(jQuery);

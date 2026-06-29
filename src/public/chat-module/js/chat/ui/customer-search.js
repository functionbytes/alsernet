/**
 * customer-search.js - Búsqueda AJAX con dropdown en página de clientes
 * Maneja el input de búsqueda en /helpdesk/customers
 * Patrón: IIFE + jQuery
 */
(function ($) {
    'use strict';

    var DEBOUNCE_MS = 400;
    var MIN_CHARS = 2;
    var MAX_RESULTS = 8;
    var SEARCH_URL = '/helpdesk/search/contacts';
    var SEARCH_PAGE_URL = '/helpdesk/search';

    var $form, $input, $dropdown, $submitBtn;
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
        return escapeHtml(text).replace(new RegExp('(' + escaped + ')', 'gi'),
               '<mark class="bg-warning px-0">$1</mark>');
    }

    // ─── Dropdown ─────────────────────────────────────────────────────────────

    function createDropdown() {
        $dropdown = $('<div>', {
            id: 'customer-search-dropdown',
            class: 'position-absolute bg-white border rounded shadow-sm z-3',
            css: {
                top: '100%',
                left: 0,
                right: 0,
                maxHeight: '450px',
                overflowY: 'auto',
                display: 'none',
                marginTop: '2px'
            },
        });

        $input.closest('.input-group').css('position', 'relative').parent().css('position', 'relative').append($dropdown);

        // Click outside to close
        $(document).on('click.customersearch', function (e) {
            if (!$(e.target).closest('#customer-search-dropdown, form:has(input[name="search"])').length) {
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
                '<div class="px-3 py-3 text-center text-muted small">' +
                    '<i class="fa fa-search me-1"></i> Sin resultados para "' + escapeHtml(q) + '"' +
                '</div>'
            );
            showDropdown();
            return;
        }

        var limited = results.slice(0, MAX_RESULTS);

        limited.forEach(function (contact) {
            var displayName = contact.name || 'Sin nombre';
            var email = contact.email || '';
            var phone = contact.phone_number || '';
            var identifier = contact.identifier || '';

            var $item = $('<a>', {
                class: 'customer-search-item d-flex align-items-center gap-2 px-3 py-2 border-bottom text-decoration-none text-dark',
                css: { cursor: 'pointer' },
                href: '/helpdesk/customers/' + contact.id,
            });

            var contactInfo = '';
            if (email) {
                contactInfo += '<small class="text-muted d-block"><i class="fa fa-envelope fs-6 me-1"></i>' +
                              highlight(email, q) + '</small>';
            }
            if (phone) {
                contactInfo += '<small class="text-muted d-block"><i class="fa fa-phone fs-6 me-1"></i>' +
                              highlight(phone, q) + '</small>';
            }
            if (!email && !phone && identifier) {
                contactInfo += '<small class="text-muted d-block"><i class="fa fa-id-card fs-6 me-1"></i>' +
                              escapeHtml(identifier) + '</small>';
            }

            $item.html(
                '<div class="flex-fill" style="min-width:0">' +
                    '<div class="fw-semibold mb-1">' + highlight(displayName, q) + '</div>' +
                    contactInfo +
                '</div>'
            );

            $item.on('mouseenter', function() {
                $(this).addClass('bg-light');
            }).on('mouseleave', function() {
                $(this).removeClass('bg-light');
            });

            $dropdown.append($item);
        });

        // "Ver todos" button
        var totalCount = results.length;
        var $viewAll = $('<a>', {
            class: 'customer-search-view-all d-flex align-items-center justify-content-center gap-2 py-3 text-primary small fw-semibold border-top bg-light',
            css: { cursor: 'pointer', textDecoration: 'none' },
            href: SEARCH_PAGE_URL + '?q=' + encodeURIComponent(q),
        }).html(
            '<i class="fa fa-search me-1"></i> Ver todos los resultados' +
            (totalCount >= MAX_RESULTS ? ' (' + totalCount + '+)' : ' (' + totalCount + ')')
        );

        $dropdown.append($viewAll);
        showDropdown();
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    function doSearch(q) {
        if (q.length < MIN_CHARS) {
            hideDropdown();
            return;
        }

        $dropdown.html(
            '<div class="px-3 py-3 text-center text-muted small">' +
                '<i class="fa fa-spinner fa-spin me-1"></i> Buscando clientes...' +
            '</div>'
        );
        showDropdown();

        $.get(SEARCH_URL, { q: q })
            .done(function (response) {
                if (currentQuery !== q) { return; } // Stale response
                var contacts = (response.payload && response.payload.contacts) ? response.payload.contacts : [];
                renderDropdown(contacts, q);
            })
            .fail(function () {
                if (currentQuery !== q) { return; }
                $dropdown.html(
                    '<div class="px-3 py-3 text-center text-danger small">' +
                        '<i class="fa fa-exclamation-triangle me-1"></i> Error al buscar. Intenta de nuevo.' +
                    '</div>'
                );
            });
    }

    // ─── Init ─────────────────────────────────────────────────────────────────

    $(function () {
        $form = $('form:has(input[name="search"])').first();
        $input = $form.find('input[name="search"]');
        $submitBtn = $form.find('button[type="submit"]');

        if (!$input.length) { return; }

        createDropdown();

        // Prevent form submission, use AJAX dropdown instead
        $form.on('submit', function(e) {
            e.preventDefault();
            currentQuery = $.trim($input.val());
            if (currentQuery.length >= MIN_CHARS) {
                // Navigate to full search page on Enter
                window.location.href = SEARCH_PAGE_URL + '?q=' + encodeURIComponent(currentQuery);
            }
        });

        // AJAX dropdown on input
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

        // Keyboard shortcuts
        $input.on('keydown', function (e) {
            if (e.key === 'Escape') {
                $(this).val('');
                currentQuery = '';
                hideDropdown();
            }
        });

        // Focus behavior
        $input.on('focus', function () {
            var val = $.trim($(this).val());
            if (val.length >= MIN_CHARS && val === currentQuery && $dropdown.children().length > 0) {
                showDropdown();
            }
        });
    });

    window.ChatCustomerSearch = { search: doSearch };

})(jQuery);

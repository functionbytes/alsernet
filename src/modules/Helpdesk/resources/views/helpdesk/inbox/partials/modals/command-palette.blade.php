{{-- Modal: Búsqueda global / command palette (#55 ve-command-palette · ⌘K) --}}
{{-- Nota: No usa el sistema bv-modal estándar. Se abre con Ctrl+K / Cmd+K (handler
     en conversations.js) o window.openCommandPalette(). Estilos en conversations.css. --}}
<div id="bv-cmd-palette" class="bv-cmd-overlay" aria-hidden="true" role="dialog" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.command_palette_aria_label') }}">
    <div class="bv-cmd-modal" role="search">
        <div class="bv-cmd-input-wrap">
            <i class="fas fa-magnifying-glass bv-cmd-icon"></i>
            <input id="bvCmdInput" type="text" class="bv-cmd-input"
                   placeholder="{{ __('helpdesk::helpdesk.inbox.modals.command_palette_search_placeholder') }}"
                   autocomplete="off" spellcheck="false">
            <kbd class="bv-cmd-esc" id="bvCmdEsc">ESC</kbd>
        </div>
        <div id="bvCmdBody" class="bv-cmd-body">
            <div class="bv-cmd-empty">
                <i class="fas fa-magnifying-glass"></i>
                <span>{{ __('helpdesk::helpdesk.inbox.modals.command_palette_empty_hint') }}</span>
            </div>
        </div>
        <div class="bv-cmd-foot">
            <span class="bv-cmd-foot-k"><kbd class="bv-cmd-kbd">↑↓</kbd> {{ __('helpdesk::helpdesk.inbox.modals.command_palette_nav_up_down') }}</span>
            <span class="bv-cmd-foot-k"><kbd class="bv-cmd-kbd">⏎</kbd> {{ __('helpdesk::helpdesk.inbox.modals.command_palette_nav_open') }}</span>
            <span class="bv-cmd-foot-k"><kbd class="bv-cmd-kbd">esc</kbd> {{ __('helpdesk::helpdesk.inbox.modals.command_palette_nav_close') }}</span>
            <span class="bv-cmd-foot-count" id="bvCmdCount"></span>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
window.HD_TICKETS_ENABLED = @json(function_exists('helpdesk_tickets_enabled') && helpdesk_tickets_enabled());
(function ($) {
    'use strict';

    var _timer   = null;
    var _cursor  = -1;
    var _results = [];

    function open() {
        $('#bv-cmd-palette').addClass('on').attr('aria-hidden', 'false');
        $('#bvCmdInput').val('').trigger('focus');
        renderEmpty();
        _cursor = -1;
        $('body').css('overflow', 'hidden');
    }

    function close() {
        $('#bv-cmd-palette').removeClass('on').attr('aria-hidden', 'true');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function setCount(n) {
        $('#bvCmdCount').text(n > 0 ? (n + (n === 1 ? ' resultado' : ' resultados')) : '');
    }

    function renderEmpty() {
        $('#bvCmdBody').html(
            '<div class="bv-cmd-empty"><i class="fas fa-magnifying-glass"></i>' +
            '<span>Escribe para buscar conversaciones, contactos o tickets…</span></div>'
        );
        _results = [];
        setCount(0);
    }

    function esc(s) { return $('<span>').text(s == null ? '' : String(s)).html(); }

    function channelIcon(ch) {
        var map = {
            whatsapp: 'fab fa-whatsapp', facebook: 'fab fa-facebook-messenger',
            instagram: 'fab fa-instagram', email: 'far fa-envelope',
            widget: 'far fa-comment', webchat: 'far fa-comment',
        };
        return map[ch] || 'far fa-comment-dots';
    }

    function itemHtml(idx, iconHtml, title, sub, extra) {
        return '<button class="bv-cmd-item" data-result-idx="' + idx + '">' +
            '<div class="bv-cmd-item__ico">' + iconHtml + '</div>' +
            '<div class="bv-cmd-item__main">' +
                '<div class="bv-cmd-item__t">' + esc(title) + '</div>' +
                (sub ? '<div class="bv-cmd-item__s">' + esc(sub) + '</div>' : '') +
            '</div>' + (extra || '') + '</button>';
    }

    function renderResults(data, query) {
        var customers     = data.customers || [];
        var conversations = data.conversations || [];
        var tickets       = data.tickets || [];
        var tags          = data.tags || [];
        var found         = customers.length + conversations.length + tickets.length + tags.length;

        _results = [];
        var html = '';

        if (conversations.length) {
            html += '<div class="bv-cmd-section">Conversaciones</div>';
            conversations.forEach(function (c) {
                _results.push({ url: c.url, type: 'conversation' });
                html += itemHtml(_results.length - 1,
                    '<i class="' + channelIcon(c.channel) + '"></i>',
                    (c.customer_name ? c.customer_name + ' · ' : '') + (c.subject || ('#' + c.id)),
                    '#' + c.id);
            });
        }

        if (customers.length) {
            html += '<div class="bv-cmd-section">Contactos</div>';
            customers.forEach(function (c) {
                _results.push({ url: c.url, type: 'customer' });
                html += itemHtml(_results.length - 1,
                    '<i class="far fa-user"></i>', c.name || '', c.email || c.phone || '');
            });
        }

        if (tickets.length) {
            html += '<div class="bv-cmd-section">Tickets</div>';
            tickets.forEach(function (t) {
                _results.push({ url: t.url, type: 'ticket' });
                html += itemHtml(_results.length - 1,
                    '<i class="fas fa-ticket"></i>',
                    (t.ticket_number ? t.ticket_number + ' · ' : '') + (t.subject || ''),
                    t.customer_name || 'Sin asignar');
            });
        }

        if (tags.length) {
            html += '<div class="bv-cmd-section">Etiquetas</div>';
            tags.forEach(function (t) {
                _results.push({ url: '/panel/helpdesk/conversations?tag=' + t.id, type: 'tag' });
                html += itemHtml(_results.length - 1,
                    '<i class="fas fa-tag"></i>', t.name, null);
            });
        }

        // Sección Acciones — contextual al término buscado
        html += '<div class="bv-cmd-section">Acciones</div>';
        _results.push({ action: 'newconv', query: query });
        html += itemHtml(_results.length - 1,
            '<i class="fas fa-pen-to-square"></i>', 'Nueva conversación con «' + query + '»', null,
            '<kbd class="bv-cmd-kbd">⌘N</kbd>');

        if (window.HD_TICKETS_ENABLED) {
            _results.push({ action: 'create-ticket', query: query });
            html += itemHtml(_results.length - 1,
                '<i class="fas fa-ticket"></i>', 'Crear ticket para «' + query + '»', null);
        }

        if (!found) {
            html = '<div class="bv-cmd-empty"><i class="fas fa-inbox"></i>' +
                   '<span>Sin resultados para «' + esc(query) + '»</span></div>' + html;
        }

        $('#bvCmdBody').html(html);

        // Auto-seleccionar el primer resultado (UX estilo Spotlight/Raycast).
        var $first = $('#bvCmdBody .bv-cmd-item').first();
        if ($first.length) { $first.addClass('on'); _cursor = 0; } else { _cursor = -1; }
        setCount(found);
    }

    function search(q) {
        if (!q || q.length < 2) { renderEmpty(); return; }

        $('#bvCmdBody').html('<div class="bv-cmd-loading"><i class="fas fa-spinner fa-spin"></i></div>');

        $.ajax({
            url: '/panel/helpdesk/search/global',
            method: 'GET',
            data: { q: q },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            renderResults(resp, q);
        }).fail(function () {
            $('#bvCmdBody').html('<div class="bv-cmd-empty"><i class="fas fa-triangle-exclamation"></i><span>Error al buscar</span></div>');
            setCount(0);
        });
    }

    function navigate(dir) {
        var $items = $('#bvCmdBody .bv-cmd-item');
        if (!$items.length) { return; }
        $items.removeClass('on');
        _cursor = (_cursor + dir + $items.length) % $items.length;
        $items.eq(_cursor).addClass('on')[0].scrollIntoView({ block: 'nearest' });
    }

    function openBvByName(name) {
        $('[data-bv-modal-name="' + name + '"]').addClass('on');
        $('body').css('overflow', 'hidden');
        $(document).trigger('bv:modal:open', [name]);
    }

    function runItem($item) {
        if (!$item || !$item.length) { return; }
        var idx = parseInt($item.data('result-idx'), 10);
        var r = _results[idx];
        if (!r) { return; }
        close();
        if (r.action === 'newconv') {
            openBvByName('newconv');
        } else if (r.action === 'create-ticket') {
            openBvByName('create-ticket');
        } else if (r.url) {
            window.location.href = r.url;
        }
    }

    function goToSelected() {
        var $item = $('#bvCmdBody .bv-cmd-item.on').first();
        if (!$item.length) { $item = $('#bvCmdBody .bv-cmd-item').first(); }
        runItem($item);
    }

    // Cerrar / navegar cuando el palette está abierto (el ⌘K de apertura vive en
    // conversations.js para evitar dobles bindings y conflictos con otros modales).
    $(document).on('keydown', function (e) {
        if (!$('#bv-cmd-palette').hasClass('on')) { return; }
        if (e.key === 'Escape')    { e.preventDefault(); close(); }
        if (e.key === 'ArrowDown') { e.preventDefault(); navigate(1); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); navigate(-1); }
        if (e.key === 'Enter')     { e.preventDefault(); goToSelected(); }
    });

    $('#bvCmdInput').on('input', function () {
        clearTimeout(_timer);
        var q = $(this).val().trim();
        _timer = setTimeout(function () { search(q); }, 220);
    });

    $(document).on('click', '#bvCmdBody .bv-cmd-item', function () {
        runItem($(this));
    });

    $('#bvCmdEsc').on('click', close);
    $('#bv-cmd-palette').on('click', function (e) {
        if ($(e.target).is('#bv-cmd-palette')) { close(); }
    });

    $(document).on('bv:modal:open', function (e, name) {
        if (name === 'command-palette') { open(); }
    });

    // ⌘K / Ctrl+K — apertura del command palette. Se registra en fase de CAPTURA
    // y detiene la propagación para ganar al buscador global del sistema
    // (#gs-dialog, Theme/header) que escucha ⌘K en bubbling. Dentro del inbox el
    // command palette del helpdesk es el único que responde a ⌘K.
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && !e.shiftKey && !e.altKey && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if ($('#bv-cmd-palette').hasClass('on')) { close(); } else { open(); }
        }
    }, true);

    window.openCommandPalette = open;
    window.closeCommandPalette = close;

}(window.jQuery));
</script>
@endpush
@endonce

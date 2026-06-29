{{-- Modal: Búsqueda global / command palette (#55 ve-command-palette · ⌘K) --}}
{{-- Nota: Este modal no usa el sistema bv-modal estándar. Se activa con Ctrl+K / Cmd+K. --}}
<div id="bv-cmd-palette" class="bv-cmd-overlay" aria-hidden="true" role="dialog" aria-label="Búsqueda global">
    <div class="bv-cmd-modal" role="search">
        <div class="bv-cmd-input-wrap">
            <i class="fas fa-magnifying-glass bv-cmd-icon"></i>
            <input id="bvCmdInput" type="text" class="bv-cmd-input"
                   placeholder="Buscar conversaciones, contactos, tickets…"
                   autocomplete="off" spellcheck="false">
            <kbd class="bv-cmd-esc" id="bvCmdEsc">ESC</kbd>
        </div>
        <div id="bvCmdBody" class="bv-cmd-body">
            {{-- Empty state --}}
            <div class="bv-cmd-empty">
                <i class="fas fa-magnifying-glass"></i>
                <span>Escribe para buscar conversaciones, contactos o tickets…</span>
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
.bv-cmd-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.45); backdrop-filter: blur(2px);
    display: flex; align-items: flex-start; justify-content: center;
    padding-top: 80px; opacity: 0; pointer-events: none; transition: opacity .15s;
}
.bv-cmd-overlay.on { opacity: 1; pointer-events: auto; }
.bv-cmd-modal {
    width: 560px; max-width: calc(100vw - 32px);
    background: var(--bv-bg, #fff); border-radius: 10px;
    box-shadow: 0 20px 60px rgba(0,0,0,.22); overflow: hidden;
}
.bv-cmd-input-wrap {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; border-bottom: 1px solid var(--bv-border, #e5e7eb);
}
.bv-cmd-icon { color: var(--bv-text-muted, #6b7280); font-size: 15px; }
.bv-cmd-input {
    flex: 1; border: none; outline: none; font-size: 14px;
    background: transparent; color: var(--bv-text, #111827);
}
.bv-cmd-esc {
    font-size: 10px; padding: 2px 5px; border-radius: 4px;
    background: var(--bv-bg-subtle, #f3f4f6); color: var(--bv-text-muted, #6b7280);
    border: 1px solid var(--bv-border, #e5e7eb); cursor: pointer;
}
.bv-cmd-body { max-height: 400px; overflow-y: auto; }
.bv-cmd-section {
    padding: 6px 12px; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    color: var(--bv-text-muted, #6b7280);
    background: var(--bv-bg-subtle, #f9fafb);
    border-bottom: 1px solid var(--bv-border, #e5e7eb);
}
.bv-cmd-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px; width: 100%; border: none; background: none;
    cursor: pointer; text-align: left; transition: background .1s;
}
.bv-cmd-item:hover, .bv-cmd-item.on { background: var(--bv-hover, #f0fdf4); }
.bv-cmd-item__ico {
    width: 28px; height: 28px; border-radius: 6px;
    background: var(--bv-bg-subtle, #f3f4f6); display: grid; place-items: center;
    font-size: 12px; color: var(--bv-text-muted, #6b7280); flex-shrink: 0;
}
.bv-cmd-item__t { font-size: 13px; font-weight: 500; color: var(--bv-text, #111827); }
.bv-cmd-item__s { font-size: 11px; color: var(--bv-text-muted, #6b7280); }
.bv-cmd-empty { padding: 32px 16px; text-align: center; color: var(--bv-text-muted, #6b7280); font-size: 13px; }
.bv-cmd-empty i { font-size: 24px; display: block; margin-bottom: 8px; opacity: .4; }
.bv-cmd-loading { padding: 24px; text-align: center; color: var(--bv-text-muted, #6b7280); }
</style>
@endpush

@push('scripts')
<script>
(function ($) {
    'use strict';

    var _timer    = null;
    var _cursor   = -1;
    var _results  = [];

    function open() {
        $('#bv-cmd-palette').addClass('on').attr('aria-hidden', 'false');
        $('#bvCmdInput').val('').focus();
        renderEmpty();
        _cursor = -1;
        $('body').css('overflow', 'hidden');
    }

    function close() {
        $('#bv-cmd-palette').removeClass('on').attr('aria-hidden', 'true');
        $('body').css('overflow', '');
    }

    function renderEmpty() {
        $('#bvCmdBody').html('<div class="bv-cmd-empty"><i class="fas fa-magnifying-glass"></i><span>Escribe para buscar…</span></div>');
        _results = [];
    }

    function channelIcon(ch) {
        var map = {
            whatsapp: 'fab fa-whatsapp', facebook: 'fab fa-facebook-messenger',
            instagram: 'fab fa-instagram', email: 'far fa-envelope',
            widget: 'far fa-comment', webchat: 'far fa-comment',
        };
        return map[ch] || 'far fa-comment-dots';
    }

    function renderResults(data) {
        var customers     = data.customers || [];
        var conversations = data.conversations || [];
        var tags          = data.tags || [];

        if (!customers.length && !conversations.length && !tags.length) {
            $('#bvCmdBody').html('<div class="bv-cmd-empty"><i class="fas fa-inbox"></i><span>Sin resultados</span></div>');
            _results = [];
            return;
        }

        _results = [];
        var html = '';

        if (conversations.length) {
            html += '<div class="bv-cmd-section">Conversaciones</div>';
            conversations.forEach(function (c) {
                _results.push({ url: c.url, type: 'conversation' });
                html += '<button class="bv-cmd-item" data-result-idx="' + (_results.length - 1) + '" data-url="' + (c.url || '') + '">' +
                    '<div class="bv-cmd-item__ico"><i class="' + channelIcon(c.channel) + '"></i></div>' +
                    '<div style="flex:1;min-width:0">' +
                        '<div class="bv-cmd-item__t">' + (c.customer_name || '') + ' · ' + (c.subject || ('#' + c.id)) + '</div>' +
                        '<div class="bv-cmd-item__s">#' + c.id + '</div>' +
                    '</div></button>';
            });
        }

        if (customers.length) {
            html += '<div class="bv-cmd-section">Contactos</div>';
            customers.forEach(function (c) {
                _results.push({ url: c.url, type: 'customer' });
                html += '<button class="bv-cmd-item" data-result-idx="' + (_results.length - 1) + '" data-url="' + (c.url || '') + '">' +
                    '<div class="bv-cmd-item__ico"><i class="far fa-user"></i></div>' +
                    '<div style="flex:1;min-width:0">' +
                        '<div class="bv-cmd-item__t">' + (c.name || '') + '</div>' +
                        '<div class="bv-cmd-item__s">' + (c.email || c.phone || '') + '</div>' +
                    '</div></button>';
            });
        }

        if (tags.length) {
            html += '<div class="bv-cmd-section">Etiquetas</div>';
            tags.forEach(function (t) {
                _results.push({ url: '/panel/helpdesk/conversations?tag=' + t.id, type: 'tag' });
                html += '<button class="bv-cmd-item" data-result-idx="' + (_results.length - 1) + '" data-url="/panel/helpdesk/conversations?tag=' + t.id + '">' +
                    '<div class="bv-cmd-item__ico"><i class="fas fa-tag" style="color:' + (t.color || '#90bb13') + '"></i></div>' +
                    '<div style="flex:1;min-width:0"><div class="bv-cmd-item__t">' + t.name + '</div></div></button>';
            });
        }

        $('#bvCmdBody').html(html);
        _cursor = -1;
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
            renderResults(resp);
        }).fail(function () {
            $('#bvCmdBody').html('<div class="bv-cmd-empty"><i class="fas fa-triangle-exclamation"></i><span>Error al buscar</span></div>');
        });
    }

    function navigate(dir) {
        var $items = $('#bvCmdBody .bv-cmd-item');
        if (!$items.length) { return; }
        $items.removeClass('on');
        _cursor = (_cursor + dir + $items.length) % $items.length;
        $items.eq(_cursor).addClass('on')[0].scrollIntoView({ block: 'nearest' });
    }

    function goToSelected() {
        var $item = $('#bvCmdBody .bv-cmd-item.on');
        var url = $item.data('url') || ($item.length ? _results[parseInt($item.data('result-idx'), 10)]?.url : null);
        if (url) { close(); window.location.href = url; }
    }

    // Keyboard shortcut: Ctrl+K / Cmd+K
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if ($('#bv-cmd-palette').hasClass('on')) { close(); } else { open(); }
        }
        if (!$('#bv-cmd-palette').hasClass('on')) { return; }
        if (e.key === 'Escape') { close(); }
        if (e.key === 'ArrowDown') { e.preventDefault(); navigate(1); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); navigate(-1); }
        if (e.key === 'Enter')     { e.preventDefault(); goToSelected(); }
    });

    // Input
    $('#bvCmdInput').on('input', function () {
        clearTimeout(_timer);
        var q = $(this).val().trim();
        _timer = setTimeout(function () { search(q); }, 220);
    });

    // Click on result
    $(document).on('click', '#bvCmdBody .bv-cmd-item', function () {
        var url = $(this).data('url');
        if (url) { close(); window.location.href = url; }
    });

    // Click on ESC badge / backdrop
    $('#bvCmdEsc').on('click', function () { close(); });
    $('#bv-cmd-palette').on('click', function (e) {
        if ($(e.target).is('#bv-cmd-palette')) { close(); }
    });

    // Also allow opening from bv-modal trigger
    $(document).on('bv:modal:open', function (e, name) {
        if (name === 'command-palette') { open(); }
    });

    // Expose globally
    window.openCommandPalette = open;

}(window.jQuery));
</script>
@endpush
@endonce

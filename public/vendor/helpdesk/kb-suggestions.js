/**
 * Artículos sugeridos en el composer del inbox (knowledge base + helpcenter).
 *
 * Botón #bv-btn-kb-suggest (inyectado vía stack hd-composer-toolbar-buttons)
 * abre el panel #bv-kb-suggest-panel, pide sugerencias para el último mensaje
 * del cliente y permite insertar enlace o extracto en la respuesta.
 *
 * Delegación de eventos en document: el thread/composer se re-renderiza al
 * cambiar de conversación (SPA pane) y los nodos se sustituyen.
 */
(function ($) {
    'use strict';

    if (!$) { return; }

    var _loadedForConv = null;

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function suggestionsUrl(convId) {
        var template = $('#bv-kb-suggest-body').data('bv-kb-url-template') || '';
        return template.replace('__CONV__', String(convId));
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function togglePanel(show) {
        var $panel = $('#bv-kb-suggest-panel');
        $panel.toggleClass('on', show);
        $('#bv-btn-kb-suggest').attr('aria-expanded', show ? 'true' : 'false');
        return $panel;
    }

    function renderList(suggestions) {
        var $list = $('#bv-kb-suggest-list');
        var $state = $('#bv-kb-suggest-state');

        if (!suggestions.length) {
            $state.show().html('No se encontraron artículos relevantes para esta conversación.');
            $list.empty();
            return;
        }

        $state.hide();

        var html = suggestions.map(function (s) {
            var src = s.source === 'knowledge_base' ? 'Base de conocimiento' : 'Centro de ayuda';
            return '<div class="bv-kb-suggest__item" data-url="' + escHtml(s.url || '') + '" data-title="' + escHtml(s.title || '') + '" data-excerpt="' + escHtml(s.excerpt || '') + '">' +
                '<div class="bv-kb-suggest__meta">' +
                    '<div class="bv-kb-suggest__title">' + escHtml(s.title) + '</div>' +
                    '<div class="bv-kb-suggest__excerpt">' + escHtml(s.excerpt) + '</div>' +
                    '<span class="bv-kb-suggest__src">' + src + '</span>' +
                '</div>' +
                '<div class="bv-kb-suggest__actions">' +
                    (s.url ? '<button type="button" data-bv-kb-insert="link"><i class="fas fa-link"></i> Enlace</button>' : '') +
                    '<button type="button" data-bv-kb-insert="excerpt"><i class="fas fa-quote-right"></i> Extracto</button>' +
                    (s.url ? '<a href="' + escHtml(s.url) + '" target="_blank" rel="noopener noreferrer" aria-label="Abrir artículo"><button type="button"><i class="fas fa-arrow-up-right-from-square"></i></button></a>' : '') +
                '</div>' +
            '</div>';
        }).join('');

        $list.html(html);
    }

    function loadSuggestions(force) {
        var convId = getConvId();
        if (!convId) { return; }

        if (!force && _loadedForConv === convId && $('#bv-kb-suggest-list').children().length) {
            return; // ya cargado para esta conversación (el server cachea igualmente)
        }

        _loadedForConv = convId;
        $('#bv-kb-suggest-list').empty();
        $('#bv-kb-suggest-state').show().html('<i class="fas fa-spinner fa-spin"></i> Buscando artículos relevantes…');

        $.ajax({
            url: suggestionsUrl(convId),
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        }).done(function (resp) {
            var data = (resp && resp.data) || {};
            renderList(data.suggestions || []);
        }).fail(function (xhr) {
            var msg = (xhr && xhr.status === 429)
                ? 'Demasiadas consultas seguidas, espera unos segundos.'
                : 'No se pudieron cargar las sugerencias.';
            $('#bv-kb-suggest-state').show().html('<i class="fas fa-triangle-exclamation"></i> ' + escHtml(msg));
        });
    }

    function insertIntoComposer(text) {
        var $input = $('.bv-composer .bv-composer-input').first();
        if (!$input.length) { return; }

        var current = $input.val() || '';
        var glue = current && !/\s$/.test(current) ? '\n' : '';
        $input.val(current + glue + text).trigger('input').focus();
    }

    // Abrir / cerrar el panel
    $(document).on('click', '#bv-btn-kb-suggest', function () {
        var show = !$('#bv-kb-suggest-panel').hasClass('on');
        togglePanel(show);
        if (show) { loadSuggestions(false); }
    });

    $(document).on('click', '#bv-kb-suggest-close', function () {
        togglePanel(false);
    });

    // Insertar enlace o extracto en la respuesta
    $(document).on('click', '[data-bv-kb-insert]', function () {
        var $item = $(this).closest('.bv-kb-suggest__item');
        var mode = $(this).data('bv-kb-insert');
        var title = $item.data('title') || '';
        var url = $item.data('url') || '';
        var excerpt = $item.data('excerpt') || '';

        var text = mode === 'link' && url ? (title + ': ' + url) : excerpt;
        if (!text) { return; }

        insertIntoComposer(text);
        togglePanel(false);
        if (window.toastr) { window.toastr.success('Contenido insertado en la respuesta'); }
    });

}(window.jQuery));

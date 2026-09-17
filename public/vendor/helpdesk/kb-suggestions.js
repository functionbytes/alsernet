/**
 * Artículos sugeridos en el composer del inbox (knowledge base + helpcenter).
 *
 * Botón #bv-btn-kb-suggest (inyectado vía stack hd-composer-toolbar-buttons)
 * abre el panel #bv-kb-suggest-panel, pide sugerencias para el último mensaje
 * del cliente y permite insertar enlace o extracto en la respuesta.
 *
 * Delegación de eventos en document: el thread/composer se re-renderiza al
 * cambiar de conversación (SPA pane) y los nodos se sustituyen.
 *
 * El panel nunca debe quedarse en el estado "Buscando artículos relevantes…":
 * la petición lleva timeout, se aborta al cambiar de conversación y toda salida
 * (vacío, error, timeout, sin conversación) pinta un estado final con reintento.
 */
(function ($) {
    'use strict';

    if (!$) { return; }

    var REQUEST_TIMEOUT_MS = 12000;

    var _loadedForConv = null;
    var _inFlight = null;

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

    function abortInFlight() {
        var xhr = _inFlight;
        _inFlight = null;

        if (xhr && typeof xhr.abort === 'function') { xhr.abort(); }
    }

    function retryButton() {
        return ' <button type="button" class="bv-kb-suggest__retry" data-bv-kb-retry>Reintentar</button>';
    }

    /** Estado final: mensaje + reintento. Nunca deja el spinner colgado. */
    function showState(iconClass, msg, withRetry) {
        $('#bv-kb-suggest-list').empty();
        $('#bv-kb-suggest-state')
            .show()
            .html('<i class="' + iconClass + '"></i> ' + escHtml(msg) + (withRetry ? retryButton() : ''));
    }

    function renderList(suggestions, query) {
        var $list = $('#bv-kb-suggest-list');
        var $state = $('#bv-kb-suggest-state');

        if (!suggestions.length) {
            // Se muestra la consulta usada: casi siempre el motivo de que no
            // haya resultados es que el último mensaje del cliente no tiene
            // términos buscables (saludo, "ok", texto de prueba…).
            var empty = query
                ? 'No se encontraron artículos relevantes para «' + escHtml(query) + '».'
                : 'No se encontraron artículos relevantes para esta conversación.';

            $list.empty();
            $state.show().html('<i class="far fa-circle-question"></i> ' + empty + retryButton());

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

    /**
     * ¿La respuesta que acaba de llegar es de una conversación que ya no es la
     * abierta? Se descarta y, si el panel sigue visible con el spinner, se pide
     * de nuevo para la conversación actual: descartar sin más dejaría el panel
     * colgado en "Buscando artículos relevantes…".
     */
    function staleFor(convId) {
        if (getConvId() === convId) { return false; }

        if ($('#bv-kb-suggest-panel').hasClass('on')) {
            _loadedForConv = null;
            // Diferido: este código corre dentro del done/fail de la petición
            // vieja, que todavía figura como en vuelo.
            setTimeout(function () { loadSuggestions(true); }, 0);
        }

        return true;
    }

    function loadSuggestions(force) {
        var convId = getConvId();

        if (!convId) {
            // Sin conversación abierta no hay nada que consultar; antes se
            // salía en silencio y el panel se quedaba con el spinner estático.
            showState('far fa-circle-question', 'Abre una conversación para ver artículos sugeridos.', false);

            return;
        }

        if (!force && _loadedForConv === convId && $('#bv-kb-suggest-list').children().length) {
            return; // ya cargado para esta conversación (el server cachea igualmente)
        }

        abortInFlight();

        _loadedForConv = convId;
        $('#bv-kb-suggest-list').empty();
        $('#bv-kb-suggest-state').show().html('<i class="fas fa-spinner fa-spin"></i> Buscando artículos relevantes…');

        _inFlight = $.ajax({
            url: suggestionsUrl(convId),
            method: 'GET',
            timeout: REQUEST_TIMEOUT_MS,
            headers: { 'Accept': 'application/json' }
        }).done(function (resp) {
            // La conversación puede haber cambiado mientras la petición volaba:
            // pintar entonces sobrescribiría el panel con datos de otro hilo.
            if (staleFor(convId)) { return; }

            var data = (resp && resp.data) || {};
            renderList(data.suggestions || [], data.query || '');
        }).fail(function (xhr, textStatus) {
            if (textStatus === 'abort' || staleFor(convId)) { return; }

            var msg;

            if (textStatus === 'timeout') {
                msg = 'La búsqueda tardó demasiado. Inténtalo de nuevo.';
            } else if (xhr && xhr.status === 429) {
                msg = 'Demasiadas consultas seguidas, espera unos segundos.';
            } else {
                msg = 'No se pudieron cargar las sugerencias.';
            }

            // Falló: la próxima apertura del panel debe reintentar, no dar por
            // buena la conversación como "ya cargada".
            _loadedForConv = null;

            showState('fas fa-triangle-exclamation', msg, true);
        }).always(function () {
            _inFlight = null;
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
        abortInFlight();
        togglePanel(false);
    });

    $(document).on('click', '[data-bv-kb-retry]', function () {
        loadSuggestions(true);
    });

    // Al cambiar de conversación (o al recargarse el pane por actividad en
    // tiempo real de otro agente sobre la misma conversación) el thread se
    // sustituye por HTML fresco del servidor: el nodo del panel es otro y
    // vuelve a su estado inicial en el markup ("Buscando artículos
    // relevantes…" estático, sin la clase "on"). Si algo restaura la UI
    // previa marcándolo como abierto sin volver a pedir datos, el spinner
    // se queda colgado. Cerrar explícitamente aquí lo evita: la próxima
    // apertura manual siempre vuelve a disparar loadSuggestions().
    $(document).on('pane:loaded', function () {
        abortInFlight();
        _loadedForConv = null;
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

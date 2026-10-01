/**
 * HelpdeskMedia · decoración de adjuntos en la bandeja.
 * El panel del hilo se reemplaza por AJAX y los mensajes llegan en vivo, así
 * que un MutationObserver detecta adjuntos nuevos y pide los metadatos de la
 * conversación (ConversationItem.metadata.media). Mientras haya adjuntos sin
 * procesar se sondea cada 5 s durante 60 s como máximo.
 */
(function ($) {
    'use strict';

    var Media = window.HdMedia;
    if (!Media || window.HdMediaInbox) return;
    window.HdMediaInbox = true;

    var POLL_MS = 5000;
    var POLL_MAX_MS = 60000;
    var DEBOUNCE_MS = 250;

    var ATTACHMENT_SELECTOR = '.bv-attachment-gallery .bv-audio-msg[data-bv-audio-src], .bv-attachment-gallery a.bv-attach-thumb, .bv-attachment-gallery a.bv-attach-file';

    var state = { convId: null, pollStart: 0, pollTimer: null, request: null };

    function currentConversationId() {
        return $('.bv-composer[data-bv-conversation-id]').first().attr('data-bv-conversation-id') || null;
    }

    function attachmentUrl($el) {
        return $el.is('.bv-audio-msg') ? $el.attr('data-bv-audio-src') : $el.attr('href');
    }

    function indexMeta(items) {
        var byItem = {}, byPath = {};
        $.each(items || {}, function (itemId, urls) {
            byItem[itemId] = {};
            $.each(urls || {}, function (url, meta) {
                var path = Media.pathOf(url);
                byItem[itemId][path] = meta;
                byPath[path] = meta;
            });
        });
        return { byItem: byItem, byPath: byPath };
    }

    function lookup(index, $el) {
        var path = Media.pathOf(attachmentUrl($el));
        var itemId = $el.closest('.bv-bubble[data-bv-item-id]').attr('data-bv-item-id');
        return (itemId && index.byItem[itemId] && index.byItem[itemId][path]) || index.byPath[path] || null;
    }

    /** Devuelve true si el adjunto sigue sin procesar. */
    function decorate($el, meta) {
        var isAudio = $el.is('.bv-audio-msg');
        var processed = !!(meta && meta.processed_at);
        var waiting = !processed && Date.now() - state.pollStart < POLL_MAX_MS;
        var signature = meta ? [meta.processed_at, meta.scan, meta.transcript ? 1 : 0].join('|') : 'none';

        signature += waiting ? '|w' : '';
        if ($el.attr('data-hdm') === signature) return waiting;
        $el.attr('data-hdm', signature);

        var html = isAudio
            ? Media.transcriptHtml(meta, waiting) + Media.badgesHtml(meta)
            : Media.badgesHtml(meta);
        Media.renderAfter($el, html);

        return waiting;
    }

    function stopPolling() {
        clearTimeout(state.pollTimer);
        state.pollTimer = null;
    }

    function schedulePoll() {
        stopPolling();
        if (Date.now() - state.pollStart >= POLL_MAX_MS) return;
        state.pollTimer = setTimeout(scan, POLL_MS);
    }

    function scan() {
        stopPolling();

        var convId = currentConversationId();
        var $attachments = $(ATTACHMENT_SELECTOR);
        if (!convId || !$attachments.length) return;

        if (convId !== state.convId) {
            state.convId = convId;
            state.pollStart = Date.now();
        }

        if (state.request) state.request.abort();
        state.request = $.ajax({
            url: Media.cfg.conversationUrl.replace('__ID__', encodeURIComponent(convId)),
            dataType: 'json',
            cache: false
        }).done(function (response) {
            if (currentConversationId() !== convId) return;
            var index = indexMeta(response.items);
            var pending = false;
            $(ATTACHMENT_SELECTOR).each(function () {
                var $el = $(this);
                if (decorate($el, lookup(index, $el))) pending = true;
            });
            if (pending) schedulePoll();
        });
    }

    var debounce = null;
    function onMutation() {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
            var unseen = $(ATTACHMENT_SELECTOR).filter(':not([data-hdm])').length;
            if (!unseen) return;
            state.pollStart = Date.now();
            scan();
        }, DEBOUNCE_MS);
    }

    $(function () {
        new MutationObserver(onMutation).observe(document.body, { childList: true, subtree: true });
        onMutation();
    });
})(window.jQuery);

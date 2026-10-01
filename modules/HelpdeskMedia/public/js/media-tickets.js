/**
 * HelpdeskMedia · adjuntos del ticket (pestaña "Archivos" del detalle).
 * Las filas .tkt-filerow de adjuntos de cliente enlazan a
 * /tickets/{ticket}/message-attachments/{id}; de ese href se saca el ticket y
 * el adjunto, se piden los metadatos (antivirus, transcripción) y se decora
 * la fila. Sondeo ligero mientras haya adjuntos sin procesar.
 */
(function ($) {
    'use strict';

    var Media = window.HdMedia;
    if (!Media || window.HdMediaTickets) return;
    window.HdMediaTickets = true;

    var POLL_MS = 5000;
    var POLL_MAX_MS = 60000;
    var DEBOUNCE_MS = 250;
    var LINK_PATTERN = /\/tickets\/(\d+)\/message-attachments\/(\d+)/;
    var ROW_SELECTOR = '.tkt-filerow';

    var state = { pollStart: 0, pollTimer: null };

    function parseRow($row) {
        var match = LINK_PATTERN.exec($row.find('a[href*="/message-attachments/"]').attr('href') || '');
        return match ? { ticketId: match[1], attachmentId: match[2] } : null;
    }

    /** Devuelve true si el adjunto sigue sin procesar. */
    function decorate($row, meta) {
        var processed = !!(meta && meta.processed_at);
        var waiting = !processed && Date.now() - state.pollStart < POLL_MAX_MS;
        var signature = meta ? [meta.processed_at, meta.scan, meta.transcript ? 1 : 0].join('|') : 'none';
        signature += waiting ? '|w' : '';
        if ($row.attr('data-hdm') === signature) return waiting;
        $row.attr('data-hdm', signature);

        var $info = $row.find('.info').first();
        $info.find('.hdm-extra').remove();

        var isAudio = meta && meta.kind === 'audio';
        var html = (isAudio ? Media.transcriptHtml(meta, waiting) : '') + Media.badgesHtml(meta);
        if (html) $info.append($('<div class="hdm-extra">').html(html));

        return waiting;
    }

    function schedulePoll() {
        clearTimeout(state.pollTimer);
        if (Date.now() - state.pollStart >= POLL_MAX_MS) return;
        state.pollTimer = setTimeout(scan, POLL_MS);
    }

    function scan() {
        clearTimeout(state.pollTimer);

        var rowsByTicket = {};
        $(ROW_SELECTOR).each(function () {
            var parsed = parseRow($(this));
            if (!parsed) return;
            (rowsByTicket[parsed.ticketId] = rowsByTicket[parsed.ticketId] || []).push({ $row: $(this), id: parsed.attachmentId });
        });

        $.each(rowsByTicket, function (ticketId, rows) {
            $.ajax({
                url: Media.cfg.ticketUrl.replace('__ID__', encodeURIComponent(ticketId)),
                dataType: 'json',
                cache: false
            }).done(function (response) {
                var pending = false;
                $.each(rows, function (_, row) {
                    if (!document.body.contains(row.$row[0])) return;
                    var meta = (response.attachments || {})[row.id] || null;
                    if (decorate(row.$row, meta)) pending = true;
                });
                if (pending) schedulePoll();
            });
        });
    }

    var debounce = null;
    function onMutation() {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
            var unseen = $(ROW_SELECTOR).filter(':not([data-hdm])').filter(function () { return !!parseRow($(this)); });
            if (!unseen.length) return;
            state.pollStart = Date.now();
            scan();
        }, DEBOUNCE_MS);
    }

    $(function () {
        new MutationObserver(onMutation).observe(document.body, { childList: true, subtree: true });
        onMutation();
    });
})(window.jQuery);

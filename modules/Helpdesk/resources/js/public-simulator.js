(function () {
    const S = window.SIM;

    let conv = null;                // { id, token, channel }
    let selectedChannel = 'whatsapp';
    let savedSession = null;        // { channel, name, identifier } for pre-fill on "Nueva"
    const seen = new Set();         // rendered message ids (dedupe)
    let lastId = 0;
    let echo = null;
    let pollTimer = null;
    let typingTimer = null;
    let conversationResolved = false;
    let unreadCount = 0;
    const originalTitle = document.title;

    // Auto-scroll state
    let pendingScrollCount = 0;
    let userScrolledUp     = false;

    // Message grouping state
    let lastRenderFrom = null;
    let lastRenderDate = null;

    // Reconnect state
    let reconnectTimer = null;
    let reconnectDelay = 3000;

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': S.csrf } });
    toastr.options = { positionClass: 'toast-bottom-right', timeOut: 4000 };

    // Auto-resize textarea
    $(document).on('input', '#sim-message', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Shift+Enter = newline, Enter = send
    $(document).on('keydown', '#sim-message', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#sim-send-form').trigger('submit');
        }
    });

    const LABELS = {
        whatsapp:  { name: 'WhatsApp',  icon: 'fab fa-whatsapp',           id: 'Teléfono (opcional)',             ph: 'Ej: 34600111222' },
        facebook:  { name: 'Messenger', icon: 'fab fa-facebook-messenger', id: 'PSID de Facebook (opcional)',     ph: 'Ej: PSID_123456' },
        instagram: { name: 'Instagram', icon: 'fab fa-instagram',          id: 'Usuario/ID Instagram (opcional)', ph: 'Ej: IG_123456' },
        web:       { name: 'Website',   icon: 'fas fa-globe',              id: 'Email (opcional)',                ph: 'Ej: cliente@correo.com' },
    };

    // ── Channel selector ──
    function updateIdentityField() {
        const l = LABELS[selectedChannel];
        $('#sim-identifier-label').text(l.id);
        $('#sim-identifier').attr('placeholder', l.ph);
    }
    updateIdentityField();

    $('.sim-channel-btn').on('click', function () {
        selectedChannel = $(this).data('channel');
        $('.sim-channel-btn').removeClass('active');
        $(this).addClass('active');
        updateIdentityField();
    });

    // ── Lookup helpers ──
    function buildLookupResults(items, $container, triggerFill) {
        $container.empty();
        if (!items || !items.length) { $container.addClass('d-none'); return; }

        items.slice(0, 5).forEach(function (item) {
            const details = [];
            if (item.prestashop_id) details.push('PrestaShop #' + item.prestashop_id);
            if (item.gestion_id)    details.push('ERP #' + item.gestion_id);
            if (item.ps_orders)     details.push(item.ps_orders + ' pedidos');

            const $item = $('<div class="sim-lookup-item">')
                .append($('<div class="sim-lookup-name">').text(
                    (item.name || '') + (item.email ? ' · ' + item.email : '')
                ))
                .append($('<div class="sim-lookup-detail">').text(details.join(' · ')));

            $item.on('click', function () { triggerFill(item); });
            $container.append($item);
        });
        $container.removeClass('d-none');
    }

    function fillFromLookup(item) {
        if (item.name)           $('#sim-name').val(item.name);
        if (item.email)          $('#sim-email').val(item.email);
        if (item.prestashop_id)  $('#sim-ps-id').val(item.prestashop_id);
        if (item.gestion_id)     $('#sim-gestion-id').val(item.gestion_id);

        // Expand links section if collapsed
        const details = document.getElementById('sim-links-details');
        if (details && !details.open) { details.open = true; }

        $('#sim-lookup-identifier').addClass('d-none');
        $('#sim-lookup-email').addClass('d-none');
        $('#sim-linked-badge').removeClass('d-none');
    }

    function makeLookupDebounce($input, $container) {
        let timer;
        $input.on('input', function () {
            clearTimeout(timer);
            const q = $input.val().trim();
            if (q.length < 2) { $container.addClass('d-none'); return; }
            timer = setTimeout(function () {
                $.getJSON(S.lookupUrl, { q: q }, function (res) {
                    buildLookupResults(res.data || [], $container, fillFromLookup);
                });
            }, 600);
        });

        // Close on outside click
        $(document).on('click', function (e) {
            if (!$input.is(e.target) && !$container.is(e.target) && !$container.has(e.target).length) {
                $container.addClass('d-none');
            }
        });
    }

    makeLookupDebounce($('#sim-identifier'), $('#sim-lookup-identifier'));
    makeLookupDebounce($('#sim-email'),      $('#sim-lookup-email'));

    // ── Start session ──
    $('#sim-start-form').on('submit', function (e) {
        e.preventDefault();
        const message = $('#sim-first-message').val().trim();
        if (!message) { toastr.warning('Escribe un mensaje para iniciar.'); return; }

        const $btn = $('#sim-start-btn').prop('disabled', true);
        $.ajax({
            url: S.startUrl,
            method: 'POST',
            data: {
                channel:        selectedChannel,
                name:           $('#sim-name').val(),
                identifier:     $('#sim-identifier').val(),
                message:        message,
                phone:          $('#sim-phone').val(),
                email:          $('#sim-email').val(),
                prestashop_id:  $('#sim-ps-id').val(),
                gestion_id:     $('#sim-gestion-id').val(),
                simulated_datetime: $('#sim-simulated-datetime').val(),
            },
        }).done(function (res) {
            if (!res.ok) { toastr.error(res.error || 'No se pudo iniciar.'); return; }
            conv = { id: res.conversation_id, token: res.token, channel: res.channel, simulatedAt: $('#sim-simulated-datetime').val() || null };
            enterChat(res.item);
        }).fail(handleAjaxError).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function enterChat(firstItem) {
        const l = LABELS[conv.channel];
        const name = $('#sim-name').val() || 'Cliente simulado';

        $('#sim-channel-badge').html('<i class="' + l.icon + ' me-1"></i>' + l.name);
        let identity = name + ' · #' + conv.id;
        if (conv.simulatedAt) {
            identity += ' · <span class="sim-clock-badge"><i class="fas fa-clock me-1"></i>' + conv.simulatedAt.replace('T', ' ') + '</span>';
        }
        $('#sim-identity').html(identity);
        $('#sim-setup').addClass('d-none');
        $('#sim-chat')
            .removeClass('sim-channel-whatsapp sim-channel-facebook sim-channel-instagram sim-channel-web')
            .addClass('sim-channel-' + conv.channel)
            .removeClass('d-none');
        conversationResolved = false;
        closeEmojiPicker();

        renderMessage(firstItem);
        loadSessions();
        subscribeRealtime();
        startPolling();
        $('#sim-message').focus();
    }

    // ── Send follow-up message as customer ──
    $('#sim-send-form').on('submit', function (e) {
        e.preventDefault();
        if (!conv || conversationResolved) return;

        const message = $('#sim-message').val().trim();
        if (!message) return;
        $('#sim-message').val('').focus();

        const $sendBtn = $('#sim-send-btn').prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i>');

        $.ajax({
            url: S.base + '/' + conv.id + '/inbound',
            method: 'POST',
            data: { message: message, token: conv.token },
        }).done(function (res) {
            if (!res.ok) { toastr.error(res.error || 'No se pudo enviar.'); return; }
            renderMessage(res.item);
        }).fail(handleAjaxError).always(function () {
            $sendBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
        });
    });

    // ── Reset: pre-fill form, don't reload page ──
    $('#sim-reset, #sim-new-conv-btn').on('click', startNewConversation);

    function startNewConversation() {
        setUnreadCount(0);

        // Save session data for pre-fill
        savedSession = {
            channel:    conv ? conv.channel : selectedChannel,
            name:       $('#sim-name').val(),
            identifier: $('#sim-identifier').val(),
        };

        // Tear down realtime connections
        if (echo) { echo.disconnect(); echo = null; }
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        if (typingTimer) { clearTimeout(typingTimer); typingTimer = null; }
        if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }

        // Reset state
        conv = null;
        seen.clear();
        lastId = 0;
        conversationResolved = false;
        lastRenderFrom = null;
        lastRenderDate = null;
        reconnectDelay = 3000;
        userScrolledUp = false;
        pendingScrollCount = 0;

        // Reset UI
        $('#sim-thread').empty().append(
            $('<div id="sim-typing-indicator" class="sim-typing d-none">').html(
                'Agente escribiendo<span></span><span></span><span></span>'
            )
        );
        $('#sim-resolved-banner').addClass('d-none');
        $('#sim-inject-panel').addClass('d-none');
        $('#sim-inject-msg').val('');
        $('#sim-scroll-badge').addClass('d-none');
        $('#sim-message').prop('disabled', false).css('height', 'auto');
        $('#sim-send-btn').prop('disabled', false);
        $('#sim-file-btn, #sim-audio-btn').prop('disabled', false);
        $('#sim-linked-badge').addClass('d-none');
        $('#sim-lookup-identifier').addClass('d-none');
        $('#sim-lookup-email').addClass('d-none');
        $('#sim-conn').removeClass('live');

        // Switch views
        closeEmojiPicker();
        $('#sim-chat')
            .removeClass('sim-channel-whatsapp sim-channel-facebook sim-channel-instagram sim-channel-web')
            .addClass('d-none');
        $('#sim-setup').removeClass('d-none');
        renderSessions(allSessions); // deselect active card

        // Pre-fill saved session data
        if (savedSession) {
            selectedChannel = savedSession.channel;
            $('.sim-channel-btn').removeClass('active');
            $('.sim-channel-btn[data-channel="' + savedSession.channel + '"]').addClass('active');
            updateIdentityField();
            $('#sim-name').val(savedSession.name);
            $('#sim-identifier').val(savedSession.identifier);
        }

        // Only clear the message so user types a new one
        $('#sim-first-message').val('').focus();
    }

    // ── Rendering ──
    function formatTime(isoString) {
        const d = isoString ? new Date(isoString) : new Date();
        return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
    }

    function buildMessageBody(m) {
        const type  = m.content_type || 'text';
        const att   = m.attachments || [];
        const first = att[0] || {};

        let $body;

        if (m.link_preview && m.link_preview.title) {
            const lp    = m.link_preview;
            const $card = $('<div>').addClass('sim-link-preview');
            if (lp.image_url) { $card.append($('<img>').attr('src', lp.image_url).addClass('sim-lp-img')); }
            $card.append($('<div>').addClass('sim-lp-title').text(lp.title));
            if (lp.description) { $card.append($('<div>').addClass('sim-lp-desc').text(lp.description)); }
            if (lp.url) { $card.append($('<a>').attr({ href: lp.url, target: '_blank', rel: 'noopener' }).addClass('sim-lp-url').text(lp.domain || lp.url)); }
            const $wrap = $('<div>');
            if (m.body) { $wrap.append($('<div>').addClass('sim-msg-body').text(m.body)); }
            $body = $wrap.append($card);
        } else {
            switch (type) {
                case 'image': {
                    const $img = $('<img>').attr({ src: first.url, alt: first.name || 'imagen', loading: 'lazy' }).addClass('sim-att-img');
                    $img.on('click', function () { window.open(first.url, '_blank'); });
                    $img.on('load', scrollBottom);
                    $body = $('<div>').append($img);
                    break;
                }
                case 'audio': {
                    $body = $('<div>').append($('<audio>').attr({ controls: true, src: first.url, preload: 'metadata' }).addClass('sim-att-audio'));
                    break;
                }
                case 'video': {
                    const ytMatch = (first.url || '').match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/);
                    if (ytMatch) {
                        const $iframe = $('<iframe>').attr({ src: 'https://www.youtube.com/embed/' + ytMatch[1], width: 250, height: 141, frameborder: 0, allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture', allowfullscreen: true }).addClass('sim-att-video');
                        $body = $('<div>').append($iframe);
                    } else {
                        $body = $('<div>').append($('<video>').attr({ controls: true, src: first.url, preload: 'metadata' }).addClass('sim-att-video'));
                    }
                    break;
                }
                case 'file': {
                    const icon    = (first.mime_type || '').includes('pdf') ? 'fa-file-pdf' : 'fa-file';
                    const sizeStr = first.size > 0 ? ' · ' + Math.round(first.size / 1024) + ' KB' : '';
                    const $card   = $('<a>').attr({ href: first.url, target: '_blank', rel: 'noopener', download: first.name }).addClass('sim-att-file d-flex align-items-center gap-2 text-decoration-none');
                    $card.append($('<i>').addClass('fas ' + icon + ' fa-lg'));
                    $card.append($('<span>').text((first.name || 'archivo') + sizeStr));
                    $body = $('<div>').append($card);
                    break;
                }
                default:
                    $body = $('<div>').addClass('sim-msg-body').text(m.body || '');
            }
        }

        // Product carousel (native cards on FB/web, stacked images on WhatsApp/Instagram)
        const cards = m.cards || [];
        if (cards.length) {
            const $cards = $('<div>').addClass('sim-cards');
            cards.slice(0, 10).forEach(function (card) {
                const $c = $('<div>').addClass('sim-pcard');
                if (card.image_url) {
                    $c.append($('<img>').attr({ src: card.image_url, alt: card.title || 'producto', loading: 'lazy' }).on('load', scrollBottom));
                }
                const $cb = $('<div>').addClass('sim-pcard-body');
                if (card.title)    { $cb.append($('<div>').addClass('sim-pcard-title').text(card.title)); }
                if (card.subtitle) { $cb.append($('<div>').addClass('sim-pcard-sub').text(card.subtitle)); }
                if (card.url)      { $cb.append($('<a>').attr({ href: card.url, target: '_blank', rel: 'noopener' }).addClass('sim-pcard-btn').text('Ver')); }
                $c.append($cb);
                $cards.append($c);
            });
            $body = $('<div>').append($body).append($cards);
        }

        // Options → native buttons within each channel's limit; otherwise a note
        // (the body already carries the numbered "1, 2, 3…" list as the fallback).
        const qr = m.quick_replies || [];
        if (qr.length) {
            const NATIVE_LIMIT = { whatsapp: 3, facebook: 11, instagram: 13, web: 99 };
            const ch    = (conv && conv.channel) || 'web';
            const limit = NATIVE_LIMIT[ch] || 99;

            if (qr.length <= limit) {
                const $qrRow = $('<div>').addClass('sim-quick-replies');
                qr.forEach(function (reply) {
                    const label = typeof reply === 'string' ? reply : (reply.title || reply.label || reply);
                    const $btn  = $('<button>').addClass('sim-qr-btn').text(label);
                    $btn.on('click', function () {
                        if (!conv || conversationResolved) return;
                        $('#sim-message').val(label);
                        $('#sim-send-form').trigger('submit');
                        $btn.closest('.sim-quick-replies').remove();
                    });
                    $qrRow.append($btn);
                });
                $body = $('<div>').append($body).append($qrRow);
            } else {
                const chName = (LABELS[ch] || {}).name || 'Este canal';
                const $note  = $('<div>').addClass('sim-limit-note').html(
                    '<i class="fas fa-info-circle me-1"></i>' + qr.length + ' opciones · ' + chName +
                    ' solo admite ' + limit + ' botones: se envían como lista numerada (1, 2, 3…).'
                );
                $body = $('<div>').append($body).append($note);
            }
        }

        return $body;
    }

    function playNotificationBeep() {
        try {
            const ctx  = new (window.AudioContext || window.webkitAudioContext)();
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) { /* no-op */ }
    }

    function showBrowserNotification(senderName, body) {
        if (!('Notification' in window) || Notification.permission !== 'granted' || document.hasFocus()) return;
        const text = typeof body === 'string' && body.length > 80 ? body.substring(0, 77) + '…' : (body || '📎 Archivo');
        const n = new Notification('Nuevo mensaje de ' + senderName, {
            body: text,
            icon: '/favicon.ico',
            tag: 'helpdesk-sim-msg',
        });
        n.onclick = function () { window.focus(); n.close(); };
        setTimeout(function () { n.close(); }, 6000);
    }

    function setUnreadCount(n) {
        unreadCount = n;
        document.title = n > 0 ? '💬 (' + n + ') ' + originalTitle : originalTitle;
    }

    $(window).on('focus', function () { setUnreadCount(0); });

    function renderMessage(m) {
        if (!m || seen.has(m.id)) return;
        seen.add(m.id);
        if (m.id > lastId) lastId = m.id;

        hideTypingIndicator();

        if (m.from === 'agent' && !document.hasFocus()) {
            playNotificationBeep();
            showBrowserNotification(m.sender_name || 'Agente', m.body || '');
            setUnreadCount(unreadCount + 1);
        }

        const mine = m.from === 'customer';
        const who  = mine ? 'Tú' : (m.sender_name || 'Agente');
        const time = formatTime(m.created_at || null);

        // Date separator
        const msgDate = m.created_at ? new Date(m.created_at).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' }) : null;
        if (msgDate && msgDate !== lastRenderDate) {
            const label = (function () {
                const today     = new Date().toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
                const yesterday = new Date(Date.now() - 86400000).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
                if (msgDate === today)     return 'Hoy';
                if (msgDate === yesterday) return 'Ayer';
                return msgDate;
            }());
            $('#sim-typing-indicator').before($('<span>').addClass('sim-date-sep').html('<span>' + label + '</span>'));
            lastRenderDate = msgDate;
            lastRenderFrom = null;
        }

        // Consecutive same-sender grouping
        const grouped = m.from === lastRenderFrom;
        lastRenderFrom = m.from;

        const $msg = $('<div>').addClass('sim-msg ' + (mine ? 'sim-msg-out' : 'sim-msg-in') + (grouped ? ' sim-msg-grouped' : ''));
        $msg.append($('<div>').addClass('sim-msg-who').text(who));
        $msg.append(buildMessageBody(m));
        $msg.append($('<div>').addClass('sim-msg-time').text(time));

        // Delivery receipt on the customer's own messages (✓ sent → ✓✓ read once the bot replies).
        if (mine) {
            $msg.append($('<div>').addClass('sim-msg-receipt').html('<i class="fas fa-check"></i>'));
        } else {
            $('.sim-msg-out .sim-msg-receipt').html('<i class="fas fa-check-double"></i> Leído');
        }

        if (m.type === 'note') {
            $msg.addClass('sim-msg-note');
        }

        // Scroll badge for agent messages when user has scrolled up
        if (userScrolledUp && m.from === 'agent') {
            pendingScrollCount++;
            $('#sim-scroll-count').text(pendingScrollCount);
            $('#sim-scroll-badge').removeClass('d-none');
        }

        $('#sim-typing-indicator').before($msg);
        scrollBottom();
    }

    function renderSystemMessage(text) {
        const $sys = $('<span>').addClass('sim-system-msg').text(text);
        $('#sim-typing-indicator').before($sys);
        scrollBottom();
    }

    function scrollBottom(force) {
        const el = document.getElementById('sim-thread');
        if (force || !userScrolledUp) {
            el.scrollTop = el.scrollHeight;
        }
    }

    // Detect manual scroll
    $('#sim-thread').on('scroll', function () {
        const el = this;
        const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 40;
        if (atBottom) {
            userScrolledUp = false;
            pendingScrollCount = 0;
            $('#sim-scroll-badge').addClass('d-none');
        } else {
            userScrolledUp = true;
        }
    });

    $('#sim-scroll-btn').on('click', function () {
        userScrolledUp = false;
        pendingScrollCount = 0;
        $('#sim-scroll-badge').addClass('d-none');
        scrollBottom(true);
    });

    // ── Typing indicator ──
    function showTypingIndicator() {
        $('#sim-typing-indicator').removeClass('d-none');
        scrollBottom();
        clearTimeout(typingTimer);
        typingTimer = setTimeout(hideTypingIndicator, 8000);
    }

    function hideTypingIndicator() {
        clearTimeout(typingTimer);
        typingTimer = null;
        $('#sim-typing-indicator').addClass('d-none');
    }

    // ── Conversation resolved ──
    function markResolved() {
        if (conversationResolved) return;
        conversationResolved = true;
        renderSystemMessage('✅ Conversación resuelta');
        $('#sim-resolved-banner').removeClass('d-none');
        $('#sim-message').prop('disabled', true);
        $('#sim-send-btn').prop('disabled', true);
        $('#sim-file-btn, #sim-audio-btn').prop('disabled', true);

        // Try to show CSAT
        $.ajax({
            url:    S.base + '/' + conv.id + '/csat',
            method: 'GET',
            data:   { token: conv.token },
        }).done(function (res) {
            if (res.ok && res.token) { showCsatWidget(res.token); }
        });
    }

    function showCsatWidget(csatToken) {
        const $widget = $('<div>').addClass('sim-csat-widget p-3 mt-2 rounded border').html(
            '<p class="mb-2 fw-semibold small">¿Cómo te atendimos? <span class="text-muted fw-normal">(Califica del 1 al 5)</span><' + '/p>' +
            '<div class="sim-csat-stars d-flex gap-2 mb-2">' +
                [1,2,3,4,5].map(function (i) {
                    return '<button type="button" class="sim-star-btn" data-val="' + i + '" title="' + i + ' estrella' + (i > 1 ? 's' : '') + '">⭐<' + '/button>';
                }).join('') +
            '<' + '/div>' +
            '<textarea class="form-control form-control-sm mb-2" id="sim-csat-comment" rows="2" placeholder="Comentario opcional…" maxlength="500"><' + '/textarea>' +
            '<button type="button" class="btn btn-sm sim-btn-primary w-100" id="sim-csat-submit" disabled>Enviar calificación<' + '/button>'
        );

        let selectedRating = 0;

        $widget.on('click', '.sim-star-btn', function () {
            selectedRating = parseInt($(this).data('val'));
            $widget.find('.sim-star-btn').each(function () {
                $(this).toggleClass('active', parseInt($(this).data('val')) <= selectedRating);
            });
            $('#sim-csat-submit').prop('disabled', false);
        });

        $widget.on('click', '#sim-csat-submit', function () {
            if (!selectedRating) return;
            $(this).prop('disabled', true).text('Enviando…');
            $.ajax({
                url:    '/helpdesk/csat/' + csatToken,
                method: 'POST',
                data:   { rating: selectedRating, comment: $('#sim-csat-comment').val(), _token: S.csrf },
            }).always(function () {
                $widget.html('<p class="mb-0 text-success small fw-semibold">✅ ¡Gracias por tu calificación!</p>');
            });
        });

        $('#sim-thread').append($widget);
        scrollBottom();
    }

    // ── File upload ──
    function uploadFile(file) {
        if (!conv || conversationResolved || !file) return;

        const fd      = new FormData();
        fd.append('file', file);
        fd.append('token', conv.token);

        let $pending = null;
        let blobUrl  = null;

        if (file.type.startsWith('image/')) {
            blobUrl  = URL.createObjectURL(file);
            $pending = $('<div>').addClass('sim-msg sim-msg-out');
            $pending.append($('<div>').addClass('sim-msg-who').text('Tú'));
            const $img = $('<img>').attr({ src: blobUrl, alt: file.name, loading: 'eager' }).addClass('sim-att-img').css('opacity', '.55');
            $img.on('load', scrollBottom);
            $pending.append($('<div>').append($img));
            $pending.append($('<div>').addClass('sim-msg-time').html('<i class="fas fa-circle-notch fa-spin me-1 bv-fs-60"></i>Enviando…'));
            $('#sim-typing-indicator').before($pending);
            scrollBottom();
        } else {
            toastr.info('Subiendo ' + file.name + '…', '', { timeOut: 2500 });
        }

        const $btn = $('#sim-file-btn').prop('disabled', true);

        $.ajax({
            url:         S.base + '/' + conv.id + '/attachment',
            method:      'POST',
            data:        fd,
            processData: false,
            contentType: false,
        }).done(function (res) {
            if ($pending) { $pending.remove(); if (blobUrl) { URL.revokeObjectURL(blobUrl); } }
            if (!res.ok) { toastr.error(res.error || 'No se pudo subir.'); return; }
            renderMessage(res.item);
        }).fail(function (xhr) {
            if ($pending) { $pending.remove(); }
            handleAjaxError(xhr);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    $('#sim-file-input').on('change', function () {
        uploadFile(this.files[0]);
        this.value = '';
    });

    // ── Paste image from clipboard ──
    $(document).on('paste', function (e) {
        if (!conv || conversationResolved) return;
        const items = (e.originalEvent.clipboardData || e.clipboardData || {}).items;
        if (!items) return;

        for (let i = 0; i < items.length; i++) {
            if (items[i].type.startsWith('image/')) {
                e.preventDefault();
                const file = items[i].getAsFile();
                if (!file) continue;

                const blobUrl  = URL.createObjectURL(file);
                const $pending = $('<div>').addClass('sim-msg sim-msg-out');
                $pending.append($('<div>').addClass('sim-msg-who').text('Tú'));
                const $img = $('<img>').attr({ src: blobUrl, alt: 'imagen', loading: 'eager' }).addClass('sim-att-img').css('opacity', '.55');
                $img.on('load', scrollBottom);
                $pending.append($('<div>').append($img));
                $pending.append($('<div>').addClass('sim-msg-time').html('<i class="fas fa-circle-notch fa-spin me-1 bv-fs-60"></i>Enviando…'));
                $('#sim-typing-indicator').before($pending);
                scrollBottom();

                const fd = new FormData();
                fd.append('file', file, 'paste-' + Date.now() + '.png');
                fd.append('token', conv.token);

                $.ajax({
                    url:         S.base + '/' + conv.id + '/attachment',
                    method:      'POST',
                    data:        fd,
                    processData: false,
                    contentType: false,
                }).done(function (res) {
                    $pending.remove();
                    URL.revokeObjectURL(blobUrl);
                    if (res.ok) { renderMessage(res.item); }
                    else { toastr.error(res.error || 'No se pudo subir la imagen.'); }
                }).fail(function (xhr) {
                    $pending.remove();
                    handleAjaxError(xhr);
                });
                break;
            }
        }
    });

    // ── Drag & drop ──
    const $chatCard = $('#sim-chat');

    $chatCard.on('dragover dragenter', function (e) {
        if (!conv || conversationResolved) return;
        e.preventDefault();
        e.stopPropagation();
        $chatCard.addClass('drag-over');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $chatCard.removeClass('drag-over');
    }).on('drop', function (e) {
        if (!conv || conversationResolved) return;
        const files = e.originalEvent.dataTransfer.files;
        if (!files || !files.length) return;
        uploadFile(files[0]);
    });

    // ── Audio recording ──
    let mediaRecorder  = null;
    let audioChunks    = [];
    let recordingTimer = null;
    let isRecording    = false;

    $('#sim-audio-btn').on('click', async function () {
        if (!conv || conversationResolved) return;

        if (isRecording) {
            mediaRecorder.stop();
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioChunks  = [];
            mediaRecorder = new MediaRecorder(stream);
            isRecording   = true;
            $('#sim-audio-icon').removeClass('fa-microphone').addClass('fa-stop text-dark');
            $('#sim-audio-btn').addClass('border-dark');

            mediaRecorder.ondataavailable = function (e) {
                if (e.data.size > 0) { audioChunks.push(e.data); }
            };

            mediaRecorder.onstop = function () {
                isRecording = false;
                $('#sim-audio-icon').removeClass('fa-stop text-dark').addClass('fa-microphone');
                $('#sim-audio-btn').removeClass('border-dark');
                stream.getTracks().forEach(function (t) { t.stop(); });
                clearTimeout(recordingTimer);

                const blob = new Blob(audioChunks, { type: 'audio/webm' });
                const fd   = new FormData();
                fd.append('file', blob, 'audio-' + Date.now() + '.webm');
                fd.append('token', conv.token);

                toastr.info('Enviando audio…', '', { timeOut: 2000 });
                $.ajax({
                    url:         S.base + '/' + conv.id + '/attachment',
                    method:      'POST',
                    data:        fd,
                    processData: false,
                    contentType: false,
                }).done(function (res) {
                    if (res.ok) { renderMessage(res.item); }
                    else { toastr.error(res.error || 'No se pudo enviar el audio.'); }
                }).fail(handleAjaxError);
            };

            mediaRecorder.start();
            // Auto-stop after 60s
            recordingTimer = setTimeout(function () {
                if (isRecording && mediaRecorder) { mediaRecorder.stop(); }
            }, 60000);
        } catch (e) {
            toastr.error('No se pudo acceder al micrófono.');
        }
    });

    // ── Share conversation link ──
    $('#sim-share-btn').on('click', function () {
        if (!conv) return;
        const url = window.location.origin + S.base + '?conv=' + conv.id + '&token=' + conv.token;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(function () { toastr.success('Enlace copiado'); });
        } else {
            const $tmp = $('<input>').val(url).appendTo('body').select();
            document.execCommand('copy');
            $tmp.remove();
            toastr.success('Enlace copiado');
        }
    });

    // ── Export conversation ──
    $('#sim-export-btn').on('click', function () {
        if (!conv) return;
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url:    S.base + '/' + conv.id + '/messages',
            method: 'GET',
            data:   { token: conv.token, after_id: 0 },
        }).done(function (res) {
            if (!res.ok) { toastr.error('No se pudo exportar.'); return; }

            const lines = ['Conversación #' + conv.id, 'Exportado: ' + new Date().toLocaleString('es-ES'), Array(40).fill('─').join(''), ''];
            res.messages.forEach(function (m) {
                const time   = m.created_at ? new Date(m.created_at).toLocaleString('es-ES') : '';
                const sender = m.from === 'agent' ? (m.sender_name || 'Agente') : 'Tú';
                const prefix = m.type === 'note' ? '[NOTA] ' : '';
                lines.push('[' + time + '] ' + prefix + sender + ':');
                if (m.body) { lines.push(m.body); }
                if (m.attachments && m.attachments.length) {
                    m.attachments.forEach(function (a) { lines.push('📎 ' + (a.name || a.url)); });
                }
                lines.push('');
            });

            const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
            const a    = document.createElement('a');
            a.href     = URL.createObjectURL(blob);
            a.download = 'conv-' + conv.id + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
            toastr.success('Conversación exportada');
        }).fail(handleAjaxError).always(function () {
            $btn.prop('disabled', false);
        });
    });

    // ── Dev inject panel ──
    $('#sim-dev-btn').on('click', function () {
        $('#sim-inject-panel').toggleClass('d-none');
        if (!$('#sim-inject-panel').hasClass('d-none')) {
            $('#sim-inject-msg').focus();
        }
    });

    $('#sim-inject-btn').on('click', function () {
        if (!conv) return;
        const msg = $('#sim-inject-msg').val().trim();
        if (!msg) return;

        const $btn = $(this).prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i>');

        $.ajax({
            url:    S.base + '/' + conv.id + '/inject',
            method: 'POST',
            data:   { message: msg, token: conv.token },
        }).done(function (res) {
            if (res.ok) {
                renderMessage(res.item);
                $('#sim-inject-msg').val('');
            } else {
                toastr.error(res.error || 'No se pudo inyectar.');
            }
        }).fail(handleAjaxError).always(function () {
            $btn.prop('disabled', false).html('Inyectar');
        });
    });

    $('#sim-inject-msg').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#sim-inject-btn').trigger('click'); }
    });

    // ── Reconnect with exponential backoff ──
    function scheduleReconnect() {
        if (reconnectTimer || !conv) return;
        reconnectTimer = setTimeout(function () {
            reconnectTimer = null;
            reconnectDelay = Math.min(reconnectDelay * 2, 30000);
            if (!conv) return;
            if (echo) { try { echo.disconnect(); } catch (e) {} echo = null; }
            subscribeRealtime();
        }, reconnectDelay);
    }

    // ── Real-time (Reverb/Echo) ──
    function subscribeRealtime() {
        if (typeof window.Echo === 'undefined' || !S.reverb.key) { return; }

        try {
            let host = S.reverb.host;
            if (!host || host === '0.0.0.0' || host === '127.0.0.1') {
                host = window.location.hostname;
            }

            echo = new window.Echo({
                broadcaster:        'reverb',
                key:                S.reverb.key,
                wsHost:             host,
                wsPort:             S.reverb.port,
                wssPort:            S.reverb.port,
                forceTLS:           S.reverb.scheme === 'https',
                enabledTransports:  ['ws', 'wss'],
                disableStats:       true,
            });

            const pusher = echo.connector.pusher;
            pusher.connection.bind('connected', function () {
                $('#sim-conn').addClass('live');
                reconnectDelay = 3000;
                if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }
            });
            pusher.connection.bind('disconnected', function () {
                $('#sim-conn').removeClass('live');
                scheduleReconnect();
            });
            pusher.connection.bind('unavailable', function () {
                $('#sim-conn').removeClass('live');
                scheduleReconnect();
            });

            echo.channel('helpdesk-widget-conversation.' + conv.id)
                .listen('.message.received', function (ev) {
                    // Only render agent (outgoing) messages; customer messages come from POST response
                    if (ev.message_type === 'outgoing') {
                        renderMessage({
                            id:          ev.id,
                            body:        ev.content,
                            from:        'agent',
                            sender_name: (ev.sender && ev.sender.name) || 'Agente',
                            created_at:  ev.created_at || null,
                        });
                    }
                })
                .listen('.UserTyping', function (ev) {
                    if (ev.is_typing) {
                        showTypingIndicator();
                    } else {
                        hideTypingIndicator();
                    }
                })
                .listen('.conversation.assigned', function (ev) {
                    const agent = ev.agent_name || 'Un agente';
                    renderSystemMessage('👤 ' + agent + ' ha tomado tu consulta');
                })
                .listen('.conversation.status.changed', function (ev) {
                    if (ev.is_closed) { markResolved(); }
                });
        } catch (err) {
            console.warn('Echo no disponible; se usará polling.', err);
        }
    }

    // ── Polling fallback (always on; deduped by id) ──
    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(pollMessages, 3000);
    }

    function pollMessages() {
        if (!conv) return;
        $.ajax({
            url:    S.base + '/' + conv.id + '/messages',
            method: 'GET',
            data:   { token: conv.token, after_id: lastId },
        }).done(function (res) {
            if (res.ok && res.messages) { res.messages.forEach(renderMessage); }
        });
    }

    function handleAjaxError(xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            Object.values(xhr.responseJSON.errors).forEach(function (msgs) {
                toastr.error(msgs[0]);
            });
            return;
        }
        const msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Error de red.';
        toastr.error(msg);
    }

    // ── Emoji picker ──
    const ALL_EMOJIS = [
        '😀','😁','😂','🤣','😃','😄','😅','😆','😊','😍','🥰','😘','😎','🤔','😐','😑','😶','🙄','😏','😒',
        '😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵',
        '😰','😱','🤗','🤭','🤫','🤥','😶','😇','🥳','🥸','🤩','🥴','😵','🤑','🤠','😷','🤒','🤕',
        '👍','👎','👋','🤝','🙏','💪','✌️','🤞','👌','🤌','🤏','☝️','👆','👇','👉','👈','🫵','✋','🖐️','👏',
        '❤️','🧡','💛','💚','💙','💜','🖤','🤍','💕','💞','💓','💗','💖','💘','💝','💔','❤️‍🔥',
        '🎉','🎊','🎈','🎁','✅','❌','⚠️','🔥','⭐','💯','🚀','💡','📦','📋','📞','✉️','🔔','💬','🏠',
        '😺','😸','😹','😻','😼','😽','🙀','😿','😾','🐶','🐱','🐭','🐰','🐻','🐼','🐨','🦁','🐸','🐣',
        '🍕','🍔','🍟','🌮','🍜','🍣','🍦','🎂','🍩','☕','🍺','🥂','🍷','🍾',
        '⚽','🏀','🎮','🎯','🎸','🎵','🎤','🏆','🥇','🎭','🎨',
        '🌍','🌈','⛅','🌙','⭐','🌸','🌺','🌻','🍀','🌴','🌊','🏔️','🏖️',
        '💻','📱','⌨️','🖥️','🖨️','💾','📷','📸','📹','🔍','🔎','📚','✏️','📝','🗒️',
    ];

    function buildEmojiGrid(filter) {
        const $grid  = $('#sim-emoji-grid').empty();
        const emojis = filter ? ALL_EMOJIS.filter(function (e) { return e.includes(filter); }) : ALL_EMOJIS;
        emojis.forEach(function (emoji) {
            $('<button>').attr({ type: 'button', title: emoji }).addClass('sim-emoji-btn-item').text(emoji)
                .on('click', function () {
                    insertAtCursor($('#sim-message'), emoji);
                    closeEmojiPicker();
                })
                .appendTo($grid);
        });
    }

    function openEmojiPicker() {
        $('#sim-emoji-search').val('');
        buildEmojiGrid('');
        $('#sim-emoji-picker').removeClass('d-none');
        $('#sim-emoji-search').focus();
    }

    function closeEmojiPicker() {
        $('#sim-emoji-picker').addClass('d-none');
    }

    function insertAtCursor($el, text) {
        const el    = $el[0];
        const start = el.selectionStart || 0;
        const end   = el.selectionEnd   || 0;
        const val   = el.value;
        el.value = val.substring(0, start) + text + val.substring(end);
        el.selectionStart = el.selectionEnd = start + text.length;
        $el.trigger('input'); // trigger auto-resize
        $el.focus();
    }

    $('#sim-emoji-btn').on('click', function (e) {
        e.stopPropagation();
        if ($('#sim-emoji-picker').hasClass('d-none')) {
            openEmojiPicker();
        } else {
            closeEmojiPicker();
        }
    });

    $('#sim-emoji-search').on('input', function () {
        buildEmojiGrid($(this).val().trim());
    }).on('keydown', function (e) {
        if (e.key === 'Escape') { closeEmojiPicker(); }
    });

    // Close on click outside
    $(document).on('click', function (e) {
        if (!$('#sim-emoji-picker').hasClass('d-none') &&
            !$(e.target).closest('#sim-emoji-picker, #sim-emoji-btn').length) {
            closeEmojiPicker();
        }
    });

    // ── Sessions list ──
    const CHANNEL_ICONS = {
        whatsapp:  'fab fa-whatsapp sim-ico-whatsapp',
        facebook:  'fab fa-facebook-messenger sim-ico-facebook',
        instagram: 'fab fa-instagram sim-ico-instagram',
        web:       'fas fa-globe sim-ico-web',
    };

    let allSessions = [];
    let showAllSessions = false;

    function timeAgo(isoString) {
        if (!isoString) return '';
        const diff = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000);
        if (diff < 60)    return 'hace un momento';
        if (diff < 3600)  return 'hace ' + Math.floor(diff / 60) + ' min';
        if (diff < 86400) return 'hace ' + Math.floor(diff / 3600) + ' h';
        return 'hace ' + Math.floor(diff / 86400) + ' d';
    }

    function getHiddenSessions() {
        try { return JSON.parse(localStorage.getItem('sim_hidden_sessions') || '[]'); } catch (e) { return []; }
    }

    function addHiddenSession(id) {
        const hidden = getHiddenSessions();
        if (hidden.indexOf(id) === -1) { hidden.push(id); }
        localStorage.setItem('sim_hidden_sessions', JSON.stringify(hidden));
    }

    function renderSessions(rawSessions) {
        const hidden   = getHiddenSessions();
        const sessions = rawSessions.filter(function (s) { return hidden.indexOf(s.id) === -1; });

        const $grid  = $('#sim-sessions-grid').empty();
        const visible = showAllSessions ? sessions : sessions.slice(0, 6);

        if (!sessions.length) {
            $grid.append('<div class="sim-sessions-empty">No hay sesiones aún</div>');
            $('#sim-sessions-more').addClass('d-none');
            return;
        }

        visible.forEach(function (s) {
            const iconClass   = CHANNEL_ICONS[s.channel] || 'fas fa-circle text-secondary';
            const statusClass = s.is_closed ? 'closed' : '';
            const isActive    = conv && conv.id === s.id;
            const chLabel     = (LABELS[s.channel] || {}).name || (s.channel || 'web');

            const $card = $('<div>')
                .addClass('sim-session-card sim-session-' + (s.channel || 'web') + (isActive ? ' active' : ''))
                .attr('data-id', s.id)
                .html(
                    '<div class="sim-session-ch"><i class="' + iconClass + ' me-1"></i>' + chLabel + '</div>' +
                    '<div class="sim-session-name">' + $('<span>').text(s.customer_name || 'Cliente').html() + ' <span class="text-muted fw-normal">#' + s.id + '</span></div>' +
                    '<div class="sim-session-preview">' + $('<span>').text(s.last_message || '—').html() + '</div>' +
                    '<div class="sim-session-time"><span class="sim-session-status ' + statusClass + '"></span>' + timeAgo(s.last_message_at) + '</div>'
                );

            if (s.message_count) {
                $card.append($('<div>').addClass('sim-session-count').text(s.message_count + ' mensajes'));
            }

            const $del = $('<button>').addClass('sim-session-del').attr('title', 'Ocultar sesión').html('&times;');
            $del.on('click', function (e) {
                e.stopPropagation();
                addHiddenSession(s.id);
                renderSessions(allSessions);
            });
            $card.append($del);

            $card.on('click', function () {
                if (conv && conv.id === s.id) return;
                resumeSession(s);
            });

            $grid.append($card);
        });

        if (sessions.length > 6) {
            $('#sim-sessions-more').toggleClass('d-none', showAllSessions);
            $('#sim-sessions-show-all').text('Ver todas (' + sessions.length + ')');
        } else {
            $('#sim-sessions-more').addClass('d-none');
        }
    }

    function resumeSession(s) {
        if (echo)       { echo.disconnect(); echo = null; }
        if (pollTimer)  { clearInterval(pollTimer); pollTimer = null; }
        if (typingTimer){ clearTimeout(typingTimer); typingTimer = null; }

        conv = { id: s.id, token: s.token, channel: s.channel };
        seen.clear();
        lastId = 0;
        conversationResolved = s.is_closed;
        lastRenderFrom = null;
        lastRenderDate = null;
        userScrolledUp = false;
        pendingScrollCount = 0;

        const l = LABELS[s.channel] || LABELS['web'];
        $('#sim-channel-badge').html('<i class="' + l.icon + ' me-1"></i>' + l.name);
        $('#sim-identity').text((s.customer_name || 'Cliente') + ' · #' + s.id);

        $('#sim-thread').empty().append(
            $('<div id="sim-typing-indicator" class="sim-typing d-none">').html(
                'Agente escribiendo<span></span><span></span><span></span>'
            )
        );
        $('#sim-resolved-banner').addClass('d-none');
        $('#sim-inject-panel').addClass('d-none');
        $('#sim-inject-msg').val('');
        $('#sim-scroll-badge').addClass('d-none');
        $('#sim-message').prop('disabled', s.is_closed).css('height', 'auto');
        $('#sim-send-btn').prop('disabled', s.is_closed);
        $('#sim-file-btn, #sim-audio-btn').prop('disabled', s.is_closed);
        $('#sim-conn').removeClass('live');

        $('#sim-setup').addClass('d-none');
        $('#sim-chat')
            .removeClass('sim-channel-whatsapp sim-channel-facebook sim-channel-instagram sim-channel-web')
            .addClass('sim-channel-' + s.channel)
            .removeClass('d-none');
        closeEmojiPicker();

        if (s.is_closed) {
            renderSystemMessage('✅ Esta conversación está resuelta');
            $('#sim-resolved-banner').removeClass('d-none');
        }

        $.ajax({
            url:    S.base + '/' + s.id + '/messages',
            method: 'GET',
            data:   { token: s.token, after_id: 0 },
        }).done(function (res) {
            if (res.ok && res.messages) { res.messages.forEach(renderMessage); }
        });

        subscribeRealtime();
        startPolling();

        renderSessions(allSessions);
    }

    function loadSessions() {
        $.ajax({
            url:    S.base + '/sessions',
            method: 'GET',
        }).done(function (res) {
            if (!res.ok || !res.data) return;
            allSessions = res.data;
            if (allSessions.length) {
                $('#sim-sessions-panel').removeClass('d-none');
                renderSessions(allSessions);
            }
        });
    }

    $('#sim-sessions-toggle-btn').on('click', function () {
        const $grid  = $('#sim-sessions-grid, #sim-sessions-more');
        const hidden = $grid.first().hasClass('d-none');
        $grid.toggleClass('d-none', !hidden);
        $(this).text(hidden ? 'Ocultar' : 'Mostrar');
    });

    $('#sim-sessions-show-all').on('click', function () {
        showAllSessions = true;
        renderSessions(allSessions);
    });

    $('#sim-sessions-search').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        const filtered = q
            ? allSessions.filter(function (s) {
                return (s.customer_name || '').toLowerCase().indexOf(q) !== -1
                    || (s.last_message || '').toLowerCase().indexOf(q) !== -1
                    || String(s.id).indexOf(q) !== -1;
            })
            : allSessions;
        renderSessions(filtered);
    });

    // URL-param resume (enlace compartido)
    (function () {
        const urlParams  = new URLSearchParams(window.location.search);
        const urlConvId  = parseInt(urlParams.get('conv'), 10);
        const urlToken   = urlParams.get('token');

        if (urlConvId && urlToken) {
            history.replaceState({}, '', window.location.pathname);
            $.ajax({ url: S.base + '/sessions', method: 'GET' }).done(function (res) {
                allSessions = (res.ok && res.data) ? res.data : [];
                if (allSessions.length) {
                    $('#sim-sessions-panel').removeClass('d-none');
                    renderSessions(allSessions);
                }
                const found = allSessions.find(function (s) { return s.id === urlConvId; });
                resumeSession(found || { id: urlConvId, token: urlToken, channel: 'web', customer_name: 'Conversación compartida', is_closed: false });
            }).fail(function () {
                resumeSession({ id: urlConvId, token: urlToken, channel: 'web', customer_name: 'Conversación compartida', is_closed: false });
            });
        } else {
            loadSessions();
        }
    }());
})();

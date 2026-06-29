/**
 * mc-conv-compose — composer for C11 thread footer (C13 / W6).
 *
 * Multiline textarea with auto-grow + channel-aware char counter, channel-
 * switcher chip row, quote-reply inset, Cmd/Ctrl+Enter to send, draft
 * persistence in localStorage per conversation.
 *
 * Public API:
 *   window.McConvCompose.clearDraft(rootEl)
 *   window.McConvCompose.setQuote(rootEl, { messageUid, body, author })
 *   window.McConvCompose.focus(rootEl)
 *
 * CustomEvents (dispatched from root):
 *   mc-conv-compose:send         { body, channel, conversationUid, replyToUid?, draftId? }
 *   mc-conv-compose:channel-change  { fromChannel, toChannel }
 *   mc-conv-compose:emoji-open      {}
 *   mc-conv-compose:media-open      {}
 *   mc-conv-compose:draft-save      { body, conversationUid }    (debounced 500ms)
 *
 * Channel caps (chars):
 *   sms      = sum of segments (delegated to C6 mc-segment-counter when
 *              consumer wires it up; default cap here = 1600 = 10 segments GSM-7)
 *   whatsapp = 1024
 *   telegram = 4096
 *   email    = no cap (consumer responsibility)
 *
 * Spec: docs/messenger/COMPONENT-PROPOSALS.md#c13--mc-conv-compose
 */
(function () {
    'use strict';

    if (window.McConvCompose && window.McConvCompose.__bound) return;

    var CHANNEL_CAPS = {
        sms: 1600,
        whatsapp: 1024,
        telegram: 4096,
        email: 0, // no cap
    };

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
    function dispatch(target, type, detail) {
        target.dispatchEvent(new CustomEvent(type, { detail: detail || {}, bubbles: true }));
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function autoGrow(ta) {
        ta.style.height = 'auto';
        var maxH = 192;
        ta.style.height = Math.min(ta.scrollHeight, maxH) + 'px';
    }

    function bindCompose(root) {
        if (root.__mcConvComposeBound) return;
        root.__mcConvComposeBound = true;

        var textarea = $('.mc-conv-compose__textarea', root);
        if (!textarea) return;

        var counter = $('.mc-conv-compose__counter', root);
        var sendBtn = $('.mc-conv-compose__send', root);
        var convUid = root.getAttribute('data-conversation-uid') || '';
        var draftKey = convUid ? 'messenger.compose.' + convUid : null;

        // Load draft if present
        if (draftKey && !textarea.value) {
            try {
                var saved = localStorage.getItem(draftKey);
                if (saved) textarea.value = saved;
            } catch (e) { /* no-op */ }
        }

        // Initial counter + button state
        updateCounter(root);
        updateSendDisabled(root);
        autoGrow(textarea);

        var saveDraft = debounce(function () {
            if (draftKey) {
                try { localStorage.setItem(draftKey, textarea.value); } catch (e) {}
                dispatch(root, 'mc-conv-compose:draft-save', { body: textarea.value, conversationUid: convUid });
            }
        }, 500);

        textarea.addEventListener('input', function () {
            autoGrow(textarea);
            updateCounter(root);
            updateSendDisabled(root);
            saveDraft();
        });

        // Cmd/Ctrl+Enter to send; Shift+Enter newline; Enter newline unless data-enter-sends="1"
        textarea.addEventListener('keydown', function (e) {
            var isMod = e.metaKey || e.ctrlKey;
            if (e.key === 'Enter') {
                if (isMod) {
                    e.preventDefault();
                    submitMessage(root);
                    return;
                }
                if (e.shiftKey) return; // newline
                if (root.getAttribute('data-enter-sends') === '1') {
                    e.preventDefault();
                    submitMessage(root);
                    return;
                }
                // Otherwise allow newline (default)
            }
            if (e.key === 'Escape' && root.getAttribute('data-quote-message-uid')) {
                clearQuote(root);
            }
        });

        // Channel switcher
        $$('.mc-conv-compose__channel-chip', root).forEach(function (chip) {
            chip.addEventListener('click', function () {
                var to = chip.getAttribute('data-value');
                var from = root.getAttribute('data-active-channel') || to;
                if (to === from) return;
                $$('.mc-conv-compose__channel-chip', root).forEach(function (c) { c.setAttribute('aria-pressed', 'false'); });
                chip.setAttribute('aria-pressed', 'true');
                root.setAttribute('data-active-channel', to);
                updatePlaceholder(root, to);
                updateCounter(root);
                updateSendDisabled(root);
                dispatch(root, 'mc-conv-compose:channel-change', { fromChannel: from, toChannel: to });
            });
        });

        // Emoji + media buttons (placeholder)
        var emojiBtn = $('.mc-conv-compose__icon-btn[data-mc-action="emoji"]', root);
        if (emojiBtn) emojiBtn.addEventListener('click', function () { dispatch(root, 'mc-conv-compose:emoji-open', {}); });
        var mediaBtn = $('.mc-conv-compose__icon-btn[data-mc-action="media"]', root);
        if (mediaBtn) mediaBtn.addEventListener('click', function () { dispatch(root, 'mc-conv-compose:media-open', {}); });

        // Send button
        if (sendBtn) {
            sendBtn.addEventListener('click', function () { submitMessage(root); });
        }

        // Quote-clear button
        var clearBtn = $('.mc-conv-compose__quote-clear', root);
        if (clearBtn) {
            clearBtn.addEventListener('click', function () { clearQuote(root); });
        }

        // Listen for bubble quote-reply events bubbling up to a shared ancestor
        // (typically the SF6 page wraps both C11 thread + C13 compose). We
        // listen on the compose's containing form/section.
        var sharedAncestor = root.closest('[data-mc-conv-pane]') || document;
        sharedAncestor.addEventListener('mc-conv-bubble:quote-reply', function (e) {
            var d = e.detail || {};
            setQuote(root, { messageUid: d.messageUid, body: d.body, author: d.displayName });
            // Focus textarea so the user can type the reply immediately
            focus(root);
        });
    }

    function updatePlaceholder(root, channel) {
        var textarea = $('.mc-conv-compose__textarea', root);
        if (!textarea) return;
        var label = ({
            sms: 'SMS', whatsapp: 'WhatsApp', telegram: 'Telegram', email: 'email',
        })[channel] || channel;
        textarea.placeholder = 'Reply via ' + label + '…';
    }

    function updateCounter(root) {
        var textarea = $('.mc-conv-compose__textarea', root);
        var counter  = $('.mc-conv-compose__counter', root);
        var warning  = $('.mc-conv-compose__warning', root);
        if (!textarea) return;
        var channel = root.getAttribute('data-active-channel') || 'sms';
        var cap = CHANNEL_CAPS[channel] || 0;
        var len = textarea.value.length;
        if (counter) {
            if (cap > 0) {
                counter.textContent = len + '/' + cap;
                counter.classList.toggle('mc-conv-compose__counter--over', len > cap);
            } else {
                counter.textContent = len + ' chars';
                counter.classList.remove('mc-conv-compose__counter--over');
            }
        }
        if (warning) {
            if (cap > 0 && len > cap) {
                warning.textContent = 'Too long for ' + channel + ' — max ' + cap + ' chars';
                warning.style.display = '';
            } else {
                warning.style.display = 'none';
            }
        }
    }

    function updateSendDisabled(root) {
        var textarea = $('.mc-conv-compose__textarea', root);
        var sendBtn  = $('.mc-conv-compose__send', root);
        if (!textarea || !sendBtn) return;
        var channel = root.getAttribute('data-active-channel') || 'sms';
        var cap = CHANNEL_CAPS[channel] || 0;
        var len = textarea.value.length;
        var empty = textarea.value.trim().length === 0;
        var over = cap > 0 && len > cap;
        sendBtn.disabled = empty || over;
    }

    function submitMessage(root) {
        var textarea = $('.mc-conv-compose__textarea', root);
        if (!textarea) return;
        var body = textarea.value;
        if (!body.trim()) return;
        var channel = root.getAttribute('data-active-channel') || 'sms';
        var convUid = root.getAttribute('data-conversation-uid') || '';
        var cap = CHANNEL_CAPS[channel] || 0;
        if (cap > 0 && body.length > cap) return;
        var replyToUid = root.getAttribute('data-quote-message-uid') || null;
        dispatch(root, 'mc-conv-compose:send', {
            body: body,
            channel: channel,
            conversationUid: convUid,
            replyToUid: replyToUid,
            draftId: convUid ? 'draft-' + convUid : null,
        });
        // Optimistic clear (consumer can re-fill on failure via setText)
        textarea.value = '';
        autoGrow(textarea);
        updateCounter(root);
        updateSendDisabled(root);
        if (replyToUid) clearQuote(root);
        clearDraft(root);
    }

    function setQuote(root, q) {
        if (!q || !q.messageUid) return;
        root.setAttribute('data-quote-message-uid', q.messageUid);
        var quoteAuthor = $('.mc-conv-compose__quote-author', root);
        var quoteBody   = $('.mc-conv-compose__quote-body', root);
        if (quoteAuthor) quoteAuthor.textContent = q.author || 'Replying to';
        if (quoteBody)   quoteBody.textContent   = (q.body || '').slice(0, 200);
    }

    function clearQuote(root) {
        root.setAttribute('data-quote-message-uid', '');
        var quoteAuthor = $('.mc-conv-compose__quote-author', root);
        var quoteBody   = $('.mc-conv-compose__quote-body', root);
        if (quoteAuthor) quoteAuthor.textContent = '';
        if (quoteBody)   quoteBody.textContent   = '';
    }

    function clearDraft(root) {
        var convUid = root.getAttribute('data-conversation-uid') || '';
        if (!convUid) return;
        try { localStorage.removeItem('messenger.compose.' + convUid); } catch (e) {}
    }

    function focus(root) {
        var textarea = $('.mc-conv-compose__textarea', root);
        if (textarea) textarea.focus();
    }

    function bindAll(scope) {
        var composers = (scope || document).querySelectorAll('.mc-conv-compose');
        for (var i = 0; i < composers.length; i++) bindCompose(composers[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindAll(); });
    } else {
        bindAll();
    }

    window.McConvCompose = {
        __bound: true,
        bind: bindAll,
        bindOne: bindCompose,
        clearDraft: clearDraft,
        setQuote: setQuote,
        clearQuote: clearQuote,
        focus: focus,
    };
}());

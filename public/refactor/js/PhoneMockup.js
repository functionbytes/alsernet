/**
 * mc-phone-mockup — Live message-preview phone frame (SMS / WhatsApp / Telegram).
 *
 * Two modes:
 *   1. STATIC — author hand-writes the bubble content via slot HTML. JS only
 *      drives optional channel-tinted theming + timestamp auto-formatting.
 *   2. LIVE-BOUND — mockup binds to a sibling `<textarea>` via
 *      `data-bind-to="#composer-id"` and re-renders the bubble body on every
 *      input event. Powers the S4.3/S4.4/S4.5 composer right-rail preview.
 *
 * Public attributes (declarative API):
 *   - data-mc-phone-mockup           — mount marker (REQUIRED)
 *   - data-channel="sms|whatsapp|telegram"  — drives header chrome + bubble tone
 *   - data-bind-to="#composer-id"    — live-bind to a textarea element
 *   - data-sender-name="Acme Co"     — shown in header (channel-specific format)
 *   - data-empty-text="Your message…" — placeholder shown when bound textarea is empty
 *   - data-size="sm|default|lg"      — frame size variant
 *
 * Per-channel header chrome rendered automatically:
 *   - SMS: contact name + "Text Message" subhead, no avatar (iOS Messages pattern)
 *   - WhatsApp: avatar dot + business name + "online" subhead + call/video icons
 *   - Telegram: avatar dot + bot name + "bot" subhead + back arrow
 *
 * Per-channel bubble chrome:
 *   - SMS: gray-tinted left-aligned bubble (incoming) — represents the sender
 *     side; outbound mockup is also supported via data-direction="outbound"
 *   - WhatsApp: green-tinted right-aligned bubble (outgoing) with delivery
 *     checkmarks (default: read/double-blue)
 *   - Telegram: blue-tinted right-aligned bubble (outgoing) with optional
 *     inline keyboard area below
 *
 * Idempotent — re-mounting an already-bound element is a no-op.
 */
(function () {
    'use strict';

    if (window.McPhoneMockup && window.McPhoneMockup.__bound) {
        return;
    }

    function escapeHtml(s) {
        return (s || '').replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]);
        });
    }

    function nowTimestamp() {
        var d = new Date();
        var h = d.getHours();
        var m = d.getMinutes();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + (m < 10 ? '0' + m : m) + ' ' + ampm;
    }

    function buildHeader(channel, senderName) {
        var name = escapeHtml(senderName || (channel === 'sms' ? '+1 555 0100' : channel === 'whatsapp' ? 'Acme Co' : 'AcmeBot'));
        if (channel === 'sms') {
            return (
                '<div class="mc-phone-mockup__chat-header">' +
                    '<div class="mc-phone-mockup__chat-header-back" aria-hidden="true">' +
                        '<svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '</div>' +
                    '<div class="mc-phone-mockup__chat-header-id">' +
                        '<div class="mc-phone-mockup__chat-header-name">' + name + '</div>' +
                        '<div class="mc-phone-mockup__chat-header-sub">Text Message · SMS</div>' +
                    '</div>' +
                '</div>'
            );
        }
        if (channel === 'whatsapp') {
            return (
                '<div class="mc-phone-mockup__chat-header">' +
                    '<div class="mc-phone-mockup__chat-header-back" aria-hidden="true">' +
                        '<svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '</div>' +
                    '<div class="mc-phone-mockup__chat-header-avatar mc-phone-mockup__chat-header-avatar--wa" aria-hidden="true">' + (name.substring(0, 1).toUpperCase()) + '</div>' +
                    '<div class="mc-phone-mockup__chat-header-id">' +
                        '<div class="mc-phone-mockup__chat-header-name">' + name + '</div>' +
                        '<div class="mc-phone-mockup__chat-header-sub">online</div>' +
                    '</div>' +
                    '<div class="mc-phone-mockup__chat-header-icons" aria-hidden="true">' +
                        '<svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M23 7l-7 5 7 5V7z M14 4H3a2 2 0 00-2 2v12a2 2 0 002 2h11a2 2 0 002-2V6a2 2 0 00-2-2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                        '<svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.97.36 1.92.7 2.84a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.92.34 1.87.57 2.84.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '</div>' +
                '</div>'
            );
        }
        // telegram
        return (
            '<div class="mc-phone-mockup__chat-header">' +
                '<div class="mc-phone-mockup__chat-header-back" aria-hidden="true">' +
                    '<svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                '</div>' +
                '<div class="mc-phone-mockup__chat-header-avatar mc-phone-mockup__chat-header-avatar--tg" aria-hidden="true">' + (name.substring(0, 1).toUpperCase()) + '</div>' +
                '<div class="mc-phone-mockup__chat-header-id">' +
                    '<div class="mc-phone-mockup__chat-header-name">' + name + '</div>' +
                    '<div class="mc-phone-mockup__chat-header-sub">bot</div>' +
                '</div>' +
            '</div>'
        );
    }

    function buildBubble(channel, bodyHtml, isEmpty, buttons) {
        var directionCls = channel === 'sms' ? 'mc-phone-mockup__bubble--inbound' : 'mc-phone-mockup__bubble--outbound';
        var meta = '';
        if (channel === 'whatsapp') {
            meta = '<span class="mc-phone-mockup__bubble-time">' + nowTimestamp() + '</span>' +
                   '<span class="mc-phone-mockup__bubble-ticks" aria-label="Read">' +
                       '<svg viewBox="0 0 18 18" width="14" height="14" fill="none"><path d="M3 9l3 3 7-8 M7 9l3 3 7-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                   '</span>';
        } else if (channel === 'sms') {
            meta = '<span class="mc-phone-mockup__bubble-time mc-phone-mockup__bubble-time--side">' + nowTimestamp() + '</span>';
        } else {
            // telegram
            meta = '<span class="mc-phone-mockup__bubble-time">' + nowTimestamp() + '</span>';
        }

        var emptyCls = isEmpty ? ' mc-phone-mockup__bubble--empty' : '';
        var inner = '<div class="mc-phone-mockup__bubble-body">' + bodyHtml + '</div>' +
                    '<div class="mc-phone-mockup__bubble-meta">' + meta + '</div>';

        var bubbleHtml = '<div class="mc-phone-mockup__bubble ' + directionCls + emptyCls + '" data-channel="' + channel + '">' + inner + '</div>';

        // Inline keyboard for Telegram
        if (channel === 'telegram' && buttons && buttons.length) {
            var keyboardRows = '';
            buttons.forEach(function (b) {
                keyboardRows += '<button type="button" class="mc-phone-mockup__keyboard-btn">' + escapeHtml(b.label || b) + '</button>';
            });
            bubbleHtml += '<div class="mc-phone-mockup__keyboard" aria-hidden="true">' + keyboardRows + '</div>';
        }
        return bubbleHtml;
    }

    function renderBody(text) {
        if (!text) return '<span class="mc-phone-mockup__bubble-empty-text" data-mc-phone-mockup-empty>Your message…</span>';
        // Newlines → <br>
        return escapeHtml(text).replace(/\r?\n/g, '<br>');
    }

    function render(mockup) {
        var channel = (mockup.getAttribute('data-channel') || 'sms').toLowerCase();
        var sender = mockup.getAttribute('data-sender-name');
        var emptyText = mockup.getAttribute('data-empty-text') || 'Your message…';
        var size = mockup.getAttribute('data-size') || 'default';

        // Resolve body source
        var bound = mockup.getAttribute('data-bind-to');
        var inlineSlot = mockup.querySelector('[data-mc-phone-mockup-slot]');
        var inlineButtons = mockup.querySelectorAll('[data-mc-phone-mockup-button]');
        var bodyText = '';
        var isEmpty = true;
        if (bound) {
            var input = document.querySelector(bound);
            if (input) {
                bodyText = input.value || '';
                isEmpty = bodyText === '';
            }
        } else if (inlineSlot) {
            bodyText = inlineSlot.textContent || '';
            isEmpty = bodyText.trim() === '';
        }

        var bodyHtml = isEmpty
            ? '<span class="mc-phone-mockup__bubble-empty-text">' + escapeHtml(emptyText) + '</span>'
            : escapeHtml(bodyText).replace(/\r?\n/g, '<br>');

        var buttons = [];
        inlineButtons.forEach(function (btn) {
            buttons.push({ label: btn.textContent.trim() });
        });

        mockup.classList.add('mc-phone-mockup');
        mockup.setAttribute('data-size', size);
        // Compose frame
        var frame = mockup.querySelector('.mc-phone-mockup__frame');
        if (!frame) {
            // First mount — build full frame
            mockup.innerHTML =
                '<div class="mc-phone-mockup__frame">' +
                    '<div class="mc-phone-mockup__notch" aria-hidden="true"></div>' +
                    '<div class="mc-phone-mockup__screen" data-screen>' +
                        buildHeader(channel, sender) +
                        '<div class="mc-phone-mockup__chat-area" data-chat-area>' +
                            buildBubble(channel, bodyHtml, isEmpty, buttons) +
                        '</div>' +
                    '</div>' +
                    '<div class="mc-phone-mockup__home-indicator" aria-hidden="true"></div>' +
                '</div>';
        } else {
            // Re-render only the chat area to avoid header flicker
            var chatArea = frame.querySelector('[data-chat-area]');
            chatArea.innerHTML = buildBubble(channel, bodyHtml, isEmpty, buttons);
            // Update header if sender attr changed
            var hdr = frame.querySelector('.mc-phone-mockup__chat-header');
            if (hdr) {
                hdr.outerHTML = buildHeader(channel, sender);
            }
        }
    }

    function mount(mockup) {
        if (!mockup || mockup.__mcPhoneMockupBound) return;
        mockup.__mcPhoneMockupBound = true;
        render(mockup);

        var bound = mockup.getAttribute('data-bind-to');
        if (bound) {
            var input = document.querySelector(bound);
            if (input) {
                var handler = function () { render(mockup); };
                input.addEventListener('input', handler);
                input.addEventListener('change', handler);
            }
        }
    }

    function mountAll(root) {
        var scope = root || document;
        var mockups = scope.querySelectorAll('[data-mc-phone-mockup]:not([data-mc-phone-mockup-init])');
        mockups.forEach(function (m) {
            m.setAttribute('data-mc-phone-mockup-init', '1');
            mount(m);
        });
    }

    window.McPhoneMockup = {
        __bound: true,
        mount: mount,
        mountAll: mountAll,
        render: render,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { mountAll(); });
    } else {
        mountAll();
    }
})();

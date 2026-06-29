/**
 * mc-conv-bubble — chat bubble JS bindings (C12 / W6).
 *
 * Pure-CSS static bubble works without JS (SSR + initial paint). This file
 * wires the optional interactive bits:
 *   - Click on `.mc-conv-bubble__menu` (kebab) — opens a tiny floating menu
 *     with Copy text / Quote-reply / Mark unread actions. Emits CustomEvents
 *     up the DOM so the parent thread (C11) / page can handle them:
 *       • mc-conv-bubble:quote-reply  { messageUid, body, displayName, channel }
 *       • mc-conv-bubble:mark-unread  { messageUid }
 *       • mc-conv-bubble:retry        { messageUid }  (from failed retry CTA)
 *   - Click on `.mc-conv-bubble__quote` — emits scroll-to-quoted event:
 *       • mc-conv-bubble:scroll-to    { messageUid }
 *   - Image-media click — opens lightbox via existing host PreviewModal.
 *
 * Idempotent — re-mounting an already-bound bubble is a no-op.
 *
 * Spec: docs/messenger/COMPONENT-PROPOSALS.md#c12--mc-conv-bubble
 */
(function () {
    'use strict';

    if (window.McConvBubble && window.McConvBubble.__bound) {
        return;
    }

    function $(sel, root) { return (root || document).querySelector(sel); }

    function dispatch(target, type, detail) {
        target.dispatchEvent(new CustomEvent(type, { detail: detail, bubbles: true }));
    }

    function bindBubble(bubble) {
        if (bubble.__mcConvBubbleBound) return;
        bubble.__mcConvBubbleBound = true;

        var messageUid = bubble.getAttribute('data-message-uid') || '';
        var channel = bubble.getAttribute('data-channel') || '';
        var direction = bubble.classList.contains('mc-conv-bubble--out') ? 'outbound' : 'inbound';

        // Kebab menu
        var menu = $('.mc-conv-bubble__menu', bubble);
        if (menu) {
            menu.addEventListener('click', function (e) {
                e.stopPropagation();
                openMenu(bubble, menu, messageUid, channel, direction);
            });
        }

        // Quote-jump
        var quote = $('.mc-conv-bubble__quote', bubble);
        if (quote && quote.dataset.repliedTo) {
            quote.addEventListener('click', function () {
                dispatch(bubble, 'mc-conv-bubble:scroll-to', {
                    messageUid: quote.dataset.repliedTo,
                });
            });
            quote.setAttribute('tabindex', '0');
            quote.setAttribute('role', 'button');
            quote.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    quote.click();
                }
            });
        }

        // Retry button (failed)
        var retry = $('.mc-conv-bubble__retry', bubble);
        if (retry) {
            retry.addEventListener('click', function (e) {
                e.preventDefault();
                dispatch(bubble, 'mc-conv-bubble:retry', { messageUid: messageUid });
            });
        }

        // Image lightbox (if host PreviewModal is available)
        var img = $('.mc-conv-bubble__media img', bubble);
        if (img && window.PreviewModal && typeof window.PreviewModal.open === 'function') {
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', function () {
                window.PreviewModal.open({
                    type: 'image',
                    src: img.src,
                    alt: img.alt || '',
                });
            });
        }
    }

    function openMenu(bubble, anchor, messageUid, channel, direction) {
        closeAllMenus();

        var menu = document.createElement('div');
        menu.className = 'mc-conv-bubble__floating-menu';
        menu.setAttribute('role', 'menu');
        menu.style.position = 'absolute';
        menu.style.zIndex = '1000';
        menu.style.background = 'var(--color-card-bg)';
        menu.style.border = '1px solid var(--color-border)';
        menu.style.borderRadius = 'var(--radius-card)';
        menu.style.boxShadow = 'var(--shadow-dropdown)';
        menu.style.padding = 'var(--space-1) 0';
        menu.style.minWidth = '160px';

        var items = [
            { label: 'Copy text', action: 'copy' },
            { label: 'Quote-reply', action: 'quote-reply' },
        ];
        if (direction === 'inbound') {
            items.push({ label: 'Mark unread', action: 'mark-unread' });
        }

        items.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'menuitem');
            btn.className = 'mc-conv-bubble__floating-menu-item';
            btn.textContent = item.label;
            btn.style.display = 'block';
            btn.style.width = '100%';
            btn.style.textAlign = 'left';
            btn.style.padding = 'var(--space-2) var(--space-3)';
            btn.style.border = '0';
            btn.style.background = 'transparent';
            btn.style.color = 'var(--color-text)';
            btn.style.fontSize = 'var(--text-sm)';
            btn.style.cursor = 'pointer';
            btn.addEventListener('mouseenter', function () { btn.style.background = 'var(--color-hover-bg)'; });
            btn.addEventListener('mouseleave', function () { btn.style.background = 'transparent'; });
            btn.addEventListener('click', function () {
                handleMenuAction(bubble, item.action, messageUid, channel);
                closeMenu(menu);
            });
            menu.appendChild(btn);
        });

        // Position below anchor
        var rect = anchor.getBoundingClientRect();
        var scrollY = window.scrollY || document.documentElement.scrollTop;
        var scrollX = window.scrollX || document.documentElement.scrollLeft;
        menu.style.top = (rect.bottom + scrollY + 4) + 'px';
        menu.style.left = (rect.left + scrollX) + 'px';

        document.body.appendChild(menu);

        // Close on outside click + ESC
        function onDocClick(e) {
            if (!menu.contains(e.target)) closeMenu(menu);
        }
        function onKey(e) {
            if (e.key === 'Escape') closeMenu(menu);
        }
        setTimeout(function () {
            document.addEventListener('click', onDocClick);
            document.addEventListener('keydown', onKey);
        }, 0);

        menu.__cleanup = function () {
            document.removeEventListener('click', onDocClick);
            document.removeEventListener('keydown', onKey);
        };
    }

    function closeMenu(menu) {
        if (!menu) return;
        if (typeof menu.__cleanup === 'function') menu.__cleanup();
        if (menu.parentNode) menu.parentNode.removeChild(menu);
    }

    function closeAllMenus() {
        var menus = document.querySelectorAll('.mc-conv-bubble__floating-menu');
        for (var i = 0; i < menus.length; i++) closeMenu(menus[i]);
    }

    function handleMenuAction(bubble, action, messageUid, channel) {
        var bodyEl = $('.mc-conv-bubble__body', bubble);
        var body = bodyEl ? bodyEl.textContent.trim() : '';
        var displayName = bubble.getAttribute('data-display-name') || '';

        switch (action) {
            case 'copy':
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(body).catch(function () {});
                }
                break;
            case 'quote-reply':
                dispatch(bubble, 'mc-conv-bubble:quote-reply', {
                    messageUid: messageUid,
                    body: body,
                    displayName: displayName,
                    channel: channel,
                });
                break;
            case 'mark-unread':
                dispatch(bubble, 'mc-conv-bubble:mark-unread', { messageUid: messageUid });
                break;
        }
    }

    function bindAll(root) {
        var bubbles = (root || document).querySelectorAll('.mc-conv-bubble');
        for (var i = 0; i < bubbles.length; i++) bindBubble(bubbles[i]);
    }

    // Initial pass
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindAll(); });
    } else {
        bindAll();
    }

    // Observe new bubbles added later (SSE inbound, infinite scroll)
    if (window.MutationObserver) {
        var mo = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var nodes = mutations[i].addedNodes;
                for (var j = 0; j < nodes.length; j++) {
                    var n = nodes[j];
                    if (n.nodeType !== 1) continue;
                    if (n.classList && n.classList.contains('mc-conv-bubble')) {
                        bindBubble(n);
                    } else {
                        bindAll(n);
                    }
                }
            }
        });
        mo.observe(document.body, { childList: true, subtree: true });
    }

    window.McConvBubble = {
        __bound: true,
        bind: bindAll,
        bindOne: bindBubble,
        closeAllMenus: closeAllMenus,
    };
}());

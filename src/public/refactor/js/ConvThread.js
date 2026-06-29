/**
 * mc-conv-thread — center pane of SF6 master-detail unified inbox (C11 / W6).
 *
 * Composes C12 mc-conv-bubble into a scrollable thread with sticky header,
 * day dividers, infinite-scroll-up history fetch, read watermark via
 * IntersectionObserver, "new messages" floater when scrolled up + inbound
 * arrives, and a back-arrow for mobile single-pane mode.
 *
 * Public API:
 *   window.McConvThread.scrollToMessage(rootEl, messageUid)
 *   window.McConvThread.appendBubble(rootEl, bubbleHtmlOrElement)
 *   window.McConvThread.prependBubbles(rootEl, bubblesHtml)
 *   window.McConvThread.markRead(rootEl, messageUids[])
 *
 * CustomEvents (dispatched from thread root):
 *   mc-conv-thread:load-older     { beforeMessageUid }
 *   mc-conv-thread:read           { messageUid }
 *   mc-conv-thread:status-change  { fromStatus, toStatus, conversationUid }
 *   mc-conv-thread:back-to-list   {}
 *   mc-conv-thread:reopen         {}
 *   mc-conv-thread:retry-fetch    {}
 *
 * Day-divider semantics: groups bubbles by their data-sent-at calendar day
 * in the viewer's local timezone. Inserts a <li> divider with relative label
 * ("Today" / "Yesterday" / weekday name same-week / "May 12" / "May 12, 2025").
 * Re-computed on bubble add/prepend.
 *
 * Spec: docs/messenger/COMPONENT-PROPOSALS.md#c11--mc-conv-thread
 */
(function () {
    'use strict';

    if (window.McConvThread && window.McConvThread.__bound) return;

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

    function dispatch(target, type, detail) {
        target.dispatchEvent(new CustomEvent(type, { detail: detail || {}, bubbles: true }));
    }

    // Day-label computation. Returns "Today" / "Yesterday" / weekday / "Mon DD" /
    // "Mon DD, YYYY". Anchor = today at local midnight.
    function dayLabel(date) {
        var now = new Date();
        var d0  = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        var t0  = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var diffMs = t0 - d0;
        var diffDays = Math.round(diffMs / 86400000);
        if (diffDays === 0) return 'Today';
        if (diffDays === 1) return 'Yesterday';
        if (diffDays > 1 && diffDays <= 6) {
            return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][date.getDay()];
        }
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var sameYear = date.getFullYear() === now.getFullYear();
        if (sameYear) return months[date.getMonth()] + ' ' + date.getDate();
        return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
    }

    function dayKey(date) {
        return date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
    }

    function ensureDayDividers(listEl) {
        if (!listEl) return;
        // Remove existing dividers
        $$('.mc-conv-thread__day-divider', listEl).forEach(function (d) { d.remove(); });
        var bubbles = $$('.mc-conv-bubble', listEl);
        var prevKey = null;
        bubbles.forEach(function (bubble) {
            var dt = readDate(bubble);
            if (!dt) return;
            var k = dayKey(dt);
            if (k !== prevKey) {
                var divider = document.createElement('li');
                divider.className = 'mc-conv-thread__day-divider';
                divider.setAttribute('role', 'separator');
                var time = document.createElement('time');
                time.setAttribute('datetime', dt.toISOString().slice(0, 10));
                time.textContent = dayLabel(dt);
                divider.appendChild(time);
                bubble.parentNode.insertBefore(divider, bubble);
                prevKey = k;
            }
        });
    }

    function readDate(bubble) {
        var t = bubble.querySelector('time');
        if (t && t.getAttribute('datetime')) {
            var d = new Date(t.getAttribute('datetime'));
            if (!isNaN(d.getTime())) return d;
        }
        var attr = bubble.getAttribute('data-sent-at');
        if (attr) {
            var d2 = new Date(attr);
            if (!isNaN(d2.getTime())) return d2;
        }
        return null;
    }

    function nearBottom(scrollEl, threshold) {
        return scrollEl.scrollHeight - scrollEl.scrollTop - scrollEl.clientHeight <= (threshold || 80);
    }

    function bindThread(thread) {
        if (thread.__mcConvThreadBound) return;
        thread.__mcConvThreadBound = true;

        var listEl = $('.mc-conv-thread__list', thread);
        if (!listEl) return;

        ensureDayDividers(listEl);

        // Initial scroll-to-bottom (no smooth animation) on first mount
        if (thread.dataset.state === 'loaded' || !thread.dataset.state) {
            requestAnimationFrame(function () {
                listEl.scrollTop = listEl.scrollHeight;
            });
        }

        // Infinite-scroll-up trigger
        listEl.addEventListener('scroll', function () {
            if (listEl.scrollTop < 80 && thread.dataset.hasOlder === '1' && !listEl.__loadingOlder) {
                var firstBubble = $('.mc-conv-bubble', listEl);
                if (!firstBubble) return;
                listEl.__loadingOlder = true;
                injectLoadingOlder(listEl);
                dispatch(thread, 'mc-conv-thread:load-older', {
                    beforeMessageUid: firstBubble.getAttribute('data-message-uid'),
                });
            }
            updateNewFloater(thread, listEl);
        });

        // "New N messages" floater click
        var floater = $('.mc-conv-thread__new-floater', thread);
        if (floater) {
            floater.addEventListener('click', function () {
                listEl.scrollTo({ top: listEl.scrollHeight, behavior: 'smooth' });
            });
        }

        // Back arrow (mobile)
        var back = $('.mc-conv-thread__back', thread);
        if (back) {
            back.addEventListener('click', function () {
                dispatch(thread, 'mc-conv-thread:back-to-list', {});
            });
        }

        // Reopen CTA (closed banner)
        var reopenBtn = $('.mc-conv-thread__readonly-banner-cta', thread);
        if (reopenBtn) {
            reopenBtn.addEventListener('click', function () {
                dispatch(thread, 'mc-conv-thread:reopen', {});
            });
        }

        // Error retry
        var retryBtn = $('.mc-conv-thread__error-retry', thread);
        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                dispatch(thread, 'mc-conv-thread:retry-fetch', {});
            });
        }

        // Read-watermark via IntersectionObserver
        if (window.IntersectionObserver) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var bubble = entry.target;
                    if (bubble.dataset.read === '1') return;
                    if (!bubble.classList.contains('mc-conv-bubble--in')) return;
                    var uid = bubble.getAttribute('data-message-uid');
                    if (!uid) return;
                    // Debounce per-bubble: 800ms in-viewport before firing
                    setTimeout(function () {
                        if (entry.isIntersecting) {
                            bubble.dataset.read = '1';
                            dispatch(thread, 'mc-conv-thread:read', { messageUid: uid });
                            observer.unobserve(bubble);
                        }
                    }, 800);
                });
            }, { root: listEl, threshold: 0.5 });

            $$('.mc-conv-bubble--in', listEl).forEach(function (b) {
                if (b.dataset.read !== '1') observer.observe(b);
            });

            thread.__mcConvThreadObserver = observer;
        }

        // Cmd+K shortcut — focus compose textarea
        document.addEventListener('keydown', function (e) {
            var isMod = e.metaKey || e.ctrlKey;
            if (!isMod) return;
            if (e.key !== 'k' && e.key !== 'K') return;
            var compose = thread.parentNode && thread.parentNode.querySelector('[data-mc-conv-compose]');
            if (!compose) {
                // Fallback: textarea inside the thread's footer
                compose = $('.mc-conv-thread__footer textarea', thread);
            }
            if (compose) {
                e.preventDefault();
                var ta = compose.tagName === 'TEXTAREA' ? compose : compose.querySelector('textarea');
                if (ta) ta.focus();
            }
        });
    }

    function injectLoadingOlder(listEl) {
        if ($('.mc-conv-thread__loading-older', listEl)) return;
        var row = document.createElement('li');
        row.className = 'mc-conv-thread__loading-older';
        row.textContent = 'Loading earlier messages…';
        listEl.insertBefore(row, listEl.firstChild);
    }

    function updateNewFloater(thread, listEl) {
        var floater = $('.mc-conv-thread__new-floater', thread);
        if (!floater) return;
        if (nearBottom(listEl)) {
            floater.classList.remove('mc-conv-thread__new-floater--visible');
            floater.dataset.count = '0';
        }
    }

    function appendBubble(thread, bubbleHtmlOrEl) {
        var listEl = $('.mc-conv-thread__list', thread);
        if (!listEl) return null;
        var bubble;
        if (typeof bubbleHtmlOrEl === 'string') {
            var tmp = document.createElement('template');
            tmp.innerHTML = bubbleHtmlOrEl.trim();
            bubble = tmp.content.firstElementChild;
        } else {
            bubble = bubbleHtmlOrEl;
        }
        if (!bubble) return null;
        var atBottom = nearBottom(listEl);
        listEl.appendChild(bubble);
        ensureDayDividers(listEl);
        if (atBottom) {
            listEl.scrollTo({ top: listEl.scrollHeight, behavior: 'smooth' });
        } else {
            var floater = $('.mc-conv-thread__new-floater', thread);
            if (floater) {
                var n = parseInt(floater.dataset.count || '0', 10) + 1;
                floater.dataset.count = n;
                var label = floater.querySelector('.mc-conv-thread__new-floater-label');
                if (label) label.textContent = n + ' new message' + (n === 1 ? '' : 's');
                floater.classList.add('mc-conv-thread__new-floater--visible');
            }
        }
        if (window.McConvBubble && typeof window.McConvBubble.bindOne === 'function') {
            window.McConvBubble.bindOne(bubble);
        }
        if (thread.__mcConvThreadObserver && bubble.classList.contains('mc-conv-bubble--in')) {
            thread.__mcConvThreadObserver.observe(bubble);
        }
        return bubble;
    }

    function prependBubbles(thread, bubblesHtml) {
        var listEl = $('.mc-conv-thread__list', thread);
        if (!listEl) return;
        var loadingRow = $('.mc-conv-thread__loading-older', listEl);
        if (loadingRow) loadingRow.remove();
        // Capture anchor element + its current top to preserve scroll position
        var anchor = $('.mc-conv-bubble', listEl);
        var anchorTop = anchor ? anchor.getBoundingClientRect().top : 0;
        var tmp = document.createElement('template');
        tmp.innerHTML = bubblesHtml.trim();
        var nodes = Array.prototype.slice.call(tmp.content.children);
        nodes.reverse().forEach(function (n) { listEl.insertBefore(n, listEl.firstChild); });
        ensureDayDividers(listEl);
        if (anchor) {
            var newTop = anchor.getBoundingClientRect().top;
            listEl.scrollTop += (newTop - anchorTop);
        }
        listEl.__loadingOlder = false;
        if (window.McConvBubble && typeof window.McConvBubble.bind === 'function') {
            window.McConvBubble.bind(listEl);
        }
        if (thread.__mcConvThreadObserver) {
            nodes.forEach(function (b) {
                if (b.classList && b.classList.contains('mc-conv-bubble--in')) {
                    thread.__mcConvThreadObserver.observe(b);
                }
            });
        }
    }

    function scrollToMessage(thread, messageUid) {
        var listEl = $('.mc-conv-thread__list', thread);
        if (!listEl) return;
        var bubble = listEl.querySelector('[data-message-uid="' + messageUid + '"]');
        if (!bubble) return;
        bubble.scrollIntoView({ block: 'center', behavior: 'smooth' });
        bubble.classList.add('mc-conv-bubble--highlight');
        setTimeout(function () { bubble.classList.remove('mc-conv-bubble--highlight'); }, 800);
    }

    function markRead(thread, messageUids) {
        var listEl = $('.mc-conv-thread__list', thread);
        if (!listEl) return;
        (messageUids || []).forEach(function (uid) {
            var b = listEl.querySelector('[data-message-uid="' + uid + '"]');
            if (b) b.dataset.read = '1';
        });
    }

    function bindAll(root) {
        var threads = (root || document).querySelectorAll('.mc-conv-thread');
        for (var i = 0; i < threads.length; i++) bindThread(threads[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindAll(); });
    } else {
        bindAll();
    }

    window.McConvThread = {
        __bound: true,
        bind: bindAll,
        bindOne: bindThread,
        scrollToMessage: scrollToMessage,
        appendBubble: appendBubble,
        prependBubbles: prependBubbles,
        markRead: markRead,
        dayLabel: dayLabel,
    };
}());

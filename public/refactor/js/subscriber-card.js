// Subscriber business-card popover.
// Triggered by hovering any element with:
//   [data-subscriber-card-list-uid] + [data-subscriber-card-id]
// Lazy-fetches /rui/lists/{listUid}/subscribers/{id}/card on first hover,
// caches DOM, positions fixed near the trigger, hides on mouseleave with
// a small delay so the user can move into the popover (CTA button click).
(function () {
    var popover = document.createElement('div');
    popover.className = 'mc-subscriber-card-popover';
    document.body.appendChild(popover);

    var cache = {};   // key = `${listUid}:${id}` → HTML
    var hideTimer = null;
    var currentKey = null;

    function position(triggerEl) {
        var r = triggerEl.getBoundingClientRect();
        var w = popover.offsetWidth || 280;
        var h = popover.offsetHeight || 240;
        var gap = 10;

        // Default: place to the LEFT of the trigger so the email anchor and
        // any neighboring rows stay readable. Fall back to RIGHT if there's
        // not enough room on the left edge.
        var left = r.left - w - gap;
        if (left < 8) {
            left = r.right + gap;
            if (left + w > window.innerWidth - 8) {
                // Both sides too tight — clamp to viewport right edge.
                left = window.innerWidth - w - 8;
            }
        }

        // Vertically center on the trigger; clamp to viewport.
        var top = r.top + (r.height / 2) - (h / 2);
        if (top < 8) top = 8;
        if (top + h > window.innerHeight - 8) top = window.innerHeight - h - 8;

        popover.style.left = left + 'px';
        popover.style.top = top + 'px';
    }

    function show(triggerEl, html) {
        popover.innerHTML = html;
        popover.classList.add('active');
        position(triggerEl);
    }

    function hide() {
        popover.classList.remove('active');
        currentKey = null;
    }

    function scheduleHide() {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(hide, 180);
    }

    function cancelHide() {
        clearTimeout(hideTimer);
    }

    document.addEventListener('mouseover', function (e) {
        var trigger = e.target.closest('[data-subscriber-card-list-uid][data-subscriber-card-id]');
        if (!trigger) return;
        var listUid = trigger.getAttribute('data-subscriber-card-list-uid');
        var id      = trigger.getAttribute('data-subscriber-card-id');
        if (!listUid || !id) return;

        cancelHide();
        var key = listUid + ':' + id;
        if (currentKey === key && popover.classList.contains('active')) return;
        currentKey = key;

        if (cache[key]) {
            show(trigger, cache[key]);
            return;
        }

        var url = '/rui/lists/' + encodeURIComponent(listUid) + '/subscribers/' + encodeURIComponent(id) + '/card';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.text() : null; })
            .then(function (html) {
                if (!html) return;
                cache[key] = html;
                if (currentKey === key) show(trigger, html);
            })
            .catch(function () { /* network failure — silently skip */ });
    });

    document.addEventListener('mouseout', function (e) {
        var trigger = e.target.closest('[data-subscriber-card-list-uid][data-subscriber-card-id]');
        if (!trigger) return;
        var to = e.relatedTarget;
        if (to && (popover.contains(to) || trigger.contains(to))) return;
        scheduleHide();
    });

    popover.addEventListener('mouseenter', cancelHide);
    popover.addEventListener('mouseleave', scheduleHide);
    window.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
})();

// Click-to-expand for truncated bounce / feedback messages.
// Markup contract: <a class="cr-fullmsg" data-fullmsg-title="..." data-fullmsg-content="...">excerpt</a>
// Falls back to a minimal native modal if McDialog isn't loaded yet (defensive
// — Dialog.js loads after this file in app.blade.php so the alert call is safe).
(function () {
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-fullmsg-content]');
        if (!trigger) return;
        e.preventDefault();
        var title = trigger.getAttribute('data-fullmsg-title') || 'Full message';
        var content = trigger.getAttribute('data-fullmsg-content') || '';
        if (window.McDialog && typeof window.McDialog.alert === 'function') {
            window.McDialog.alert({ title: title, message: content, type: 'info', okText: 'Close' });
        } else {
            alert(title + '\n\n' + content);
        }
    });
})();

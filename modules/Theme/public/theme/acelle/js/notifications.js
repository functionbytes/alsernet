/**
 * Notifications client — bell dropdown + per-item actions.
 *
 * Same script powers admin and customer notification surfaces. Each bell
 * stores its endpoint URLs in data-* attributes; per-item DOM stores the
 * notification id; this file just wires event delegation.
 *
 * Public API (window.McNotifications):
 *   readAll(routePrefix)      — POST mark-all-read for the given prefix
 *                               ('refactor.admin.notifications' / 'refactor.account.notifications').
 *   refreshBadge()            — reload every bell's badge from /badge.
 */
(function () {
    'use strict';

    var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body || '',
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        });
    }

    // -------- Bell dropdown lazy-load ---------------------------------------
    document.addEventListener('click', function (e) {
        var bell = e.target.closest('[data-mc-notification-bell]');
        if (!bell) return;

        var trigger = e.target.closest('[data-bell-trigger]');
        if (!trigger) return;

        var panel = bell.querySelector('[data-bell-panel]');
        if (!panel) return;

        // Lazy-load on first open. Subsequent opens reuse cached HTML;
        // refresh happens on action (read/dismiss) or manual reload.
        if (panel.dataset.loaded !== '1') {
            fetch(bell.dataset.dropdownUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.text(); })
              .then(function (html) {
                  panel.innerHTML = html;
                  panel.dataset.loaded = '1';
              })
              .catch(function (err) { console.error('Notification dropdown load failed', err); });
        }
    });

    // -------- Per-item actions (event delegation) ---------------------------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-mc-notification-action]');
        if (!btn) return;
        e.preventDefault();

        var item = btn.closest('[data-mc-notification-item]');
        if (!item) return;

        var bell = item.closest('[data-mc-notification-bell], [data-mc-notifications-list]');
        if (!bell) return;

        var id      = item.dataset.id;
        var action  = btn.dataset.mcNotificationAction; // read | unread | dismiss
        var template = bell.dataset['route' + capitalize(action)];
        if (!template) {
            console.warn('No route template for action', action, 'on', bell);
            return;
        }
        var url = template.replace('__ID__', encodeURIComponent(id));

        postJson(url).then(function () {
            if (action === 'dismiss') {
                item.classList.add('is-dismissed');
                item.style.transition = 'opacity 200ms';
                item.style.opacity = '0';
                setTimeout(function () { item.remove(); }, 220);
            } else if (action === 'read') {
                item.classList.remove('is-unread');
                swapButtonAction(btn, 'unread');
            } else if (action === 'unread') {
                item.classList.add('is-unread');
                swapButtonAction(btn, 'read');
            }
            McNotifications.refreshBadge();
            document.dispatchEvent(new CustomEvent('list:reload'));
        }).catch(function (err) { console.error('Notification action failed', err); });
    });

    // -------- CTA click — mark read before navigating -----------------------
    document.addEventListener('click', function (e) {
        var cta = e.target.closest('[data-mc-notification-cta]');
        if (!cta) return;

        var item = cta.closest('[data-mc-notification-item]');
        var bell = cta.closest('[data-mc-notification-bell], [data-mc-notifications-list]');
        if (!item || !bell) return;

        var url = (bell.dataset.routeRead || '').replace('__ID__', encodeURIComponent(item.dataset.id));
        if (!url) return;

        // Fire-and-forget; navigator.sendBeacon survives the page nav.
        var fd = new FormData();
        fd.append('_token', CSRF);
        navigator.sendBeacon(url, fd);
    });

    // -------- Read all ------------------------------------------------------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-readall-trigger]');
        if (!btn) return;
        e.preventDefault();

        var bell = btn.closest('[data-mc-notification-bell], [data-mc-notifications-list]');
        var url  = bell ? bell.dataset.readallUrl : null;
        if (!url) return;

        postJson(url).then(function () {
            // Reset visual unread state in this view
            (bell || document).querySelectorAll('.mc-notification-item.is-unread')
                .forEach(function (el) { el.classList.remove('is-unread'); });
            McNotifications.refreshBadge();
            document.dispatchEvent(new CustomEvent('list:reload'));
        });
    });

    // -------- Public API ----------------------------------------------------
    var McNotifications = {
        readAll: function (routePrefix) {
            var bell = document.querySelector('[data-mc-notification-bell][data-readall-url*="' + routePrefix.replace(/\./g, '/').replace('refactor/', '') + '"]')
                    || document.querySelector('[data-mc-notification-bell]');
            if (!bell) return Promise.resolve();
            return postJson(bell.dataset.readallUrl);
        },

        refreshBadge: function () {
            document.querySelectorAll('[data-mc-notification-bell]').forEach(function (bell) {
                var url = bell.dataset.badgeUrl;
                if (!url) return;
                fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        var badge = bell.querySelector('[data-badge]');
                        if (!badge) return;
                        var n = j.unread || 0;
                        if (n === 0) {
                            badge.setAttribute('hidden', '');
                            badge.textContent = '0';
                        } else {
                            badge.removeAttribute('hidden');
                            badge.textContent = n > 99 ? '99+' : String(n);
                        }
                    });
            });
        },
    };
    window.McNotifications = McNotifications;

    function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    function swapButtonAction(btn, newAction) {
        // Icon mirrors current row state (closed envelope = unread, open = read).
        // newAction is the next action ("now that it's read, click to mark unread"),
        // so the icon flips to the OPPOSITE: read state shows `drafts`, unread shows
        // `mark_email_unread`.
        btn.dataset.mcNotificationAction = newAction;
        var icon = btn.querySelector('.material-symbols-rounded');
        if (!icon) return;
        icon.textContent = newAction === 'unread' ? 'drafts' : 'mark_email_unread';
    }
})();

/**
 * mc-channel-priority-ladder — ordered, reorderable channel priority list (C24 / W7).
 *
 * Mounts on any `<div data-mc-channel-priority-ladder>` and renders the campaign's
 * channel ladder for S7.2 cross-channel wizard step 1 (and S7.6 read-only review).
 * Each row shows position number, channel icon + label, health pill, and ↑/↓/× action
 * chiclets. Reorder is KEYBOARD-FIRST (L53 — no drag-only); ↑/↓ buttons move the row
 * AND keep focus on it. Remove is gated by data-min-channels (default 2). Add is
 * driven by data-available-channels.
 *
 * Data attributes (declarative API):
 *   - data-mc-channel-priority-ladder  — mount marker (REQUIRED)
 *   - data-channels                    — CSV ordered list of channel keys (default "")
 *                                          supported: email | sms | whatsapp | telegram
 *   - data-available-channels          — CSV of channels eligible to add (default all 4)
 *   - data-credential-health           — JSON map {channel: 'ok'|'warning'|'error'}
 *   - data-min-channels                — integer, default 2 (block remove below this)
 *   - data-readonly                    — "1" | "0" — when 1, no interactive controls
 *
 * Emits (all bubble from root):
 *   - mc-channel-priority-ladder:reorder  detail: { channels: [...new order] }
 *   - mc-channel-priority-ladder:add      detail: { channel }
 *   - mc-channel-priority-ladder:remove   detail: { channel }
 *
 * Public read state on the root element:
 *   root.dataset.mcChannelPriorityLadderChannels = "email,sms,whatsapp,telegram"
 *
 * Public helpers (window.McChannelPriorityLadder):
 *   - mount(root)
 *   - mountAll(scope?)
 *   - getChannels(root): string[]
 *   - setChannels(root, channels): void
 *   - setHealth(root, channel, status): void
 *
 * Idempotent — re-mounting an already-bound element is a no-op.
 */
(function () {
    'use strict';

    if (window.McChannelPriorityLadder && window.McChannelPriorityLadder.__bound) {
        return;
    }

    var ALL_CHANNELS = ['email', 'sms', 'whatsapp', 'telegram'];

    var CHANNEL_LABELS = {
        email:    'Email',
        sms:      'SMS',
        whatsapp: 'WhatsApp',
        telegram: 'Telegram',
    };

    // Inline SVG glyph per channel — kept small + monochrome (uses currentColor so the
    // ladder row's --tone-ink token paints it). Each glyph is 16×16.
    var CHANNEL_GLYPHS = {
        email:    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>',
        sms:      '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        whatsapp: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 3.45z"/><path d="M9 10.5c.5 1.5 1.5 2.5 3 3 .8.3 1.5 0 1.8-.5l.5-.8c.2-.3.6-.4.9-.2l1.5.8c.3.2.5.6.4.9-.3 1-1 1.8-2 2-3 .5-6-1.5-7-4.5-.2-1 .3-2 1.2-2.4.3-.1.7 0 .9.3l.8 1.5c.2.3.1.7-.2.9z"/></svg>',
        telegram: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 4L2.5 11l6.5 2 2 6.5L21 4z"/><path d="M9 13l8.5-7"/></svg>',
    };

    function escapeHtml(s) {
        return (s == null ? '' : String(s)).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]);
        });
    }

    function parseCsv(s) {
        return (s || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
    }

    function parseHealth(root) {
        var raw = root.getAttribute('data-credential-health');
        if (!raw) return {};
        try {
            var obj = JSON.parse(raw);
            return (obj && typeof obj === 'object') ? obj : {};
        } catch (e) {
            // eslint-disable-next-line no-console
            console.warn('McChannelPriorityLadder: data-credential-health is not valid JSON', e);
            return {};
        }
    }

    function minChannels(root) {
        var n = parseInt(root.getAttribute('data-min-channels') || '2', 10);
        return isFinite(n) && n > 0 ? n : 2;
    }

    function isReadonly(root) {
        return root.getAttribute('data-readonly') === '1';
    }

    function availableChannels(root) {
        var raw = root.getAttribute('data-available-channels');
        return raw == null ? ALL_CHANNELS.slice() : parseCsv(raw);
    }

    // Health label + sr-only text for the pill.
    var HEALTH_LABELS = {
        ok:      { short: 'Connected',  cls: 'mc-channel-priority-ladder__health--ok' },
        warning: { short: 'Warning',    cls: 'mc-channel-priority-ladder__health--warning' },
        error:   { short: 'Disconnected', cls: 'mc-channel-priority-ladder__health--error' },
    };

    function buildHealthPill(status) {
        var meta = HEALTH_LABELS[status] || HEALTH_LABELS.ok;
        var dotSvg;
        if (status === 'error') {
            dotSvg = '<svg viewBox="0 0 16 16" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="4" y1="4" x2="12" y2="12"/><line x1="12" y1="4" x2="4" y2="12"/></svg>';
        } else if (status === 'warning') {
            dotSvg = '<svg viewBox="0 0 16 16" width="10" height="10" fill="currentColor" aria-hidden="true"><path d="M8 1.5 L15 14 L1 14 Z" stroke="currentColor" stroke-width="0.5" stroke-linejoin="round"/></svg>';
        } else {
            dotSvg = '<svg viewBox="0 0 16 16" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3,8 7,12 13,4"/></svg>';
        }
        return (
            '<span class="mc-channel-priority-ladder__health ' + meta.cls + '" role="status">' +
                dotSvg +
                '<span class="mc-channel-priority-ladder__health-label">' + escapeHtml(meta.short) + '</span>' +
            '</span>'
        );
    }

    function buildRowHtml(channel, idx, total, healthStatus, readonly, atMin) {
        var label = CHANNEL_LABELS[channel] || channel;
        var glyph = CHANNEL_GLYPHS[channel] || '';
        var pos = idx + 1;

        var upDisabled = idx === 0 ? ' disabled aria-disabled="true"' : '';
        var downDisabled = idx === total - 1 ? ' disabled aria-disabled="true"' : '';
        var removeDisabled = atMin ? ' disabled aria-disabled="true"' : '';
        var removeTitle = atMin ? ' title="Cross-channel requires at least ' + minChannelsTooltip(total, atMin) + ' channels."' : '';

        var actions = readonly ? '' : (
            '<div class="mc-channel-priority-ladder__actions" role="group" aria-label="Reorder or remove ' + escapeHtml(label) + '">' +
                '<button type="button" class="mc-channel-priority-ladder__action" data-action="move-up" aria-label="Move ' + escapeHtml(label) + ' up"' + upDisabled + '>' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>' +
                '</button>' +
                '<button type="button" class="mc-channel-priority-ladder__action" data-action="move-down" aria-label="Move ' + escapeHtml(label) + ' down"' + downDisabled + '>' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>' +
                '</button>' +
                '<button type="button" class="mc-channel-priority-ladder__action mc-channel-priority-ladder__action--danger" data-action="remove" aria-label="Remove ' + escapeHtml(label) + ' from ladder"' + removeDisabled + removeTitle + '>' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                '</button>' +
            '</div>'
        );

        var connectCta = (healthStatus === 'error' && !readonly) ? (
            '<a href="#" class="mc-channel-priority-ladder__reconnect" data-action="reconnect" data-channel="' + escapeHtml(channel) + '">Connect first</a>'
        ) : (healthStatus === 'warning' && !readonly) ? (
            '<a href="#" class="mc-channel-priority-ladder__reconnect" data-action="reconnect" data-channel="' + escapeHtml(channel) + '">Reconnect</a>'
        ) : '';

        return (
            '<li class="mc-channel-priority-ladder__row" data-channel="' + escapeHtml(channel) + '" data-position="' + pos + '" data-health="' + escapeHtml(healthStatus) + '">' +
                '<span class="mc-channel-priority-ladder__position" aria-hidden="true">' + pos + '</span>' +
                '<span class="mc-channel-priority-ladder__icon" aria-hidden="true">' + glyph + '</span>' +
                '<span class="mc-channel-priority-ladder__label">' + escapeHtml(label) + '</span>' +
                buildHealthPill(healthStatus) +
                connectCta +
                actions +
            '</li>'
        );
    }

    function minChannelsTooltip(total, atMin) {
        // total is current size; if atMin and removing would drop below the floor,
        // surface the floor value. We re-derive via the root in caller — kept simple here.
        return atMin ? (total) : (total - 1);
    }

    function buildAddCta(rows, available, readonly) {
        if (readonly) return '';
        var addable = available.filter(function (c) { return rows.indexOf(c) === -1; });
        if (addable.length === 0) {
            return (
                '<button type="button" class="mc-channel-priority-ladder__add" data-action="open-add-menu" disabled aria-disabled="true" title="Every channel already in ladder">' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>' +
                    '<span>Add channel</span>' +
                '</button>'
            );
        }
        var menuItems = addable.map(function (ch) {
            var label = CHANNEL_LABELS[ch] || ch;
            var glyph = CHANNEL_GLYPHS[ch] || '';
            return (
                '<button type="button" class="mc-channel-priority-ladder__add-option" data-action="add" data-channel="' + escapeHtml(ch) + '">' +
                    '<span class="mc-channel-priority-ladder__add-option-icon" data-channel-tone="' + escapeHtml(ch) + '">' + glyph + '</span>' +
                    '<span>' + escapeHtml(label) + '</span>' +
                '</button>'
            );
        }).join('');

        return (
            '<div class="mc-channel-priority-ladder__add-wrap">' +
                '<button type="button" class="mc-channel-priority-ladder__add" data-action="open-add-menu" aria-haspopup="true" aria-expanded="false">' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>' +
                    '<span>Add channel</span>' +
                    '<svg class="mc-channel-priority-ladder__add-chev" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>' +
                '</button>' +
                '<div class="mc-channel-priority-ladder__add-menu" hidden role="menu">' + menuItems + '</div>' +
            '</div>'
        );
    }

    function render(root) {
        var rows = root.__mcCplChannels;
        var health = root.__mcCplHealth;
        var avail = availableChannels(root);
        var readonly = isReadonly(root);
        var floor = minChannels(root);
        var atMin = rows.length <= floor;

        var rowsHtml = rows.map(function (ch, i) {
            return buildRowHtml(ch, i, rows.length, (health[ch] || 'ok'), readonly, atMin);
        }).join('');

        root.innerHTML =
            '<ol class="mc-channel-priority-ladder__list" role="list">' + rowsHtml + '</ol>' +
            buildAddCta(rows, avail, readonly);

        root.dataset.mcChannelPriorityLadderChannels = rows.join(',');
        root.classList.toggle('mc-channel-priority-ladder--readonly', readonly);
    }

    function fireReorder(root) {
        root.dispatchEvent(new CustomEvent('mc-channel-priority-ladder:reorder', {
            bubbles: true,
            detail: { channels: root.__mcCplChannels.slice() },
        }));
    }

    function focusRowAction(root, channel, action) {
        var sel = '.mc-channel-priority-ladder__row[data-channel="' + channel + '"] [data-action="' + action + '"]';
        var el = root.querySelector(sel);
        if (el && !el.disabled) {
            el.focus();
            return;
        }
        // Fallback — focus any enabled action on the row, otherwise the row itself.
        var row = root.querySelector('.mc-channel-priority-ladder__row[data-channel="' + channel + '"]');
        if (row) {
            var fallback = row.querySelector('[data-action]:not([disabled])');
            if (fallback) fallback.focus();
        }
    }

    function closeAddMenu(root) {
        var menu = root.querySelector('.mc-channel-priority-ladder__add-menu');
        var btn = root.querySelector('[data-action="open-add-menu"]');
        if (menu) menu.setAttribute('hidden', '');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    function openAddMenu(root) {
        var menu = root.querySelector('.mc-channel-priority-ladder__add-menu');
        var btn = root.querySelector('[data-action="open-add-menu"]');
        if (!menu || !btn) return;
        menu.removeAttribute('hidden');
        btn.setAttribute('aria-expanded', 'true');
        var firstItem = menu.querySelector('[data-action="add"]');
        if (firstItem) firstItem.focus();
    }

    function mount(root) {
        if (!root || root.__mcCplBound) return;
        root.__mcCplBound = true;

        root.__mcCplChannels = parseCsv(root.getAttribute('data-channels'));
        root.__mcCplHealth = parseHealth(root);

        // Strip any channels that aren't recognised — keep behaviour predictable.
        root.__mcCplChannels = root.__mcCplChannels.filter(function (c) {
            return ALL_CHANNELS.indexOf(c) !== -1;
        });

        root.classList.add('mc-channel-priority-ladder');
        render(root);

        root.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('[data-action]');
            if (!btn || btn.disabled) return;
            e.preventDefault();
            var action = btn.getAttribute('data-action');

            if (action === 'open-add-menu') {
                var menu = root.querySelector('.mc-channel-priority-ladder__add-menu');
                var isOpen = menu && !menu.hasAttribute('hidden');
                if (isOpen) closeAddMenu(root); else openAddMenu(root);
                return;
            }

            if (action === 'add') {
                var newCh = btn.getAttribute('data-channel');
                if (!newCh || root.__mcCplChannels.indexOf(newCh) !== -1) return;
                root.__mcCplChannels.push(newCh);
                render(root);
                root.dispatchEvent(new CustomEvent('mc-channel-priority-ladder:add', {
                    bubbles: true,
                    detail: { channel: newCh },
                }));
                fireReorder(root);
                focusRowAction(root, newCh, 'move-up');
                return;
            }

            if (action === 'reconnect') {
                var rc = btn.getAttribute('data-channel');
                root.dispatchEvent(new CustomEvent('mc-channel-priority-ladder:reconnect', {
                    bubbles: true,
                    detail: { channel: rc },
                }));
                return;
            }

            var row = btn.closest('.mc-channel-priority-ladder__row');
            if (!row) return;
            var channel = row.getAttribute('data-channel');
            var idx = root.__mcCplChannels.indexOf(channel);
            if (idx === -1) return;

            if (action === 'remove') {
                if (root.__mcCplChannels.length <= minChannels(root)) return;
                root.__mcCplChannels.splice(idx, 1);
                render(root);
                root.dispatchEvent(new CustomEvent('mc-channel-priority-ladder:remove', {
                    bubbles: true,
                    detail: { channel: channel },
                }));
                fireReorder(root);
                return;
            }
            if (action === 'move-up' && idx > 0) {
                var prev = root.__mcCplChannels[idx - 1];
                root.__mcCplChannels[idx - 1] = channel;
                root.__mcCplChannels[idx] = prev;
                render(root);
                fireReorder(root);
                focusRowAction(root, channel, 'move-up');
                return;
            }
            if (action === 'move-down' && idx < root.__mcCplChannels.length - 1) {
                var next = root.__mcCplChannels[idx + 1];
                root.__mcCplChannels[idx + 1] = channel;
                root.__mcCplChannels[idx] = next;
                render(root);
                fireReorder(root);
                focusRowAction(root, channel, 'move-down');
                return;
            }
        });

        // Close add menu on outside click or Escape.
        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) closeAddMenu(root);
        });
        root.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAddMenu(root);
        });
    }

    function mountAll(scopeRoot) {
        var scope = scopeRoot || document;
        var roots = scope.querySelectorAll('[data-mc-channel-priority-ladder]:not([data-mc-cpl-init])');
        roots.forEach(function (r) {
            r.setAttribute('data-mc-cpl-init', '1');
            mount(r);
        });
    }

    function getChannels(root) {
        return (root && root.__mcCplChannels) ? root.__mcCplChannels.slice() : [];
    }

    function setChannels(root, channels) {
        if (!root || !Array.isArray(channels)) return;
        root.__mcCplChannels = channels.filter(function (c) { return ALL_CHANNELS.indexOf(c) !== -1; });
        render(root);
        fireReorder(root);
    }

    function setHealth(root, channel, status) {
        if (!root || !channel) return;
        if (!root.__mcCplHealth) root.__mcCplHealth = {};
        root.__mcCplHealth[channel] = status;
        render(root);
    }

    window.McChannelPriorityLadder = {
        __bound: true,
        mount: mount,
        mountAll: mountAll,
        getChannels: getChannels,
        setChannels: setChannels,
        setHealth: setHealth,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { mountAll(); });
    } else {
        mountAll();
    }
})();

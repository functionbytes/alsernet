/**
 * mc-conv-thread-list — left sidebar of SF6 master-detail inbox (C10 / W6).
 *
 * Vertical list of conversation rows (per messenger_conversations row) with
 * avatar / last-message-preview / channel chip / unread badge. Filter rail at
 * the top (status / channel chips / debounced search). Bulk-select mode.
 *
 * Public API:
 *   window.McConvThreadList.upsertRow(rootEl, conversationData)  // SSE bump-to-top
 *   window.McConvThreadList.markRead(rootEl, conversationUid)    // zero unread
 *   window.McConvThreadList.removeRow(rootEl, conversationUid)
 *
 * CustomEvents (dispatched from list root):
 *   mc-conv-thread-list:select         { conversationUid, channel, subscriberId }
 *   mc-conv-thread-list:load-more      { afterTimestamp }
 *   mc-conv-thread-list:filter-change  { status, channels, query }
 *   mc-conv-thread-list:bulk-action    { action, conversationUids }
 *   mc-conv-thread-list:search         { query }            (debounced 300ms)
 *
 * Spec: docs/messenger/COMPONENT-PROPOSALS.md#c10--mc-conv-thread-list
 */
(function () {
    'use strict';

    if (window.McConvThreadList && window.McConvThreadList.__bound) return;

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

    function bindList(root) {
        if (root.__mcConvThreadListBound) return;
        root.__mcConvThreadListBound = true;

        var rowsEl = $('.mc-conv-thread-list__rows', root);
        if (!rowsEl) return;

        // Row click → select
        rowsEl.addEventListener('click', function (e) {
            var row = e.target.closest('.mc-conv-thread-list__row');
            if (!row) return;
            // In bulk mode, click toggles checkbox; otherwise selects
            if (root.dataset.bulk === '1') {
                var cb = $('.mc-conv-thread-list__row-check', row);
                if (cb && e.target !== cb) {
                    cb.checked = !cb.checked;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                }
                updateBulkBar(root);
                return;
            }
            $$('.mc-conv-thread-list__row[aria-selected="true"]', rowsEl).forEach(function (r) {
                r.setAttribute('aria-selected', 'false');
            });
            row.setAttribute('aria-selected', 'true');
            root.dataset.activeConversationUid = row.getAttribute('data-conversation-uid') || '';
            // Zero unread badge in DOM
            var unreadEl = $('.mc-conv-thread-list__row-unread', row);
            if (unreadEl) unreadEl.remove();
            row.setAttribute('data-unread', '0');
            dispatch(root, 'mc-conv-thread-list:select', {
                conversationUid: row.getAttribute('data-conversation-uid'),
                channel: row.getAttribute('data-channel'),
                subscriberId: row.getAttribute('data-subscriber-id') || null,
            });
        });

        // Bulk checkbox change
        rowsEl.addEventListener('change', function (e) {
            if (e.target.matches('.mc-conv-thread-list__row-check')) {
                updateBulkBar(root);
            }
        });

        // Filter chips
        $$('.mc-conv-thread-list__chip', root).forEach(function (chip) {
            chip.addEventListener('click', function () {
                var group = chip.getAttribute('data-filter-group');
                if (group === 'status') {
                    $$('.mc-conv-thread-list__chip[data-filter-group="status"]', root).forEach(function (c) {
                        c.setAttribute('aria-pressed', 'false');
                    });
                    chip.setAttribute('aria-pressed', 'true');
                    root.dataset.filterStatus = chip.getAttribute('data-value') || 'open';
                } else if (group === 'channel') {
                    var pressed = chip.getAttribute('aria-pressed') === 'true';
                    chip.setAttribute('aria-pressed', pressed ? 'false' : 'true');
                    var current = (root.dataset.filterChannels || 'all').split(',').filter(Boolean);
                    var val = chip.getAttribute('data-value');
                    if (val === 'all') {
                        $$('.mc-conv-thread-list__chip[data-filter-group="channel"]', root).forEach(function (c) {
                            if (c !== chip) c.setAttribute('aria-pressed', 'false');
                        });
                        current = ['all'];
                    } else {
                        var idx = current.indexOf(val);
                        if (idx === -1 && !pressed) current.push(val);
                        if (idx >= 0 && pressed) current.splice(idx, 1);
                        // Remove 'all' if any specific channel was selected
                        var allIdx = current.indexOf('all');
                        if (allIdx >= 0) current.splice(allIdx, 1);
                        if (current.length === 0) current = ['all'];
                        // Sync "all" chip pressed state
                        var allChip = $('.mc-conv-thread-list__chip[data-filter-group="channel"][data-value="all"]', root);
                        if (allChip) allChip.setAttribute('aria-pressed', current[0] === 'all' ? 'true' : 'false');
                    }
                    root.dataset.filterChannels = current.join(',');
                }
                dispatch(root, 'mc-conv-thread-list:filter-change', {
                    status: root.dataset.filterStatus || 'open',
                    channels: (root.dataset.filterChannels || 'all').split(',').filter(Boolean),
                    query: root.dataset.searchQuery || '',
                });
            });
        });

        // Debounced search
        var searchInput = $('.mc-conv-thread-list__search', root);
        if (searchInput) {
            var emitSearch = debounce(function (q) {
                root.dataset.searchQuery = q;
                dispatch(root, 'mc-conv-thread-list:search', { query: q });
                dispatch(root, 'mc-conv-thread-list:filter-change', {
                    status: root.dataset.filterStatus || 'open',
                    channels: (root.dataset.filterChannels || 'all').split(',').filter(Boolean),
                    query: q,
                });
            }, 300);
            searchInput.addEventListener('input', function () { emitSearch(searchInput.value); });
        }

        // Clear filters CTA
        var clearBtn = $('.mc-conv-thread-list__clear-filters', root);
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                root.dataset.filterStatus = 'open';
                root.dataset.filterChannels = 'all';
                root.dataset.searchQuery = '';
                $$('.mc-conv-thread-list__chip[data-filter-group="status"]', root).forEach(function (c) {
                    c.setAttribute('aria-pressed', c.getAttribute('data-value') === 'open' ? 'true' : 'false');
                });
                $$('.mc-conv-thread-list__chip[data-filter-group="channel"]', root).forEach(function (c) {
                    c.setAttribute('aria-pressed', c.getAttribute('data-value') === 'all' ? 'true' : 'false');
                });
                if (searchInput) searchInput.value = '';
                dispatch(root, 'mc-conv-thread-list:filter-change', {
                    status: 'open', channels: ['all'], query: '',
                });
            });
        }

        // Bulk action buttons
        $$('.mc-conv-thread-list__bulk-btn', root).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.getAttribute('data-bulk-action');
                var uids = $$('.mc-conv-thread-list__row-check:checked', rowsEl).map(function (cb) {
                    return cb.closest('.mc-conv-thread-list__row').getAttribute('data-conversation-uid');
                });
                if (uids.length === 0) return;
                dispatch(root, 'mc-conv-thread-list:bulk-action', { action: action, conversationUids: uids });
            });
        });

        // Infinite scroll-down (load-more)
        rowsEl.addEventListener('scroll', function () {
            var nearBottom = rowsEl.scrollHeight - rowsEl.scrollTop - rowsEl.clientHeight <= 200;
            if (nearBottom && root.dataset.hasMore === '1' && !rowsEl.__loadingMore) {
                rowsEl.__loadingMore = true;
                var lastRow = rowsEl.querySelector('.mc-conv-thread-list__row:last-child');
                dispatch(root, 'mc-conv-thread-list:load-more', {
                    afterTimestamp: lastRow ? lastRow.getAttribute('data-last-message-at') : null,
                });
            }
        });
    }

    function updateBulkBar(root) {
        var bar = $('.mc-conv-thread-list__bulk-actions', root);
        if (!bar) return;
        var n = $$('.mc-conv-thread-list__row-check:checked', root).length;
        var countEl = $('.mc-conv-thread-list__bulk-count', root);
        if (countEl) countEl.textContent = n + ' selected';
        $$('.mc-conv-thread-list__bulk-btn', root).forEach(function (b) {
            b.disabled = n === 0;
            b.style.opacity = n === 0 ? '0.5' : '1';
        });
    }

    function upsertRow(root, data) {
        if (!data || !data.conversation_uid) return;
        var rowsEl = $('.mc-conv-thread-list__rows', root);
        if (!rowsEl) return;
        var existing = rowsEl.querySelector('[data-conversation-uid="' + data.conversation_uid + '"]');
        if (existing) {
            // Bump to top + update preview + timestamp + unread
            rowsEl.insertBefore(existing, rowsEl.firstChild);
            var preview = $('.mc-conv-thread-list__row-preview-body', existing);
            if (preview && data.last_message_preview != null) preview.textContent = data.last_message_preview;
            var time = $('.mc-conv-thread-list__row-time', existing);
            if (time && data.last_message_at_relative != null) time.textContent = data.last_message_at_relative;
            var isSelected = existing.getAttribute('aria-selected') === 'true';
            if (!isSelected && data.unread_count != null) {
                existing.setAttribute('data-unread', String(data.unread_count));
                var unreadEl = $('.mc-conv-thread-list__row-unread', existing);
                if (data.unread_count > 0) {
                    if (!unreadEl) {
                        unreadEl = document.createElement('span');
                        unreadEl.className = 'mc-conv-thread-list__row-unread';
                        var meta = $('.mc-conv-thread-list__row-meta', existing);
                        if (meta) meta.appendChild(unreadEl);
                    }
                    unreadEl.textContent = String(data.unread_count);
                } else if (unreadEl) {
                    unreadEl.remove();
                }
            }
        }
        // Note: if !existing, the consumer is responsible for rendering the row
        // HTML and prepending it (this method only updates known rows). Inserting
        // a new conversation requires server-side data, so the consumer wires
        // its SSE handler to fetch the row HTML then call insertNewRow().
    }

    function markRead(root, conversationUid) {
        var row = root.querySelector('[data-conversation-uid="' + conversationUid + '"]');
        if (!row) return;
        var unreadEl = $('.mc-conv-thread-list__row-unread', row);
        if (unreadEl) unreadEl.remove();
        row.setAttribute('data-unread', '0');
    }

    function removeRow(root, conversationUid) {
        var row = root.querySelector('[data-conversation-uid="' + conversationUid + '"]');
        if (row) row.remove();
    }

    function bindAll(root) {
        var lists = (root || document).querySelectorAll('.mc-conv-thread-list');
        for (var i = 0; i < lists.length; i++) bindList(lists[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindAll(); });
    } else {
        bindAll();
    }

    window.McConvThreadList = {
        __bound: true,
        bind: bindAll,
        bindOne: bindList,
        upsertRow: upsertRow,
        markRead: markRead,
        removeRow: removeRow,
    };
}());

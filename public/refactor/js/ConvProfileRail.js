/**
 * mc-conv-profile-rail — right 320px rail of SF6 master-detail (C14 / W6).
 *
 * 4 cards: Identity / Quick actions / Tags / Last 5 sends. Tag input
 * dispatches add/remove events. Quick-action buttons dispatch named events.
 * Send row click emits scroll-to-message (consumer wires C11.scrollToMessage).
 *
 * CustomEvents (dispatched from rail root):
 *   mc-conv-profile-rail:mark-unread         { conversationUid }
 *   mc-conv-profile-rail:mute                { conversationUid, isMuted }
 *   mc-conv-profile-rail:view-subscriber     { subscriberId }
 *   mc-conv-profile-rail:tag-add             { subscriberId, tagValue }
 *   mc-conv-profile-rail:tag-remove          { subscriberId, tagValue }
 *   mc-conv-profile-rail:scroll-to-message   { messageUid }
 *
 * Spec: docs/messenger/COMPONENT-PROPOSALS.md#c14--mc-conv-profile-rail
 */
(function () {
    'use strict';

    if (window.McConvProfileRail && window.McConvProfileRail.__bound) return;

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
    function dispatch(target, type, detail) {
        target.dispatchEvent(new CustomEvent(type, { detail: detail || {}, bubbles: true }));
    }

    function bindRail(root) {
        if (root.__mcConvProfileRailBound) return;
        root.__mcConvProfileRailBound = true;

        var convUid = root.getAttribute('data-conversation-uid') || '';
        var subscriberId = root.getAttribute('data-subscriber-id') || null;

        // Quick action buttons (Mark unread, Mute, View subscriber, View tracking history)
        $$('.mc-conv-profile-rail__action-btn', root).forEach(function (btn) {
            var action = btn.getAttribute('data-action');
            if (!action) return;
            btn.addEventListener('click', function (e) {
                if (btn.tagName === 'A') return; // links navigate naturally
                e.preventDefault();
                switch (action) {
                    case 'mark-unread':
                        dispatch(root, 'mc-conv-profile-rail:mark-unread', { conversationUid: convUid });
                        break;
                    case 'mute':
                        var isMuted = btn.getAttribute('aria-pressed') === 'true';
                        btn.setAttribute('aria-pressed', isMuted ? 'false' : 'true');
                        dispatch(root, 'mc-conv-profile-rail:mute', { conversationUid: convUid, isMuted: !isMuted });
                        break;
                    case 'view-subscriber':
                        dispatch(root, 'mc-conv-profile-rail:view-subscriber', { subscriberId: subscriberId });
                        break;
                }
            });
        });

        // Tag remove buttons
        $$('.mc-conv-profile-rail__tag-remove', root).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var chip = btn.closest('.mc-conv-profile-rail__tag');
                if (!chip) return;
                var tagValue = chip.getAttribute('data-tag-value') || '';
                chip.remove();
                dispatch(root, 'mc-conv-profile-rail:tag-remove', { subscriberId: subscriberId, tagValue: tagValue });
            });
        });

        // Tag input — Enter adds tag
        var tagInput = $('.mc-conv-profile-rail__tag-input', root);
        if (tagInput) {
            tagInput.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                var val = tagInput.value.trim();
                if (!val) return;
                var tagsContainer = $('.mc-conv-profile-rail__tags', root);
                if (tagsContainer && !$$('.mc-conv-profile-rail__tag', tagsContainer).some(function (c) { return c.getAttribute('data-tag-value') === val; })) {
                    var chip = document.createElement('span');
                    chip.className = 'mc-conv-profile-rail__tag';
                    chip.setAttribute('data-tag-value', val);
                    chip.innerHTML = val + ' <button type="button" class="mc-conv-profile-rail__tag-remove" aria-label="Remove tag"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="3" x2="13" y2="13"/><line x1="13" y1="3" x2="3" y2="13"/></svg></button>';
                    tagsContainer.appendChild(chip);
                    var removeBtn = $('.mc-conv-profile-rail__tag-remove', chip);
                    if (removeBtn) {
                        removeBtn.addEventListener('click', function () {
                            chip.remove();
                            dispatch(root, 'mc-conv-profile-rail:tag-remove', { subscriberId: subscriberId, tagValue: val });
                        });
                    }
                }
                tagInput.value = '';
                dispatch(root, 'mc-conv-profile-rail:tag-add', { subscriberId: subscriberId, tagValue: val });
            });
        }

        // Last-sends rows
        $$('.mc-conv-profile-rail__send', root).forEach(function (row) {
            row.addEventListener('click', function () {
                var messageUid = row.getAttribute('data-message-uid') || '';
                if (messageUid) {
                    dispatch(root, 'mc-conv-profile-rail:scroll-to-message', { messageUid: messageUid });
                }
            });
        });
    }

    function bindAll(scope) {
        var rails = (scope || document).querySelectorAll('.mc-conv-profile-rail');
        for (var i = 0; i < rails.length; i++) bindRail(rails[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindAll(); });
    } else {
        bindAll();
    }

    window.McConvProfileRail = {
        __bound: true,
        bind: bindAll,
        bindOne: bindRail,
    };
}());

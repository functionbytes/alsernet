/**
 * mc-fallback-strategy-card — illustrated radio card pair for W7 send strategy (C26).
 *
 * Used at S7.5 wizard step 4 to pick `send_and_stop` vs `send_to_all`. The card
 * group exposes WAI-ARIA `radiogroup` semantics; cards are role="radio" with
 * arrow-key navigation between them. Read-only mode (S7.6 review) hides the
 * unselected card entirely.
 *
 * Data attributes (declarative API):
 *   - data-mc-fallback-strategy-card  — mount marker on the group root (REQUIRED)
 *   - data-strategy                   — "send_and_stop" | "send_to_all"
 *   - data-readonly                   — "1" | "0" — read-only review mode
 *   - data-disabled                   — "1" | "0" — both cards disabled
 *
 * Emits (bubble):
 *   - mc-fallback-strategy-card:change   detail: { strategy: 'send_and_stop'|'send_to_all' }
 *
 * Public read state:
 *   root.dataset.mcFallbackStrategyCardStrategy = "send_and_stop"
 *
 * Idempotent.
 */
(function () {
    'use strict';

    if (window.McFallbackStrategyCard && window.McFallbackStrategyCard.__bound) {
        return;
    }

    var STRATEGY_LABELS = {
        send_and_stop: 'Send and stop',
        send_to_all:   'Send to all',
    };

    var STRATEGY_BLURBS = {
        send_and_stop: 'First eligible channel wins per subscriber. Lower spend, lower noise — the ladder decides.',
        send_to_all:   'Every eligible channel fires for every subscriber. Highest reach, highest spend — for must-receive announcements.',
    };

    function isReadonly(root) {
        return root.getAttribute('data-readonly') === '1';
    }

    function isDisabled(root) {
        return root.getAttribute('data-disabled') === '1';
    }

    function currentStrategy(root) {
        var s = root.getAttribute('data-strategy');
        return (s === 'send_to_all') ? 'send_to_all' : 'send_and_stop';
    }

    function selectStrategy(root, strategy) {
        if (isDisabled(root) || isReadonly(root)) return;
        if (strategy !== 'send_and_stop' && strategy !== 'send_to_all') return;

        var prev = currentStrategy(root);
        if (prev === strategy) return;

        root.setAttribute('data-strategy', strategy);
        root.dataset.mcFallbackStrategyCardStrategy = strategy;

        // Update DOM
        var cards = root.querySelectorAll('.mc-fallback-strategy-card');
        cards.forEach(function (card) {
            var cardStrategy = card.getAttribute('data-strategy');
            var isSelected = cardStrategy === strategy;
            card.setAttribute('aria-checked', isSelected ? 'true' : 'false');
            card.classList.toggle('mc-fallback-strategy-card--selected', isSelected);
            card.setAttribute('tabindex', isSelected ? '0' : '-1');
            var input = card.querySelector('input[type="radio"]');
            if (input) input.checked = isSelected;
        });

        root.dispatchEvent(new CustomEvent('mc-fallback-strategy-card:change', {
            bubbles: true,
            detail: { strategy: strategy },
        }));
    }

    function focusCard(root, strategy) {
        var card = root.querySelector('.mc-fallback-strategy-card[data-strategy="' + strategy + '"]');
        if (card) card.focus();
    }

    function mount(root) {
        if (!root || root.__mcFscBound) return;
        root.__mcFscBound = true;

        // Apply initial state to DOM (in case server-rendered HTML doesn't match data-strategy).
        var initial = currentStrategy(root);
        root.dataset.mcFallbackStrategyCardStrategy = initial;
        var cards = root.querySelectorAll('.mc-fallback-strategy-card');
        cards.forEach(function (card) {
            var cardStrategy = card.getAttribute('data-strategy');
            var isSelected = cardStrategy === initial;
            card.setAttribute('role', 'radio');
            card.setAttribute('aria-checked', isSelected ? 'true' : 'false');
            card.classList.toggle('mc-fallback-strategy-card--selected', isSelected);
            if (!card.hasAttribute('tabindex')) {
                card.setAttribute('tabindex', isSelected ? '0' : '-1');
            }
        });
        if (!root.hasAttribute('role')) root.setAttribute('role', 'radiogroup');

        root.addEventListener('click', function (e) {
            var card = e.target.closest && e.target.closest('.mc-fallback-strategy-card');
            if (!card) return;
            if (card.hasAttribute('data-disabled') || isDisabled(root) || isReadonly(root)) return;
            e.preventDefault();
            var s = card.getAttribute('data-strategy');
            selectStrategy(root, s);
            focusCard(root, s);
        });

        root.addEventListener('keydown', function (e) {
            var card = e.target.closest && e.target.closest('.mc-fallback-strategy-card');
            if (!card) return;
            if (isDisabled(root) || isReadonly(root)) return;

            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                var s = card.getAttribute('data-strategy');
                selectStrategy(root, s);
                focusCard(root, s);
                return;
            }
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                selectStrategy(root, 'send_to_all');
                focusCard(root, 'send_to_all');
                return;
            }
            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                selectStrategy(root, 'send_and_stop');
                focusCard(root, 'send_and_stop');
                return;
            }
        });
    }

    function mountAll(scopeRoot) {
        var scope = scopeRoot || document;
        var roots = scope.querySelectorAll('[data-mc-fallback-strategy-card]:not([data-mc-fsc-init])');
        roots.forEach(function (r) {
            r.setAttribute('data-mc-fsc-init', '1');
            mount(r);
        });
    }

    window.McFallbackStrategyCard = {
        __bound: true,
        mount: mount,
        mountAll: mountAll,
        select: selectStrategy,
        labels: STRATEGY_LABELS,
        blurbs: STRATEGY_BLURBS,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { mountAll(); });
    } else {
        mountAll();
    }
})();

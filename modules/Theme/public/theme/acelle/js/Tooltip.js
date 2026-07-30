/**
 * Tooltip — Modern, accessible, viewport-aware tooltip library.
 *
 * Drop-in replacement for CSS-only `[data-tooltip]::after`. Every element with
 * `data-tooltip="..."` gets auto-enhanced on page load and via event delegation
 * (AJAX-loaded triggers just work).
 *
 * Declarative API (attributes):
 *   data-tooltip           — content (required). HTML if data-tooltip-html="true".
 *   data-tooltip-position  — top|bottom|left|right[-start|-end]  (default: top)
 *   data-tooltip-variant   — default|light|teal|success|danger|warning|info
 *   data-tooltip-size      — sm|md|lg  (default: md)
 *   data-tooltip-html      — true|false
 *   data-tooltip-max-width — int px (default 240)
 *   data-tooltip-arrow     — true|false (default true)
 *   data-tooltip-delay     — int ms show delay (default 80)
 *   data-tooltip-hide-delay— int ms hide delay (default 80)
 *   data-tooltip-trigger   — hover|focus|click|manual (space-separated combos, default "hover focus")
 *   data-tooltip-interactive — true|false (default false)
 *   data-tooltip-offset    — int px distance from trigger (default 8)
 *   data-tooltip-class     — extra class(es) appended to tooltip element
 *
 * Programmatic API:
 *   McTooltip.attach(el, options);
 *   McTooltip.show(el);  McTooltip.hide(el);  McTooltip.toggle(el);
 *   McTooltip.setContent(el, content);
 *   McTooltip.destroy(el);
 *   McTooltip.hideAll();
 */
(function () {
    'use strict';

    var VARIANTS = ['default', 'light', 'teal', 'success', 'danger', 'warning', 'info'];
    var SIZES = ['sm', 'md', 'lg'];
    var POSITIONS = [
        'top', 'top-start', 'top-end',
        'bottom', 'bottom-start', 'bottom-end',
        'left', 'left-start', 'left-end',
        'right', 'right-start', 'right-end'
    ];
    var VIEWPORT_PAD = 8;
    var ARROW_SIZE = 6;
    var ID_COUNTER = 0;

    function hasTouch() {
        return ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    }

    function parseBool(v, def) {
        if (v === undefined || v === null || v === '') return def;
        return v === 'true' || v === '1' || v === true;
    }

    function parseInt10(v, def) {
        var n = parseInt(v, 10);
        return isNaN(n) ? def : n;
    }

    function optionsFromAttributes(el) {
        var ds = el.dataset;
        var position = ds.tooltipPosition || 'top';
        if (POSITIONS.indexOf(position) === -1) position = 'top';

        var variant = ds.tooltipVariant || 'default';
        if (VARIANTS.indexOf(variant) === -1) variant = 'default';

        var size = ds.tooltipSize || 'md';
        if (SIZES.indexOf(size) === -1) size = 'md';

        var triggerStr = ds.tooltipTrigger || 'hover focus';
        var triggers = triggerStr.split(/\s+/).filter(Boolean);

        return {
            content: ds.tooltip || '',
            position: position,
            variant: variant,
            size: size,
            html: parseBool(ds.tooltipHtml, false),
            maxWidth: parseInt10(ds.tooltipMaxWidth, 240),
            arrow: parseBool(ds.tooltipArrow, true),
            delay: parseInt10(ds.tooltipDelay, 80),
            hideDelay: parseInt10(ds.tooltipHideDelay, 80),
            triggers: triggers,
            interactive: parseBool(ds.tooltipInteractive, false),
            offset: parseInt10(ds.tooltipOffset, 8),
            extraClass: ds.tooltipClass || ''
        };
    }

    // -----------------------------------------------------------------------
    //  Tooltip singleton DOM
    // -----------------------------------------------------------------------

    var tooltipEl = null;          // the single shared tooltip element
    var arrowEl = null;            // arrow inside tooltip
    var contentEl = null;          // content wrapper inside tooltip
    var activeTrigger = null;      // element currently showing the tooltip
    var activeOptions = null;
    var showTimer = null;
    var hideTimer = null;
    var autoHideTimer = null;      // touch auto-dismiss
    var isPointerOnTooltip = false;
    var perElementOptions = new WeakMap(); // el → options override (from attach())
    var nodeCount = 0;             // count of wired listeners (for debug)

    function ensureTooltipNode() {
        if (tooltipEl) return tooltipEl;
        tooltipEl = document.createElement('div');
        tooltipEl.className = 'mc-tooltip';
        tooltipEl.id = 'mc-tooltip-' + (++ID_COUNTER);
        tooltipEl.setAttribute('role', 'tooltip');
        tooltipEl.setAttribute('aria-hidden', 'true');

        arrowEl = document.createElement('span');
        arrowEl.className = 'mc-tooltip-arrow';
        arrowEl.setAttribute('aria-hidden', 'true');

        contentEl = document.createElement('span');
        contentEl.className = 'mc-tooltip-content';

        tooltipEl.appendChild(arrowEl);
        tooltipEl.appendChild(contentEl);
        document.body.appendChild(tooltipEl);

        // Interactive: track whether cursor is on tooltip itself
        tooltipEl.addEventListener('mouseenter', function () {
            isPointerOnTooltip = true;
            clearTimeout(hideTimer);
        });
        tooltipEl.addEventListener('mouseleave', function () {
            isPointerOnTooltip = false;
            if (activeTrigger && activeOptions && activeOptions.interactive) {
                scheduleHide(activeOptions.hideDelay);
            }
        });

        return tooltipEl;
    }

    // -----------------------------------------------------------------------
    //  Options resolution
    // -----------------------------------------------------------------------

    function resolveOptions(el) {
        var base = optionsFromAttributes(el);
        var override = perElementOptions.get(el);
        if (override) {
            for (var k in override) {
                if (Object.prototype.hasOwnProperty.call(override, k)) {
                    base[k] = override[k];
                }
            }
            if (override.triggers) base.triggers = override.triggers;
        }
        return base;
    }

    // -----------------------------------------------------------------------
    //  Show / hide
    // -----------------------------------------------------------------------

    function applyOptions(opts) {
        // Reset classes
        tooltipEl.className = 'mc-tooltip';
        tooltipEl.classList.add('mc-tooltip--' + opts.variant);
        tooltipEl.classList.add('mc-tooltip--size-' + opts.size);
        tooltipEl.classList.add('mc-tooltip--pos-' + opts.position);
        if (!opts.arrow) tooltipEl.classList.add('mc-tooltip--no-arrow');
        if (opts.interactive) tooltipEl.classList.add('mc-tooltip--interactive');
        if (opts.extraClass) {
            opts.extraClass.split(/\s+/).forEach(function (c) {
                if (c) tooltipEl.classList.add(c);
            });
        }
        tooltipEl.style.maxWidth = opts.maxWidth + 'px';

        // Content
        if (opts.html) {
            contentEl.innerHTML = opts.content;
        } else {
            contentEl.textContent = opts.content;
        }

        // Reset arrow inline styles
        arrowEl.style.left = '';
        arrowEl.style.top = '';
    }

    function show(el) {
        if (!el) return;
        var opts = resolveOptions(el);
        if (!opts.content) return;

        // If already shown for this element, just reposition
        if (activeTrigger === el && tooltipEl && tooltipEl.classList.contains('is-visible')) {
            position(el, opts);
            return;
        }

        ensureTooltipNode();
        clearTimeout(hideTimer);
        clearTimeout(autoHideTimer);

        // Hide any previous
        if (activeTrigger && activeTrigger !== el) {
            hideImmediate();
        }

        activeTrigger = el;
        activeOptions = opts;
        applyOptions(opts);

        // ARIA wiring
        var prevDescribedBy = el.getAttribute('aria-describedby') || '';
        if (prevDescribedBy.indexOf(tooltipEl.id) === -1) {
            el.setAttribute('data-tooltip-prev-aria', prevDescribedBy);
            el.setAttribute('aria-describedby',
                prevDescribedBy ? (prevDescribedBy + ' ' + tooltipEl.id) : tooltipEl.id);
        }

        // Measure + position (before fade-in to avoid first-frame jump)
        tooltipEl.classList.add('is-measuring');
        position(el, opts);
        tooltipEl.classList.remove('is-measuring');

        // Animate in
        // Force reflow so transition picks up the from-state
        // eslint-disable-next-line no-unused-expressions
        tooltipEl.offsetHeight;
        tooltipEl.classList.add('is-visible');
        tooltipEl.setAttribute('aria-hidden', 'false');

        // Scroll / resize listeners
        attachViewportListeners();

        // Touch auto-dismiss
        if (hasTouch() && opts.triggers.indexOf('hover') !== -1) {
            clearTimeout(autoHideTimer);
            autoHideTimer = setTimeout(function () { hide(el); }, 2500);
        }
    }

    function hide(el) {
        if (el && activeTrigger !== el) return;
        scheduleHide(activeOptions ? activeOptions.hideDelay : 80);
    }

    function scheduleHide(delay) {
        clearTimeout(hideTimer);
        clearTimeout(autoHideTimer);
        if (activeOptions && activeOptions.interactive && isPointerOnTooltip) return;
        hideTimer = setTimeout(hideImmediate, Math.max(0, delay || 0));
    }

    function hideImmediate() {
        if (!tooltipEl || !activeTrigger) {
            activeTrigger = null;
            activeOptions = null;
            return;
        }
        tooltipEl.classList.remove('is-visible');
        tooltipEl.setAttribute('aria-hidden', 'true');

        // Restore aria-describedby
        var prev = activeTrigger.getAttribute('data-tooltip-prev-aria');
        if (prev !== null) {
            if (prev) {
                activeTrigger.setAttribute('aria-describedby', prev);
            } else {
                activeTrigger.removeAttribute('aria-describedby');
            }
            activeTrigger.removeAttribute('data-tooltip-prev-aria');
        }

        activeTrigger = null;
        activeOptions = null;
        detachViewportListeners();
    }

    function toggle(el) {
        if (activeTrigger === el && tooltipEl && tooltipEl.classList.contains('is-visible')) {
            hideImmediate();
        } else {
            show(el);
        }
    }

    function hideAll() {
        clearTimeout(showTimer);
        clearTimeout(hideTimer);
        clearTimeout(autoHideTimer);
        if (activeTrigger) hideImmediate();
    }

    // -----------------------------------------------------------------------
    //  Positioning
    // -----------------------------------------------------------------------

    function position(el, opts) {
        var rect = el.getBoundingClientRect();
        var ttRect = tooltipEl.getBoundingClientRect();
        var vw = window.innerWidth;
        var vh = window.innerHeight;
        var offset = opts.offset;

        var primary = opts.position.split('-')[0];   // top / bottom / left / right
        var align = opts.position.split('-')[1] || 'center';

        // Flip if overflow on primary axis
        var flipped = maybeFlip(primary, rect, ttRect, vw, vh, offset);
        primary = flipped;

        var coords = computeCoords(primary, align, rect, ttRect, offset);

        // Clamp within viewport with ARROW_SIZE buffer
        var clamp = clampToViewport(primary, coords, ttRect, vw, vh);
        coords = clamp.coords;

        tooltipEl.style.left = coords.left + 'px';
        tooltipEl.style.top = coords.top + 'px';

        // Refresh pos class to drive arrow position
        POSITIONS.forEach(function (p) { tooltipEl.classList.remove('mc-tooltip--pos-' + p); });
        tooltipEl.classList.add('mc-tooltip--pos-' + primary + (align !== 'center' ? '-' + align : ''));

        // Shift arrow if we clamped cross-axis
        if (clamp.shift !== 0) {
            if (primary === 'top' || primary === 'bottom') {
                // Arrow is horizontally positioned — shift horizontally opposite of clamp
                arrowEl.style.left = 'calc(50% + ' + (-clamp.shift) + 'px)';
            } else {
                arrowEl.style.top = 'calc(50% + ' + (-clamp.shift) + 'px)';
            }
        }
    }

    function maybeFlip(primary, rect, ttRect, vw, vh, offset) {
        var needed;
        if (primary === 'top') {
            needed = rect.top - ttRect.height - offset;
            if (needed < VIEWPORT_PAD) {
                if (vh - rect.bottom - offset - ttRect.height >= VIEWPORT_PAD) return 'bottom';
            }
        } else if (primary === 'bottom') {
            needed = vh - rect.bottom - ttRect.height - offset;
            if (needed < VIEWPORT_PAD) {
                if (rect.top - offset - ttRect.height >= VIEWPORT_PAD) return 'top';
            }
        } else if (primary === 'left') {
            needed = rect.left - ttRect.width - offset;
            if (needed < VIEWPORT_PAD) {
                if (vw - rect.right - offset - ttRect.width >= VIEWPORT_PAD) return 'right';
            }
        } else if (primary === 'right') {
            needed = vw - rect.right - ttRect.width - offset;
            if (needed < VIEWPORT_PAD) {
                if (rect.left - offset - ttRect.width >= VIEWPORT_PAD) return 'left';
            }
        }
        return primary;
    }

    function computeCoords(primary, align, rect, ttRect, offset) {
        var left = 0, top = 0;
        if (primary === 'top' || primary === 'bottom') {
            top = primary === 'top'
                ? rect.top - ttRect.height - offset
                : rect.bottom + offset;
            if (align === 'start') left = rect.left;
            else if (align === 'end') left = rect.right - ttRect.width;
            else left = rect.left + rect.width / 2 - ttRect.width / 2;
        } else {
            left = primary === 'left'
                ? rect.left - ttRect.width - offset
                : rect.right + offset;
            if (align === 'start') top = rect.top;
            else if (align === 'end') top = rect.bottom - ttRect.height;
            else top = rect.top + rect.height / 2 - ttRect.height / 2;
        }
        return { left: left, top: top };
    }

    function clampToViewport(primary, coords, ttRect, vw, vh) {
        var shift = 0;
        if (primary === 'top' || primary === 'bottom') {
            var origLeft = coords.left;
            coords.left = Math.max(VIEWPORT_PAD, Math.min(coords.left, vw - ttRect.width - VIEWPORT_PAD));
            shift = coords.left - origLeft;
        } else {
            var origTop = coords.top;
            coords.top = Math.max(VIEWPORT_PAD, Math.min(coords.top, vh - ttRect.height - VIEWPORT_PAD));
            shift = coords.top - origTop;
        }
        return { coords: coords, shift: shift };
    }

    // -----------------------------------------------------------------------
    //  Viewport listeners (attached only while visible)
    // -----------------------------------------------------------------------

    var scrollHandler = null;
    var resizeHandler = null;

    function attachViewportListeners() {
        if (scrollHandler) return;
        scrollHandler = function () {
            if (!activeTrigger) return;
            position(activeTrigger, activeOptions);
        };
        resizeHandler = function () {
            if (!activeTrigger) return;
            position(activeTrigger, activeOptions);
        };
        window.addEventListener('scroll', scrollHandler, { capture: true, passive: true });
        window.addEventListener('resize', resizeHandler, { passive: true });
    }

    function detachViewportListeners() {
        if (!scrollHandler) return;
        window.removeEventListener('scroll', scrollHandler, { capture: true });
        window.removeEventListener('resize', resizeHandler);
        scrollHandler = null;
        resizeHandler = null;
    }

    // -----------------------------------------------------------------------
    //  Event delegation (hover / focus / click)
    // -----------------------------------------------------------------------

    var _initialized = false;

    function init() {
        if (_initialized) return;
        _initialized = true;

        document.addEventListener('mouseover', function (e) {
            var el = findTrigger(e.target);
            if (!el) return;
            var opts = resolveOptions(el);
            if (opts.triggers.indexOf('hover') === -1) return;
            clearTimeout(hideTimer);
            clearTimeout(showTimer);
            showTimer = setTimeout(function () { show(el); }, opts.delay);
        }, true);

        document.addEventListener('mouseout', function (e) {
            var el = findTrigger(e.target);
            if (!el) return;
            // Still hovering a descendant? ignore
            if (e.relatedTarget && el.contains(e.relatedTarget)) return;
            var opts = resolveOptions(el);
            if (opts.triggers.indexOf('hover') === -1) return;
            clearTimeout(showTimer);
            if (opts.interactive) {
                scheduleHide(opts.hideDelay + 100);
            } else {
                scheduleHide(opts.hideDelay);
            }
        }, true);

        document.addEventListener('focusin', function (e) {
            var el = findTrigger(e.target);
            if (!el) return;
            var opts = resolveOptions(el);
            if (opts.triggers.indexOf('focus') === -1) return;
            clearTimeout(showTimer);
            showTimer = setTimeout(function () { show(el); }, opts.delay);
        });

        document.addEventListener('focusout', function (e) {
            var el = findTrigger(e.target);
            if (!el) return;
            var opts = resolveOptions(el);
            if (opts.triggers.indexOf('focus') === -1) return;
            clearTimeout(showTimer);
            scheduleHide(opts.hideDelay);
        });

        document.addEventListener('click', function (e) {
            var el = findTrigger(e.target);
            if (el) {
                var opts = resolveOptions(el);
                if (opts.triggers.indexOf('click') !== -1) {
                    e.preventDefault();
                    toggle(el);
                    return;
                }
            }
            // Click outside closes click-mode tooltips
            if (activeTrigger && activeOptions && activeOptions.triggers.indexOf('click') !== -1) {
                if (tooltipEl && !tooltipEl.contains(e.target) && !activeTrigger.contains(e.target)) {
                    hideImmediate();
                }
                return;
            }
            // Hover-mode tooltip: any click dismisses it AND cancels any
            // pending show (matches native browser behavior and gets out of
            // the way when clicking a dropdown trigger, popup opener, or any
            // action button). Cancel showTimer too — otherwise the 80ms show
            // timer fires after the dropdown opens and overlays the menu.
            clearTimeout(showTimer);
            if (activeTrigger) hideImmediate();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideAll();
        });

        // Touch: tap shows, tap-elsewhere hides
        document.addEventListener('touchstart', function (e) {
            if (!hasTouch()) return;
            var el = findTrigger(e.target);
            if (el) {
                var opts = resolveOptions(el);
                if (opts.triggers.indexOf('hover') !== -1 || opts.triggers.indexOf('click') !== -1) {
                    // Already handled by click synth on mobile
                    return;
                }
            }
            if (activeTrigger && tooltipEl && !tooltipEl.contains(e.target) && !activeTrigger.contains(e.target)) {
                hideImmediate();
            }
        }, { passive: true });

        // Initial scan (logging only — delegation handles everything)
        nodeCount = document.querySelectorAll('[data-tooltip]').length;
    }

    function findTrigger(node) {
        if (!node || node.nodeType !== 1) return null;

        // Fast path — already has data-tooltip
        var el = node.closest('[data-tooltip], [data-tooltip-ref]');
        if (el) {
            if (el.hasAttribute('data-tooltip-disabled')) return null;
            if (el.hasAttribute('data-tooltip') && !el.getAttribute('data-tooltip')) return null;
            return el;
        }

        // Auto-attach "More actions" tooltip to every 3-dot dropdown trigger
        // (data-dropdown-trigger) across the app. Explicit data-tooltip wins
        // if the caller wants a different label.
        var dropTrigger = node.closest('[data-dropdown-trigger]');
        if (dropTrigger && !dropTrigger.hasAttribute('data-tooltip-disabled')) {
            var defaults = (typeof window !== 'undefined' && window.McTooltipDefaults) || {};
            var label = defaults.moreActions || 'More actions';
            dropTrigger.setAttribute('data-tooltip', label);
            if (!dropTrigger.hasAttribute('aria-label') && !dropTrigger.hasAttribute('aria-labelledby')) {
                dropTrigger.setAttribute('aria-label', label);
            }
            return dropTrigger;
        }

        // Lazy auto-promotion — any element with a native `title` attribute gets
        // upgraded to the modern tooltip on first interaction. This turns every
        // existing title="…" in the app (hundreds) into a mc-tooltip with zero
        // code migration. We strip `title` so the browser's native tooltip
        // doesn't render on top of ours, and we preserve accessibility by
        // copying the value into aria-label (only when missing).
        var titled = node.closest('[title]');
        if (!titled) return null;
        if (titled.hasAttribute('data-tooltip-disabled')) return null;

        // Skip form elements where `title` has semantic meaning beyond tooltip
        // (e.g. <iframe title="…"> for a11y, <input title="…"> as HTML5
        // validation hint). These keep native behavior.
        var tag = titled.tagName;
        if (tag === 'IFRAME' || tag === 'HTML' || tag === 'TITLE') return null;

        var titleVal = titled.getAttribute('title');
        if (!titleVal) return null;

        titled.setAttribute('data-tooltip', titleVal);
        if (!titled.hasAttribute('aria-label') && !titled.hasAttribute('aria-labelledby')) {
            titled.setAttribute('aria-label', titleVal);
        }
        titled.removeAttribute('title');
        return titled;
    }

    // -----------------------------------------------------------------------
    //  Public API
    // -----------------------------------------------------------------------

    var api = {
        init: init,
        show: show,
        hide: hide,
        toggle: toggle,
        hideAll: hideAll,

        attach: function (el, options) {
            if (!el) return;
            var existing = perElementOptions.get(el) || {};
            for (var k in options) {
                if (Object.prototype.hasOwnProperty.call(options, k)) {
                    existing[k] = options[k];
                }
            }
            // Mirror content into data-tooltip so findTrigger picks it up
            if (options && options.content !== undefined) {
                el.setAttribute('data-tooltip', options.content);
            }
            perElementOptions.set(el, existing);
        },

        setContent: function (el, content) {
            if (!el) return;
            el.setAttribute('data-tooltip', content);
            if (activeTrigger === el && activeOptions) {
                activeOptions.content = content;
                if (activeOptions.html) contentEl.innerHTML = content;
                else contentEl.textContent = content;
                position(el, activeOptions);
            }
        },

        destroy: function (el) {
            if (!el) return;
            if (activeTrigger === el) hideImmediate();
            perElementOptions.delete(el);
            el.removeAttribute('data-tooltip');
            el.removeAttribute('data-tooltip-position');
            el.removeAttribute('data-tooltip-variant');
            el.removeAttribute('data-tooltip-size');
        },

        // Debug / introspection
        _state: function () {
            return {
                initialized: _initialized,
                activeTrigger: activeTrigger,
                activeOptions: activeOptions,
                tooltipEl: tooltipEl,
                triggerCount: nodeCount
            };
        }
    };

    window.McTooltip = api;

    // Auto-init on DOMContentLoaded (or immediately if already loaded)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

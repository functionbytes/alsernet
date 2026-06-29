/**
 * mc-multi-coverage-bar — horizontal stacked bar per-channel reach % (C25 / W7).
 *
 * Mounts on any `<div data-mc-multi-coverage-bar>` and renders the per-channel
 * coverage stack. Used at S7.4 wizard step 3 (coverage forecast) and S7.7
 * cross-channel report funnel (with data-mode="funnel" so position numbers
 * ①②③④ are rendered inline).
 *
 * Data attributes (declarative API):
 *   - data-mc-multi-coverage-bar  — mount marker (REQUIRED)
 *   - data-segments               — JSON array of { channel, count, percent, label? }
 *   - data-total                  — total subscriber count (int)
 *   - data-unreachable-count      — number of subscribers receiving on NO channel
 *   - data-mode                   — "coverage" (default) | "funnel"
 *   - data-state                  — "ready" (default) | "loading" | "empty"
 *
 * Rendered shape:
 *   - role="img" on each segment with aria-label of count/total/percent
 *   - hover: tooltip
 *   - tap (mobile): toggles a single-line caption above the bar
 *
 * Public helpers (window.McMultiCoverageBar):
 *   - mount(root)
 *   - mountAll(scope?)
 *   - setSegments(root, segments, options): void
 *
 * Idempotent — re-mounting an already-bound element is a no-op.
 */
(function () {
    'use strict';

    if (window.McMultiCoverageBar && window.McMultiCoverageBar.__bound) {
        return;
    }

    var CHANNEL_LABELS = {
        email:    'Email',
        sms:      'SMS',
        whatsapp: 'WhatsApp',
        telegram: 'Telegram',
    };

    var POSITION_GLYPHS = ['①', '②', '③', '④', '⑤', '⑥'];

    function escapeHtml(s) {
        return (s == null ? '' : String(s)).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]);
        });
    }

    function parseSegments(root) {
        var raw = root.getAttribute('data-segments');
        if (!raw) return [];
        try {
            var data = JSON.parse(raw);
            if (!Array.isArray(data)) return [];
            return data.map(function (s) {
                return {
                    channel: String(s.channel || 'multi'),
                    count: parseInt(s.count, 10) || 0,
                    percent: typeof s.percent === 'number' ? s.percent : parseFloat(s.percent) || 0,
                    label: s.label != null ? String(s.label) : (CHANNEL_LABELS[s.channel] || s.channel),
                };
            });
        } catch (e) {
            // eslint-disable-next-line no-console
            console.warn('McMultiCoverageBar: data-segments is not valid JSON', e);
            return [];
        }
    }

    function getMode(root) {
        var m = root.getAttribute('data-mode');
        return m === 'funnel' ? 'funnel' : 'coverage';
    }

    function getState(root) {
        var s = root.getAttribute('data-state');
        return (s === 'loading' || s === 'empty') ? s : 'ready';
    }

    function buildSegmentHtml(seg, idx, mode) {
        var glyph = (mode === 'funnel' && idx < POSITION_GLYPHS.length) ? (POSITION_GLYPHS[idx] + ' ') : '';
        var label = glyph + (seg.label || CHANNEL_LABELS[seg.channel] || seg.channel);
        // Width is driven by inline custom-property — A5 token-assignment whitelist.
        return (
            '<div class="mc-multi-coverage-bar__segment" ' +
                  'data-channel-tone="' + escapeHtml(seg.channel) + '" ' +
                  'role="img" ' +
                  'aria-label="' + escapeHtml(label) + ': ' + seg.count + ' subscribers (' + seg.percent + '%)" ' +
                  'tabindex="0" ' +
                  'style="--mc-seg-pct: ' + Math.max(0, seg.percent) + '%">' +
                '<span class="mc-multi-coverage-bar__segment-label">' +
                    '<span class="mc-multi-coverage-bar__segment-label-text">' + escapeHtml(label) + '</span>' +
                    '<span class="mc-multi-coverage-bar__segment-label-pct">' + escapeHtml(String(seg.percent)) + '%</span>' +
                '</span>' +
            '</div>'
        );
    }

    function buildUnreachableHtml(unreachable, total) {
        if (!unreachable || unreachable <= 0 || !total) return '';
        var pct = Math.round((unreachable / total) * 1000) / 10;
        return (
            '<div class="mc-multi-coverage-bar__segment mc-multi-coverage-bar__segment--unreachable" ' +
                  'role="img" aria-label="Unreachable: ' + unreachable + ' subscribers (' + pct + '%)" ' +
                  'tabindex="0" ' +
                  'style="--mc-seg-pct: ' + pct + '%">' +
                '<span class="mc-multi-coverage-bar__segment-label">' +
                    '<span class="mc-multi-coverage-bar__segment-label-text">No channel</span>' +
                    '<span class="mc-multi-coverage-bar__segment-label-pct">' + pct + '%</span>' +
                '</span>' +
            '</div>'
        );
    }

    function buildLegendHtml(segments, unreachable, mode) {
        var items = segments.map(function (seg, i) {
            var glyph = (mode === 'funnel' && i < POSITION_GLYPHS.length) ? (POSITION_GLYPHS[i] + ' ') : '';
            return (
                '<li class="mc-multi-coverage-bar__legend-item">' +
                    '<span class="mc-multi-coverage-bar__legend-swatch" data-channel-tone="' + escapeHtml(seg.channel) + '" aria-hidden="true"></span>' +
                    '<span class="mc-multi-coverage-bar__legend-label">' + escapeHtml(glyph + (seg.label || CHANNEL_LABELS[seg.channel] || seg.channel)) + '</span>' +
                    '<span class="mc-multi-coverage-bar__legend-value">' + seg.count.toLocaleString() + ' · ' + escapeHtml(String(seg.percent)) + '%</span>' +
                '</li>'
            );
        });
        if (unreachable > 0) {
            items.push(
                '<li class="mc-multi-coverage-bar__legend-item">' +
                    '<span class="mc-multi-coverage-bar__legend-swatch mc-multi-coverage-bar__legend-swatch--unreachable" aria-hidden="true"></span>' +
                    '<span class="mc-multi-coverage-bar__legend-label">No channel</span>' +
                    '<span class="mc-multi-coverage-bar__legend-value">' + unreachable.toLocaleString() + '</span>' +
                '</li>'
            );
        }
        return '<ul class="mc-multi-coverage-bar__legend" role="list">' + items.join('') + '</ul>';
    }

    function buildSkeleton() {
        return (
            '<div class="mc-multi-coverage-bar__skeleton" aria-busy="true" aria-label="Loading coverage">' +
                '<div class="mc-multi-coverage-bar__skeleton-bar"></div>' +
                '<div class="mc-multi-coverage-bar__skeleton-legend">' +
                    '<span></span><span></span><span></span><span></span>' +
                '</div>' +
            '</div>'
        );
    }

    function buildEmpty(message) {
        return (
            '<div class="mc-multi-coverage-bar__empty" role="status">' +
                '<svg viewBox="0 0 64 64" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                    '<rect x="6" y="22" width="52" height="14" rx="3" stroke-dasharray="3 3"/>' +
                    '<circle cx="14" cy="46" r="2"/><circle cx="26" cy="46" r="2"/><circle cx="38" cy="46" r="2"/><circle cx="50" cy="46" r="2"/>' +
                '</svg>' +
                '<div class="mc-multi-coverage-bar__empty-text">' + escapeHtml(message) + '</div>' +
            '</div>'
        );
    }

    function render(root) {
        var state = getState(root);
        if (state === 'loading') {
            root.innerHTML = buildSkeleton();
            return;
        }

        var segments = root.__mcMcbSegments;
        var unreachable = parseInt(root.getAttribute('data-unreachable-count') || '0', 10) || 0;
        var total = parseInt(root.getAttribute('data-total') || '0', 10) || 0;
        var mode = getMode(root);

        if (state === 'empty' || (segments.length === 0 && unreachable === 0)) {
            var emptyMsg = root.getAttribute('data-empty-message') || 'No coverage data yet — pick an audience first';
            root.innerHTML = buildEmpty(emptyMsg);
            return;
        }

        var segmentsHtml = segments.map(function (seg, i) { return buildSegmentHtml(seg, i, mode); }).join('');
        var unreachableHtml = buildUnreachableHtml(unreachable, total);

        root.innerHTML =
            '<div class="mc-multi-coverage-bar__caption" aria-live="polite" hidden></div>' +
            '<div class="mc-multi-coverage-bar__bar" role="presentation">' +
                segmentsHtml + unreachableHtml +
            '</div>' +
            buildLegendHtml(segments, unreachable, mode);

        root.dataset.mcMultiCoverageBarMode = mode;
    }

    function mount(root) {
        if (!root || root.__mcMcbBound) return;
        root.__mcMcbBound = true;
        root.__mcMcbSegments = parseSegments(root);
        root.classList.add('mc-multi-coverage-bar');
        render(root);

        root.addEventListener('mouseenter', function (e) {
            var seg = e.target.closest && e.target.closest('.mc-multi-coverage-bar__segment');
            if (!seg) return;
            // Browser handles tooltip via aria-label.
        }, true);

        // Mobile tap toggles caption above the bar.
        root.addEventListener('click', function (e) {
            var seg = e.target.closest && e.target.closest('.mc-multi-coverage-bar__segment');
            if (!seg) return;
            var caption = root.querySelector('.mc-multi-coverage-bar__caption');
            if (!caption) return;
            var label = seg.getAttribute('aria-label') || '';
            if (caption.textContent === label && !caption.hasAttribute('hidden')) {
                caption.setAttribute('hidden', '');
                caption.textContent = '';
            } else {
                caption.textContent = label;
                caption.removeAttribute('hidden');
            }
        });

        // Keyboard accessibility — Enter on a segment toggles caption (mirrors tap).
        root.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var seg = e.target.closest && e.target.closest('.mc-multi-coverage-bar__segment');
            if (!seg) return;
            e.preventDefault();
            seg.click();
        });
    }

    function mountAll(scopeRoot) {
        var scope = scopeRoot || document;
        var roots = scope.querySelectorAll('[data-mc-multi-coverage-bar]:not([data-mc-mcb-init])');
        roots.forEach(function (r) {
            r.setAttribute('data-mc-mcb-init', '1');
            mount(r);
        });
    }

    function setSegments(root, segments, options) {
        if (!root) return;
        var opts = options || {};
        root.__mcMcbSegments = (segments || []).map(function (s) {
            return {
                channel: String(s.channel || 'multi'),
                count: parseInt(s.count, 10) || 0,
                percent: typeof s.percent === 'number' ? s.percent : parseFloat(s.percent) || 0,
                label: s.label != null ? String(s.label) : (CHANNEL_LABELS[s.channel] || s.channel),
            };
        });
        if (opts.total != null) root.setAttribute('data-total', String(opts.total));
        if (opts.unreachable != null) root.setAttribute('data-unreachable-count', String(opts.unreachable));
        if (opts.state) root.setAttribute('data-state', opts.state);
        render(root);
    }

    window.McMultiCoverageBar = {
        __bound: true,
        mount: mount,
        mountAll: mountAll,
        setSegments: setSegments,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { mountAll(); });
    } else {
        mountAll();
    }
})();

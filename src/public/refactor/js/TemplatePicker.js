/**
 * mc-template-picker — Card-grid picker for WhatsApp message templates (C8 / W4).
 *
 * Mounts on any `<div data-mc-template-picker>` and renders:
 *   - filter rail (status / category / language / search)
 *   - card grid of templates with status pill, category badge, language chip, body preview
 *   - empty state when 0 templates match filter
 *   - loading state (3 skeleton cards) when `data-state="loading"` (host swaps to "loaded" after async fetch)
 *
 * Data attributes (declarative API):
 *   - data-mc-template-picker      — mount marker (REQUIRED)
 *   - data-state                   — "loading" | "loaded" | "empty" (REQUIRED for initial paint; default "loaded")
 *   - data-templates               — JSON array of template objects (eager mode)
 *   - data-fetch-url               — URL for async JSON fetch (lazy mode); consumer wires the actual call,
 *                                    the picker just toggles state when external code sets data-templates
 *   - data-search-debounce-ms      — search input debounce window (default 200ms)
 *
 * Template object shape:
 *   {
 *     id:             number,
 *     name:           string,                       // slug-like identifier
 *     category:       "marketing" | "utility" | "authentication",
 *     language:       string,                       // e.g. "en_US", "vi_VN"
 *     status:         "approved" | "pending" | "rejected" | "disabled",
 *     header_type:    "text" | "image" | "video" | "document" | "location",
 *     variable_count: number,
 *     body:           string                        // preview body w/ :variable_n placeholders
 *   }
 *
 * Emits `mc-template-picker:change` CustomEvent on selection with full template detail.
 * Selection is single-pick (radio-style); selecting a non-approved template fires the
 * event with a disabled-CTA banner inside the picker.
 *
 * Public read state on the picker root (DOM dataset):
 *   root.dataset.mcTemplatePickerSelected = "{id}" | "" (empty when none selected)
 *   root.dataset.state                    = "loading" | "loaded" | "empty"
 *
 * Idempotent — re-mounting an already-bound element is a no-op.
 */
(function () {
    'use strict';

    if (window.McTemplatePicker && window.McTemplatePicker.__bound) {
        return;
    }

    function escapeHtml(s) {
        return (s == null ? '' : String(s)).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]);
        });
    }

    function categoryTone(category) {
        // Categorical labels use chart-N (not state) — stable mapping per the spec.
        return ({
            marketing: 'chart-2',
            utility: 'chart-4',
            authentication: 'chart-7',
        })[category] || 'chart-1';
    }

    function statusTone(status) {
        // Status IS state — use semantic tokens per L13 carve-out.
        return ({
            approved: 'green',
            pending: 'blue',
            rejected: 'red',
            disabled: 'default',
        })[status] || 'default';
    }

    function parseTemplates(root) {
        var raw = root.getAttribute('data-templates');
        if (!raw) return [];
        try {
            var data = JSON.parse(raw);
            return Array.isArray(data) ? data : [];
        } catch (e) {
            // Malformed JSON is a programming error — surface to console + render empty.
            // eslint-disable-next-line no-console
            console.error('McTemplatePicker: data-templates is not valid JSON', e);
            return [];
        }
    }

    function uniqueValues(templates, key) {
        var s = new Set();
        templates.forEach(function (t) { if (t[key] != null) s.add(t[key]); });
        return Array.from(s);
    }

    function buildFilterRail(allTemplates) {
        var statuses = ['all', 'approved', 'pending', 'rejected'];
        var categories = ['all', 'marketing', 'utility', 'authentication'];
        var langs = ['all'].concat(uniqueValues(allTemplates, 'language').sort());

        return (
            '<div class="mc-template-picker__filters" role="region" aria-label="Filter templates">' +
                '<div class="mc-template-picker__filter">' +
                    '<label class="mc-template-picker__filter-label" for="mctp-status">Status</label>' +
                    '<select class="mc-form-input mc-form-select mc-form-input-sm mc-template-picker__filter-select" id="mctp-status" data-filter="status">' +
                        statuses.map(function (s) {
                            return '<option value="' + s + '">' + (s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1)) + '</option>';
                        }).join('') +
                    '</select>' +
                '</div>' +
                '<div class="mc-template-picker__filter">' +
                    '<label class="mc-template-picker__filter-label" for="mctp-category">Category</label>' +
                    '<select class="mc-form-input mc-form-select mc-form-input-sm mc-template-picker__filter-select" id="mctp-category" data-filter="category">' +
                        categories.map(function (c) {
                            return '<option value="' + c + '">' + (c === 'all' ? 'All' : c.charAt(0).toUpperCase() + c.slice(1)) + '</option>';
                        }).join('') +
                    '</select>' +
                '</div>' +
                '<div class="mc-template-picker__filter">' +
                    '<label class="mc-template-picker__filter-label" for="mctp-language">Language</label>' +
                    '<select class="mc-form-input mc-form-select mc-form-input-sm mc-template-picker__filter-select" id="mctp-language" data-filter="language">' +
                        langs.map(function (l) {
                            return '<option value="' + l + '">' + (l === 'all' ? 'All' : l) + '</option>';
                        }).join('') +
                    '</select>' +
                '</div>' +
                '<div class="mc-template-picker__filter mc-template-picker__filter--search">' +
                    '<label class="mc-template-picker__filter-label" for="mctp-search">Search</label>' +
                    '<input type="search" class="mc-form-input mc-form-input-sm mc-template-picker__filter-input" id="mctp-search" data-filter="search" placeholder="Search by template name…" autocomplete="off">' +
                '</div>' +
            '</div>'
        );
    }

    function buildCard(t, isSelected) {
        var statusToneCls = 'mc-badge-' + statusTone(t.status);
        var catToneVar = '--' + categoryTone(t.category);
        var body = t.body || '';
        var preview = body.length > 240 ? body.slice(0, 237) + '…' : body;

        return (
            '<button type="button" class="mc-template-picker__card' + (isSelected ? ' is-selected' : '') + '"' +
                ' data-template-id="' + escapeHtml(t.id) + '"' +
                ' data-template-status="' + escapeHtml(t.status) + '"' +
                ' aria-pressed="' + (isSelected ? 'true' : 'false') + '">' +
                '<div class="mc-template-picker__card-head">' +
                    '<span class="mc-template-picker__card-cat" style="--cat-tone:var(' + catToneVar + ')">' +
                        escapeHtml(t.category) +
                    '</span>' +
                    '<span class="mc-badge ' + statusToneCls + ' mc-badge-sm mc-template-picker__card-status">' +
                        escapeHtml(t.status) +
                    '</span>' +
                '</div>' +
                '<div class="mc-template-picker__card-name">' + escapeHtml(t.name) + '</div>' +
                '<div class="mc-template-picker__card-meta">' +
                    '<span class="mc-template-picker__card-meta-pill" title="Language">' + escapeHtml(t.language) + '</span>' +
                    '<span class="mc-template-picker__card-meta-pill" title="Header type">' + escapeHtml(t.header_type || 'text') + '</span>' +
                    '<span class="mc-template-picker__card-meta-pill" title="Variable count">' + (t.variable_count || 0) + ' vars</span>' +
                '</div>' +
                '<div class="mc-template-picker__card-body">' + escapeHtml(preview) + '</div>' +
            '</button>'
        );
    }

    function buildEmptyState(activeFilters) {
        var chips = [];
        if (activeFilters.status !== 'all') chips.push('Status: ' + activeFilters.status);
        if (activeFilters.category !== 'all') chips.push('Category: ' + activeFilters.category);
        if (activeFilters.language !== 'all') chips.push('Language: ' + activeFilters.language);
        if (activeFilters.search) chips.push('Search: "' + activeFilters.search + '"');

        return (
            '<div class="mc-template-picker__empty" role="status">' +
                '<div class="mc-template-picker__empty-icon" aria-hidden="true">' +
                    '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
                        '<rect x="3" y="4" width="18" height="16" rx="2"/>' +
                        '<path d="M7 9h10M7 13h7M7 17h4"/>' +
                    '</svg>' +
                '</div>' +
                '<div class="mc-template-picker__empty-title">No templates match these filters</div>' +
                '<div class="mc-template-picker__empty-body">' +
                    (chips.length ? '<div class="mc-template-picker__empty-chips">' + chips.map(function (c) {
                        return '<span class="mc-template-picker__empty-chip">' + escapeHtml(c) + '</span>';
                    }).join('') + '</div>' : '') +
                    '<div class="mc-template-picker__empty-actions">' +
                        '<button type="button" class="mc-btn mc-btn-ghost mc-btn-sm" data-mc-template-picker-clear>Clear filters</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
    }

    function buildSkeleton() {
        var card = '<div class="mc-template-picker__card mc-template-picker__card--skeleton" aria-hidden="true"></div>';
        return (
            '<div class="mc-template-picker__grid">' +
                card + card + card +
            '</div>'
        );
    }

    function applyFilter(templates, filters) {
        var needle = (filters.search || '').toLowerCase().trim();
        return templates.filter(function (t) {
            if (filters.status !== 'all' && t.status !== filters.status) return false;
            if (filters.category !== 'all' && t.category !== filters.category) return false;
            if (filters.language !== 'all' && t.language !== filters.language) return false;
            if (needle && !(String(t.name).toLowerCase().includes(needle))) return false;
            return true;
        });
    }

    function buildDisabledBanner() {
        return (
            '<div class="mc-template-picker__disabled-banner" role="status" data-mc-template-picker-disabled-banner>' +
                '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>' +
                '<span data-mc-template-picker-disabled-text>This template is <strong>not approved</strong> — only approved templates can send.</span>' +
            '</div>'
        );
    }

    function render(root) {
        var state = root.getAttribute('data-state') || 'loaded';
        var templates = state === 'loading' ? [] : parseTemplates(root);
        var filters = root.__mctpFilters || { status: 'all', category: 'all', language: 'all', search: '' };
        var selectedId = root.dataset.mcTemplatePickerSelected || '';

        if (state === 'loading') {
            root.innerHTML = '<div class="mc-template-picker">' + buildSkeleton() + '</div>';
            return;
        }

        var filtered = applyFilter(templates, filters);
        var bodyHtml;
        if (filtered.length === 0) {
            bodyHtml = buildEmptyState(filters);
        } else {
            bodyHtml = '<div class="mc-template-picker__grid">' +
                filtered.map(function (t) { return buildCard(t, String(t.id) === String(selectedId)); }).join('') +
                '</div>';
        }

        // Build disabled-status banner if a non-approved template is selected
        var selected = templates.filter(function (t) { return String(t.id) === String(selectedId); })[0];
        var bannerHtml = '';
        if (selected && selected.status !== 'approved') {
            bannerHtml = buildDisabledBanner();
        }

        root.innerHTML = '<div class="mc-template-picker">' +
            buildFilterRail(templates) +
            bannerHtml +
            bodyHtml +
            '</div>';

        // Re-apply current filter values onto the freshly-rendered controls
        var fStatus = root.querySelector('[data-filter="status"]');
        var fCategory = root.querySelector('[data-filter="category"]');
        var fLanguage = root.querySelector('[data-filter="language"]');
        var fSearch = root.querySelector('[data-filter="search"]');
        if (fStatus) fStatus.value = filters.status;
        if (fCategory) fCategory.value = filters.category;
        if (fLanguage) fLanguage.value = filters.language;
        if (fSearch) fSearch.value = filters.search;
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(null, args); }, ms);
        };
    }

    function select(root, templateId) {
        var templates = parseTemplates(root);
        var t = templates.filter(function (x) { return String(x.id) === String(templateId); })[0];
        if (!t) return;

        root.dataset.mcTemplatePickerSelected = String(t.id);
        render(root);

        root.dispatchEvent(new CustomEvent('mc-template-picker:change', {
            bubbles: true,
            detail: {
                templateId: t.id,
                name: t.name,
                category: t.category,
                language: t.language,
                status: t.status,
                headerType: t.header_type,
                variableCount: t.variable_count,
                body: t.body,
            },
        }));
    }

    function mount(root) {
        if (!root || root.__mcTemplatePickerBound) return;
        root.__mcTemplatePickerBound = true;
        root.__mctpFilters = { status: 'all', category: 'all', language: 'all', search: '' };

        var debounceMs = parseInt(root.getAttribute('data-search-debounce-ms') || '200', 10);

        render(root);

        var rerender = function () { render(root); };
        var debouncedRender = debounce(rerender, debounceMs);

        root.addEventListener('change', function (e) {
            var target = e.target;
            if (!target || !target.dataset || !target.dataset.filter) return;
            var key = target.dataset.filter;
            if (key === 'search') return;
            root.__mctpFilters[key] = target.value;
            rerender();
        });

        root.addEventListener('input', function (e) {
            var target = e.target;
            if (!target || !target.dataset || target.dataset.filter !== 'search') return;
            root.__mctpFilters.search = target.value;
            debouncedRender();
        });

        root.addEventListener('click', function (e) {
            var clear = e.target.closest && e.target.closest('[data-mc-template-picker-clear]');
            if (clear) {
                e.preventDefault();
                root.__mctpFilters = { status: 'all', category: 'all', language: 'all', search: '' };
                rerender();
                return;
            }
            var card = e.target.closest && e.target.closest('.mc-template-picker__card');
            if (!card || card.classList.contains('mc-template-picker__card--skeleton')) return;
            var id = card.getAttribute('data-template-id');
            select(root, id);
        });
    }

    function mountAll(scopeRoot) {
        var scope = scopeRoot || document;
        var roots = scope.querySelectorAll('[data-mc-template-picker]:not([data-mc-template-picker-init])');
        roots.forEach(function (r) {
            r.setAttribute('data-mc-template-picker-init', '1');
            mount(r);
        });
    }

    window.McTemplatePicker = {
        __bound: true,
        mount: mount,
        mountAll: mountAll,
        // Test seam — selecting + re-rendering from outside
        select: select,
        _applyFilter: applyFilter,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { mountAll(); });
    } else {
        mountAll();
    }
})();

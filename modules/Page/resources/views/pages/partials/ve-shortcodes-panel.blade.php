@php
$shortcodes = $shortcodes ?? [];

$categoryMap = [
    'button'         => 'estructura',
    'columns'        => 'estructura',
    'column'         => 'estructura',
    'card'           => 'estructura',
    'accordion'      => 'estructura',
    'accordion-item' => 'estructura',
    'spacer'         => 'estructura',
    'alert'          => 'contenido',
    'badge'          => 'contenido',
    'quote'          => 'contenido',
    'icon'           => 'contenido',
    'ticker'         => 'contenido',
    'raw-html'       => 'contenido',
    'faq'            => 'contenido',
    'cta'            => 'contenido',
    'youtube'        => 'media',
    'image'          => 'media',
    'gallery'        => 'media',
    'image-gallery'  => 'media',
    'video'          => 'media',
    'contact-form'   => 'formularios',
    'form'           => 'formularios',
    'reviews'        => 'tema',
    'our-offices'    => 'tema',
    'site-features'  => 'tema',
    'testimonials'   => 'tema',
    'map'            => 'otros',
];

$iconMap = [
    'button'         => 'fa-hand-pointer',
    'alert'          => 'fa-exclamation-circle',
    'columns'        => 'fa-columns',
    'column'         => 'fa-bars',
    'youtube'        => 'fa-youtube',
    'image'          => 'fa-image',
    'icon'           => 'fa-icons',
    'badge'          => 'fa-tag',
    'card'           => 'fa-id-card',
    'accordion'      => 'fa-layer-group',
    'accordion-item' => 'fa-list',
    'quote'          => 'fa-quote-left',
    'contact-form'   => 'fa-envelope',
    'form'           => 'fa-wpforms',
    'our-offices'    => 'fa-building',
    'site-features'  => 'fa-star',
    'reviews'        => 'fa-star-half-alt',
    'gallery'        => 'fa-images',
    'spacer'         => 'fa-arrows-alt-v',
    // DB shortcodes
    'raw-html'       => 'fa-code',
    'image-gallery'  => 'fa-images',
    'video'          => 'fa-video',
    'faq'            => 'fa-question-circle',
    'testimonials'   => 'fa-quote-left',
    'cta'            => 'fa-bullhorn',
    'map'            => 'fa-map-marker-alt',
    'ticker'         => 'fa-scroll',
];

$categoryLabels = \Modules\Template\Models\ShortcodeCategory::active()
    ->pluck('label', 'slug')
    ->all();

// Enrich and group shortcodes by category
$grouped = [];
foreach ($shortcodes as $sc) {
    $sc['category'] = $sc['category'] ?? $categoryMap[$sc['name']] ?? 'otros';
    $sc['icon']     = $iconMap[$sc['name']] ?? 'fa-code';
    $grouped[$sc['category']][] = $sc;
}
@endphp

<div id="ve-shortcodes-panel" style="height:100%; display:flex; flex-direction:column; overflow:hidden;">

    {{-- Header --}}
    <div style="padding:10px 12px; border-bottom:1px solid #eee; flex-shrink:0;">
        <div style="font-size:10px; font-weight:600; color:#999; text-transform:uppercase; letter-spacing:.5px;">Bloques</div>
        <span style="font-size:13px; font-weight:700; color:#333;">Contenido disponible</span>
    </div>

    {{-- Hidden search (synced from topbar) --}}
    <div style="display:none;">
        <input type="text" id="ve-sc-search">
    </div>

    {{-- Shortcodes accordion --}}
    <div id="ve-sc-list" style="flex:1; overflow-y:auto;">
        <div id="ve-sc-accordion">

            @forelse($grouped as $cat => $items)
            @php $catId = 've-sc-cat-' . $cat; $isFirst = $loop->first; @endphp
            <div class="ve-blocks-category ve-sc-group" data-cat="{{ $cat }}">

                <button type="button"
                        class="ve-category-header {{ $isFirst ? '' : 'collapsed' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $catId }}"
                        data-bs-parent="#ve-sc-accordion"
                        aria-expanded="{{ $isFirst ? 'true' : 'false' }}"
                        aria-controls="{{ $catId }}">
                    {{ $categoryLabels[$cat] ?? ucfirst($cat) }}
                    <i class="fa-duotone fa-solid fa-chevron-down ve-cat-chevron"></i>
                </button>

                <div id="{{ $catId }}" class="collapse {{ $isFirst ? 'show' : '' }}" data-bs-parent="#ve-sc-accordion">
                    <div class="ve-blocks-grid">
                        @foreach($items as $sc)
                        <div class="ve-block-item ve-sc-item"
                             draggable="true"
                             data-name="{{ $sc['name'] }}"
                             data-category="{{ $sc['category'] }}"
                             data-example="{{ $sc['example'] ?? '[' . $sc['name'] . '][/' . $sc['name'] . ']' }}"
                             data-has-attrs="{{ count($sc['attributes'] ?? []) > 0 ? 'true' : 'false' }}"
                             data-sc='@json($sc)'
                             title="{{ $sc['description'] ?? $sc['name'] }}">

                            @if(count($sc['attributes'] ?? []) > 0)
                            <span class="ve-sc-cfg-badge" title="Configurable"><i class="fas fa-sliders-h"></i></span>
                            @endif

                            <div class="ve-block-icon">
                                <i class="fas {{ $sc['icon'] }}"></i>
                            </div>
                            <div class="ve-block-name">[{{ $sc['name'] }}]</div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
            @empty
            <p class="text-muted text-center py-4" style="font-size:12px;">
                No hay shortcodes registrados
            </p>
            @endforelse

        </div>

        {{-- Grid presets (visual cards at bottom) --}}
        <div class="ve-sc-grid-presets">
            <div class="ve-sc-grid-presets-title">Insertar estructura</div>
            <div class="ve-sc-grid-presets-grid">
                <button type="button" class="ve-insert-layout ve-grid-card" data-cols="1">
                    <div class="ve-grid-card-vis"><div class="ve-grid-col-block" style="flex:1"></div></div>
                    <span class="ve-grid-card-label">1 Col</span>
                </button>
                <button type="button" class="ve-insert-layout ve-grid-card" data-cols="2">
                    <div class="ve-grid-card-vis"><div class="ve-grid-col-block" style="flex:1"></div><div class="ve-grid-col-block" style="flex:1"></div></div>
                    <span class="ve-grid-card-label">2 Cols</span>
                </button>
                <button type="button" class="ve-insert-layout ve-grid-card" data-cols="3">
                    <div class="ve-grid-card-vis"><div class="ve-grid-col-block" style="flex:1"></div><div class="ve-grid-col-block" style="flex:1"></div><div class="ve-grid-col-block" style="flex:1"></div></div>
                    <span class="ve-grid-card-label">3 Cols</span>
                </button>
                <button type="button" class="ve-insert-layout ve-grid-card" data-cols="4">
                    <div class="ve-grid-card-vis"><div class="ve-grid-col-block" style="flex:1"></div><div class="ve-grid-col-block" style="flex:1"></div><div class="ve-grid-col-block" style="flex:1"></div><div class="ve-grid-col-block" style="flex:1"></div></div>
                    <span class="ve-grid-card-label">4 Cols</span>
                </button>
                <button type="button" class="ve-insert-layout ve-grid-card" data-type="1-2">
                    <div class="ve-grid-card-vis"><div class="ve-grid-col-block" style="flex:1"></div><div class="ve-grid-col-block ve-grid-col-wide" style="flex:2"></div></div>
                    <span class="ve-grid-card-label">1/3 · 2/3</span>
                </button>
                <button type="button" class="ve-insert-layout ve-grid-card" data-type="2-1">
                    <div class="ve-grid-card-vis"><div class="ve-grid-col-block ve-grid-col-wide" style="flex:2"></div><div class="ve-grid-col-block" style="flex:1"></div></div>
                    <span class="ve-grid-card-label">2/3 · 1/3</span>
                </button>
            </div>
        </div>

    </div>

</div>

<style>
/* Grid preset visual cards */
.ve-sc-grid-presets-title { padding:8px 10px; font-size:13px; font-weight:600; color:#444; border-top:1px solid #eee; }
.ve-sc-grid-presets-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px; padding:6px 10px 12px; }
.ve-grid-card {
    display:flex; flex-direction:column; align-items:stretch; gap:6px;
    padding:10px 10px 8px; background:#f8f9fa; border:1px solid #eee;
    border-radius:8px; cursor:pointer; transition:all .15s; text-align:center;
}
.ve-grid-card:hover { background:#fff; border-color:#ccc; box-shadow:0 2px 6px rgba(0,0,0,.06); }
.ve-grid-card.ve-insert-active { border-color:#b10100; background:#fdf2f2; }
.ve-grid-card-vis { display:flex; gap:3px; height:22px; border-radius:4px; overflow:hidden; }
.ve-grid-col-block { background:#ddd; border-radius:3px; min-width:8px; }
.ve-grid-col-wide { background:#ccc; }
.ve-grid-card:hover .ve-grid-col-block { background:#bbb; }
.ve-grid-card:hover .ve-grid-col-wide { background:#aaa; }
.ve-grid-card-label { font-size:10px; font-weight:600; color:#666; }

/* ── Accordion categories ─────────── */
.ve-blocks-category { border-bottom: none; }
.ve-category-header {
    display: flex; align-items: center; justify-content: space-between;
    width: 100%; padding: 8px 10px;
    background: transparent; border: none; border-bottom: none;
    font-size: 10px; font-weight: 700; color: #888; cursor: pointer;
    text-transform: uppercase; letter-spacing: .8px;
}
.ve-category-header:hover { background: #fafafa; color: #555; }
.ve-category-header[aria-expanded="true"],
.ve-category-header:not(.collapsed) { background: transparent; color: #b10100; }
.ve-category-header i.ve-cat-chevron { font-size: 9px; color: #bbb; transition: transform .2s; }
.ve-category-header.collapsed i.ve-cat-chevron { transform: rotate(-90deg); }
.ve-category-header:not(.collapsed) i.ve-cat-chevron { color: #b10100; }

.ve-blocks-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 6px 6px 8px;
}
.ve-block-item {
    cursor: grab; border: 1px solid #eee; border-radius: 8px;
    padding: 12px 6px 8px; text-align: center;
    transition: all .15s;
    user-select: none; display: flex; flex-direction: column;
    align-items: center; gap: 4px; background: #f8f9fa;
}
.ve-block-item .ve-block-icon { font-size: 22px; color: #333; line-height: 1; }
.ve-block-item .ve-block-name { font-size: 10px; color: #666; font-weight: 600; line-height: 1.3; }
.ve-block-item:hover { background: #fff; border-color: #ccc; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.ve-block-item:hover .ve-block-icon { color: #b10100; }
.ve-block-item:active { cursor: grabbing; transform: scale(.97); }
/* Custom blocks — same style as regular */
.ve-block-item-custom { background: #f8f9fa; position: relative; }
.ve-block-item .ve-block-icon .fa-lg { font-size: inherit; }
/* Force ALL block icons to dark color (override FA color classes) */
.ve-block-item .ve-block-icon,
.ve-block-item .ve-block-icon i,
.ve-block-item .ve-block-icon .fas,
.ve-block-item .ve-block-icon .far,
.ve-block-item .ve-block-icon .fa-duotone { color: #333 !important; }
.ve-block-item:hover .ve-block-icon,
.ve-block-item:hover .ve-block-icon i,
.ve-block-item:hover .ve-block-icon .fas,
.ve-block-item:hover .ve-block-icon .far,
.ve-block-item:hover .ve-block-icon .fa-duotone { color: #b10100 !important; }

/* ── Shortcode-specific ────────────────────────────────────────────── */
.ve-sc-item { position: relative; cursor: grab; }
.ve-sc-item .ve-block-name { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 9px; color: #555; font-weight: 600; }
.ve-sc-cfg-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    font-size: 11px;
    color: #555;
    line-height: 1;
}
.ve-sc-item:hover .ve-sc-cfg-badge { color: #333; }
.ve-sc-item.dragging { opacity: .5; transform: scale(.97); }
.ve-sc-delete-btn {
    position: absolute;
    top: 6px; right: 6px;
    background: #f4f6f8;
    border: 1px solid #eee;
    border-radius: 4px;
    font-size: 10px;
    padding: 2px 5px;
    color: #999;
    cursor: pointer;
    line-height: 1.2;
}
.ve-sc-delete-btn:hover { background: #b10100; border-color: #b10100; color: #fff; }

/* Category header — same style as inspector sections */
.ve-category-header {
    display: flex; align-items: center; justify-content: space-between;
    width: 100%; padding: 10px 12px;
    background: #fff; border: none; border-bottom: 1px solid #eee;
    font-size: 13px; font-weight: 700; color: #333; cursor: pointer;
    text-transform: none; letter-spacing: 0;
}
.ve-category-header:hover { background: #fafafa; }
.ve-category-header[aria-expanded="true"],
.ve-category-header:not(.collapsed) { background: #fff; color: #333; }
.ve-category-header i.ve-cat-chevron { font-size: 11px; color: #ccc; transition: transform .2s; }
.ve-category-header.collapsed i.ve-cat-chevron { transform: rotate(-90deg); }
.ve-category-header:not(.collapsed) i.ve-cat-chevron { color: #999; }
/* Remove blue focus border */
.ve-blocks-category { border: none; }
.ve-category-header:focus { outline: none; box-shadow: none; }
</style>

<script>
(function ($) {
    'use strict';

    const CSRF = $('meta[name="csrf-token"]').attr('content');

    /* ── Search filter ───────────────────────────────────────────────── */
    let searchTimer;
    $('#ve-sc-search').on('input', function () {
        clearTimeout(searchTimer);
        const q = $(this).val().toLowerCase();
        searchTimer = setTimeout(function () { filterShortcodes(q); }, 150);
    });

    function filterShortcodes(q) {
        if (!q) {
            $('.ve-sc-item').show();
            $('.ve-sc-group').show();
            return;
        }
        $('.ve-sc-group').each(function () {
            let visible = 0;
            $(this).find('.ve-sc-item').each(function () {
                const match = $(this).data('name').toLowerCase().includes(q);
                $(this).toggle(match);
                if (match) visible++;
            });
            $(this).toggle(visible > 0);
        });
    }

    /* ── Expand HTML (cached) ────────────────────────────────────────── */
    function expandHtml($item) {
        if ($item.data('expanded-html')) return Promise.resolve($item.data('expanded-html'));

        const example = $item.data('example');
        const url     = (typeof EXPAND_SHORTCODE_URL !== 'undefined') ? EXPAND_SHORTCODE_URL : null;
        if (!url || !example) return Promise.resolve(null);

        return $.ajax({ url, method: 'POST', data: { _token: CSRF, shortcode: example } })
            .then(function (res) {
                const html = res.html || null;
                $item.data('expanded-html', html);
                return html;
            })
            .catch(function () { return null; });
    }

    /* ── Eager pre-fetch all shortcodes on init ──────────────────────── */
    function prefetchAll() {
        const url = (typeof EXPAND_SHORTCODE_URL !== 'undefined') ? EXPAND_SHORTCODE_URL : null;
        if (!url) return;
        $('.ve-sc-item').each(function () { expandHtml($(this)); });
    }

    prefetchAll();

    /* ── Drag ────────────────────────────────────────────────────────── */
    $(document).on('dragstart', '.ve-sc-item', function (e) {
        const $item     = $(this);
        const directHtml = $item.data('custom-html');   // HTML blocks or saved custom
        const cached     = $item.data('expanded-html'); // PHP shortcodes (pre-fetched)

        // HTML block / custom saved — HTML is already in memory
        if (directHtml) {
            $item.addClass('dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'copy';
            e.originalEvent.dataTransfer.setData('text/plain', $item.data('name') || '');
            if (window.veStartBlockDrag) window.veStartBlockDrag(directHtml);
            return;
        }

        // PHP shortcode — needs server expansion
        if (!cached) {
            e.preventDefault();
            expandHtml($item).then(function () {
                if (window.showToast) window.showToast('Shortcode listo, arrastra de nuevo');
            });
            return;
        }

        $item.addClass('dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'copy';
        e.originalEvent.dataTransfer.setData('text/plain', $item.data('name'));
        if (window.veStartBlockDrag) window.veStartBlockDrag(cached);
    });

    $(document).on('dragend', '.ve-sc-item', function () {
        $(this).removeClass('dragging');
    });

    /* ── Insert helper ───────────────────────────────────────────────── */
    function insertShortcode($item) {
        function doInsert(html) {
            const frame = document.getElementById('ve-preview-frame');
            const ck    = frame?.contentDocument?.querySelector('.ck-content');
            if (!ck) return;

            const tmp = frame.contentDocument.createElement('div');
            tmp.innerHTML = html;
            while (tmp.firstChild) ck.appendChild(tmp.firstChild);

            $item.data('expanded-html', html);

            if (window.isModified !== undefined) window.isModified = true;
            if (window.scheduleAutoSave) window.scheduleAutoSave();
            if (window.getContentToSave && window.pushHistory) {
                window.getContentToSave().then(function (saved) {
                    window.pushHistory('Insertar: [' + $item.data('name') + ']', saved);
                });
            }
            if (window.showToast) {
                window.showToast('<i class="fa-duotone fa-solid fa-code me-1"></i>Shortcode insertado');
            }
        }

        const cached = $item.data('expanded-html');
        if (cached) {
            doInsert(cached);
        } else {
            expandHtml($item).then(function (html) { if (html) doInsert(html); });
        }
    }

    /* ── Click: configure if has attrs, else insert directly ────────── */
    $(document).on('click', '.ve-sc-item', function (e) {
        if ($(e.target).closest('.ve-sc-delete-btn').length) return;
        const $item   = $(this);
        const sc      = $item.data('sc');
        const hasAttr = $item.data('has-attrs') === 'true' || $item.data('has-attrs') === true;

        if ($item.data('custom-html')) {
            // Custom saved shortcode — insert raw HTML directly
            insertRawHtml($item.data('custom-html'), $item.data('name') || 'custom');
            return;
        }

        if (hasAttr && window.veOpenShortcodeBuilder) {
            window.veOpenShortcodeBuilder(sc);
        } else {
            insertShortcode($item);
        }
    });

    /* ── Insert raw HTML (custom shortcodes) ─────────────────────────── */
    function insertRawHtml(html, label) {
        const frame = document.getElementById('ve-preview-frame');
        const ck    = frame?.contentDocument?.querySelector('.ck-content');
        if (!ck) return;

        const tmp = frame.contentDocument.createElement('div');
        tmp.innerHTML = html;
        while (tmp.firstChild) ck.appendChild(tmp.firstChild);

        if (window.isModified !== undefined) window.isModified = true;
        if (window.scheduleAutoSave) window.scheduleAutoSave();
        if (window.showToast) {
            window.showToast('<i class="fa-duotone fa-solid fa-bookmark me-1"></i>' + label + ' insertado');
        }
    }

    /* ── Custom shortcodes (from localStorage) ───────────────────────── */
    function getCustomShortcodes() {
        try { return JSON.parse(localStorage.getItem('ve-custom-shortcodes') || '[]'); }
        catch (e) { return []; }
    }

    function renderCustomShortcodes() {
        const customs = getCustomShortcodes();
        const catId   = 've-sc-cat-custom';
        $('#ve-sc-group-custom').remove();

        if (!customs.length) return;

        const $group = $('<div class="ve-blocks-category ve-sc-group" id="ve-sc-group-custom" data-cat="custom">');
        const $header = $('<button type="button" class="ve-category-header collapsed">')
            .attr({ 'data-bs-toggle': 'collapse', 'data-bs-target': '#' + catId, 'data-bs-parent': '#ve-sc-accordion', 'aria-expanded': 'false', 'aria-controls': catId })
            .html('Personalizados <i class="fa-duotone fa-solid fa-chevron-down ve-cat-chevron"></i>');

        const $collapse = $('<div class="collapse">').attr({ id: catId, 'data-bs-parent': '#ve-sc-accordion' });
        const $grid = $('<div class="ve-blocks-grid">');

        customs.forEach(function (item) {
            const $card = $('<div class="ve-block-item ve-sc-item ve-block-item-custom">')
                .attr('draggable', 'true')
                .attr('data-name', item.label || 'custom')
                .attr('title', item.label || 'Shortcode personalizado')
                .data('custom-html', item.html || '');

            $card.html(
                '<button class="ve-sc-delete-btn" data-id="' + item.id + '" title="Eliminar">' +
                '<i class="fas fa-times"></i></button>' +
                '<div class="ve-block-icon"><i class="fa-duotone fa-solid fa-bookmark"></i></div>' +
                '<div class="ve-block-name">' + $('<span>').text(item.label || 'custom').html() + '</div>'
            );

            $grid.append($card);
        });

        $collapse.append($grid);
        $group.append($header, $collapse);
        $('#ve-sc-accordion').append($group);
    }

    $(document).on('click', '.ve-sc-delete-btn', function (e) {
        e.stopPropagation();
        const id = $(this).data('id');
        window.veConfirm('Se eliminará el shortcode personalizado de tu biblioteca.', function () {
            const stored = getCustomShortcodes().filter(function (s) { return s.id !== id; });
            localStorage.setItem('ve-custom-shortcodes', JSON.stringify(stored));
            renderCustomShortcodes();
        });
    });

    /* ── Render HTML blocks (window.veBlocks) ───────────────────────── */
    // Maps ve-blocks category → existing Blade-rendered accordion collapse ID.
    // If mapped, items are appended to the existing category grid.
    // If not mapped, a new category is created.
    const BLOCK_TO_SC_CAT = {
        content: 've-sc-cat-contenido',
        forms:   've-sc-cat-formularios',
    };
    const BLOCK_CATEGORY_LABELS = {
        sections: 'Secciones',
        cards:    'Cards',
    };
    const BLOCK_CATEGORY_ORDER = ['sections', 'cards', 'content', 'forms'];

    function buildBlockCard(block) {
        return $('<div class="ve-block-item ve-sc-item">')
            .attr({ draggable: 'true', title: block.name })
            .data('custom-html', block.html || '')
            .data('name', block.name || '')
            .html('<div class="ve-block-icon">' + block.icon + '</div>' +
                  '<div class="ve-block-name">' + $('<span>').text(block.name).html() + '</div>');
    }

    function renderHtmlBlocks() {
        $('.ve-sc-group-blocks').remove();

        const blocks = window.veBlocks || [];
        if (!blocks.length) return;

        const groups = {};
        const order  = [];
        blocks.forEach(function (b) {
            const cat = b.category || 'content';
            if (!groups[cat]) { groups[cat] = []; order.push(cat); }
            groups[cat].push(b);
        });

        const finalOrder = BLOCK_CATEGORY_ORDER.filter(function (c) { return groups[c]; })
            .concat(order.filter(function (c) { return !BLOCK_CATEGORY_ORDER.includes(c) && groups[c]; }));

        finalOrder.forEach(function (cat) {
            const existingCollapseId = BLOCK_TO_SC_CAT[cat];

            if (existingCollapseId) {
                // Merge into existing Blade-rendered category
                const $existingGrid = $('#' + existingCollapseId + ' .ve-blocks-grid');
                if ($existingGrid.length) {
                    groups[cat].forEach(function (block) {
                        $existingGrid.append(buildBlockCard(block));
                    });
                    return;
                }
            }

            // Create new category
            const catId  = 've-sc-blocks-' + cat;
            const label  = BLOCK_CATEGORY_LABELS[cat] || cat;
            const $group = $('<div class="ve-blocks-category ve-sc-group ve-sc-group-blocks">').attr('data-cat', cat);
            const $header = $('<button type="button" class="ve-category-header collapsed">')
                .attr({ 'data-bs-toggle': 'collapse', 'data-bs-target': '#' + catId,
                        'data-bs-parent': '#ve-sc-accordion', 'aria-expanded': 'false', 'aria-controls': catId })
                .html(label + ' <i class="fa-duotone fa-solid fa-chevron-down ve-cat-chevron"></i>');
            const $collapse = $('<div class="collapse">').attr({ id: catId, 'data-bs-parent': '#ve-sc-accordion' });
            const $grid = $('<div class="ve-blocks-grid">');

            groups[cat].forEach(function (block) { $grid.append(buildBlockCard(block)); });

            $collapse.append($grid);
            $group.append($header, $collapse);
            $('#ve-sc-accordion').append($group);
        });
    }

    /* ── Expose globally for after-save refresh ──────────────────────── */
    window.veRenderCustomShortcodes = renderCustomShortcodes;

    // Defer until all inline scripts (including ve-blocks-data) have run
    $(function () {
        renderHtmlBlocks();
        renderCustomShortcodes();
    });

})(jQuery);
</script>

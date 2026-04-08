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

    {{-- Search --}}
    <div style="padding:8px; border-bottom:1px solid #e9ecef; flex-shrink:0;">
        <div style="position:relative;">
            <i class="fa-duotone fa-solid fa-search" style="position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#aaa; font-size:12px; pointer-events:none;"></i>
            <input type="text" id="ve-sc-search" class="form-control form-control-sm"
                   placeholder="Buscar shortcode..."
                   style="padding-left:28px; font-size:12px;">
        </div>
    </div>

    {{-- Drag hint --}}
    <div style="padding:5px 10px; background:#f5f5f5; border-bottom:1px solid #dee2e6; flex-shrink:0; font-size:11px; color:#888;">
        <i class="fa-duotone fa-solid fa-hand-pointer me-1"></i>
        Arrastra al preview o haz clic para insertar
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
    </div>

</div>

<style>
/* ── Accordion categories (previously in ve-blocks-panel) ─────────── */
.ve-blocks-category { border-bottom: 1px solid #f0f0f0; }
.ve-category-header {
    display: flex; align-items: center; justify-content: space-between;
    width: 100%; padding: 9px 12px;
    background: transparent; border: none; border-bottom: 1px solid #f0f0f0;
    font-size: 12px; font-weight: 600; color: #444; cursor: pointer;
    text-transform: uppercase; letter-spacing: .5px;
}
.ve-category-header:hover { background: #f8f9fa; }
.ve-category-header[aria-expanded="true"],
.ve-category-header:not(.collapsed) { background: rgba(99,102,241,.08); }
.ve-category-header i.ve-cat-chevron { font-size: 10px; color: #aaa; transition: transform .2s; }
.ve-category-header.collapsed i.ve-cat-chevron { transform: rotate(-90deg); }

.ve-blocks-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 10px;
}
.ve-block-item {
    cursor: grab; border: 1px solid #e9ecef; border-radius: 8px;
    padding: 12px 8px; text-align: center;
    transition: border-color .15s, box-shadow .15s;
    user-select: none; display: flex; flex-direction: column;
    align-items: center; gap: 6px; background: #fff;
}
.ve-block-item .ve-block-icon { font-size: 20px; color: #6c757d; line-height: 1; }
.ve-block-item .ve-block-name { font-size: 11px; color: #333; font-weight: 500; line-height: 1.3; }
.ve-block-item:hover { border-color: #1a1a1a; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
.ve-block-item:active { cursor: grabbing; }
.ve-block-item-custom { background: #fafff0; position: relative; }
/* Normalize fa-lg override inside block icon container */
.ve-block-item .ve-block-icon .fa-lg { font-size: inherit; }

/* ── Shortcode-specific ────────────────────────────────────────────── */
.ve-sc-item { position: relative; cursor: grab; }
.ve-sc-item .ve-block-name { font-family: monospace; font-size: 10px; }
.ve-sc-cfg-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 9px;
    color: #aaa;
    line-height: 1;
}
.ve-sc-item:hover .ve-sc-cfg-badge { color: #555; }
.ve-sc-item.dragging { opacity: .5; transform: scale(.97); }
.ve-sc-delete-btn {
    position: absolute;
    top: 4px; right: 4px;
    background: transparent;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    font-size: 9px;
    padding: 1px 4px;
    color: #aaa;
    cursor: pointer;
    line-height: 1.4;
}
.ve-sc-delete-btn:hover { background: #dc3545; border-color: #dc3545; color: #fff; }
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

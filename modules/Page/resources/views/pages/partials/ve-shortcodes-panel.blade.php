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

<div id="ve-shortcodes-panel" class="ve-panel-root">

    {{-- Header --}}
    <div class="ve-sc-header">
        <div class="ve-panel-label">Bloques</div>
        <div class="position-relative mt-2">
            <i class="fas fa-search ve-search-icon"></i>
            <input type="text" id="ve-sc-search" class="form-control form-control-sm ve-search-input" placeholder="Buscar bloque..." autocomplete="off">
            <button type="button" id="ve-sc-search-clear" class="ve-search-clear-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    {{-- Blocks in page section --}}
    <div id="ve-blocks-in-page-section" class="ve-blocks-in-page-section">
        <div class="ve-blocks-in-page-toggle" id="ve-blocks-toggle" role="button" tabindex="0">
            <span>EN ESTA PÁGINA</span>
            <button type="button" class="btn btn-link btn-sm p-0" id="btn-refresh-blocks" title="Actualizar lista">
                <i class="fa-solid fa-rotate ve-blocks-refresh-icon"></i>
            </button>
        </div>
        <div id="ve-blocks-in-page-list" class="ve-blocks-in-page-list"></div>
    </div>


    {{-- Shortcodes accordion --}}
    <div id="ve-sc-list" class="ve-scrollable-area">

        {{-- Favorites section (improvement 20) --}}
        <div id="ve-sc-favorites-section" style="display:none;">
            <div class="ve-sc-category-header">
                <i class="fa-solid fa-star me-1 ve-icon-warning"></i> Favoritos
            </div>
            <div id="ve-sc-favorites-list" class="ve-sc-grid ve-blocks-grid"></div>
            <hr class="ve-sc-divider">
        </div>

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
                    <i class="fa-solid fa-chevron-down ve-cat-chevron"></i>
                </button>

                <div id="{{ $catId }}" class="collapse {{ $isFirst ? 'show' : '' }}" data-bs-parent="#ve-sc-accordion">
                    <div class="ve-blocks-grid">
                        @foreach($items as $sc)
                        <div class="ve-block-item ve-sc-item"
                             draggable="true"
                             data-name="{{ $sc['name'] }}"
                             data-sc-name="{{ $sc['name'] }}"
                             data-category="{{ $sc['category'] }}"
                             data-example="{{ $sc['example'] ?? '[' . $sc['name'] . '][/' . $sc['name'] . ']' }}"
                             data-has-attrs="{{ count($sc['attributes'] ?? []) > 0 ? 'true' : 'false' }}"
                             data-sc='@json($sc)'>

                            @if(count($sc['attributes'] ?? []) > 0)
                            <span class="ve-sc-cfg-badge" title="Configurable"><i class="fa-solid fa-sliders-h"></i></span>
                            @endif

                            <button class="ve-sc-fav-btn" data-sc-name="{{ $sc['name'] }}" title="Agregar a favoritos">
                                <i class="fa-solid fa-star"></i>
                            </button>

                            <div class="ve-block-icon">
                                <i class="fa-solid {{ $sc['icon'] }}"></i>
                            </div>
                            <div class="ve-block-name">[{{ $sc['name'] }}]</div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
            @empty
            <p class="text-muted text-center py-4 ve-sc-empty-msg">
                No hay shortcodes registrados
            </p>
            @endforelse

        </div>

        {{-- Grid presets (visual cards at bottom) --}}
        <div class="ve-sc-grid-presets">
            <div class="ve-sc-grid-presets-title">Insertar estructura</div>
            <div class="ve-sc-grid-presets-grid">
                @php
                $gridTemplates = [
                    ['cols' => '1', 'label' => '1 Col', 'vis' => [1], 'html' => '<section class="py-5"><div class="container"><div class="row g-4"><div class="col-md-12"><p>Contenido de la columna</p></div></div></div></section>'],
                    ['cols' => '2', 'label' => '2 Cols', 'vis' => [1,1], 'html' => '<section class="py-5"><div class="container"><div class="row g-4"><div class="col-md-6"><p>Contenido de la columna</p></div><div class="col-md-6"><p>Contenido de la columna</p></div></div></div></section>'],
                    ['cols' => '3', 'label' => '3 Cols', 'vis' => [1,1,1], 'html' => '<section class="py-5"><div class="container"><div class="row g-4"><div class="col-md-4"><p>Contenido de la columna</p></div><div class="col-md-4"><p>Contenido de la columna</p></div><div class="col-md-4"><p>Contenido de la columna</p></div></div></div></section>'],
                    ['cols' => '4', 'label' => '4 Cols', 'vis' => [1,1,1,1], 'html' => '<section class="py-5"><div class="container"><div class="row g-4"><div class="col-md-3"><p>Contenido</p></div><div class="col-md-3"><p>Contenido</p></div><div class="col-md-3"><p>Contenido</p></div><div class="col-md-3"><p>Contenido</p></div></div></div></section>'],
                    ['type' => '1-2', 'label' => '1/3 · 2/3', 'vis' => [1,2], 'html' => '<section class="py-5"><div class="container"><div class="row g-4"><div class="col-md-4"><p>Contenido de la columna</p></div><div class="col-md-8"><p>Contenido de la columna</p></div></div></div></section>'],
                    ['type' => '2-1', 'label' => '2/3 · 1/3', 'vis' => [2,1], 'html' => '<section class="py-5"><div class="container"><div class="row g-4"><div class="col-md-8"><p>Contenido de la columna</p></div><div class="col-md-4"><p>Contenido de la columna</p></div></div></div></section>'],
                ];
                @endphp
                @foreach($gridTemplates as $tpl)
                <div class="ve-block-item ve-sc-item ve-insert-layout ve-grid-card"
                     draggable="true"
                     data-{{ isset($tpl['cols']) ? 'cols' : 'type' }}="{{ $tpl['cols'] ?? $tpl['type'] }}"
                     data-name="layout-{{ $tpl['cols'] ?? $tpl['type'] }}"
                     data-example="{{ $tpl['html'] }}"
                     data-category="estructura">
                    <div class="ve-grid-card-vis">
                        @foreach($tpl['vis'] as $flex)
                        <div class="ve-grid-col-block {{ $flex > 1 ? 've-grid-col-wide ve-grid-col-f2' : 've-grid-col-f1' }}"></div>
                        @endforeach
                    </div>
                    <span class="ve-grid-card-label">{{ $tpl['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

</div>

<style>
/* ── Panel root & header ────────────────────────────────────────── */
.ve-sc-header {
    padding: 10px 12px;
    border-bottom: 1px solid var(--ve-border, #eee);
    flex-shrink: 0;
}
.ve-sc-empty-msg { font-size: 12px; }

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
.ve-grid-col-f1 { flex: 1; }
.ve-grid-col-f2 { flex: 2; }
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
.ve-block-item .ve-block-icon { font-size: 22px; color: #b10100; line-height: 1; }
.ve-block-item .ve-block-name { font-size: 10px; color: #666; font-weight: 600; line-height: 1.3; }
.ve-block-item:hover { background: #fff; border-color: #ccc; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.ve-block-item:hover .ve-block-icon { color: #b10100; }
.ve-block-item:active { cursor: grabbing; transform: scale(.97); }
/* Custom blocks — same style as regular */
.ve-block-item-custom { background: #f8f9fa; position: relative; }
.ve-block-item .ve-block-icon .fa-lg { font-size: inherit; }
/* Force ALL block icons to brand color (override FA color classes) */
.ve-block-item .ve-block-icon,
.ve-block-item .ve-block-icon i,
.ve-block-item .ve-block-icon .fas,
.ve-block-item .ve-block-icon .far,
.ve-block-item .ve-block-icon .fa-solid { color: #b10100 !important; }
.ve-block-item:hover .ve-block-icon,
.ve-block-item:hover .ve-block-icon i,
.ve-block-item:hover .ve-block-icon .fas,
.ve-block-item:hover .ve-block-icon .far,
.ve-block-item:hover .ve-block-icon .fa-solid { color: #7b0000 !important; }

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

/* ── Blocks in page section ─────────────────────────────────────── */
.ve-blocks-in-page-section {
    border-bottom: 1px solid #eee;
    padding: 8px 10px;
    flex-shrink: 0;
    background: #fafafa;
}
.ve-blocks-in-page-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 10px;
    font-weight: 700;
    color: #888;
    letter-spacing: .8px;
    text-transform: uppercase;
}
.ve-blocks-refresh-icon { font-size: 10px; color: #aaa; }
.ve-blocks-in-page-list { margin-top: 6px; }
.ve-blocks-in-page-list .btn { font-size: 10px; text-align: left; }
.ve-blocks-empty-msg { font-size: 10px; color: #aaa; margin: 0; }

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

/* ── Improvement 8: Block entry hover styles ────────────────────── */
.ve-block-entry-btn.ve-block-entry-hover {
    background: #f0f7e0;
    border-color: #90bb13;
}

/* ── Improvement 10: Modified sentinel badge ────────────────────── */
.ve-block-modified-badge {
    color: #FEC90F;
    font-size: 8px;
    margin-left: 4px;
    vertical-align: middle;
}

/* ── Improvement 20: Favorites ──────────────────────────────────── */
.ve-sc-fav-btn {
    position: absolute;
    top: 4px;
    left: 4px;
    background: none;
    border: none;
    padding: 2px 4px;
    cursor: pointer;
    opacity: .4;
    transition: opacity .15s, color .15s;
    font-size: 11px;
    color: #999;
    line-height: 1;
    z-index: 1;
}
.ve-sc-fav-btn:hover,
.ve-sc-fav-btn.active { opacity: 1; color: #FEC90F; }
.ve-sc-category-header {
    padding: 8px 10px;
    font-size: 10px;
    font-weight: 700;
    color: #888;
    letter-spacing: .8px;
    text-transform: uppercase;
}

/* ── Improvement 9: Position number in block list ───────────────── */
.ve-block-position-num {
    font-size: 9px;
    color: #bbb;
    font-weight: 700;
    margin-right: 3px;
    min-width: 14px;
    display: inline-block;
}
</style>

<script>
(function ($) {
    'use strict';

    const CSRF = $('meta[name="csrf-token"]').attr('content');

    /* ── Improvement 10: Track modified sentinels (initialized early) ── */
    window._veModifiedSentinels = window._veModifiedSentinels || new Set();

    /* ── Search filter ───────────────────────────────────────────────── */
    let searchTimer;
    $('#ve-sc-search').on('input', function () {
        clearTimeout(searchTimer);
        const q = $(this).val().toLowerCase();
        $('#ve-sc-search-clear').toggle(q.length > 0);
        searchTimer = setTimeout(function () { filterShortcodes(q); }, 150);
    });
    $(document).on('click', '#ve-sc-search-clear', function () {
        $('#ve-sc-search').val('').trigger('input').focus();
        $(this).hide();
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

            // Wrap with a sentinel div so extractContent() can restore the raw shortcode
            // tag on save instead of persisting the expanded HTML.
            const tag     = $item.data('example') || '';
            const safeTag = tag.replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                               .replace(/\[/g, '&#91;').replace(/\]/g, '&#93;');
            const tmp = frame.contentDocument.createElement('div');
            tmp.innerHTML = '<div data-ve-sc="' + safeTag + '">' + html + '</div>';
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
                window.showToast('<i class="fa-solid fa-code me-1"></i>Shortcode insertado');
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
        if ($(e.target).closest('.ve-sc-delete-btn, .ve-sc-fav-btn').length) return;
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
            window.showToast('<i class="fa-solid fa-bookmark me-1"></i>' + label + ' insertado');
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
            .html('Personalizados <i class="fa-solid fa-chevron-down ve-cat-chevron"></i>');

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
                '<i class="fa-solid fa-times"></i></button>' +
                '<div class="ve-block-icon"><i class="fa-solid fa-bookmark"></i></div>' +
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
                .html(label + ' <i class="fa-solid fa-chevron-down ve-cat-chevron"></i>');
            const $collapse = $('<div class="collapse">').attr({ id: catId, 'data-bs-parent': '#ve-sc-accordion' });
            const $grid = $('<div class="ve-blocks-grid">');

            groups[cat].forEach(function (block) { $grid.append(buildBlockCard(block)); });

            $collapse.append($grid);
            $group.append($header, $collapse);
            $('#ve-sc-accordion').append($group);
        });
    }

    /* ── Improvement 8: Hover highlight of sentinels ────────────────── */
    $(document).on('mouseenter', '#ve-blocks-in-page-list .ve-block-entry-btn', function () {
        var scNodeId = $(this).data('sc-node-id');
        var frame    = document.getElementById('ve-preview-frame');
        if (!frame || !scNodeId) return;
        frame.contentWindow.postMessage({ type: 've-highlight-sentinel-hover', scNodeId: scNodeId }, '*');
        $(this).addClass('ve-block-entry-hover');
    });

    $(document).on('mouseleave', '#ve-blocks-in-page-list .ve-block-entry-btn', function () {
        var frame = document.getElementById('ve-preview-frame');
        if (frame) {
            frame.contentWindow.postMessage({ type: 've-unhighlight-sentinel-hover' }, '*');
        }
        $(this).removeClass('ve-block-entry-hover');
    });

    /* ── Improvement 10: Listen for sentinel updates and page save ───── */
    window.addEventListener('message', function (e) {
        var data = e.data || {};

        if (data.type === 've-update-shortcode-sentinel' && data.scNodeId) {
            window._veModifiedSentinels.add(data.scNodeId);
            refreshBlocksInPage();
            return;
        }

        if (data.type === 've-page-saved') {
            window._veModifiedSentinels.clear();
            refreshBlocksInPage();
        }
    });

    /* ── Improvement 18: Tooltips for shortcode items ───────────────── */
    function applyShortcodeTooltips() {
        $('.ve-sc-item[data-sc-name]').each(function () {
            var name = $(this).data('sc-name');
            var meta = (window.veScMetaMap || {})[name] || {};
            if (meta.description) {
                $(this).attr('title', '[' + name + ']\n' + meta.description);
            }
        });
    }

    /* ── Improvement 20: Favorites system ───────────────────────────── */
    function getFavorites() {
        try { return JSON.parse(localStorage.getItem('ve-sc-favorites') || '[]'); }
        catch (e) { return []; }
    }

    function saveFavorites(favs) {
        localStorage.setItem('ve-sc-favorites', JSON.stringify(favs));
    }

    function buildFavCard(name) {
        var $source = $('[data-sc-name="' + name + '"].ve-sc-item').first();
        if (!$source.length) return null;

        var $card = $source.clone();
        // Remove nested fav button to avoid double-click issues — re-bind on the clone
        $card.find('.ve-sc-fav-btn').addClass('active');
        return $card;
    }

    function renderFavorites() {
        var favs    = getFavorites();
        var $section = $('#ve-sc-favorites-section');
        var $list   = $('#ve-sc-favorites-list').empty();

        if (!favs.length) {
            $section.hide();
            return;
        }

        var hasAny = false;
        favs.forEach(function (name) {
            var $card = buildFavCard(name);
            if ($card) { $list.append($card); hasAny = true; }
        });

        $section.toggle(hasAny);
    }

    function syncFavButtons() {
        var favs = getFavorites();
        $('.ve-sc-fav-btn').each(function () {
            var name = $(this).data('sc-name');
            $(this).toggleClass('active', favs.indexOf(name) !== -1);
        });
    }

    $(document).on('click', '.ve-sc-fav-btn', function (e) {
        e.stopPropagation();
        var name = $(this).data('sc-name');
        if (!name) return;

        var favs = getFavorites();
        var idx  = favs.indexOf(name);

        if (idx === -1) {
            favs.push(name);
        } else {
            favs.splice(idx, 1);
        }

        saveFavorites(favs);
        syncFavButtons();
        renderFavorites();
    });

    /* ── Expose globally for after-save refresh ──────────────────────── */
    window.veRenderCustomShortcodes = renderCustomShortcodes;

    /* ── Blocks in page list ─────────────────────────────────────────── */
    // querySelectorAll returns elements in DOM order (top to bottom) — no sorting needed.
    function refreshBlocksInPage() {
        var frame = window.parent ? window.parent.document.getElementById('ve-preview-frame') : null;
        var $list = $('#ve-blocks-in-page-list').empty();

        if (!frame || !frame.contentDocument) {
            $list.append('<p class="ve-blocks-empty-msg">Preview no disponible</p>');
            return;
        }

        var sentinels = frame.contentDocument.querySelectorAll('[data-ve-sc]');
        if (!sentinels.length) {
            $list.append('<p class="ve-blocks-empty-msg">No hay bloques en la página</p>');
            return;
        }

        sentinels.forEach(function (el, i) {
            var sc      = el.getAttribute('data-ve-sc') || '';
            var nodeId  = el.getAttribute('data-ve-node-id') || '';
            var decoded = $('<div>').html(sc).text();
            var match   = decoded.match(/^\[([a-z0-9_-]+)/i);
            var name    = match ? match[1] : 'bloque ' + (i + 1);
            var posNum  = i + 1;
            var isModified = nodeId && window._veModifiedSentinels.has(nodeId);

            var modifiedBadge = isModified
                ? '<span class="ve-block-modified-badge" title="Modificado sin guardar">●</span>'
                : '';

            var $btn = $('<button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start mb-1 ve-block-entry-btn">')
                .attr('data-sc-node-id', nodeId)
                .html(
                    '<span class="ve-block-position-num">#' + posNum + '</span>' +
                    '<i class="fa-solid fa-puzzle-piece me-1 ve-icon-primary"></i>' +
                    '<span>[' + name + ']</span>' +
                    modifiedBadge
                );

            $btn.on('click', function () {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function () {
                    var target = el.firstElementChild || el;
                    target.click();
                    if (window.parent && window.parent.$) {
                        window.parent.$('[data-panel="inspector"]').trigger('click');
                    }
                }, 300);
            });

            $list.append($btn);
        });
    }

    $('#btn-refresh-blocks').on('click', function (e) {
        e.stopPropagation();
        refreshBlocksInPage();
    });

    // Defer until all inline scripts (including ve-blocks-data) have run
    $(function () {
        renderHtmlBlocks();
        renderCustomShortcodes();
        setTimeout(refreshBlocksInPage, 2000);

        // Improvement 18: apply tooltips after all cards are rendered
        setTimeout(applyShortcodeTooltips, 100);

        // Improvement 20: load favorites from localStorage
        syncFavButtons();
        renderFavorites();
    });

})(jQuery);
</script>

@php
$scMetaMap = [];
foreach ($shortcodes as $sc) {
    $scMetaMap[$sc['name']] = $sc;
}
@endphp
<script>
window.veScMetaMap = @json($scMetaMap);
</script>

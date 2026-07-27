{{-- Modal: Recomendar producto de PrestaShop — Diseño C · dos columnas --}}
<div class="bv-modal" data-bv-modal-name="ps-product-recommend">
    <div class="bv-modal-dialog xl">

        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-gift"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · PRODUCTOS</span>
                <div class="bv-modal-title"><span>Recomendar producto</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body ps-modal-body">

            {{-- ── IZQUIERDA: buscador + lista ─────────────────────────────── --}}
            <div class="ps-list-pane">
                <div class="search-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="prProductSearch"
                           placeholder="Nombre, ID o referencia…" autocomplete="off">
                </div>
                <div class="ps-stock-filter">
                    <label class="ps-stock-label">
                        <input type="checkbox" id="prInStockOnly">
                        <span>Solo en stock</span>
                    </label>
                    <select id="prSortBy" class="ps-sort-select">
                        <option value="">Relevancia</option>
                        <option value="name_asc">Nombre A-Z</option>
                        <option value="name_desc">Nombre Z-A</option>
                        <option value="price_asc">Precio ↑</option>
                        <option value="price_desc">Precio ↓</option>
                        <option value="stock_desc">Stock disponible</option>
                    </select>
                    <select id="prCategoryFilter" class="ps-sort-select">
                        <option value="">Todas las categorías</option>
                    </select>
                </div>

                <div class="ps-sec-label">
                    <span id="prSectionTitle">Compras anteriores</span>
                    <span class="ct bv-hidden" id="prResultCount"></span>
                    <span class="ln"></span>
                </div>

                <div class="ps-prc-list ps-prc-list--full" id="prProductList">
                    <div class="bv-oc-loading">
                        <i class="fas fa-spinner fa-spin"></i> Cargando…
                    </div>
                </div>
            </div>

            {{-- ── DERECHA: detalle del producto seleccionado ───────────────── --}}
            <div class="ps-detail-pane">

                {{-- Estado vacío --}}
                <div class="ps-detail-empty" id="prDetailEmpty">
                    <i class="fas fa-hand-pointer"></i>
                    <div>Selecciona un producto de la lista</div>
                </div>

                {{-- Detalle del producto --}}
                <div class="bv-hidden" id="prDetailZone">

                    {{-- Card principal --}}
                    <div class="ps-pc">
                        <div class="ps-pc-top">
                            <div class="ps-pc-thumb" id="prDThumb"></div>
                            <div class="ps-pc-info">
                                <div class="ps-pc-nm" id="prDName"></div>
                                <div class="ps-pc-cat" id="prDCat"></div>
                            </div>
                            <div class="ps-pc-price" id="prDPrice"></div>
                        </div>
                        <hr class="ps-pc-hr">
                        <div class="ps-pc-rows" id="prDRows"></div>
                    </div>

                    {{-- Atributos / combinaciones --}}
                    <div class="ps-attr-block bv-hidden" id="prAttrBlock">
                        <div class="ps-sec-label ps-sec-label--combos">
                            <span>Combinaciones</span>
                            <span class="ct bv-hidden" id="prComboCount"></span>
                            <span class="ln"></span>
                        </div>
                        <p class="ps-combos-desc">Selecciona una variante para ver su referencia y disponibilidad.</p>
                        <div id="prAttrGroups"></div>
                        <div class="ps-attr-sel bv-hidden" id="prAttrSel">
                            <span>Combinación:</span>
                            <span class="ref" id="prAttrSelRef"></span>
                            <span class="sp"></span>
                            <span class="ps-prc-stock" id="prAttrSelStock"></span>
                        </div>
                    </div>

                    {{-- Precios por volumen --}}
                    <div id="prVolBlock"></div>

                    {{-- Alternativas relacionadas --}}
                    <div class="ps-alt-block bv-hidden" id="prAltBlock">
                        <div class="ps-sec-label">
                            <span>Alternativas relacionadas</span>
                            <span class="ln"></span>
                        </div>
                        <div class="ps-alt-list" id="prAltList"></div>
                    </div>

                    {{-- Historial de recomendaciones previas --}}
                    <div class="ps-alt-block bv-hidden" id="prHistBlock">
                        <div class="ps-sec-label">
                            <span>Ya recomendados</span>
                            <span class="ln"></span>
                        </div>
                        <div class="ps-hist-list" id="prHistList"></div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Footer sin selección --}}
        <div class="bv-modal-foot" id="prFootDefault">
            <button class="btn-secondary" data-bv-close><i class="fas fa-xmark"></i>Cerrar</button>
        </div>

        {{-- Footer con producto seleccionado --}}
        <div class="bv-modal-foot bv-hidden" id="prFootSelected">
            <div class="ps-note-wrap" id="prNoteWrap">
                <textarea class="ps-note-input" id="prInternalNote" rows="2" placeholder="Nota interna (opcional)…"></textarea>
            </div>
            <div class="ps-footer-actions">
                <button class="btn-secondary" id="prSendToChat" type="button"><i class="fas fa-paper-plane"></i>Recomendar en chat</button>
                <button class="ps-email-btn" id="prSendEmail" type="button"><i class="fas fa-envelope"></i> Email</button>
            </div>
            <div class="ps-qty-wrap">
                <button class="ps-qty-btn" id="prQtyMinus" type="button"><i class="fas fa-minus"></i></button>
                <input class="ps-qty-input" id="prQtyInput" type="number" value="1" min="1" max="999" readonly>
                <button class="ps-qty-btn" id="prQtyPlus" type="button"><i class="fas fa-plus"></i></button>
                <button class="btn-primary" id="prAddToCart" type="button"><i class="fas fa-cart-plus"></i>Añadir al carrito</button>
            </div>
            <button class="btn-secondary" data-bv-close type="button"><i class="fas fa-xmark"></i>Cerrar</button>
        </div>

    </div>
</div>

@once

@push('scripts')
<script>
(function () {
    var _timer              = null;
    var _searchTimer        = null;  // Mejora 4: debounce búsqueda en stock filter
    var _pool               = [];
    var _selected           = null;
    var _selectedCombo      = null;  // combinación actualmente activa
    var _combinations       = [];
    var _selAttrs           = {};
    var _qty                = 1;
    var _searchQuery        = '';
    var _searchOffset       = 0;
    var _hasMore            = false;
    var _convRecsLoaded     = false;
    var _sessionCachePrefix = 'ps-search-';  // MEJORA 9: cache sessionStorage

    // ── Helpers ───────────────────────────────────────────────────────────────

    function stockLabel(p) {
        if (!p.in_stock) { return { text: 'Sin stock', cls: 'out' }; }
        var qty = parseInt(p.stock, 10);
        if (qty > 0 && qty < 99999) {
            return { text: qty + ' ud.', cls: qty < 20 ? 'low' : 'in' };
        }
        return { text: 'En stock', cls: 'in' };
    }

    function money(v) {
        return parseFloat(v).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = String(s || '');
        return d.innerHTML;
    }

    function safeImg(url, alt) {
        return '<img src="' + esc(url) + '" alt="' + esc(alt || '') + '" loading="lazy" class="ps-img-safe">';
    }

    // ── MEJORA 1: Descripción truncada expandible ─────────────────────────────

    function truncatedDesc(text, maxLen) {
        maxLen = maxLen || 120;
        if (!text || text.length <= maxLen) { return text ? esc(text) : ''; }
        return '<span class="ps-desc-short">' + esc(text.substring(0, maxLen)) + '… ' +
            '<button class="ps-desc-toggle" type="button">Ver más</button></span>' +
            '<span class="ps-desc-full bv-hidden">' + esc(text) +
            ' <button class="ps-desc-toggle" type="button">Ver menos</button></span>';
    }

    // ── MEJORA 9: Cache de sesión ─────────────────────────────────────────────

    function getSessionCache(key) {
        try {
            var raw = sessionStorage.getItem(_sessionCachePrefix + key);
            if (!raw) { return null; }
            var entry = JSON.parse(raw);
            if (Date.now() - entry.ts > 120000) {
                sessionStorage.removeItem(_sessionCachePrefix + key);
                return null;
            }
            return entry.data;
        } catch (e) { return null; }
    }

    function setSessionCache(key, data) {
        try {
            sessionStorage.setItem(_sessionCachePrefix + key, JSON.stringify({ ts: Date.now(), data: data }));
        } catch (e) {}
    }

    // ── Render: lista de productos ────────────────────────────────────────────

    // ── Mejora 8: Estado vacío con contexto según query ───────────────────────

    function emptyStateHtml(query) {
        var tips = '';
        var clean = (query || '').replace(/[\s\-]/g, '');
        if (/^\d{8,14}$/.test(clean)) {
            tips = '<small>Parece un EAN13. Prueba sin espacios ni guiones.</small>';
        } else if (/^\d+$/.test(query)) {
            tips = '<small>¿Buscas por ID de producto? Prueba con el número exacto.</small>';
        } else if (query && query.length < 3) {
            tips = '<small>Escribe al menos 3 caracteres para buscar.</small>';
        } else if (query) {
            tips = '<small>Prueba con el nombre, referencia o código EAN13.</small>';
        } else {
            tips = '<small>Escribe el nombre, referencia o SKU del producto.</small>';
        }
        return '<div class="ps-empty"><i class="fas fa-search"></i><span>Sin resultados</span>' + tips + '</div>';
    }

    // ── Mejora 9: Búsqueda reciente en localStorage ──────────────────────────

    function saveRecentSearch(q) {
        if (!q || q.length < 2) { return; }
        try {
            var key   = 'ps-recent-searches';
            var saved = JSON.parse(localStorage.getItem(key) || '[]');
            saved = [q].concat(saved.filter(function (s) { return s !== q; })).slice(0, 5);
            localStorage.setItem(key, JSON.stringify(saved));
        } catch (e) {}
    }

    function getRecentSearches() {
        try {
            return JSON.parse(localStorage.getItem('ps-recent-searches') || '[]');
        } catch (e) { return []; }
    }

    function renderList(products, hasMore) {
        _pool    = (_searchOffset > 0) ? _pool.concat(products || []) : (products || []);
        _hasMore = !!hasMore;
        var $c   = $('#prProductList');
        if (!_pool.length) {
            $c.html(emptyStateHtml(_searchQuery));
            return;
        }
        // Guardar búsqueda reciente cuando hay resultados (Mejora 9)
        if (_searchQuery) { saveRecentSearch(_searchQuery); }
        var html = _pool.map(function (p, i) {
            var thumb = p.image
                ? '<span class="ps-prc-thumb">' + safeImg(p.image) + '</span>'
                : '<span class="ps-prc-thumb"><i class="fas fa-box"></i></span>';
            var sk = stockLabel(p);
            var on = (_selected && _selected.id === p.id) ? ' on' : '';
            var skuEl = p.sku ? '<small>' + esc(p.sku) + '</small>' : '';
            return '<button class="ps-prc-item' + on + '" type="button" data-idx="' + i + '">' +
                thumb +
                '<span class="nm">' + esc(p.name) + skuEl + '</span>' +
                '<span class="ps-prc-stock ' + sk.cls + '">' + sk.text + '</span>' +
            '</button>';
        }).join('');
        if (_hasMore) {
            html += '<button class="ps-load-more" type="button"><i class="fas fa-chevron-down"></i> Ver más resultados</button>';
        }
        $c.html(html);
    }

    // ── Mejora 6: renderListFiltered — no modifica _pool ────────────────────

    function renderListFiltered(products, hasMore) {
        var $c = $('#prProductList');
        if (!products.length) {
            $c.html(emptyStateHtml(_searchQuery));
            return;
        }
        var html = products.map(function (p) {
            var i     = _pool.indexOf(p);
            var thumb = p.image
                ? '<span class="ps-prc-thumb">' + safeImg(p.image) + '</span>'
                : '<span class="ps-prc-thumb"><i class="fas fa-box"></i></span>';
            var sk    = stockLabel(p);
            var on    = (_selected && _selected.id === p.id) ? ' on' : '';
            var skuEl = p.sku ? '<small>' + esc(p.sku) + '</small>' : '';
            return '<button class="ps-prc-item' + on + '" type="button" data-idx="' + i + '">' +
                thumb +
                '<span class="nm">' + esc(p.name) + skuEl + '</span>' +
                '<span class="ps-prc-stock ' + sk.cls + '">' + sk.text + '</span>' +
            '</button>';
        }).join('');
        if (hasMore) {
            html += '<button class="ps-load-more" type="button"><i class="fas fa-chevron-down"></i> Ver más resultados</button>';
        }
        $c.html(html);
    }

    // ── Render: card de detalle básico ────────────────────────────────────────

    function renderDetail(p) {
        // Thumb
        $('#prDThumb').html(p.image ? safeImg(p.image) : '<i class="fas fa-box"></i>');

        // Cabecera
        $('#prDName').text(p.name);
        var cat = [p.brand, p.category].filter(Boolean).join(' · ');
        $('#prDCat').text(cat).toggleClass('bv-hidden', !cat);

        // Precio con soporte de descuento
        var displayPrice = p.price_with_tax > 0 ? p.price_with_tax : p.price;
        var priceHtml = '';
        if (p.has_discount && p.price_original && p.price_original > displayPrice) {
            priceHtml = '<span class="ps-orig-price">' + money(p.price_original) + '</span>' +
                        '<span class="ps-final-price">' + money(displayPrice) + '</span>';
        } else {
            priceHtml = displayPrice > 0 ? money(displayPrice) : '';
        }

        // Mejora 3: Badge stock bajo
        var stockQty = parseInt(p.stock, 10);
        var stockBadge = '';
        if (p.in_stock && stockQty > 0 && stockQty < 5) {
            stockBadge = '<span class="ps-stock-warn"><i class="fas fa-exclamation-triangle"></i> ¡Solo quedan ' + stockQty + '!</span>';
        }
        $('#prDPrice').html(priceHtml + stockBadge);

        // Mejora 5: Botón copiar ficha completa + MEJORA 2: Botón PS Admin
        var psAdminUrl = 'http://localhost:8091/adminalsernet1/index.php?controller=AdminProducts&id_product=' + p.id + '&updateproduct';
        var adminBtn = '<a class="ps-admin-btn" href="' + psAdminUrl + '" target="_blank" rel="noopener" title="Editar en PrestaShop"><i class="fas fa-external-link-alt"></i> PS Admin</a>';
        var copyFichaBtn = '<div class="ps-ficha-actions">' +
            '<button class="ps-copy-ficha" type="button" title="Copiar ficha completa"><i class="fas fa-clipboard"></i> Copiar ficha</button>' +
            adminBtn +
            '</div>';

        // Specs
        var sk = stockLabel(p);
        var barW = p.in_stock ? (parseInt(p.stock, 10) >= 99999 ? 100 : Math.min(100, Math.max(5, p.stock))) : 0;
        var specs = [];
        if (p.id) { specs.push(spec('fa-hashtag', 'ID producto', '#' + p.id, 'mono')); }
        if (p.sku) {
            specs.push(
                '<div class="ps-spec">' +
                '<span class="ic"><i class="fas fa-barcode"></i></span>' +
                '<span class="lbl">Referencia</span>' +
                '<span class="val mono" id="prSpecRef">' + esc(p.sku) + '</span>' +
                '<button class="ps-copy-btn" type="button" data-copy-target="prSpecRef" title="Copiar"><i class="fas fa-copy"></i></button>' +
                '</div>'
            );
        }
        if (p.ean13) {
            specs.push(
                '<div class="ps-spec">' +
                '<span class="ic"><i class="fas fa-qrcode"></i></span>' +
                '<span class="lbl">EAN13</span>' +
                '<span class="val mono" id="prSpecEan">' + esc(p.ean13) + '</span>' +
                '<button class="ps-copy-btn" type="button" data-copy-target="prSpecEan" title="Copiar"><i class="fas fa-copy"></i></button>' +
                '</div>'
            );
        }
        specs.push(
            '<div class="ps-spec"><span class="ic"><i class="fas fa-box"></i></span>' +
            '<span class="lbl">Stock</span>' +
            '<div class="stockwrap"><div class="bar"><span class="ps-spec-stk-bar" style="width:' + barW + '%"></span></div>' +
            '<span class="ps-spec-stk ' + (p.in_stock ? 'ok' : 'ko') + '">' + sk.text + '</span></div></div>'
        );
        if (p.brand)    { specs.push(spec('fa-tag',         'Marca',     p.brand)); }
        if (p.category) { specs.push(spec('fa-layer-group', 'Categoría', p.category)); }
        if (p.price > 0 && p.tax_rate > 0) {
            var taxAmt = p.price * p.tax_rate / 100;
            if (p.has_discount && p.price_original) {
                specs.push(
                    '<div class="ps-spec ps-spec--orig">' +
                    '<span class="ic"><i class="fas fa-tag"></i></span>' +
                    '<span class="lbl">Precio original</span>' +
                    '<span class="val ps-crossed">' + money(p.price_original) + '</span></div>'
                );
            }
            specs.push('<div class="ps-spec" id="prSpecPriceRow"><span class="ic"><i class="fas fa-euro-sign"></i></span><span class="lbl">Precio sin IVA</span><span class="val" id="prSpecPriceBase">' + money(p.price) + '</span></div>');
            specs.push('<div class="ps-spec" id="prSpecIvaRow"><span class="ic"><i class="fas fa-percent"></i></span><span class="lbl">IVA (<span id="prSpecIvaPct">' + p.tax_rate.toFixed(0) + '</span>%)</span><span class="val" id="prSpecIvaAmt">' + money(taxAmt) + '</span></div>');
            specs.push('<div class="ps-spec ps-spec--total" id="prSpecTotalRow"><span class="ic"><i class="fas fa-receipt"></i></span><span class="lbl">Precio con IVA</span><span class="val" id="prSpecPriceTotal">' + money(p.price_with_tax) + '</span></div>');
        } else {
            specs.push(spec('fa-euro-sign', 'Precio', money(p.price)));
        }
        // MEJORA 1: descripción expandible debajo de specs
        var descHtml = '';
        if (p.description) {
            descHtml = '<div class="ps-desc-block">' + truncatedDesc(p.description) + '</div>';
        }
        $('#prDRows').html('<div class="ps-specs">' + specs.join('') + '</div>' + descHtml + copyFichaBtn);

        $('#prDetailEmpty').addClass('bv-hidden');
        $('#prDetailZone').removeClass('bv-hidden');
        $('#prFootDefault').addClass('bv-hidden');
        $('#prFootSelected').removeClass('bv-hidden');

        // MEJORA 4: Precios por volumen
        $('#prVolBlock').html('');
        loadVolumePrices(p.id);

        // Mejora 7: Alternativas del mismo fabricante vía AJAX
        loadAlternatives(p);
    }

    // ── Mejora 7: Cargar alternativas del mismo fabricante ────────────────────

    function loadAlternatives(product) {
        var base = HDCommerce.base();
        if (!base || !product.brand) {
            $('#prAltBlock').addClass('bv-hidden');
            return;
        }
        $('#prAltBlock').addClass('bv-hidden');
        $.ajax({
            url: base + '/ps/products/' + product.id + '/alternatives',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (r) {
            if (r.success && r.products && r.products.length) {
                var html = r.products.map(function (p) {
                    var thumb = p.image ? '<img class="ps-img-safe" src="' + esc(p.image) + '" loading="lazy">' : '<i class="fas fa-box"></i>';
                    var price = p.price_with_tax > 0 ? money(p.price_with_tax) : '';
                    var stTxt = p.in_stock ? '' : ' · Sin stock';
                    return '<button class="ps-alt-row" type="button" data-pid="' + p.id + '">' +
                        '<span class="at">' + thumb + '</span>' +
                        '<span class="ab">' +
                            '<span class="an">' + esc(p.name) + '</span>' +
                            '<span class="ar">' + (p.sku ? esc(p.sku) + ' · ' : '') + price + stTxt + '</span>' +
                        '</span>' +
                    '</button>';
                }).join('');
                $('#prAltList').html(html);
                $('#prAltBlock').removeClass('bv-hidden');
            } else {
                // Fallback: mostrar alternativas del pool local
                var alts = _pool.filter(function (x) { return x.id !== product.id; }).slice(0, 3);
                if (alts.length) {
                    $('#prAltList').html(alts.map(function (a) {
                        var ask     = stockLabel(a);
                        var refPart = a.sku ? 'Ref: ' + esc(a.sku) + ' · ' : '';
                        var stPart  = '<span class="' + ask.cls + '">' + ask.text + '</span>';
                        var thumbEl = a.image ? safeImg(a.image) : '<i class="fas fa-box"></i>';
                        return '<button class="ps-alt-row" type="button" data-idx="' + _pool.indexOf(a) + '">' +
                            '<span class="at">' + thumbEl + '</span>' +
                            '<span class="ab">' +
                                '<span class="an">' + esc(a.name) + '</span>' +
                                '<span class="ar">' + refPart + stPart + '</span>' +
                            '</span>' +
                            (a.price > 0 ? '<span class="ap">' + money(a.price) + '</span>' : '') +
                            '<span class="add"><i class="fas fa-plus"></i></span>' +
                        '</button>';
                    }).join(''));
                    $('#prAltBlock').removeClass('bv-hidden');
                }
            }
        }).fail(function () {
            // Fallback silencioso al pool local
            var alts = _pool.filter(function (x) { return x.id !== product.id; }).slice(0, 3);
            if (alts.length) {
                $('#prAltList').html(alts.map(function (a) {
                    var ask     = stockLabel(a);
                    var refPart = a.sku ? 'Ref: ' + esc(a.sku) + ' · ' : '';
                    var stPart  = '<span class="' + ask.cls + '">' + ask.text + '</span>';
                    var thumbEl = a.image ? safeImg(a.image) : '<i class="fas fa-box"></i>';
                    return '<button class="ps-alt-row" type="button" data-idx="' + _pool.indexOf(a) + '">' +
                        '<span class="at">' + thumbEl + '</span>' +
                        '<span class="ab">' +
                            '<span class="an">' + esc(a.name) + '</span>' +
                            '<span class="ar">' + refPart + stPart + '</span>' +
                        '</span>' +
                        (a.price > 0 ? '<span class="ap">' + money(a.price) + '</span>' : '') +
                        '<span class="add"><i class="fas fa-plus"></i></span>' +
                    '</button>';
                }).join(''));
                $('#prAltBlock').removeClass('bv-hidden');
            }
        });
    }

    // ── MEJORA 4: Precios por volumen ────────────────────────────────────────

    function loadVolumePrices(productId) {
        var base = HDCommerce.base();
        if (!base) { return; }
        $.ajax({
            url: base + '/ps/products/' + productId + '?include_volume_prices=1',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (r) {
            var vp = r.product && r.product.volume_prices;
            if (!vp || !vp.length) { return; }
            var html = '<div class="ps-vol-block">' +
                '<div class="ps-sec-label"><span>Precios por cantidad</span><span class="ln"></span></div>' +
                '<table class="ps-vol-table"><tr><th>Desde</th><th>Precio/ud</th><th>Ahorro</th></tr>' +
                vp.map(function (v) {
                    var saving = _selected && _selected.price_with_tax > v.price_with_tax
                        ? '−' + money(_selected.price_with_tax - v.price_with_tax) + '/ud'
                        : '';
                    return '<tr><td>' + v.from_quantity + '+ uds.</td><td>' + money(v.price_with_tax) + '</td><td class="ps-vol-save">' + saving + '</td></tr>';
                }).join('') +
                '</table></div>';
            $('#prVolBlock').html(html);
        });
    }

    // ── MEJORA 6: Cargar categorías para filtro ──────────────────────────────

    function loadCategories() {
        if ($('#prCategoryFilter option').length > 1) { return; }
        $.ajax({
            url: '/panel/helpdesk/ps/categories',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (r) {
            if (r.success && r.categories) {
                var opts = r.categories.map(function (c) {
                    return '<option value="' + c.id + '">' + esc(c.name) + '</option>';
                }).join('');
                $('#prCategoryFilter').append(opts);
            }
        });
    }

    function spec(icon, label, value, cls) {
        var v = cls === 'mono'
            ? '<span class="val mono">' + esc(value) + '</span>'
            : '<span class="val">' + esc(value) + '</span>';
        return '<div class="ps-spec"><span class="ic"><i class="fas ' + icon + '"></i></span><span class="lbl">' + esc(label) + '</span>' + v + '</div>';
    }

    // ── Actualiza el bloque de precio cuando cambia la combinación ────────────

    function updatePriceForCombo(combo) {
        if (!_selected || !combo) { return; }
        var base     = _selected.price + (combo.price_delta || 0);
        var taxRate  = _selected.tax_rate || 0;
        var taxAmt   = base * taxRate / 100;
        var total    = taxRate > 0 ? base * (1 + taxRate / 100) : base;
        var priceHtml = '';
        if (_selected.has_discount && _selected.price_original && _selected.price_original > total) {
            priceHtml = '<span class="ps-orig-price">' + money(_selected.price_original) + '</span>' +
                        '<span class="ps-final-price">' + money(total) + '</span>';
        } else {
            priceHtml = total > 0 ? money(total) : '';
        }
        $('#prDPrice').html(priceHtml);
        $('#prSpecPriceBase').text(money(base));
        $('#prSpecIvaAmt').text(money(taxAmt));
        $('#prSpecPriceTotal').text(money(total));
    }

    // ── Render: atributos / combinaciones ─────────────────────────────────────

    function renderAttributes(attributes, combinations) {
        _combinations  = combinations || [];
        _selectedCombo = null;
        _selAttrs      = {};
        $('#prAttrSel').addClass('bv-hidden');

        if (!_combinations.length) {
            $('#prAttrBlock').addClass('bv-hidden');
            $('#prComboCount').addClass('bv-hidden');
            return;
        }

        $('#prComboCount').text(_combinations.length).removeClass('bv-hidden');

        var baseTaxRate = (_selected && _selected.tax_rate) ? _selected.tax_rate : 0;
        var html = _combinations.map(function (c) {
            var sw = c.color
                ? '<span class="combo-sw" style="background:' + esc(c.color) + '"></span>'
                : '';
            var qty      = parseInt(c.stock, 10);
            var stockTxt = c.in_stock ? (qty < 99999 ? qty + ' ud.' : 'En stock') : 'Agotado';
            var stockCls = c.in_stock ? 'ok' : 'out';
            var disCls   = c.in_stock ? '' : ' dis';
            var comboPricePart = '';
            if (_selected && _selected.price > 0) {
                var combBase   = _selected.price + (c.price_delta || 0);
                var comboFinal = baseTaxRate > 0 ? combBase * (1 + baseTaxRate / 100) : combBase;
                comboPricePart = '<span class="cpr">' + money(comboFinal) + '</span>';
            }
            // Mejora 2: indicador de stock junto al label
            var comboStockBadge = '';
            var comboStockQty   = parseInt(c.stock, 10);
            if (c.stock !== null && c.stock !== undefined && !isNaN(comboStockQty) && comboStockQty > 0) {
                comboStockBadge = '<span class="ps-combo-stock ps-combo-stock--ok">(' + comboStockQty + ' uds.)</span>';
            } else if (comboStockQty === 0 && !c.in_stock) {
                comboStockBadge = '<span class="ps-combo-stock ps-combo-stock--out">Sin stock</span>';
            }
            return '<button class="combo-row' + disCls + '" type="button" data-cid="' + c.id + '">' +
                '<span class="radio"></span>' +
                sw +
                '<span class="cb">' +
                    '<span class="cn">' + esc(c.label || 'Combinación') + comboStockBadge + '</span>' +
                    (c.reference ? '<span class="cr">' + esc(c.reference) + '</span>' : '') +
                '</span>' +
                '<span class="cstock ' + stockCls + '">' + stockTxt + '</span>' +
                comboPricePart +
            '</button>';
        }).join('');

        $('#prAttrGroups').html(html);
        $('#prAttrBlock').removeClass('bv-hidden');

        // Auto-seleccionar la primera combinación disponible
        var firstAvailable = _combinations.find(function (c) { return c.in_stock; });
        if (firstAvailable) {
            var $first = $('#prAttrGroups .combo-row[data-cid="' + firstAvailable.id + '"]');
            if ($first.length) { $first.trigger('click'); }
        }
    }

    // ── Carga de atributos por AJAX al seleccionar ────────────────────────────

    function fetchAttributes(productId) {
        var base = HDCommerce.base();
        if (!base) { return; }
        $('#prAttrBlock').addClass('bv-hidden');
        HDCommerce.ajax({ url: base + '/ps/products/' + productId, method: 'GET' })
            .done(function (r) {
                if (r.success) {
                    renderAttributes(r.attributes, r.combinations);
                }
            });
    }

    // ── Seleccionar un producto ───────────────────────────────────────────────

    function selectProduct(p) {
        _selected      = p;
        _selectedCombo = null;
        $('#prProductList .ps-prc-item').each(function (i) {
            $(this).toggleClass('on', !!(_pool[i] && _pool[i].id === p.id));
        });
        renderDetail(p);
        fetchAttributes(p.id);
    }

    // ── Deseleccionar ────────────────────────────────────────────────────────

    function clearSelection() {
        _selected      = null;
        _selectedCombo = null;
        _combinations  = [];
        _selAttrs      = {};
        _qty           = 1;
        _searchQuery   = '';
        _searchOffset  = 0;
        _hasMore       = false;
        _convRecsLoaded = false;
        $('#prQtyInput').val(1);
        $('#prInternalNote').val('');  // MEJORA 8: limpiar nota interna
        $('#prVolBlock').html('');     // MEJORA 4: limpiar precios por volumen
        $('#prProductList .ps-prc-item').removeClass('on');
        $('#prDetailZone').addClass('bv-hidden');
        $('#prDetailEmpty').removeClass('bv-hidden');
        $('#prAttrBlock, #prAltBlock, #prHistBlock').addClass('bv-hidden');
        $('#prComboCount').addClass('bv-hidden');
        $('#prFootDefault').removeClass('bv-hidden');
        $('#prFootSelected').addClass('bv-hidden');
    }

    // ── Fallback de imagen rota → icono ───────────────────────────────────────

    $(document).on('error', '.ps-img-safe', function () {
        var $wrap = $(this).closest('.ps-prc-thumb, .ps-pc-thumb, .at');
        if ($wrap.length) {
            $wrap.html('<i class="fas fa-box"></i>');
        } else {
            $(this).replaceWith('<i class="fas fa-box"></i>');
        }
    });

    // ── Carga de recomendados / historial ─────────────────────────────────────

    function loadRecommended() {
        var base = HDCommerce.base();
        if (!base) { return; }
        $('#prSectionTitle').text('Compras anteriores');
        $('#prResultCount').addClass('bv-hidden');
        $('#prProductList').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        HDCommerce.ajax({ url: base + '/ps/products', method: 'GET' })
            .done(function (r) { renderList(r.products || [], r.has_more); })
            .fail(function () {
                $('#prProductList').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">Error al cargar</div></div>');
            });
    }

    // ── Historial de recomendaciones de la conversación ───────────────────────

    function loadConversationHistory() {
        var convId = HDCommerce.conversationId();
        var base   = HDCommerce.base();
        if (!convId || !base || _convRecsLoaded) { return; }
        var url = '/panel/helpdesk/conversations/' + convId + '/ps/recommendations';
        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (r) {
            _convRecsLoaded = true;
            if (r.success && r.recommendations && r.recommendations.length) {
                renderHistory(r.recommendations);
            }
        });
    }

    function renderHistory(recs) {
        if (!recs || !recs.length) { return; }
        var html = recs.map(function (r) {
            var thumbEl = r.product_image ? safeImg(r.product_image) : '<i class="fas fa-box"></i>';
            return '<div class="ps-hist-row">' +
                '<span class="at">' + thumbEl + '</span>' +
                '<span class="ab">' +
                    '<span class="an">' + esc(r.product_name) + '</span>' +
                    '<span class="ar">' + (r.product_sku ? 'Ref: ' + esc(r.product_sku) + ' · ' : '') +
                        (r.price_with_tax > 0 ? money(r.price_with_tax) : '') + '</span>' +
                '</span>' +
            '</div>';
        }).join('');
        $('#prHistList').html(html);
        $('#prHistBlock').removeClass('bv-hidden');
    }

    // ── Búsqueda con debounce ─────────────────────────────────────────────────

    $(document).on('input', '#prProductSearch', function () {
        var q = $(this).val().trim();
        clearTimeout(_timer);
        clearSelection();
        if (q.length < 2) {
            loadRecommended();
            return;
        }
        _timer = setTimeout(function () {
            var base = HDCommerce.base();
            if (!base) { return; }
            _searchQuery  = q;
            _searchOffset = 0;
            $('#prSortBy').val('');  // MEJORA 5: resetear orden en nueva búsqueda
            $('#prSectionTitle').text('Resultados');
            $('#prResultCount').text('…').removeClass('bv-hidden');
            // MEJORA 9: verificar cache antes de AJAX
            var cacheKey = q + '|0';
            var cached   = getSessionCache(cacheKey);
            if (cached) {
                $('#prResultCount').text((cached.products || []).length);
                renderList(cached.products || [], cached.has_more);
                return;
            }
            $('#prProductList').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Buscando…</div>');
            HDCommerce.ajax({ url: base + '/ps/products', method: 'GET', data: { q: q } })
                .done(function (r) {
                    var prods = r.products || [];
                    $('#prResultCount').text(prods.length);
                    setSessionCache(cacheKey, { products: prods, has_more: r.has_more });
                    renderList(prods, r.has_more);
                })
                .fail(function () {
                    $('#prProductList').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">Error en la búsqueda</div></div>');
                    $('#prResultCount').addClass('bv-hidden');
                });
        }, 250);
    });

    // ── Seleccionar producto de la lista ──────────────────────────────────────

    $(document).on('click', '.ps-prc-item', function () {
        var idx = parseInt($(this).attr('data-idx'), 10);
        if (_pool[idx]) { selectProduct(_pool[idx]); }
    });

    // ── Seleccionar alternativa (pool local o AJAX por pid) ───────────────────

    $(document).on('click', '.ps-alt-row', function () {
        var pid = $(this).attr('data-pid');
        if (pid) {
            // Alternativa cargada vía AJAX (Mejora 7): buscar en pool o cargar detalle
            pid = parseInt(pid, 10);
            var fromPool = _pool.find(function (p) { return p.id === pid; });
            if (fromPool) {
                selectProduct(fromPool);
            } else {
                // No está en el pool: cargar directamente vía API
                var base = HDCommerce.base();
                if (!base) { return; }
                HDCommerce.ajax({ url: base + '/ps/products/' + pid, method: 'GET' })
                    .done(function (r) {
                        if (r.success && r.product) {
                            _pool.push(r.product);
                            selectProduct(r.product);
                        }
                    });
            }
            return;
        }
        var idx = parseInt($(this).attr('data-idx'), 10);
        if (_pool[idx]) { selectProduct(_pool[idx]); }
    });

    // ── Ver más resultados ────────────────────────────────────────────────────

    $(document).on('click', '.ps-load-more', function () {
        if (!_hasMore) { return; }
        _searchOffset += 10;
        var base = HDCommerce.base();
        if (!base) { return; }
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Cargando…').prop('disabled', true);
        // MEJORA 9: cache para paginación
        var cacheKey = _searchQuery + '|' + _searchOffset;
        var cached   = getSessionCache(cacheKey);
        if (cached) {
            renderList(cached.products || [], cached.has_more);
            return;
        }
        HDCommerce.ajax({ url: base + '/ps/products', method: 'GET', data: { q: _searchQuery, offset: _searchOffset } })
            .done(function (r) {
                setSessionCache(cacheKey, { products: r.products || [], has_more: r.has_more });
                renderList(r.products || [], r.has_more);
            });
    });

    // ── Combinaciones: seleccionar fila ──────────────────────────────────────

    $(document).on('click', '.combo-row:not(.dis)', function () {
        var $row = $(this);
        $('#prAttrGroups .combo-row').removeClass('on');
        $row.addClass('on');

        var cid   = parseInt($row.attr('data-cid'), 10);
        var combo = _combinations.find(function (c) { return c.id === cid; });
        if (!combo) { return; }

        _selectedCombo = combo;

        // Actualizar imagen con la de la combinación si existe
        if (combo.image) {
            $('#prDThumb').html(safeImg(combo.image));
        } else if (_selected && _selected.image) {
            $('#prDThumb').html(safeImg(_selected.image));
        }

        // Stock
        var qty      = parseInt(combo.stock, 10);
        var stockTxt = combo.in_stock ? (qty < 99999 ? qty + ' ud.' : 'En stock') : 'Sin stock';
        var stkCls   = combo.in_stock ? 'ok' : 'ko';
        var barW     = combo.in_stock ? (qty >= 99999 ? 100 : Math.min(100, Math.max(5, qty))) : 0;
        $('.ps-spec-stk').text(stockTxt).removeClass('ok ko').addClass(stkCls);
        $('.ps-spec-stk-bar').css('width', barW + '%');

        // Referencia
        if (combo.reference && $('#prSpecRef').length) {
            $('#prSpecRef').text(combo.reference);
        }

        // Precio de la combinación (actualiza cabecera y desglose)
        updatePriceForCombo(combo);

        // Indicador bajo la lista de combinaciones
        if (combo.reference) {
            $('#prAttrSelRef').text(combo.reference);
            $('#prAttrSelStock').text(stockTxt).removeClass('in out').addClass(combo.in_stock ? 'in' : 'out');
            $('#prAttrSel').removeClass('bv-hidden');
        } else {
            $('#prAttrSel').addClass('bv-hidden');
        }
    });

    // ── Deseleccionar ────────────────────────────────────────────────────────

    $(document).on('click', '#prClearSelection', clearSelection);

    // ── Selector de cantidad ──────────────────────────────────────────────────

    $(document).on('click', '#prQtyMinus', function () {
        if (_qty > 1) { _qty--; $('#prQtyInput').val(_qty); }
    });

    $(document).on('click', '#prQtyPlus', function () {
        if (_qty < 999) { _qty++; $('#prQtyInput').val(_qty); }
    });

    // ── Click-to-copy ─────────────────────────────────────────────────────────

    $(document).on('click', '.ps-copy-btn', function () {
        var targetId = $(this).attr('data-copy-target');
        var text = $('#' + targetId).text().trim();
        if (!text) { return; }
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Copiado: ' + text, '', { timeOut: 1500 });
        }).catch(function () {
            var el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            toastr.success('Copiado: ' + text, '', { timeOut: 1500 });
        });
    });

    // ── Recomendar en chat ────────────────────────────────────────────────────

    $(document).on('click', '#prSendToChat', function () {
        if (!_selected) { return; }
        var url = (_selected.url || '').trim();
        if (!url) { toastr.warning('Este producto no tiene URL disponible.'); return; }
        // Añadir id_product_attribute si hay una combinación seleccionada
        if (_selectedCombo) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'id_product_attribute=' + _selectedCombo.id;
        }

        // Mensaje enriquecido
        var priceStr = _selected.price_with_tax > 0 ? money(_selected.price_with_tax) : '';
        var refStr   = (_selectedCombo && _selectedCombo.reference)
            ? _selectedCombo.reference
            : (_selected.sku || '');
        var msg = _selected.name;
        if (refStr) { msg += ' (' + refStr + ')'; }
        if (priceStr) { msg += ' — ' + priceStr; }
        msg += '\n' + url;

        var ta = document.querySelector('.bv-composer-input');
        if (ta) {
            ta.value = msg;
            ta.focus();
            ta.dispatchEvent(new Event('input'));
        }

        // Guardar en historial de la conversación
        var convId = HDCommerce.conversationId();
        if (convId && _selected) {
            $.ajax({
                url: '/panel/helpdesk/conversations/' + convId + '/ps/recommendations',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                contentType: 'application/json',
                data: JSON.stringify({
                    product_id:           _selected.id,
                    id_product_attribute: _selectedCombo ? _selectedCombo.id : null,
                    product_name:         _selected.name,
                    product_sku:          (_selectedCombo && _selectedCombo.reference) || _selected.sku || null,
                    price_with_tax:       _selected.price_with_tax || 0,
                    product_url:          url,
                    product_image:        (_selectedCombo && _selectedCombo.image) || _selected.image || null,
                }),
            });
        }

        // MEJORA 8: nota interna opcional
        var note = $('#prInternalNote').val().trim();
        if (note) {
            $(document).trigger('helpdesk:internal-note', {
                conversationId: HDCommerce.conversationId(),
                text: '[PS] Nota sobre producto recomendado (' + (_selected ? _selected.name : '') + '): ' + note,
            });
        }
        $('#prInternalNote').val('');

        HDCommerce.close('ps-product-recommend');
        toastr.success('Producto insertado en el chat.');
    });

    // ── Añadir al carrito — Mejora 1: spinner + "Añadido ✓" ──────────────────

    $(document).on('click', '#prAddToCart', function () {
        if (!_selected) { return; }
        var base = HDCommerce.base();
        if (!base) { return; }
        var $btn    = $(this);
        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Añadiendo…');
        var data = {
            product_id:      _selected.id,
            quantity:        _qty,
            conversation_id: HDCommerce.conversationId(),
        };
        if (_selectedCombo) {
            data.id_product_attribute = _selectedCombo.id;
        }
        HDCommerce.ajax({
            url:    base + '/cart/items',
            method: 'POST',
            data:   data,
        }).done(function (r) {
            if (r && r.success === false) {
                $btn.prop('disabled', false).html(origHtml);
                toastr.error(r.message || 'Error al añadir al carrito');
                return;
            }
            $btn.html('<i class="fas fa-check"></i> Añadido');
            toastr.success('Producto añadido al carrito');
            setTimeout(function () {
                $btn.prop('disabled', false).html(origHtml);
            }, 2000);
        }).fail(function (xhr) {
            $btn.prop('disabled', false).html(origHtml);
            toastr.error(HDCommerce.errorMessage(xhr, 'Error de conexión'));
        });
    });

    // ── Mejora 5: Copiar ficha completa ──────────────────────────────────────

    $(document).on('click', '.ps-copy-ficha', function () {
        if (!_selected) { return; }
        var ref   = (_selectedCombo && _selectedCombo.reference) || _selected.sku || '';
        var price = _selected.price_with_tax > 0 ? money(_selected.price_with_tax) : '';
        var url   = _selected.url || '';
        var text  = _selected.name;
        if (ref)   { text += '\nRef: ' + ref; }
        if (price) { text += '\nPrecio: ' + price; }
        if (url)   { text += '\nURL: ' + url; }
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Ficha copiada al portapapeles', '', { timeOut: 2000 });
        }).catch(function () {
            var el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            toastr.success('Ficha copiada al portapapeles', '', { timeOut: 2000 });
        });
    });

    // ── Mejora 6: Filtro "Solo en stock" ─────────────────────────────────────

    $(document).on('change', '#prInStockOnly', function () {
        var onlyStock = $(this).is(':checked');
        if (!_pool.length) { return; }
        var filtered = onlyStock ? _pool.filter(function (p) { return p.in_stock; }) : _pool;
        if (onlyStock && !filtered.length) {
            // Sin resultados en pool: lanzar nueva búsqueda con in_stock=1
            var base = HDCommerce.base();
            if (!base) { renderListFiltered([], false); return; }
            $('#prProductList').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Buscando en stock…</div>');
            HDCommerce.ajax({ url: base + '/ps/products', method: 'GET', data: { q: _searchQuery, in_stock: 1 } })
                .done(function (r) { renderListFiltered(r.products || [], false); })
                .fail(function () { renderListFiltered([], false); });
        } else {
            renderListFiltered(filtered, _hasMore && !onlyStock);
        }
    });

    // ── Mejora 9: Búsquedas recientes al hacer focus en el input ─────────────

    $(document).on('focus', '#prProductSearch', function () {
        var q = $(this).val().trim();
        if (q.length > 0) { return; }
        var recents = getRecentSearches();
        if (!recents.length) { return; }
        var html = '<div class="ps-recents">' +
            '<span class="ps-recents-label">Búsquedas recientes</span>' +
            recents.map(function (r) {
                return '<button class="ps-recent-chip" type="button" data-q="' + esc(r) + '">' + esc(r) + '</button>';
            }).join('') +
        '</div>';
        $('#prProductList').html(html);
    });

    $(document).on('click', '.ps-recent-chip', function () {
        var q = $(this).attr('data-q');
        $('#prProductSearch').val(q).trigger('input');
    });

    // ── MEJORA 1: Toggle descripción expandible ──────────────────────────────

    $(document).on('click', '.ps-desc-toggle', function () {
        var $current = $(this).closest('.ps-desc-short, .ps-desc-full');
        var $sibling = $current.siblings('.ps-desc-short, .ps-desc-full');
        $current.addClass('bv-hidden');
        $sibling.removeClass('bv-hidden');
    });

    // ── MEJORA 3: Lightbox en imagen del detalle ──────────────────────────────

    $(document).on('click', '#prDThumb img', function () {
        var src = $(this).attr('src');
        if (!src || src.indexOf('base64') > -1) { return; }
        var $lb = $('<div class="ps-lightbox"><img src="' + esc(src) + '"><button class="ps-lb-close" type="button"><i class="fas fa-times"></i></button></div>');
        $('body').append($lb);
        $lb.on('click', function (e) {
            if ($(e.target).is($lb) || $(e.target).closest('.ps-lb-close').length) {
                $lb.remove();
            }
        });
    });

    // ── MEJORA 5: Ordenar resultados ─────────────────────────────────────────

    $(document).on('change', '#prSortBy', function () {
        var sort = $(this).val();
        if (!_pool.length) { return; }
        var sorted = _pool.slice();
        if (sort === 'name_asc')  { sorted.sort(function (a, b) { return (a.name || '').localeCompare(b.name || ''); }); }
        if (sort === 'name_desc') { sorted.sort(function (a, b) { return (b.name || '').localeCompare(a.name || ''); }); }
        if (sort === 'price_asc') { sorted.sort(function (a, b) { return (a.price_with_tax || 0) - (b.price_with_tax || 0); }); }
        if (sort === 'price_desc'){ sorted.sort(function (a, b) { return (b.price_with_tax || 0) - (a.price_with_tax || 0); }); }
        if (sort === 'stock_desc'){ sorted.sort(function (a, b) { return (b.stock || 0) - (a.stock || 0); }); }
        renderListFiltered(sorted, _hasMore && !sort);
    });

    // ── MEJORA 6: Filtro por categoría ──────────────────────────────────────

    $(document).on('change', '#prCategoryFilter', function () {
        var catId = $(this).val();
        if (!catId) {
            renderListFiltered(_pool, _hasMore);
            return;
        }
        var filtered = _pool.filter(function (p) {
            return !catId || String(p.category_id) === catId;
        });
        if (filtered.length) {
            renderListFiltered(filtered, false);
        } else {
            renderListFiltered([], false);
        }
    });

    // ── MEJORA 7: Compartir por email ────────────────────────────────────────

    $(document).on('click', '#prSendEmail', function () {
        if (!_selected) { return; }
        var ref     = (_selectedCombo && _selectedCombo.reference) || _selected.sku || '';
        var price   = _selected.price_with_tax > 0 ? money(_selected.price_with_tax) : '';
        var url     = _selected.url || '';
        var subject = 'Producto recomendado: ' + _selected.name;
        var body    = _selected.name;
        if (ref)   { body += '\nReferencia: ' + ref; }
        if (price) { body += '\nPrecio: ' + price; }
        if (url)   { body += '\nEnlace: ' + url; }
        if (typeof window.openEmailComposer === 'function') {
            window.openEmailComposer({ subject: subject, body: body });
        } else {
            window.open('mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body));
        }
        $('#productRecommendModal').modal('hide');
        HDCommerce.close('ps-product-recommend');
    });

    // ── Mejora 10: Auto-detectar producto mencionado en el chat ──────────────

    function detectProductFromChat() {
        var lastMsg = '';
        var $msgs   = $('.bv-message--inbound .bv-message__text, .msg-in .msg-body, .conversation-message.inbound .message-text');
        if ($msgs.length) {
            lastMsg = $msgs.last().text().trim();
        }
        if (!lastMsg) { return; }

        // Detectar EAN13/EAN8
        var eanMatch = lastMsg.match(/\b(\d{8,14})\b/);
        if (eanMatch) {
            setSearchQuery(eanMatch[1]);
            return;
        }

        // Detectar referencia (letra + alfanuméricos, 4-20 chars)
        var refMatch = lastMsg.match(/\b([A-Z][A-Z0-9\-]{3,19})\b/);
        if (refMatch) {
            setSearchQuery(refMatch[1]);
        }
    }

    function setSearchQuery(q) {
        var $input = $('#prProductSearch');
        if ($input.length && $input.val() === '') {
            $input.val(q).trigger('input');
        }
    }

    // ── Reset al abrir ────────────────────────────────────────────────────────

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'ps-product-recommend') { return; }
        $('#prProductSearch').val('');
        _pool = []; _selected = null; _selectedCombo = null; _combinations = []; _selAttrs = {};
        _qty = 1; _searchQuery = ''; _searchOffset = 0; _hasMore = false; _convRecsLoaded = false;
        $('#prQtyInput').val(1);
        $('#prInternalNote').val('');    // MEJORA 8: limpiar nota al abrir
        $('#prVolBlock').html('');       // MEJORA 4: limpiar precios volumen
        $('#prSortBy').val('');         // MEJORA 5: resetear orden
        $('#prDetailZone').addClass('bv-hidden');
        $('#prDetailEmpty').removeClass('bv-hidden');
        $('#prAttrBlock, #prAltBlock, #prHistBlock, #prAttrSel').addClass('bv-hidden');
        $('#prFootDefault').removeClass('bv-hidden');
        $('#prFootSelected').addClass('bv-hidden');
        loadRecommended();
        loadConversationHistory();
        loadCategories();               // MEJORA 6: cargar categorías al abrir
        detectProductFromChat();        // Mejora 10
    });

    // ── API pública ───────────────────────────────────────────────────────────

    window.openProductRecommend = function () {
        if (!HDCommerce.customerId()) {
            toastr.warning('Selecciona una conversación con cliente.');
            return;
        }
        HDCommerce.open('ps-product-recommend');
    };
})();
</script>
@endpush
@endonce

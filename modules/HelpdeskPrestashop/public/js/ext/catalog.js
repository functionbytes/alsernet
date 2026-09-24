/**
 * Extensión "catalog" · HelpdeskPrestashop
 *
 * Piezas del documento "Alvarez PrestaShop en el Chat":
 *  - 16 Disponibilidad y plazos, 25 Stock por almacén y 26 Precio por grupo:
 *    bloques que se pintan dentro de la ficha del modal "Recomendar
 *    producto" (detrás de #prVolBlock) al elegir un producto o combinación.
 *  - 10 Comparar productos: botón "Comparar" en el pie de ese modal, que lo
 *    cierra y abre el modal ps-product-compare (nunca uno encima de otro).
 *
 * No se toca product-recommend.js: el producto elegido se detecta por la
 * petición de detalle que ese fichero hace al seleccionar
 * (GET …/ps/products/{id}) y la combinación por el clic en .combo-row.
 *
 * Fuente: modules/HelpdeskPrestashop/public/js/ext/catalog.js — asset() sirve
 * desde public/modules/helpdeskprestashop/js/ext/, hay que copiarlo allí.
 */
(function () {
    'use strict';

    var MODAL_REC = 'ps-product-recommend';
    var MODAL_CMP = 'ps-product-compare';
    var MAX_COMPARE = 3;
    // Por encima de esto el "stock" es disponibilidad del proveedor, no
    // unidades reales (la importación del ERP pone 999999 y similares).
    var SUPPLIER_STOCK = 99999;
    // Detalle de producto que product-recommend.js pide al elegir uno. No
    // casa con …/alternatives ni con la búsqueda (?q=).
    var DETAIL_RE = /\/customers\/\d+\/ps\/products\/(\d+)(?:\?|$)/;
    var SEARCH_RE = /\/customers\/\d+\/ps\/products(?:\?|$)/;

    var sheet = { productId: 0, attrId: 0, timer: null, xhr: null, data: null, can: {} };
    var seen = {};   // id → datos básicos del producto vistos en búsqueda/detalle
    var cmp = { ids: [], items: {}, xhr: null, searchTimer: null, searchXhr: null, missingNote: '' };

    /* ── Utilidades ───────────────────────────────────────────── */

    function store() { return window.PscStore || null; }

    function esc(s) {
        if (store()) { return store().esc(s); }
        return $('<span>').text(s == null ? '' : String(s)).html();
    }

    // esc() de PscStore no escapa comillas: para valores dentro de atributos.
    function escAttr(s) {
        if (store() && store().escAttr) { return store().escAttr(s); }
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function money(n) {
        if (n === null || n === undefined || n === '' || isNaN(parseFloat(n))) { return '—'; }
        if (store()) { return store().money(n); }
        var parts = (parseFloat(n) || 0).toFixed(2).split('.');
        return parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + parts[1] + ' €';
    }

    function units(n) {
        n = parseInt(n, 10) || 0;
        if (n >= SUPPLIER_STOCK) { return 'Disponible (proveedor)'; }
        if (n <= 0) { return 'Sin stock'; }
        return n === 1 ? '1 ud' : n + ' uds';
    }

    function csrf() {
        return (window.HDCommerce && HDCommerce.csrf && HDCommerce.csrf()) || $('meta[name="csrf-token"]').attr('content');
    }

    function errorMessage(xhr, fallback) {
        if (window.HDCommerce && HDCommerce.errorMessage) { return HDCommerce.errorMessage(xhr, fallback); }
        return (xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback;
    }

    function customerBase() {
        return window.HDCommerce && HDCommerce.base ? HDCommerce.base() : '';
    }

    function extBase() {
        var base = customerBase();
        return base ? base + '/ps/ext/catalog' : '';
    }

    function insertComposer(text) {
        if (store() && store().insert(text)) { return true; }
        var ta = document.querySelector('.bv-composer-input');
        if (!ta) { return false; }
        ta.value = ta.value ? ta.value.replace(/\s+$/, '') + '\n\n' + text : text;
        ta.dispatchEvent(new Event('input'));
        ta.focus();
        return true;
    }

    function capitalize(s) {
        s = String(s || '').trim();
        return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
    }

    // "envío aproximado entre el vie. 25 sept. y el lun. 28 sept." → "vie. 25 sept. – lun. 28 sept."
    function shortRange(text) {
        var m = /entre\s+(?:el\s+)?(.+?)\s+y\s+(?:el\s+)?(.+?)\.?$/i.exec(String(text || '').trim());
        return m ? m[1] + ' – ' + m[2] + (/\.$/.test(m[2]) ? '' : '.') : '';
    }

    function recOpen() {
        return $('[data-bv-modal-name="' + MODAL_REC + '"]').hasClass('on');
    }

    function remember(p) {
        if (!p || !p.id) { return; }
        seen[p.id] = $.extend({}, seen[p.id] || {}, p);
    }

    function row(k, v, cls, vCls) {
        return '<div class="psc-row' + (cls ? ' ' + cls : '') + '"><span class="k">' + k + '</span>' +
            '<span class="v' + (vCls ? ' ' + vCls : '') + '">' + v + '</span></div>';
    }

    function section(title, body) {
        return '<div class="psc-catalog-sec">' +
            '<div class="ps-sec-label"><span>' + esc(title) + '</span><span class="ln"></span></div>' +
            '<div class="psc-catalog-sec-body">' + body + '</div>' +
        '</div>';
    }

    /* ── Piezas 16 / 25 / 26 · ficha dentro de "Recomendar producto" ─ */

    function sheetBox() {
        var $box = $('#pscCatalogSheet');
        if (!$box.length) {
            var $anchor = $('#prVolBlock');
            if (!$anchor.length) { return $(); }
            $box = $('<div class="psc-catalog-sheet" id="pscCatalogSheet"></div>').insertAfter($anchor);
        }
        return $box;
    }

    function scheduleSheet() {
        clearTimeout(sheet.timer);
        // Al elegir un producto product-recommend.js lanza dos peticiones de
        // detalle y, si hay combinaciones, un clic automático en la primera
        // con stock: se agrupan en una sola carga.
        sheet.timer = setTimeout(loadSheet, 250);
    }

    function loadSheet(fresh) {
        var base = extBase();
        var $box = sheetBox();
        if (!base || !sheet.productId || !$box.length) { return; }

        if (sheet.xhr) { sheet.xhr.abort(); }
        var productId = sheet.productId;
        var attrId = sheet.attrId;

        $box.html('<div class="psc-skel"></div><div class="psc-skel"></div>');

        sheet.xhr = $.ajax({
            url: base + '/products/' + productId + '/sheet',
            data: { product_attribute_id: attrId, fresh: fresh ? 1 : 0 },
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        }).done(function (r) {
            if (productId !== sheet.productId || attrId !== sheet.attrId) { return; }
            sheet.data = r.data || null;
            sheet.can = r.can || {};
            renderSheet();
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort' || productId !== sheet.productId || attrId !== sheet.attrId) { return; }
            sheet.data = null;
            $box.html(
                '<div class="psc-note psc-note--warn"><span class="psc-note-txt">' +
                esc(errorMessage(xhr, 'No se han podido cargar stock y precios de la tienda.')) +
                '</span><button type="button" class="psc-note-act" id="pscCatalogRetry">Reintentar</button></div>'
            );
        }).always(function () {
            sheet.xhr = null;
        });
    }

    function renderSheet() {
        var d = sheet.data;
        var $box = sheetBox();
        if (!d || !$box.length) { $box.empty(); return; }

        $box.html(availabilityHtml(d) + warehousesHtml(d) + pricingHtml(d));
    }

    function bestOtherLocation(stock) {
        var best = null;
        (stock.locations || []).forEach(function (l) {
            if (l.units > 0 && (!best || l.units > best.units)) { best = l; }
        });
        return best;
    }

    // Pieza 16 · ps-stock-eta
    function availabilityHtml(d) {
        var a = d.availability || {};
        var st = d.stock || {};
        var prod = d.product || {};
        // Producto con variantes sin combinación elegida: el stock es la suma
        // y el aviso de reposición no se puede pedir (va por combinación).
        var needsCombo = !!prod.has_combinations && !prod.product_attribute_id;
        var html = '';

        if (prod.product_attribute_id && prod.reference) {
            html += row('Combinación', esc('ref. ' + prod.reference), '', 'mono');
        }
        html += row('Stock en la web', esc(units(st.web)), '', 'mono ' + (st.web > 0 ? 'is-pos' : 'is-zero'));

        if (a.in_stock) {
            if (a.delivery_text) {
                html += '<div class="psc-note psc-note--good"><span class="psc-note-txt">' + esc(capitalize(a.delivery_text)) + '</span></div>';
            } else {
                html += row('Plazo de envío', a.no48h ? 'Sin plazo publicado (no 48 h)' : 'Sin plazo publicado', '', 'is-zero');
            }
        } else {
            var other = bestOtherLocation(st);
            html += row('En otro almacén', other ? esc(units(other.units) + ' · ' + other.label) : 'Sin unidades', '', other ? 'is-pos' : 'is-zero');
            // No hay fecha de reposición en la tienda: se dice, no se inventa.
            html += row('Reposición prevista', 'Sin fecha en la tienda', '', 'is-zero');
            if (st.supplier_days) {
                html += row('Servicio del proveedor', esc(st.supplier_days + (st.supplier_days === 1 ? ' día' : ' días')));
            }
        }

        var acts = '';
        if (!a.in_stock && a.alerts_enabled && sheet.can.stock_alert && needsCombo) {
            acts += '<p class="psc-catalog-hint">Elige la combinación (también sirven las agotadas) para apuntar al cliente al aviso de reposición.</p>';
        } else if (!a.in_stock && a.alerts_enabled && sheet.can.stock_alert) {
            acts += a.alert_subscribed
                ? '<button type="button" class="psc-btn psc-btn--outline is-disabled" disabled>Aviso de reposición activado</button>'
                : '<button type="button" class="psc-btn psc-btn--primary" id="pscCatalogAlert">Avisar cuando vuelva</button>';
        }
        acts += '<button type="button" class="psc-btn psc-btn--outline" id="pscCatalogSendEta">Enviar plazos al chat</button>';

        return section('Disponibilidad y plazos', html + '<div class="psc-catalog-acts">' + acts + '</div>');
    }

    // Pieza 25 · ps-stock-wh
    function warehousesHtml(d) {
        var st = d.stock || {};
        if (!st.has_breakdown) { return ''; }

        var html = (st.locations || []).map(function (l) {
            return row(esc(l.label), esc(units(l.units)), '', 'mono ' + (l.units > 0 ? 'is-pos' : 'is-zero'));
        }).join('');
        html += row('Disponible total', esc(units(st.total)), 'psc-row--total', 'mono');

        var hint = 'Stock físico por ubicación, del ERP.';
        if (st.web >= SUPPLIER_STOCK) {
            hint += ' La web lo vende bajo pedido al proveedor.';
        } else if (st.web <= 0 && st.total > 0) {
            hint += ' La web no tiene ahora unidades a la venta.';
        } else if (st.web !== st.total) {
            hint += ' La web vende ahora ' + units(st.web) + '.';
        }

        return section('Stock por almacén', html + '<p class="psc-catalog-hint">' + esc(hint) + '</p>');
    }

    // Pieza 26 · ps-price-group
    function pricingHtml(d) {
        var p = d.pricing || {};
        var pub = p.public || {};
        var mine = p.customer;
        var tiers = p.tiers || [];
        var html = '';
        var hint;

        if (mine) {
            var label = esc(mine.group_name || 'Grupo del cliente') + ' · este cliente';
            if (mine.country_id && mine.country_id !== pub.country_id) {
                label += ' · ' + esc(mine.country_name);
            }
            html += row(label, money(mine.price_with_tax), 'psc-row--good psc-catalog-mine', 'mono');
        }
        html += row('Precio público · ' + esc(pub.country_name || 'web'), money(pub.price_with_tax), '', 'mono');
        tiers.forEach(function (t) {
            html += row('Desde ' + (parseInt(t.from_quantity, 10) || 0) + ' uds', money(t.price_with_tax), '', 'mono');
        });
        if (!tiers.length) {
            html += '<p class="psc-catalog-hint">Sin precios por cantidad para este producto.</p>';
        }

        if (!mine) {
            hint = 'El cliente no está vinculado a una cuenta de la tienda: se muestra el precio público.';
        } else if (Math.abs((mine.price_with_tax || 0) - (pub.price_with_tax || 0)) < 0.005) {
            hint = 'Su grupo (' + (mine.group_name || '—') + ') no tiene tarifa propia: paga el precio público.';
        } else {
            hint = 'Responde “¿por qué a mí me sale otro precio?” sin abrir la tienda.';
        }

        html += '<button type="button" class="psc-btn psc-btn--primary" id="pscCatalogSendTiers">' +
            (tiers.length ? 'Enviar tramos al chat' : 'Enviar precio al chat') + '</button>';
        html += '<p class="psc-catalog-hint">' + esc(hint) + '</p>';

        return section('Precio por grupo', html);
    }

    function productLabel(d) {
        var prod = d.product || {};
        return prod.name + (prod.reference ? ' (ref. ' + prod.reference + ')' : '');
    }

    function etaText(d) {
        var a = d.availability || {};
        var st = d.stock || {};
        var text = productLabel(d) + ': ';

        if (a.in_stock) {
            text += 'lo tenemos disponible en la web.';
            if (a.delivery_text) { text += ' ' + capitalize(a.delivery_text).replace(/\.?$/, '.'); }
            return text;
        }

        text += 'ahora mismo no tenemos stock en la web.';
        var other = bestOtherLocation(st);
        if (other) {
            text += ' Quedan ' + units(other.units).toLowerCase() + ' en ' + other.label + '.';
        }
        if (a.alert_subscribed) {
            text += ' Te hemos apuntado para avisarte por correo en cuanto vuelva.';
        }
        return text;
    }

    function tiersText(d) {
        var p = d.pricing || {};
        var unit = p.customer ? p.customer.price_with_tax : (p.public || {}).price_with_tax;
        var text = productLabel(d) + ': ' + money(unit) + ' la unidad' + (p.customer ? ' con tu cuenta' : '') + '.';
        var tiers = p.tiers || [];
        if (tiers.length) {
            text += ' Por cantidad: ' + tiers.map(function (t) {
                return 'desde ' + t.from_quantity + ' uds, ' + money(t.price_with_tax) + ' cada una';
            }).join('; ') + '.';
        }
        return text;
    }

    function subscribeAlert($btn) {
        var d = sheet.data;
        var base = extBase();
        if (!d || !base) { return; }

        $btn.prop('disabled', true).addClass('is-disabled').text('Guardando…');

        $.ajax({
            url: base + '/products/' + d.product.id + '/stock-alert',
            method: 'POST',
            dataType: 'json',
            data: {
                product_attribute_id: d.product.product_attribute_id || 0,
                conversation_id: window.HDCommerce && HDCommerce.conversationId ? HDCommerce.conversationId() : null,
            },
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        }).done(function (r) {
            if (sheet.data === d) {
                d.availability.alert_subscribed = true;
                renderSheet();
            }
            toastr.success(r.data && r.data.already
                ? 'El cliente ya estaba apuntado al aviso de reposición.'
                : 'Cliente apuntado: la tienda le avisará por correo cuando vuelva.');
        }).fail(function (xhr) {
            $btn.prop('disabled', false).removeClass('is-disabled').text('Avisar cuando vuelva');
            toastr.error(errorMessage(xhr, 'No se ha podido apuntar al cliente al aviso.'));
        });
    }

    /* ── Pieza 10 · Comparar productos ───────────────────────── */

    function $cmp() { return $('[data-bv-modal-name="' + MODAL_CMP + '"]'); }

    function cmpItem(id) { return cmp.items[id] || seen[id] || { id: id, name: '#' + id }; }

    function colsClass() { return 'cols-' + Math.max(1, cmp.ids.length); }

    function openCompare(ids) {
        cmp.ids = ids.slice(0, MAX_COMPARE);
        cmp.items = {};
        cmp.missingNote = '';
        HDCommerce.close(MODAL_REC);
        HDCommerce.open(MODAL_CMP);
    }

    function renderCompare() {
        var html = cmp.ids.map(function (id, i) {
            var p = cmpItem(id);
            var price = p.price_with_tax !== undefined ? p.price_with_tax : null;
            var stock = p.stock !== undefined ? p.stock : null;
            var thumb = p.image ? '<img src="' + escAttr(p.image) + '" alt="" loading="lazy">' : '';
            return '<div class="psc-catalog-cmp-card' + (i === 0 ? ' is-base' : '') + '">' +
                '<button type="button" class="psc-catalog-cmp-x" data-id="' + id + '" title="Quitar de la comparación" aria-label="Quitar de la comparación"><i class="fas fa-xmark"></i></button>' +
                '<div class="psc-thumb psc-catalog-cmp-thumb">' + thumb + '</div>' +
                '<div class="nm">' + esc(p.name || '') + '</div>' +
                '<div class="pr">' + (price !== null ? money(price) : '—') + '</div>' +
                (stock !== null ? '<div class="st' + (stock > 0 ? '' : ' is-out') + '">' + esc(stock > 0 && stock < SUPPLIER_STOCK ? units(stock) : (stock > 0 ? 'En stock' : 'Sin stock')) + '</div>' : '') +
            '</div>';
        }).join('');
        $('#pscCatalogCmpCards').attr('class', 'psc-catalog-cmp-cards ' + colsClass()).html(html);

        var n = cmp.ids.length;
        $('#pscCatalogCmpAdd')
            .toggleClass('bv-hidden', n >= MAX_COMPARE)
            .text(n >= 2 ? 'Añadir un tercer producto' : 'Añadir otro producto');
        if (n >= MAX_COMPARE) { $('#pscCatalogCmpPicker').addClass('bv-hidden'); }

        var ready = n >= 2 && cmp.ids.every(function (id) { return !!cmp.items[id]; });
        $('#pscCatalogCmpSend').prop('disabled', !ready).toggleClass('is-disabled', !ready);

        $('#pscCatalogCmpError').toggleClass('bv-hidden', !cmp.missingNote).find('.psc-note-txt').text(cmp.missingNote);

        renderCompareRows(ready);
    }

    function valsRow(label, values, bestIdx) {
        return '<div class="psc-catalog-cmp-row"><div class="k">' + esc(label) + '</div>' +
            '<div class="vals ' + colsClass() + '">' + values.map(function (v, i) {
                return '<span class="v' + (i === bestIdx ? ' is-best' : '') + (v === '—' ? ' is-empty' : '') + '">' + esc(v) + '</span>';
            }).join('') + '</div></div>';
    }

    function renderCompareRows(ready) {
        var $rows = $('#pscCatalogCmpRows');
        if (cmp.ids.length < 2) {
            $rows.html('<p class="psc-catalog-hint">Añade al menos otro producto para ver la comparación.</p>');
            return;
        }
        if (!ready) {
            $rows.html(cmp.xhr ? '<div class="psc-loading">Comparando en la tienda…</div>' : '');
            return;
        }

        var items = cmp.ids.map(function (id) { return cmp.items[id]; });
        var prices = items.map(function (it) { return parseFloat(it.price_with_tax); });
        var min = Math.min.apply(null, prices.filter(function (x) { return !isNaN(x); }));
        var distinct = prices.some(function (x) { return x !== prices[0]; });
        var html = '';

        html += valsRow('Precio', items.map(function (it) { return money(it.price_with_tax); }),
            distinct ? prices.indexOf(min) : -1);
        if (items.some(function (it) { return it.has_discount && it.price_original; })) {
            html += valsRow('Antes', items.map(function (it) { return it.has_discount && it.price_original ? money(it.price_original) : '—'; }));
        }
        html += valsRow('Stock en la web', items.map(function (it) { return units(it.stock); }));
        html += valsRow('Envío', items.map(function (it) { return shortRange(it.delivery_text) || (it.in_stock ? 'Sin plazo publicado' : '—'); }));
        html += valsRow('Marca', items.map(function (it) { return it.brand || '—'; }));
        html += valsRow('Categoría', items.map(function (it) { return it.category || '—'; }));

        var groups = [];
        items.forEach(function (it) {
            (it.variants || []).forEach(function (g) {
                if (groups.indexOf(g.group) === -1) { groups.push(g.group); }
            });
        });
        groups.forEach(function (name) {
            html += valsRow(name, items.map(function (it) {
                var g = (it.variants || []).filter(function (x) { return x.group === name; })[0];
                if (!g || !g.values.length) { return '—'; }
                return g.values.slice(0, 6).join(', ') + (g.values.length > 6 ? '…' : '');
            }));
        });
        html += valsRow('Referencia', items.map(function (it) { return it.reference || '—'; }));

        $rows.html(html);
    }

    function loadCompare() {
        var base = extBase();
        if (!base || cmp.ids.length < 2) { renderCompare(); return; }
        if (cmp.xhr) { cmp.xhr.abort(); }

        var ids = cmp.ids.slice();
        cmp.xhr = $.ajax({
            url: base + '/compare',
            method: 'POST',
            dataType: 'json',
            data: { product_ids: ids },
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        }).done(function (r) {
            (r.items || []).forEach(function (it) { cmp.items[it.id] = it; remember(it); });
            var missing = r.missing || [];
            if (missing.length) {
                cmp.ids = cmp.ids.filter(function (id) { return missing.indexOf(id) === -1; });
                cmp.missingNote = missing.length === 1
                    ? 'Un producto no está activo en la tienda y se ha quitado de la comparación.'
                    : 'Algunos productos no están activos en la tienda y se han quitado de la comparación.';
            }
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') { return; }
            cmp.missingNote = errorMessage(xhr, 'No se ha podido comparar en la tienda ahora mismo.');
        }).always(function () {
            cmp.xhr = null;
            renderCompare();
        });
        renderCompare();
    }

    function addToCompare(id) {
        id = parseInt(id, 10);
        if (!id || cmp.ids.indexOf(id) !== -1 || cmp.ids.length >= MAX_COMPARE) { return; }
        cmp.ids.push(id);
        cmp.missingNote = '';
        $('#pscCatalogCmpPicker').addClass('bv-hidden');
        $('#pscCatalogCmpSearch').val('');
        loadCompare();
    }

    function resultsHtml(list) {
        var items = (list || []).filter(function (p) { return p && p.id && cmp.ids.indexOf(parseInt(p.id, 10)) === -1; });
        if (!items.length) {
            return '<p class="psc-catalog-hint">Sin productos que añadir.</p>';
        }
        return items.slice(0, 8).map(function (p) {
            remember(p);
            var thumb = p.image ? '<img src="' + escAttr(p.image) + '" alt="" loading="lazy">' : '';
            return '<button type="button" class="psc-catalog-cmp-res" data-id="' + parseInt(p.id, 10) + '">' +
                '<span class="psc-thumb psc-thumb--sm">' + thumb + '</span>' +
                '<span class="info"><span class="t">' + esc(p.name) + '</span>' +
                '<span class="s">' + esc(p.sku || p.reference || ('#' + p.id)) + '</span></span>' +
                '<span class="pr">' + (p.price_with_tax > 0 ? money(p.price_with_tax) : '') + '</span>' +
            '</button>';
        }).join('');
    }

    function loadSuggestions() {
        var base = customerBase();
        var $res = $('#pscCatalogCmpResults');
        $('#pscCatalogCmpResultsTitle').text('Del mismo fabricante o categoría');
        if (!base || !cmp.ids.length) { $res.html(''); return; }

        $res.html('<div class="psc-loading">Buscando alternativas…</div>');
        $.ajax({
            url: base + '/ps/products/' + cmp.ids[0] + '/alternatives',
            dataType: 'json',
            headers: { Accept: 'application/json' },
        }).done(function (r) {
            if ($.trim($('#pscCatalogCmpSearch').val())) { return; }
            $res.html(resultsHtml(r.products));
        }).fail(function () {
            $res.html('<p class="psc-catalog-hint">Busca el producto por nombre o referencia.</p>');
        });
    }

    function searchProducts(q) {
        var base = customerBase();
        var $res = $('#pscCatalogCmpResults');
        if (cmp.searchXhr) { cmp.searchXhr.abort(); }
        if (q.length < 2) { loadSuggestions(); return; }

        $('#pscCatalogCmpResultsTitle').text('Resultados');
        $res.html('<div class="psc-loading">Buscando…</div>');
        cmp.searchXhr = $.ajax({
            url: base + '/ps/products',
            data: { q: q },
            dataType: 'json',
            headers: { Accept: 'application/json' },
        }).done(function (r) {
            $res.html(resultsHtml(r.products));
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') { return; }
            $res.html('<p class="psc-catalog-hint">' + esc(errorMessage(xhr, 'No se ha podido buscar en la tienda.')) + '</p>');
        }).always(function () {
            cmp.searchXhr = null;
        });
    }

    function compareText() {
        var lines = ['Te dejo la comparación:'];
        cmp.ids.forEach(function (id) {
            var it = cmp.items[id];
            if (!it) { return; }
            var parts = [money(it.price_with_tax), it.in_stock ? 'disponible' : 'sin stock ahora mismo'];
            var eta = it.in_stock ? shortRange(it.delivery_text) : '';
            if (eta) { parts.push('envío ' + eta); }
            lines.push('');
            lines.push('• ' + it.name + (it.reference ? ' (ref. ' + it.reference + ')' : ''));
            lines.push('  ' + parts.join(' · '));
            (it.variants || []).forEach(function (g) {
                lines.push('  ' + g.group + ': ' + g.values.slice(0, 8).join(', ') + (g.values.length > 8 ? '…' : ''));
            });
            if (it.url) { lines.push('  ' + it.url); }
        });
        return lines.join('\n');
    }

    /* ── Eventos ──────────────────────────────────────────────── */

    // Producto elegido en "Recomendar producto": la petición de detalle que
    // hace product-recommend.js al seleccionarlo.
    $(document).ajaxSend(function (e, xhr, settings) {
        var m = DETAIL_RE.exec((settings && settings.url) || '');
        if (!m || !recOpen()) { return; }
        var id = parseInt(m[1], 10);
        if (id !== sheet.productId) {
            sheet.productId = id;
            sheet.attrId = 0;
            sheet.data = null;
            scheduleSheet();
        }
    });

    // Datos básicos (nombre, foto, precio, stock) para las tarjetas de la
    // comparación, de las respuestas de búsqueda y detalle que ya llegan.
    $(document).ajaxSuccess(function (e, xhr, settings, data) {
        var url = (settings && settings.url) || '';
        if (!data || typeof data !== 'object') { return; }
        if (DETAIL_RE.test(url) && data.product) { remember(data.product); }
        if (SEARCH_RE.test(url) && $.isArray(data.products)) { data.products.forEach(remember); }
    });

    // También las combinaciones agotadas (.dis), que product-recommend.js no
    // deja elegir para enviar: son justo las que necesitan plazo y aviso de
    // reposición. Solo cambian la ficha de abajo, no la selección del envío.
    $(document).on('click', '#prAttrGroups .combo-row', function () {
        var $row = $(this);
        var cid = parseInt($row.attr('data-cid'), 10) || 0;
        $('#prAttrGroups .combo-row').removeClass('psc-catalog-picked');
        if ($row.hasClass('dis')) { $row.addClass('psc-catalog-picked'); }
        if (!sheet.productId || cid === sheet.attrId) { return; }
        sheet.attrId = cid;
        scheduleSheet();
    });

    $(document).on('click', '#pscCatalogRetry', function () { loadSheet(true); });

    $(document).on('click', '#pscCatalogAlert', function () { subscribeAlert($(this)); });

    $(document).on('click', '#pscCatalogSendEta', function () {
        if (!sheet.data) { return; }
        if (insertComposer(etaText(sheet.data))) { toastr.success('Plazos insertados en el chat.'); }
    });

    $(document).on('click', '#pscCatalogSendTiers', function () {
        if (!sheet.data) { return; }
        if (insertComposer(tiersText(sheet.data))) { toastr.success('Precios insertados en el chat.'); }
    });

    $(document).on('click', '#pscCatalogCompareBtn', function () {
        if (!sheet.productId) {
            toastr.warning('Elige primero un producto de la lista.');
            return;
        }
        openCompare([sheet.productId]);
    });

    $(document).on('click', '#pscCatalogCmpAdd', function () {
        var $picker = $('#pscCatalogCmpPicker');
        $picker.toggleClass('bv-hidden');
        if (!$picker.hasClass('bv-hidden')) {
            $('#pscCatalogCmpSearch').val('').trigger('focus');
            loadSuggestions();
        }
    });

    $(document).on('input', '#pscCatalogCmpSearch', function () {
        var q = $.trim($(this).val());
        clearTimeout(cmp.searchTimer);
        cmp.searchTimer = setTimeout(function () { searchProducts(q); }, 300);
    });

    $(document).on('click', '.psc-catalog-cmp-res', function () { addToCompare($(this).attr('data-id')); });

    $(document).on('click', '.psc-catalog-cmp-x', function () {
        var id = parseInt($(this).attr('data-id'), 10);
        cmp.ids = cmp.ids.filter(function (x) { return x !== id; });
        cmp.missingNote = '';
        renderCompare();
    });

    $(document).on('click', '#pscCatalogCmpSend', function () {
        if ($(this).prop('disabled')) { return; }
        if (insertComposer(compareText())) {
            HDCommerce.close(MODAL_CMP);
            toastr.success('Comparación insertada en el chat.');
        }
    });

    $(document).on('bv:modal:open', function (e, name) {
        if (name === MODAL_REC) {
            // Cada apertura empieza sin producto: product-recommend.js también
            // se resetea aquí.
            clearTimeout(sheet.timer);
            if (sheet.xhr) { sheet.xhr.abort(); }
            sheet.productId = 0;
            sheet.attrId = 0;
            sheet.data = null;
            $('#pscCatalogSheet').empty();

            var $acts = $('#prFootSelected .ps-footer-actions');
            if ($acts.length && !$('#pscCatalogCompareBtn').length) {
                $acts.append('<button class="btn btn-outline" id="pscCatalogCompareBtn" type="button">Comparar</button>');
            }
            return;
        }

        if (name === MODAL_CMP) {
            $('#pscCatalogCmpPicker').addClass('bv-hidden');
            $('#pscCatalogCmpResults').empty();
            renderCompare();
            if (cmp.ids.length >= 2) {
                loadCompare();
            } else {
                // Con un solo producto lo siguiente es elegir con qué compararlo.
                $('#pscCatalogCmpPicker').removeClass('bv-hidden');
                loadSuggestions();
            }
        }
    });
})();

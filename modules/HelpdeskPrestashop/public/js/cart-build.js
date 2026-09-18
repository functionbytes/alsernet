/*!
 * HelpdeskPrestashop · modal "cart-build" del inbox.
 *
 * Extraido de resources/views/modals/cart-build.blade.php, donde vivia como
 * <script> inline que se re-descargaba en CADA carga del inbox (el modal se
 * incluye siempre desde helpdesk/inbox/partials/modals.blade.php). No tiene
 * interpolacion Blade: la config llega por atributos data-* y por
 * window.HDCommerce, que define el core en modals/_commerce-js.blade.php.
 *
 * OJO 1: depende de window.HDCommerce en el nivel superior (var C = ...), asi
 * que debe cargarse DESPUES de _commerce-js — lo garantiza el orden de
 * @include en modals.blade.php (_commerce-js va en la linea 36, este en 38-42).
 * OJO 2: la fuente es este fichero; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ — hay que copiarlo alli tras editar.
 */
(function () {
    var C = window.HDCommerce;
    var _cart = null;
    var _pending = null; // 'order' | 'payment'
    var _searchTimer = null;
    var _cartsLoaded = false;

    function esc(s) { return C.esc(s); }
    function money(n) { return C.money(n); }

    function fmtWhen(iso) {
        if (!iso) { return ''; }
        var d = new Date(iso);
        if (isNaN(d.getTime())) { return ''; }
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }) + ' ' +
            d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    // ── Contenido del carrito ──────────────────────────────────────────────
    function emptyCart() {
        return '<div class="bv-oc-empty"><i class="fas fa-cart-shopping"></i>' +
            '<div class="title">El carrito está vacío</div>' +
            '<div>Pulsa + para buscar productos y añadirlos</div></div>';
    }

    function renderCart(cart) {
        _cart = cart;
        var items = (cart && cart.items) || [];

        // Cabecera: id, cliente, estado
        $('#cbCartId').text('CART-#' + (cart.id || '—'));
        $('#cbCustName').text(C.customer().name || '');
        var group = cart.status_group || 'active';
        var pillCls = group === 'converted' ? 'received' : (group === 'abandoned' ? 'pending' : 'approved');
        $('#cbStatus').html('<span class="docs-status ' + pillCls + '">' + esc(cart.status_label || 'Activo') + '</span>');

        if (!items.length) {
            $('#cbLines').html(emptyCart());
        } else {
            $('#cbLines').html(items.map(function (it) {
                var thumb = it.image_url
                    ? '<div class="thumb"><img src="' + esc(it.image_url) + '" alt=""></div>'
                    : '<div class="thumb"><i class="fas fa-box"></i></div>';
                return '<div class="bv-cart-line" data-item-id="' + it.id + '">' + thumb +
                    '<div class="body"><span class="nm">' + esc(it.name) + '</span>' +
                        '<span class="sku">' + (it.sku ? 'Ref: ' + esc(it.sku) + ' · ' : '') + money(it.unit_price) + '/ud</span></div>' +
                    '<div class="bv-qty-stepper"><button type="button" data-step="-1">−</button>' +
                        '<span class="v">' + it.quantity + '</span><button type="button" data-step="1">+</button></div>' +
                    '<span class="price">' + money(it.line_total) + '</span>' +
                    '<button class="rm" type="button" title="Quitar"><i class="fas fa-xmark"></i></button></div>';
            }).join(''));
        }

        if (cart && cart.discount_code) {
            $('#cbDiscountApplied').html('<div class="bv-oc-discount-applied"><i class="fas fa-tag"></i> ' +
                esc(cart.discount_code) + ' (−' + money(cart.discount_amount) + ')' +
                '<span class="x" id="cbRemoveDiscount"><i class="fas fa-xmark"></i></span></div>');
            $('#cbDiscountCode').val('');
        } else {
            $('#cbDiscountApplied').html('');
        }

        var rows = '<div class="row"><span class="k">Subtotal (' + (cart.units_count || 0) + ' ud.)</span><span class="v">' + money(cart.subtotal) + '</span></div>';
        if (cart.shipping_amount > 0) { rows += '<div class="row"><span class="k">Envío</span><span class="v">' + money(cart.shipping_amount) + '</span></div>'; }
        if (cart.discount_amount > 0) { rows += '<div class="row discount"><span class="k">Descuento</span><span class="v">− ' + money(cart.discount_amount) + '</span></div>'; }
        rows += '<div class="row total"><span class="k">Total</span><span class="v">' + money(cart.total) + '</span></div>';
        $('#cbTotals').html(rows);

        $('#cbSummary').text((cart.items_count || items.length) + ' artículo(s) · ' + (cart.units_count || 0) + ' unidades');
        $('#cbGenerateOrder, #cbSendLink, #cbClear').prop('disabled', !items.length);

        renderEvents(cart);
    }

    // Eventos derivados del estado REAL del carrito (creación + ítems actuales
    // + hitos de estado). Sin datos inventados: refleja el carrito tal cual.
    function renderEvents(cart) {
        var items = (cart && cart.items) || [];
        var html = '';
        if (cart.converted_at || cart.order_code) {
            html += '<div class="bv-cev-item"><div class="bv-cev-ic"><i class="fas fa-receipt"></i></div>' +
                '<div class="bv-cev-bd"><div class="bv-cev-lbl">Convertido a pedido' + (cart.order_code ? ' <span class="pv">' + esc(cart.order_code) + '</span>' : '') + '</div>' +
                '<div class="bv-cev-sub">' + fmtWhen(cart.updated_at) + '</div></div></div>';
        }
        items.forEach(function (it) {
            html += '<div class="bv-cev-item"><div class="bv-cev-ic add"><i class="fas fa-cart-plus"></i></div>' +
                '<div class="bv-cev-bd"><div class="bv-cev-lbl">En el carrito · <span class="pv">' + esc(it.name) + '</span> ×' + it.quantity + '</div>' +
                '<div class="bv-cev-sub">' + (it.sku ? 'Ref: ' + esc(it.sku) : '') + '</div></div>' +
                '<div class="bv-cev-amt up">' + money(it.line_total) + '</div></div>';
        });
        html += '<div class="bv-cev-item"><div class="bv-cev-ic"><i class="fas fa-flag"></i></div>' +
            '<div class="bv-cev-bd"><div class="bv-cev-lbl">Carrito creado</div>' +
            '<div class="bv-cev-sub">' + fmtWhen(cart.created_at) + '</div></div></div>';
        $('#cbEvents').html(html);
        $('#cbEventsSub').text('Sesión CART-#' + (cart.id || '—'));
    }

    function loadCart() {
        var base = C.base();
        if (!base) { return; }
        showCartView();
        $('#cbLines').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        $('#cbTotals').html('');
        C.ajax({ url: base + '/cart', method: 'GET', data: { conversation_id: C.conversationId() } })
            .done(function (resp) { renderCart(resp.cart); })
            .fail(function () { $('#cbLines').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">No se pudo cargar el carrito</div></div>'); });
    }

    // ── Buscar / añadir producto ───────────────────────────────────────────
    $(document).on('click', '#cbAddToggle', function () {
        $('#cbSearchWrap').toggleClass('bv-hidden');
        if (!$('#cbSearchWrap').hasClass('bv-hidden')) { $('#cbProductSearch').trigger('focus'); }
        else { $('#cbSearchResults').addClass('bv-hidden').html(''); $('#cbProductSearch').val(''); }
    });

    function renderSearchResults(products) {
        if (!products || !products.length) {
            $('#cbSearchResults').html('<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i><div>Sin resultados</div></div>').removeClass('bv-hidden');
            return;
        }
        $('#cbSearchResults').html(products.map(function (p) {
            var thumb = p.image ? '<div class="thumb"><img src="' + esc(p.image) + '" alt=""></div>' : '<div class="thumb"><i class="fas fa-box"></i></div>';
            return '<button class="bv-oc-product" type="button" data-product-id="' + p.id + '">' + thumb +
                '<span class="nm">' + esc(p.name) + '</span><span class="price">' + money(p.price) + '</span></button>';
        }).join('')).removeClass('bv-hidden');
    }

    $(document).on('input', '#cbProductSearch', function () {
        var q = $(this).val().trim();
        clearTimeout(_searchTimer);
        if (q.length < 2) { $('#cbSearchResults').addClass('bv-hidden').html(''); return; }
        _searchTimer = setTimeout(function () {
            $.ajax({ url: '/api/v1/ecommerce/products/suggestions', method: 'GET', dataType: 'json', data: { q: q } })
                .done(function (resp) { renderSearchResults(resp.products || []); });
        }, 250);
    });

    $(document).on('click', '#cbSearchResults .bv-oc-product', function () {
        var productId = $(this).data('product-id'); var base = C.base();
        if (!base || !productId) { return; }
        C.ajax({ url: base + '/cart/items', method: 'POST', data: { product_id: productId, quantity: 1, conversation_id: C.conversationId() } })
            .done(function (resp) { renderCart(resp.cart); $('#cbProductSearch').val(''); $('#cbSearchResults').addClass('bv-hidden').html(''); })
            .fail(function (xhr) { toastr.error(C.errorMessage(xhr, 'No se pudo añadir el producto.')); });
    });

    // ── Cantidad / quitar ──────────────────────────────────────────────────
    $(document).on('click', '#cbLines .bv-qty-stepper button', function () {
        var $line = $(this).closest('.bv-cart-line'); var itemId = $line.data('item-id');
        var step = parseInt($(this).data('step'), 10);
        var current = parseInt($line.find('.bv-qty-stepper .v').text(), 10) || 1;
        var base = C.base(); if (!base) { return; }
        C.ajax({ url: base + '/cart/items/' + itemId, method: 'PATCH', data: { quantity: current + step } })
            .done(function (resp) { renderCart(resp.cart); })
            .fail(function (xhr) { toastr.error(C.errorMessage(xhr, 'No se pudo actualizar la cantidad.')); });
    });

    $(document).on('click', '#cbLines .bv-cart-line .rm', function () {
        var itemId = $(this).closest('.bv-cart-line').data('item-id'); var base = C.base();
        if (!base) { return; }
        C.ajax({ url: base + '/cart/items/' + itemId, method: 'DELETE' })
            .done(function (resp) { renderCart(resp.cart); })
            .fail(function (xhr) { toastr.error(C.errorMessage(xhr, 'No se pudo quitar el producto.')); });
    });

    // ── Cupón ──────────────────────────────────────────────────────────────
    function applyDiscount(code) {
        var base = C.base(); if (!base) { return; }
        C.ajax({ url: base + '/cart/discount', method: 'POST', data: { code: code } })
            .done(function (resp) { renderCart(resp.cart); if (resp.message) { toastr[resp.success ? 'success' : 'warning'](resp.message); } })
            .fail(function (xhr) { toastr.error(C.errorMessage(xhr, 'No se pudo aplicar el descuento.')); });
    }
    $(document).on('click', '#cbApplyDiscount', function () { var code = $('#cbDiscountCode').val().trim(); if (code) { applyDiscount(code); } });
    $(document).on('click', '#cbRemoveDiscount', function () { applyDiscount(''); });
    $(document).on('keydown', '#cbDiscountCode', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#cbApplyDiscount').click(); } });

    // ── Vaciar ─────────────────────────────────────────────────────────────
    $(document).on('click', '#cbClear', function () {
        var base = C.base(); if (!base) { return; }
        C.ajax({ url: base + '/cart/clear', method: 'POST' })
            .done(function (resp) { renderCart(resp.cart); toastr.info('Carrito vaciado.'); if (typeof window.refreshCartsTab === 'function') { window.refreshCartsTab(); } _cartsLoaded = false; loadCarritos(); })
            .fail(function (xhr) { toastr.error(C.errorMessage(xhr, 'No se pudo vaciar el carrito.')); });
    });

    // ── Cancelar (abandonar) carrito ───────────────────────────────────────
    $(document).on('click', '#cbCancel', function () {
        var base = C.base(); if (!base) { return; }
        C.ajax({ url: base + '/cart/cancel', method: 'POST' })
            .done(function () { toastr.info('Carrito cancelado.'); C.close('cart-build'); if (typeof window.refreshCartsTab === 'function') { window.refreshCartsTab(); } })
            .fail(function (xhr) { toastr.error(C.errorMessage(xhr, 'No se pudo cancelar el carrito.')); });
    });

    // ── Vistas carrito ↔ checkout ──────────────────────────────────────────
    function showCartView() {
        $('#cbGrid').removeClass('bv-hidden'); $('#cbCheckoutView').addClass('bv-hidden');
        $('#cbFootCart').removeClass('bv-hidden'); $('#cbFootCheckout').addClass('bv-hidden');
    }
    function showCheckoutView(action) {
        _pending = action; var c = C.customer();
        $('#cbName').val(c.name); $('#cbEmail').val(c.email); $('#cbPhone').val(c.phone);
        $('#cbCity').val(c.city); $('#cbState').val(c.state); $('#cbZip').val(c.zip_code); $('#cbCountry').val(c.country);
        $('#cbCheckoutTitle').text(action === 'payment' ? 'Datos para el link de pago' : 'Datos de envío del pedido');
        $('#cbConfirm').text(action === 'payment' ? 'Confirmar y enviar link' : 'Confirmar pedido');
        $('#cbGrid').addClass('bv-hidden'); $('#cbCheckoutView').removeClass('bv-hidden');
        $('#cbFootCart').addClass('bv-hidden'); $('#cbFootCheckout').removeClass('bv-hidden');
    }
    $(document).on('click', '#cbGenerateOrder', function () { showCheckoutView('order'); });
    $(document).on('click', '#cbSendLink', function () { showCheckoutView('payment'); });
    $(document).on('click', '#cbBack', function () { showCartView(); });

    $(document).on('click', '#cbConfirm', function () {
        var base = C.base(); if (!base) { return; }
        var payload = {
            name: $('#cbName').val().trim(), email: $('#cbEmail').val().trim(), phone: $('#cbPhone').val().trim(),
            address: $('#cbAddress').val().trim(), city: $('#cbCity').val().trim(), state: $('#cbState').val().trim(),
            country: $('#cbCountry').val().trim(), zip_code: $('#cbZip').val().trim(),
        };
        var url = base + (_pending === 'payment' ? '/cart/send-payment-link' : '/cart/generate-order');
        var $btn = $(this).prop('disabled', true);
        C.ajax({ url: url, method: 'POST', data: payload })
            .done(function (resp) { toastr.success(resp.message || 'Operación completada.'); C.close('cart-build'); if (typeof window.refreshCartsTab === 'function') { window.refreshCartsTab(); } })
            .fail(function (xhr) { toastr.error(C.errorMessage(xhr, 'No se pudo completar la operación.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ── Panel cliente 360: pestañas ────────────────────────────────────────
    $(document).on('click', '#cwTabs .bv-po-tab', function () {
        var go = $(this).data('cw-tab');
        $('#cwTabs .bv-po-tab').removeClass('on'); $(this).addClass('on');
        $('[data-bv-modal-name="cart-build"] .bv-po-panel').addClass('bv-hidden').filter('[data-cw-panel="' + go + '"]').removeClass('bv-hidden');
        if (go === 'carritos') { loadCarritos(); }
        else if (go === 'info') { renderInfo(); }
        else if (go === 'pedidos' || go === 'actividad') { loadCarritos(function () { go === 'pedidos' ? renderPedidos() : renderActividad(); }); }
        else if (go === 'notas') { renderNotas(); }
    });

    // INFO
    function renderInfo() {
        var c = C.customer();
        var initials = (c.name || '?').split(' ').map(function (w) { return w.charAt(0); }).join('').substring(0, 2).toUpperCase();
        // Stats reales calculadas de los carritos del cliente (ya cargados).
        var cw = window._cwCarts || { counts: {}, carts: [] };
        var totalPlay = (cw.carts || []).reduce(function (s, x) { return s + (x.total || 0); }, 0);
        var statsCard = '<div class="bv-cw-stats">' +
            '<div class="st"><div class="v">' + ((cw.counts && cw.counts.converted) || 0) + '</div><div class="k">Pedidos</div></div>' +
            '<div class="st"><div class="v">' + money(totalPlay) + '</div><div class="k">En juego</div></div>' +
            '<div class="st"><div class="v">' + ((cw.counts && cw.counts.all) || (cw.carts || []).length) + '</div><div class="k">Carritos</div></div>' +
        '</div>';
        $('#cwInfo').html(
            '<div class="bv-cw-c360-head"><div class="bv-cw-c360-av">' + esc(initials) + '</div>' +
                '<div><div class="bv-cw-c360-nm">' + esc(c.name || '—') + '</div>' +
                '<div class="bv-cw-c360-since">' + esc(c.city || c.country || '') + '</div></div></div>' +
            statsCard +
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><div class="bv-po-card-ht"><span class="t">Contacto</span><span class="s">Datos del cliente</span></div></div>' +
                '<div class="bv-po-kv">' +
                    (c.name ? '<div><div class="k">Nombre</div><div class="v">' + esc(c.name) + '</div></div>' : '') +
                    (c.email ? '<div><div class="k">Correo</div><div class="v mono">' + esc(c.email) + '</div></div>' : '') +
                    (c.phone ? '<div><div class="k">Teléfono</div><div class="v mono">' + esc(c.phone) + '</div></div>' : '') +
                    ((c.city || c.country) ? '<div><div class="k">Ubicación</div><div class="v">' + esc([c.city, c.country].filter(Boolean).join(' · ')) + '</div></div>' : '') +
                '</div>' +
            '</div>' +
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-bolt"></i></span><div class="bv-po-card-ht"><span class="t">Acciones rápidas</span></div></div>' +
                '<div class="bv-cw-quick">' +
                    '<button type="button" class="btn-secondary" data-cw-tab="carritos">Ver carritos</button>' +
                    '<button type="button" class="btn-secondary" id="cwGoOrder">Generar pedido</button>' +
                '</div>' +
            '</div>'
        );
    }
    $(document).on('click', '#cwGoOrder', function () { showCheckoutView('order'); });

    // CARRITOS (real vía /carts)
    function loadCarritos(cb) {
        var base = C.base(); if (!base) { return; }
        if (_cartsLoaded && !cb) { return; }
        if (!_cartsLoaded) { $('#cwCarritos').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando carritos…</div>'); }
        C.ajax({ url: base + '/carts', method: 'GET' })
            .done(function (resp) { _cartsLoaded = true; window._cwCarts = resp; renderCarritos(resp); if (cb) { cb(); } })
            .fail(function () { $('#cwCarritos').html('<div class="bv-po-empty">No se pudieron cargar los carritos.</div>'); });
    }
    window.refreshCartsTab = function () { _cartsLoaded = false; loadCarritos(); };

    function cstClass(group) { return group === 'converted' ? 'conv' : (group === 'abandoned' ? 'aband' : 'act'); }

    function renderCarritos(data) {
        var counts = data.counts || {}; var carts = data.carts || [];
        var active = carts.filter(function (c) { return c.status_group === 'active'; })[0];
        var totalPlay = carts.reduce(function (s, c) { return s + (c.total || 0); }, 0);

        var html = '<div class="bv-po-card">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-cart-shopping"></i></span>' +
                '<div class="bv-po-card-ht"><span class="t">Resumen de carritos</span><span class="s">' + (counts.all || carts.length) + ' carritos · ' + money(totalPlay) + ' en juego</span></div></div>' +
            '<div class="bv-cw-mini">' +
                '<div class="m"><div class="v">' + (counts.active || 0) + '</div><div class="k">Activos</div></div>' +
                '<div class="m"><div class="v">' + (counts.abandoned || 0) + '</div><div class="k">Abandonados</div></div>' +
                '<div class="m"><div class="v">' + (counts.converted || 0) + '</div><div class="k">Convertidos</div></div>' +
            '</div></div>';

        if (active) {
            // Barra: proporción REAL del carrito activo frente al carrito de
            // mayor valor del cliente (no un dato inventado).
            var maxTotal = carts.reduce(function (m, c) { return Math.max(m, c.total || 0); }, 0) || 1;
            var pct = Math.min(100, Math.round((active.total || 0) / maxTotal * 100));
            html += '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-bolt"></i></span><span class="bv-po-card-ht"><span class="t">Activo ahora</span></span><span class="bv-cw-dot"></span></div>' +
                '<div class="bv-cw-live"><div class="top"><span class="lbl-live">EN VIVO</span><span class="id">' + esc(active.reference) + '</span></div>' +
                '<div class="price">' + money(active.total) + '</div>' +
                '<div class="meta">' + (active.items_count || 0) + ' artículos · ' + (active.units_count || 0) + ' uds. · actualizado ' + esc(active.updated_at_human || '') + '</div>' +
                '<div class="bv-cw-bar"><span data-w="' + pct + '"></span></div>' +
                '<div class="bv-cw-cap">' + pct + '% del carrito más alto del cliente</div></div></div>';
        }

        html += '<div class="bv-po-card">' +
            '<div class="bv-po-card-h"><div class="bv-po-card-ht"><span class="t">Todos los carritos</span><span class="s">activos · abandonados · convertidos</span></div></div>' +
            '<div class="bv-cw-filter">' +
                '<button type="button" class="bv-cw-pill on" data-cwf="all">Todos <span class="c">' + (counts.all || carts.length) + '</span></button>' +
                '<button type="button" class="bv-cw-pill" data-cwf="active">Activos <span class="c">' + (counts.active || 0) + '</span></button>' +
                '<button type="button" class="bv-cw-pill" data-cwf="abandoned">Abandonados <span class="c">' + (counts.abandoned || 0) + '</span></button>' +
                '<button type="button" class="bv-cw-pill" data-cwf="converted">Convertidos <span class="c">' + (counts.converted || 0) + '</span></button>' +
            '</div>' +
            '<div class="bv-cw-rows">' + carts.map(function (c) {
                var extra = c.order_code ? '<i class="fas fa-check"></i> Pedido ' + esc(c.order_code) : (c.items_count + ' artículo(s) · ' + esc(c.created_at_human || ''));
                return '<div class="bv-cw-row" data-cwg="' + c.status_group + '">' +
                    '<div class="r1"><span class="cid">' + esc(c.reference) + '</span><span class="cst ' + cstClass(c.status_group) + '">' + esc(c.status_label) + '</span></div>' +
                    '<div class="r2"><span>' + extra + '</span><span class="amt">' + money(c.total) + '</span></div></div>';
            }).join('') + '</div>' +
            ((counts.abandoned || 0) > 0
                ? '<button type="button" class="btn-secondary w-100 bv-cw-recover" id="cwRecover"><i class="fas fa-paper-plane"></i> Recuperar abandonados</button>'
                : '') +
            '</div>';

        $('#cwCarritos').html(html);
        // Ancho de la barra (sin estilos inline en el HTML).
        $('#cwCarritos .bv-cw-bar span').each(function () { this.style.width = ($(this).data('w') || 0) + '%'; });
    }

    // Recuperar abandonados: no hay sistema de recordatorios de carrito en el
    // backend, así que se informa con honestidad en vez de simular un envío.
    $(document).on('click', '#cwRecover', function () {
        if (window.toastr) { toastr.info('Los recordatorios de carrito abandonado se gestionan desde Campañas.'); }
    });

    $(document).on('click', '#cwCarritos .bv-cw-pill', function () {
        var f = $(this).data('cwf');
        $('#cwCarritos .bv-cw-pill').removeClass('on'); $(this).addClass('on');
        $('#cwCarritos .bv-cw-row').each(function () { $(this).toggle(f === 'all' || $(this).data('cwg') === f); });
    });

    // PEDIDOS: pedidos reales de la tienda (PS/ERP, ya cargados en el inbox) +
    // pedidos generados desde carritos asistidos.
    function storeOrders() {
        return $('.rp3-order[data-order-platform]').map(function () {
            var $o = $(this);
            var raw = String($o.data('order-total') || '0').replace(/\./g, '').replace(',', '.');
            return { ref: $o.data('order-ref') || ('#' + $o.data('order-id')), platform: $o.data('order-platform'),
                status: $o.data('order-status') || '', date: $o.data('order-date') || '', total: parseFloat(raw) || 0 };
        }).get();
    }

    function renderPedidos() {
        var store = storeOrders();
        var carts = (window._cwCarts && window._cwCarts.carts) || [];
        var ordered = carts.filter(function (c) { return c.order_code; });
        var html = '';

        if (store.length) {
            html += '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-box"></i></span><div class="bv-po-card-ht"><span class="t">Pedidos del cliente</span><span class="s">' + store.length + ' pedido(s) en la tienda</span></div></div>' +
                store.map(function (o) {
                    return '<div class="bv-cw-ord"><div class="oi"><i class="fas fa-box"></i></div>' +
                        '<div class="ob"><div class="n">' + esc(o.ref) + '</div><div class="m">' + esc(o.status) + (o.date ? ' · ' + esc(o.date) : '') + '</div></div>' +
                        '<div class="oa">' + money(o.total) + '</div></div>';
                }).join('') + '</div>';
        }
        if (ordered.length) {
            html += '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-cart-arrow-down"></i></span><div class="bv-po-card-ht"><span class="t">Generados desde carrito</span><span class="s">' + ordered.length + ' pedido(s)</span></div></div>' +
                ordered.map(function (c) {
                    return '<div class="bv-cw-ord"><div class="oi"><i class="fas fa-box"></i></div>' +
                        '<div class="ob"><div class="n">' + esc(c.order_code) + '</div><div class="m">Desde ' + esc(c.reference) + ' · ' + esc(c.updated_at_human || '') + '</div></div>' +
                        '<div class="oa">' + money(c.total) + '</div></div>';
                }).join('') + '</div>';
        }
        if (!html) { html = '<div class="bv-po-card"><div class="bv-po-empty">Este cliente aún no tiene pedidos.</div></div>'; }
        $('#cwPedidos').html(html);
    }

    // NOTAS — estado honesto (sin endpoint de notas de cliente en este modal)
    function renderNotas() {
        $('#cwNotas').html('<div class="bv-po-card">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-pen-to-square"></i></span><div class="bv-po-card-ht"><span class="t">Notas del cliente</span><span class="s">Anotaciones internas</span></div></div>' +
            '<div class="bv-po-empty">Gestiona las notas del cliente desde el panel «Nota» de la conversación.</div></div>');
    }

    // ACTIVIDAD — timeline real de carritos
    function renderActividad() {
        var carts = (window._cwCarts && window._cwCarts.carts) || [];
        if (!carts.length) { $('#cwActividad').html('<div class="bv-po-card"><div class="bv-po-empty">Sin actividad de carritos.</div></div>'); return; }
        $('#cwActividad').html('<div class="bv-po-card">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-clock-rotate-left"></i></span><div class="bv-po-card-ht"><span class="t">Actividad</span><span class="s">Carritos del cliente</span></div></div>' +
            '<div class="bv-po-tl">' + carts.map(function (c) {
                var ok = c.status_group === 'converted';
                return '<div class="bv-po-tl-item"><div class="bv-po-tl-dot' + (ok ? ' ok' : '') + '"></div>' +
                    '<div class="bv-po-tl-lbl">' + esc(c.reference) + ' · ' + esc(c.status_label) + (c.order_code ? ' → ' + esc(c.order_code) : '') + '</div>' +
                    '<div class="bv-po-tl-sub">' + esc(c.created_at_human || '') + ' · ' + money(c.total) + '</div></div>';
            }).join('') + '</div></div>');
    }

    // ── API pública ────────────────────────────────────────────────────────
    window.openCartBuild = function () {
        if (!C.customerId()) { if (window.toastr) { toastr.warning('Selecciona una conversación con cliente.'); } return; }
        // Reset a pestaña Carritos
        $('#cwTabs .bv-po-tab').removeClass('on').filter('[data-cw-tab="carritos"]').addClass('on');
        $('[data-bv-modal-name="cart-build"] .bv-po-panel').addClass('bv-hidden').filter('[data-cw-panel="carritos"]').removeClass('bv-hidden');
        _cartsLoaded = false;
        C.open('cart-build');
        loadCart();
        loadCarritos();
    };
})();

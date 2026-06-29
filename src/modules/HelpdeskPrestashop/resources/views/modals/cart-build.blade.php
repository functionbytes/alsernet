{{-- Modal: Carrito en construcción (asistido por el agente) --}}
<div class="bv-modal" data-bv-modal-name="cart-build">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-cart-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · CARRITO</span>
                <div class="bv-modal-title"><span>Carrito en construcción</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">
            {{-- Vista A: edición del carrito --}}
            <div id="cbCartView">
                <div class="search-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="cbProductSearch" placeholder="Buscar producto para añadir…" autocomplete="off">
                </div>
                <div class="bv-oc-search-results bv-hidden" id="cbSearchResults"></div>

                <div class="field">
                    <div class="flabel bv-oc-section-title">Artículos en el carrito</div>
                    <div id="cbLines"></div>
                </div>

                <div class="field">
                    <div class="flabel bv-oc-section-title">Código de descuento</div>
                    <div id="cbDiscountApplied"></div>
                    <div class="bv-oc-discount-row">
                        <input type="text" class="bv-oc-input" id="cbDiscountCode" placeholder="Introducir código…">
                        <button class="btn-secondary" id="cbApplyDiscount" type="button">Aplicar</button>
                    </div>
                </div>

                <div class="bv-cart-totals" id="cbTotals"></div>
            </div>

            {{-- Vista B: datos de envío (checkout) --}}
            <div id="cbCheckoutView" class="bv-hidden">
                <div class="bv-oc-section-title" id="cbCheckoutTitle">Datos de envío</div>
                <div class="bv-oc-field">
                    <label>Nombre</label>
                    <input type="text" class="bv-oc-input" id="cbName">
                </div>
                <div class="bv-oc-checkout-grid">
                    <div class="bv-oc-field">
                        <label>Correo</label>
                        <input type="email" class="bv-oc-input" id="cbEmail">
                    </div>
                    <div class="bv-oc-field">
                        <label>Teléfono</label>
                        <input type="text" class="bv-oc-input" id="cbPhone">
                    </div>
                </div>
                <div class="bv-oc-field">
                    <label>Dirección</label>
                    <input type="text" class="bv-oc-input" id="cbAddress" placeholder="Calle, número, piso…">
                </div>
                <div class="bv-oc-checkout-grid">
                    <div class="bv-oc-field">
                        <label>Ciudad</label>
                        <input type="text" class="bv-oc-input" id="cbCity">
                    </div>
                    <div class="bv-oc-field">
                        <label>Provincia</label>
                        <input type="text" class="bv-oc-input" id="cbState">
                    </div>
                </div>
                <div class="bv-oc-checkout-grid">
                    <div class="bv-oc-field">
                        <label>Código postal</label>
                        <input type="text" class="bv-oc-input" id="cbZip">
                    </div>
                    <div class="bv-oc-field">
                        <label>País</label>
                        <input type="text" class="bv-oc-input" id="cbCountry">
                    </div>
                </div>
            </div>
        </div>

        <div class="bv-modal-foot">
            {{-- Footer vista carrito --}}
            <div id="cbFootCart">
                <button class="btn-primary" id="cbGenerateOrder" type="button">Generar pedido</button>
                <button class="btn-secondary" id="cbSendLink" type="button">Enviar link de pago al cliente</button>
                <button class="btn-secondary" id="cbClear" type="button">Vaciar carrito</button>
                <button class="btn-secondary" data-bv-close>Cerrar</button>
            </div>
            {{-- Footer vista checkout --}}
            <div id="cbFootCheckout" class="bv-hidden">
                <button class="btn-primary" id="cbConfirm" type="button">Confirmar</button>
                <button class="btn-secondary" id="cbBack" type="button">Volver al carrito</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var _cart = null;
    var _pending = null; // 'order' | 'payment'
    var _searchTimer = null;

    function body() { return $('[data-bv-modal-name="cart-build"]'); }

    function emptyCart() {
        return '<div class="bv-oc-empty"><i class="fas fa-cart-shopping"></i>' +
            '<div class="title">El carrito está vacío</div>' +
            '<div>Busca productos arriba para añadirlos</div></div>';
    }

    function renderCart(cart) {
        _cart = cart;
        var items = (cart && cart.items) || [];

        if (!items.length) {
            $('#cbLines').html(emptyCart());
        } else {
            var html = items.map(function (it) {
                var thumb = it.image_url
                    ? '<div class="thumb"><img src="' + HDCommerce.esc(it.image_url) + '" alt=""></div>'
                    : '<div class="thumb"><i class="fas fa-box"></i></div>';
                return '<div class="bv-cart-line" data-item-id="' + it.id + '">' +
                    thumb +
                    '<div class="body">' +
                        '<span class="nm">' + HDCommerce.esc(it.name) + '</span>' +
                        '<span class="sku">' + (it.sku ? 'Ref: ' + HDCommerce.esc(it.sku) : '') + '</span>' +
                    '</div>' +
                    '<div class="bv-qty-stepper">' +
                        '<button type="button" data-step="-1">−</button>' +
                        '<span class="v">' + it.quantity + '</span>' +
                        '<button type="button" data-step="1">+</button>' +
                    '</div>' +
                    '<span class="price">' + HDCommerce.money(it.line_total) + '</span>' +
                    '<button class="rm" type="button" title="Quitar"><i class="fas fa-xmark"></i></button>' +
                '</div>';
            }).join('');
            $('#cbLines').html(html);
        }

        // Descuento aplicado
        if (cart && cart.discount_code) {
            $('#cbDiscountApplied').html(
                '<div class="bv-oc-discount-applied"><i class="fas fa-tag"></i> ' +
                HDCommerce.esc(cart.discount_code) + ' (−' + HDCommerce.money(cart.discount_amount) + ')' +
                '<span class="x" id="cbRemoveDiscount"><i class="fas fa-xmark"></i></span></div>'
            );
            $('#cbDiscountCode').val('');
        } else {
            $('#cbDiscountApplied').html('');
        }

        // Totales
        var rows = '';
        rows += '<div class="row"><span class="k">Subtotal (' + (cart.units_count || 0) + ' ud.)</span><span class="v">' + HDCommerce.money(cart.subtotal) + '</span></div>';
        if (cart.shipping_amount > 0) {
            rows += '<div class="row"><span class="k">Envío</span><span class="v">' + HDCommerce.money(cart.shipping_amount) + '</span></div>';
        }
        if (cart.discount_amount > 0) {
            rows += '<div class="row"><span class="k">Descuento</span><span class="v">− ' + HDCommerce.money(cart.discount_amount) + '</span></div>';
        }
        rows += '<div class="row total"><span class="k">Total</span><span class="v">' + HDCommerce.money(cart.total) + '</span></div>';
        $('#cbTotals').html(rows);

        // Habilitar/deshabilitar acciones
        var disabled = !items.length;
        $('#cbGenerateOrder, #cbSendLink, #cbClear').prop('disabled', disabled);
    }

    function loadCart() {
        var base = HDCommerce.base();
        if (!base) { return; }
        showCartView();
        $('#cbLines').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        $('#cbTotals').html('');
        HDCommerce.ajax({
            url: base + '/cart',
            method: 'GET',
            data: { conversation_id: HDCommerce.conversationId() },
        }).done(function (resp) {
            renderCart(resp.cart);
        }).fail(function () {
            $('#cbLines').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">No se pudo cargar el carrito</div></div>');
        });
    }

    // ── Búsqueda de productos ──
    function renderSearchResults(products) {
        if (!products || !products.length) {
            $('#cbSearchResults').html('<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i><div>Sin resultados</div></div>').removeClass('bv-hidden');
            return;
        }
        var html = products.map(function (p) {
            var thumb = p.image
                ? '<div class="thumb"><img src="' + HDCommerce.esc(p.image) + '" alt=""></div>'
                : '<div class="thumb"><i class="fas fa-box"></i></div>';
            return '<button class="bv-oc-product" type="button" data-product-id="' + p.id + '">' +
                thumb +
                '<span class="nm">' + HDCommerce.esc(p.name) + '</span>' +
                '<span class="price">' + HDCommerce.money(p.price) + '</span>' +
            '</button>';
        }).join('');
        $('#cbSearchResults').html(html).removeClass('bv-hidden');
    }

    $(document).on('input', '#cbProductSearch', function () {
        var q = $(this).val().trim();
        clearTimeout(_searchTimer);
        if (q.length < 2) {
            $('#cbSearchResults').addClass('bv-hidden').html('');
            return;
        }
        _searchTimer = setTimeout(function () {
            $.ajax({
                url: '/api/v1/ecommerce/products/suggestions',
                method: 'GET',
                dataType: 'json',
                data: { q: q },
            }).done(function (resp) {
                renderSearchResults(resp.products || []);
            });
        }, 250);
    });

    $(document).on('click', '#cbSearchResults .bv-oc-product', function () {
        var productId = $(this).data('product-id');
        var base = HDCommerce.base();
        if (!base || !productId) { return; }
        HDCommerce.ajax({
            url: base + '/cart/items',
            method: 'POST',
            data: { product_id: productId, quantity: 1, conversation_id: HDCommerce.conversationId() },
        }).done(function (resp) {
            renderCart(resp.cart);
            $('#cbProductSearch').val('');
            $('#cbSearchResults').addClass('bv-hidden').html('');
        }).fail(function (xhr) {
            toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo añadir el producto.'));
        });
    });

    // ── Stepper de cantidad ──
    $(document).on('click', '#cbLines .bv-qty-stepper button', function () {
        var $line = $(this).closest('.bv-cart-line');
        var itemId = $line.data('item-id');
        var step = parseInt($(this).data('step'), 10);
        var current = parseInt($line.find('.bv-qty-stepper .v').text(), 10) || 1;
        var next = current + step;
        var base = HDCommerce.base();
        if (!base) { return; }
        HDCommerce.ajax({
            url: base + '/cart/items/' + itemId,
            method: 'PATCH',
            data: { quantity: next },
        }).done(function (resp) {
            renderCart(resp.cart);
        }).fail(function (xhr) {
            toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo actualizar la cantidad.'));
        });
    });

    // ── Quitar línea ──
    $(document).on('click', '#cbLines .bv-cart-line .rm', function () {
        var itemId = $(this).closest('.bv-cart-line').data('item-id');
        var base = HDCommerce.base();
        if (!base) { return; }
        HDCommerce.ajax({
            url: base + '/cart/items/' + itemId,
            method: 'DELETE',
        }).done(function (resp) {
            renderCart(resp.cart);
        }).fail(function (xhr) {
            toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo quitar el producto.'));
        });
    });

    // ── Aplicar / quitar descuento ──
    function applyDiscount(code) {
        var base = HDCommerce.base();
        if (!base) { return; }
        HDCommerce.ajax({
            url: base + '/cart/discount',
            method: 'POST',
            data: { code: code },
        }).done(function (resp) {
            renderCart(resp.cart);
            if (resp.message) { toastr[resp.success ? 'success' : 'warning'](resp.message); }
        }).fail(function (xhr) {
            toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo aplicar el descuento.'));
        });
    }

    $(document).on('click', '#cbApplyDiscount', function () {
        var code = $('#cbDiscountCode').val().trim();
        if (!code) { return; }
        applyDiscount(code);
    });

    $(document).on('click', '#cbRemoveDiscount', function () {
        applyDiscount('');
    });

    // ── Vaciar carrito ──
    $(document).on('click', '#cbClear', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        HDCommerce.ajax({
            url: base + '/cart/clear',
            method: 'POST',
        }).done(function (resp) {
            renderCart(resp.cart);
            toastr.info('Carrito vaciado.');
        }).fail(function (xhr) {
            toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo vaciar el carrito.'));
        });
    });

    // ── Vistas: carrito ↔ checkout ──
    function showCartView() {
        $('#cbCartView').removeClass('bv-hidden');
        $('#cbCheckoutView').addClass('bv-hidden');
        $('#cbFootCart').removeClass('bv-hidden');
        $('#cbFootCheckout').addClass('bv-hidden');
    }

    function showCheckoutView(action) {
        _pending = action;
        var c = HDCommerce.customer();
        $('#cbName').val(c.name);
        $('#cbEmail').val(c.email);
        $('#cbPhone').val(c.phone);
        $('#cbCity').val(c.city);
        $('#cbState').val(c.state);
        $('#cbZip').val(c.zip_code);
        $('#cbCountry').val(c.country);
        $('#cbCheckoutTitle').text(action === 'payment' ? 'Datos para el link de pago' : 'Datos de envío del pedido');
        $('#cbConfirm').text(action === 'payment' ? 'Confirmar y enviar link' : 'Confirmar pedido');
        $('#cbCartView').addClass('bv-hidden');
        $('#cbCheckoutView').removeClass('bv-hidden');
        $('#cbFootCart').addClass('bv-hidden');
        $('#cbFootCheckout').removeClass('bv-hidden');
    }

    $(document).on('click', '#cbGenerateOrder', function () { showCheckoutView('order'); });
    $(document).on('click', '#cbSendLink', function () { showCheckoutView('payment'); });
    $(document).on('click', '#cbBack', function () { showCartView(); });

    $(document).on('click', '#cbConfirm', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        var payload = {
            name: $('#cbName').val().trim(),
            email: $('#cbEmail').val().trim(),
            phone: $('#cbPhone').val().trim(),
            address: $('#cbAddress').val().trim(),
            city: $('#cbCity').val().trim(),
            state: $('#cbState').val().trim(),
            country: $('#cbCountry').val().trim(),
            zip_code: $('#cbZip').val().trim(),
        };
        var url = base + (_pending === 'payment' ? '/cart/send-payment-link' : '/cart/generate-order');
        var $btn = $(this).prop('disabled', true);
        HDCommerce.ajax({ url: url, method: 'POST', data: payload })
            .done(function (resp) {
                toastr.success(resp.message || 'Operación completada.');
                HDCommerce.close('cart-build');
                if (typeof window.refreshCartsTab === 'function') { window.refreshCartsTab(); }
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo completar la operación.'));
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ── API pública ──
    window.openCartBuild = function () {
        if (!HDCommerce.customerId()) {
            if (window.toastr) { toastr.warning('Selecciona una conversación con cliente.'); }
            return;
        }
        HDCommerce.open('cart-build');
        loadCart();
    };
})();
</script>
@endpush
@endonce

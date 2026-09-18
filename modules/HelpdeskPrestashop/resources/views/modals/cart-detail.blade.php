{{-- Modal: Detalle de carrito (#38c ve-cart-detail) --}}
<div class="bv-modal" data-bv-modal-name="cart-detail">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-cart-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CARRITO · DETALLE</span>
                <div class="bv-modal-title"><span id="cdTitle">Carrito</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body" id="cdBody">
            <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="cdConvert">Convertir a pedido</button>
            <button class="btn-secondary" id="cdEdit">Editar carrito</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var _cartId = null;

    /* ── helpers ── */
    function formatDate(iso) {
        if (!iso) { return '—'; }
        return new Date(iso).toLocaleString('es-ES', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function thumb(item) {
        if (item.image_url) {
            return '<img src="' + HDCommerce.esc(item.image_url) + '" alt="" loading="lazy">';
        }
        return '<i class="fas fa-box"></i>';
    }

    function renderItems(items) {
        if (!items || !items.length) {
            return '<div class="bv-text-muted-12">Sin artículos</div>';
        }
        return items.map(function (it) {
            return '<div class="bv-cart-line">' +
                '<div class="thumb">' + thumb(it) + '</div>' +
                '<div class="body">' +
                    '<span class="nm">' + HDCommerce.esc(it.name) + '</span>' +
                    '<span class="sku">Ref: ' + HDCommerce.esc(it.sku) + ' · ×' + it.quantity + ' · ' + HDCommerce.money(it.unit_price) + '/ud</span>' +
                '</div>' +
                '<span class="price">' + HDCommerce.money(it.line_total) + '</span>' +
            '</div>';
        }).join('');
    }

    function renderTotals(cart) {
        var rows = '<div class="row"><span class="k">Subtotal (' + cart.units_count + ' ud.)</span><span class="v">' + HDCommerce.money(cart.subtotal) + '</span></div>' +
            '<div class="row"><span class="k">Envío estimado</span><span class="v">' + HDCommerce.money(cart.shipping_amount) + '</span></div>';

        if (cart.discount_amount > 0) {
            rows += '<div class="row"><span class="k">Descuento</span><span class="v">−' + HDCommerce.money(cart.discount_amount) + '</span></div>';
        }

        rows += '<div class="row total"><span class="k">Total</span><span class="v">' + HDCommerce.money(cart.total) + '</span></div>';
        return rows;
    }

    function renderTimeline(events) {
        if (!events || !events.length) {
            return '<div class="bv-text-muted-12">Sin eventos registrados</div>';
        }
        return events.map(function (ev) {
            return '<div class="bv-cart-tl-item">' +
                '<div class="ic"><i class="fas ' + HDCommerce.esc(ev.icon) + '"></i></div>' +
                '<div class="body"><div class="t">' + HDCommerce.esc(ev.title) + '</div><div class="s">' + HDCommerce.esc(ev.at_human) + '</div></div>' +
            '</div>';
        }).join('');
    }

    function render(cart) {
        $('#cdTitle').text(cart.reference || 'Carrito');

        var html = '<div class="info-table">' +
            '<div class="lbl">Cliente</div><div class="val">—</div>' +
            '<div class="lbl">Estado</div><div class="val"><span class="r-tag">' + HDCommerce.esc(cart.status_label) + '</span></div>' +
            '<div class="lbl">Iniciado</div><div class="val">' + formatDate(cart.created_at) + '</div>' +
            '<div class="lbl">Origen</div><div class="val">Chat · soporte</div>' +
        '</div>' +

        '<div class="field">' +
            '<div class="flabel">Líneas del carrito <span class="hint">' + cart.items_count + ' artículos · ' + cart.units_count + ' unidades</span></div>' +
            '<div class="bv-cart-lines">' + renderItems(cart.items) + '</div>' +
        '</div>' +

        '<div class="bv-cart-totals">' + renderTotals(cart) + '</div>' +

        '<div class="field">' +
            '<div class="flabel">Eventos del carrito</div>' +
            '<div class="bv-cart-timeline">' + renderTimeline(cart.timeline) + '</div>' +
        '</div>';

        $('#cdBody').html(html);

        if (!cart.editable) {
            $('#cdConvert, #cdEdit').prop('disabled', true);
        } else {
            $('#cdConvert, #cdEdit').prop('disabled', false);
        }
    }

    /* ── load ── */
    function loadCart(cartId) {
        $('#cdTitle').text('Carrito');
        $('#cdBody').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        $('#cdConvert, #cdEdit').prop('disabled', true);

        $.ajax({
            url: HDCommerce.base() + '/carts/' + cartId,
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (resp) {
            if (resp.success && resp.cart) {
                render(resp.cart);
            } else {
                $('#cdBody').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">Sin datos</div></div>');
            }
        }).fail(function () {
            $('#cdBody').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">No se pudo cargar</div></div>');
        });
    }

    /* ── footer actions ── */
    $('#cdConvert, #cdEdit').on('click', function () {
        HDCommerce.close('cart-detail');
        if (typeof window.openCartBuild === 'function') {
            window.openCartBuild();
        }
    });

    /* ── public API ── */
    window.openCartDetail = function (cartId) {
        if (!HDCommerce.customerId() || !cartId) { return; }
        _cartId = cartId;
        HDCommerce.open('cart-detail');
        loadCart(cartId);
    };
}());
</script>
@endpush
@endonce

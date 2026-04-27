<div id="miniCartDrawer" class="mini-cart-drawer" aria-hidden="true">
    <div class="mini-cart-overlay" id="miniCartOverlay"></div>
    <aside class="mini-cart-panel" role="dialog" aria-modal="true" aria-label="Mini carrito">
        <div class="mini-cart-header d-flex justify-content-between align-items-center p-3 border-bottom">
            <h5 class="mb-0 fw-bold">Mi carrito</h5>
            <button type="button" class="btn-close" id="miniCartClose" aria-label="Cerrar"></button>
        </div>
        <div class="mini-cart-body p-3" id="miniCartBody">
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
            </div>
        </div>
        <div class="mini-cart-footer p-3 border-top bg-light">
            <a href="{{ route('cart.index') }}" class="btn btn-outline-primary w-100 mb-2">Ver carrito completo</a>
            <a href="{{ route('checkout.index') }}" class="btn btn-primary w-100">Ir al checkout</a>
        </div>
    </aside>
</div>

<style>
.mini-cart-drawer {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1050;
}
.mini-cart-drawer.open { display: block; }
.mini-cart-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
}
.mini-cart-panel {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 380px;
    max-width: 100vw;
    background: #fff;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
}
.mini-cart-drawer.open .mini-cart-panel { transform: translateX(0); }
.mini-cart-body { flex: 1; overflow-y: auto; }
.mini-cart-item {
    display: flex;
    gap: 12px;
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px solid #e9ecef;
}
.mini-cart-item:last-child { border-bottom: none; }
.mini-cart-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; flex-shrink: 0; }
</style>

<script>
$(function () {
    var $drawer = $('#miniCartDrawer');

    function openCartDrawer(e) {
        if (e) { e.preventDefault(); }
        $drawer.addClass('open').attr('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        loadMiniCart();
    }

    function closeCartDrawer() {
        $drawer.removeClass('open').attr('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function loadMiniCart() {
        $.get('/api/v1/ecommerce/cart')
            .done(function (data) {
                if (!data.length) {
                    $('#miniCartBody').html(
                        '<div class="text-center py-5 text-muted">' +
                        '<i class="fas fa-shopping-cart fa-3x opacity-25 mb-3 d-block"></i>' +
                        '<p class="mb-0">Tu carrito está vacío</p>' +
                        '</div>'
                    );
                    return;
                }

                var items = data.map(function (item) {
                    var name  = $('<span>').text(item.product ? item.product.name : 'Producto').html();
                    var image = item.product && item.product.featured_image
                        ? '/storage/' + item.product.featured_image
                        : '/modules/ecommerce/images/404.png';
                    var price = item.product ? parseFloat(item.product.price || 0).toFixed(2) : '0.00';

                    return '<div class="mini-cart-item">' +
                        '<img src="' + image + '" alt="">' +
                        '<div class="flex-grow-1">' +
                        '<p class="small fw-semibold mb-1">' + name + '</p>' +
                        '<p class="small text-muted mb-0">Cant: ' + item.qty + '</p>' +
                        '</div>' +
                        '<span class="small fw-bold text-brand">$' + price + '</span>' +
                        '</div>';
                });

                $('#miniCartBody').html(items.join(''));
            })
            .fail(function () {
                $('#miniCartBody').html(
                    '<p class="text-center py-3 text-muted small">No se pudo cargar el carrito.</p>'
                );
            });
    }

    $(document).on('click', '.mini-cart-icon, .js-open-cart-drawer', openCartDrawer);
    $('#miniCartClose, #miniCartOverlay').on('click', closeCartDrawer);
    $(document).on('keydown', function (e) { if (e.key === 'Escape') { closeCartDrawer(); } });
    $(document).on('cartUpdated', loadMiniCart);
});
</script>

<script>
window.ecommerceAnalytics = {
    track: function(eventName, params) {
        params = params || {};

        if (typeof gtag === 'function') {
            gtag('event', eventName, params);
        }

        if (window.dataLayer) {
            window.dataLayer.push(Object.assign({event: eventName}, params));
        }

        if (typeof fbq === 'function') {
            var pixelMap = {
                'view_item':       'ViewContent',
                'add_to_cart':     'AddToCart',
                'begin_checkout':  'InitiateCheckout',
                'purchase':        'Purchase',
                'add_to_wishlist': 'AddToWishlist',
                'search':          'Search',
            };
            var pixelEvent = pixelMap[eventName];
            if (pixelEvent) fbq('track', pixelEvent, params);
        }
    }
};

$(document).on('submit', '.js-add-to-cart', function() {
    var $form = $(this);
    var productName = $form.closest('.product-cart-wrap, .product-detail').find('.product-content-wrap h2, .title-detail').first().text().trim();
    window.ecommerceAnalytics.track('add_to_cart', {item_name: productName});
});
</script>

$(document).ready(function() {

    // alert("cambio");

    function actionAnalytics() {


        window.dataLayer = window.dataLayer || [];

        items = $('#product_analytics').val();

        customer = JSON.parse(r.customer_analytics);

        user_id = customer.user_id;
        user_type = customer.user_type;
        country = customer.country;
        page_type = customer.page_type;
        currency = customer.currency;
        item_list_id = customer.item_list_id;
        item_list_name = customer.item_list_name;

        window.dataLayer.push({
            'event': 'view_item_list',
            'user_id': user_id,
            'user_type': user_type,
            'country': country,
            'page_type': page_type,
            'ecommerce': {
                'currency': currency,
                'item_list_id': item_list_id,
                'item_list_name': item_list_name,
                'items' : items
            },
        });
    }


    $("body").on("click", ".product-miniature", function(e) {

        console.log("product-miniature");
        var $this = $(this);
        var product = $this.data('id-product');
        var category = $('#category_analytics').val();

        $.ajax({
            cache: true,
            url: "/module/alsernetgooglegtm/gtm?method=select&product="+product+"&category="+category
        }).done(function(data) {

            console.log(data);
            items = [];

            window.dataLayer = window.dataLayer || [];

            let customer = data.customer_analytics;
            let product = data.product_analytics;

            items.push({
                'item_id': product.item_id,
                'item_name': product.item_name,
                'item_brand': product.item_brand,
                'item_category': product.item_category,
                'item_variant': product.item_variant,
                'item_list_name':  product.item_list_name,
                'item_list_id':  product.item_list_id,
                'price': product.price,
                'discount': product.discount,
                'quantity': product.quantity,
                'index': 1
            });

            window.dataLayer.push({
                'event': 'select_item',
                'user_id': customer.user_id,
                'user_type': customer.user_type,
                'country': customer.country,
                'page_type': customer.page_type,
                'ecommerce': {
                    'currency': customer.currency,
                    'items' : items
                },
            });


        });

    });

});

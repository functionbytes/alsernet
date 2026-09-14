{if $product.view == "fitting"}
    <div class="js-product-add-to-cart">
        {if (!isset($request_price) || !$request_price.value) && (!isset($phone_sale) || !$phone_sale.value) && (!isset($custom_show_add_basket_btn) || $custom_show_add_basket_btn)}
            <button class="btn btn-primary {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0)}disabled{/if} add-cart-product"
                    data-id-cart="{$cart.id}" data-id-product="{$product.id}"
                    data-id-product-attribute="{$product.id_product_attribute}" {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0) || !empty($product.country_blocked)} disabled{/if}>
                {l s='Booking fitting' d='Shop.Theme.Actions'}
            </button>
        {/if}
    </div>
{elseif $product.view == "hunt" || $product.view == "demoday"}
    <div class="js-product-add-to-cart">
        <div class="product-quantity">
            <button class="btn btn-primary add-cart-product" data-id-cart="{$cart.id}" data-id-product="{$product.id}"
                    data-id-product-attribute="{$product.id_product_attribute}" {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0) || !empty($product.country_blocked)} disabled{/if}>
                {l s='Booking demoday' d='Shop.Theme.Actions'}
            </button>
        </div>
    </div>
{elseif $product.view == "lotery"}
    <div class="js-product-add-to-cart">
        {if !$configuration.is_catalog}

            {if (!isset($request_price) || !$request_price.value)  && (!isset($phone_sale) || !$phone_sale.value) && (!isset($custom_show_add_basket_btn) || $custom_show_add_basket_btn)}
                <div class="product-sticky">

                    {include file="catalog/_partials/product-prices-sticky.tpl" product=$product}

                    <button class="btn btn-primary {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0)} outstock {/if} add-cart-product"
                            data-id-cart="{$cart.id}" data-id-product="{$product.id}"
                            data-id-product-attribute="{$product.id_product_attribute}" {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0) || !empty($product.country_blocked)} disabled{/if}>
                        {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0)}
                            <i class="fa-light fa-bag-shopping"></i>
                            {l s='Out of stock' d='Shop.Theme.Actions'}
                        {else}
                            <i class="fa-light fa-bag-shopping"></i>
                            {l s='Add to cart' d='Shop.Theme.Actions'}
                        {/if}
                    </button>

                </div>
            {/if}

        {/if}
    </div>
{else}

    {if empty($product.country_blocked)}
        <div class="js-product-add-to-cart">
            {if !$configuration.is_catalog}

                {if  (!isset($product.request_price) || !is_array($product.request_price)) && (!isset($product.phone_sale) || !is_array($product.phone_sale))}
                    <div class="product-sticky">

                        {include file="catalog/_partials/product-prices-sticky.tpl" product=$product}
                        {if isset($product.specific_prices) && $product.specific_prices}
                            <button class="btn btn-primary {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0)} outstock {/if} add-cart-product"
                                    data-id-cart="{$cart.id}" data-id-product="{$product.id}"
                                    data-id-product-attribute="{$product.id_product_attribute}" {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0) || !empty($product.country_blocked)} disabled{/if}
                                    disabled>
                                {if ($product.quantity <= 0) && ($product.out_of_stock == 2 || $product.out_of_stock == 0)}
                                    <i class="fa-light fa-bag-shopping"></i>
                                    {l s='Out of stock' d='Shop.Theme.Actions'}
                                {else}
                                    <i class="fa-light fa-bag-shopping"></i>
                                    {l s='Add to cart' d='Shop.Theme.Actions'}
                                {/if}
                            </button>
                        {/if}

                    </div>
                {/if}

                {if (isset($custom_show_add_basket_btn) && !$custom_show_add_basket_btn) && isset($text_unsaleable_products) && !empty($text_unsaleable_products)}
                    <div class="add-to-cart-msg-not-show-btn">
                        {l s='To purchase this product, contact us via email: web@a-alvarez.com' sprintf=['[phone]' => $shop.phone] d='Shop.Theme.Catalog'}
                    </div>
                {/if}

                {if (isset($custom_show_add_basket_btn) && !$custom_show_add_basket_btn) && isset($text_unsaleable_products) && !empty($text_unsaleable_products)}
                    <div class="add-to-cart-msg-not-show-btn-stores">
                        <div class="product-store-states-title">{l s='Exclusive sale product in physical stores' d='Shop.Theme.Catalog'}</div>
                        <div class="row product-store-states">
                            <div class="col-sp-12 col-xs-12 col-sm-6 product-store-state-item">
                                <div class="product-store-state-item-name">{l s='Madrid' d='Shop.Theme.Global'}</div>
                                <div class="product-store-state-item-store">{l s='Capitán Haya nº60 (now Poeta Joan Maragall)' d='Shop.Theme.Global'}</div>
                                <div class="product-store-state-item-store">{l s='Diego de León, 56' d='Shop.Theme.Global'}</div>
                            </div>
                            <div class="col-sp-12 col-xs-12 col-sm-6 product-store-state-item">
                                <div class="product-store-state-item-name">{l s='A Coruña' d='Shop.Theme.Global'}</div>
                                <div class="product-store-state-item-store">{l s='Polígono de Pocomaco, Primera Avenida, 81. Parcela C-13' d='Shop.Theme.Global'}</div>
                            </div>
                        </div>
                    </div>
                {/if}

                {widget name="alsernetproducts" type="buttons" product=$product}

                {widget name="alsernetproducts" type="hour" product=$product}

                {widget name="alvarezcalplazos" product=$product}

                {if $product['paypal_banner']}
                    <div>
                        <div class="paypal-container">
                            <div class="paypal-content">
                                <div class="paypal-images">
                                    <img src="/themes/alvarez/assets/img/theme/product/comun-11_25-banda-FP-paypal-600x74-{$iso_code|upper}.jpg"
                                         class="img img-fluid manufacturer-logo" alt="Leica" loading="lazy">
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}


            {/if}
        </div>
    {/if}
{/if}




<div class="dropdown cart-dropdown cart-offcanvas">
    <div class="cart-overlay"></div>
    <div class="dropdown-box">
        <div class="cart-header">
            <span>{$translations.cart}</span>
            <a href="#" class="btn-close dropdown-menu-close"><i class="fa-light fa-xmark"></i></a>
        </div>

        {if $cart.shipping_progress.active}
            <div class="shipping-amount">
                <div class="success-box">
                    <p class="text">
                        {$translations.missing_for_free_shipping}
                        <span class="shipping">{$cart.shipping_progress.amount_remaining|number_format:2:'.':''} €</span>
                        {$translations.free_shipping_message}
                    </p>
                    <div class="progress warning-progress">
                        <div role="progressbar"
                             aria-valuenow="{$cart.shipping_progress.percentage}"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             class="progress-bar progress-bar-striped progress-bar-animated"
                             style="width: {$cart.shipping_progress.percentage}%; {if $cart.shipping_progress.percentage == 100}display: none;{/if}">
                            <i class="fa-duotone fa-solid fa-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
        {/if}

        <div class="cart-products">
            {if $cart.products|@count > 0}
                {foreach from=$cart.products item=product}
                    <div class="product-item"
                         data-id-product="{$product.id}"
                         data-id-product-attribute="{$product.id_product_attribute}"
                         data-id-cart="{$cart.id}">
                        <div class="product-detail">
                            <a class="product-name" href="{$product.url_product}" >
                                {$product.name} <span class="product-quantity">x ({$product.quantity})</span>
                            </a>

                            <p class="product-referencia">Ref. {$product.reference}</p>
                            <div class="product-detail-price">
                                <div class="product-has-price">
                                      <span class="product-price{if $product.has_discount} has-discount{/if}">
                                        {$product.price}
                                      </span>
                                </div>
                                {if $product.has_discount}
                                    <div class="product-has-discount">
                                        <div class="product-regular">
                                            <span class="price-regular">{$product.regular_price}</span>
                                        </div>
                                        <div class="product-discount">
                                            {if $product.discount_type == 'percentage'}
                                                <span class="discount-percentage">-{$product.discount_percentage_absolute}%</span>
                                            {else}
                                                <span class="discount-amount">-{$product.discount_to_display}</span>
                                            {/if}
                                        </div>
                                    </div>
                                {/if}
                            </div>
                        </div>
                        {if $product.images|@count > 0}
                            <figure class="product-media">
                                <a class="label" title="{$product.name}">
                                    <img width="84" height="94" src="{$product.images.0.bySize.small_default.url}" alt="{$product.name}"/>
                                </a>
                            </figure>
                        {/if}
                        <a class="btn btn-close delete-to-product" title="Eliminar del carrito">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                {/foreach}
            {/if}
        </div>

        <div class="cart-result">
            <div class="cart-items">
                {if isset($cart.subtotals)}
                    {foreach from=$cart.subtotals item=subtotal}
                        {if $subtotal}
                            <div class="cart-item">
                                <label>{$subtotal.label}:</label>
                                <span class="price">{sprintf('%.2f', $subtotal.amount)}€</span>
                            </div>
                        {/if}
                    {/foreach}
                {/if}
            </div>

            {if isset($cart.totals.total)}
                <div class="cart-items">
                    <div class="cart-total">
                        <label>{$cart.totals.total.label}:</label>
                        <span class="price">{$cart.totals.total.value}</span>
                    </div>
                </div>
            {/if}
        </div>

        <div class="cart-action">
            <a href="{$order_link}" class="btn btn-primary btn-rounded order-btn">{$translations.checkout}</a>
            <a href="{$cart_link}" class="btn  btn-rounded cart-btn">{$translations.view_cart}</a>
        </div>

        {if isset($cart.iva)}
            {foreach item=event from=$cart.iva}
                {if $event.total_discount_iva > 0}
                    <div class="iva-amount">
                        <div class="iva-box">
                            <p class="iva-text">
                                {$event.iva_message} <span>{$event.total_discount_iva|number_format:2:',':'.'} €</span>
                            </p>
                            <p>
                                {$translations.iva_message_additional}
                            </p>
                        </div>
                    </div>
                {/if}
            {/foreach}
        {/if}
    </div>
</div>

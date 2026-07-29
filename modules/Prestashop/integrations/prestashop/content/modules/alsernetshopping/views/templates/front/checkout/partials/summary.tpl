<div class="cart-summary">
    <div class="title-summary">
        <h5>{$translations.order_summary}</h5>
    </div>

    <div class="summary">

        <div class="cart-products-lists">
            {if $cart.products|@count > 0}
                {foreach from=$cart.products item=product}
                    <div class="product-item item-product" data-id-cart="{$id_cart}" data-id-product="{$product.id}" data-id-product-attribute="{$product.id_product_attribute}">
                        <div class="product-detail">
                            <p class="product-name">
                                <a >{$product.name}</a>
                            </p>
                            <p class="product-referencia">Ref. {$product.reference}</p>
                            {if $product.view == "fitting"}
                                <p class="referencia-prd">
                                    {$product.fitting.fitting_day} - {$product.fitting.fitting_hour}
                                    {if isset($product.fitting.fitting_location)} / {$product.fitting.fitting_location}{/if}
                                </p>
                            {/if}
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
                            {if $product.view != "fitting"}
                                <div class="quantity-body">
                                    <div class="le-quantity">
                                        <a class="minus" href="#reduce"></a>
                                        <input name="quantity" readonly type="text" data-qty-prev="{$product.quantity}"  value="{$product.quantity}" class="cart-product-quantity"   disabled>
                                        <a class="plus" href="#add"></a>
                                    </div>
                                </div>
                            {/if}

                        </div>

                        {if $product.images|@count > 0}
                            <figure class="product-media">
                                <a class="label" title="{$product.name}" >
                                    <img width="84" height="94" src="{$product.images.0.bySize.small_default.url}" alt="{$product.name}"/>
                                </a>
                            </figure>
                        {/if}
                        <a class="btn cart-button delete-to-cart" data-id-cart="{$id_cart}" data-id-product="{$product.id}" data-id-product-attribute="{$product.id_product_attribute}">
                            <i class="fa-light fa-xmark"></i>
                        </a>
                    </div>
                {/foreach}
            {/if}
        </div>

        <div class="cart-summary-subtotals">
            <ul class="summary-total">

                {foreach from=$subtotals item=sub}
                    {if isset($sub.value) && $sub.value|count_characters > 0 && !in_array($sub.type, ['tax', 'discount', 'gift_wrapping'])}
                        <li id="cart-subtotal-{$sub.type}">
                            <h4>{$sub.label}</h4>
                            <h4 class="price">{$sub.value}</h4>
                        </li>
                    {/if}
                {/foreach}



                {if $total_discounts > 0 }
                    <li class="voucher" id="cart-subtotal-shipping">
                        <h4>{$translations.with_this_purchase_you_save}</h4>
                        <h4 class="price">-{$discount|number_format:2:',':'.'} €</h4>
                    </li>
                {/if}

                {if $vouchers}
                    {foreach from=$vouchers item=voucher}
                        <li class="voucher">
                            <h4>{$voucher.name}</h4>
                            <h4 class="price">{$voucher.reduction_formatted}</h4>
                        </li>
                    {/foreach}
                {/if}

                {if isset($cart.iva)}
                    {foreach item=event from=$cart.iva}
                        {if $event.total_discount_iva > 0}
                            <li class="voucher">
                                <h4>{$event.iva_message}</h4>
                                <h4 class="price">{$event.total_discount_iva|number_format:2:',':'.'} €</h4>
                                <span style="font-size:14px;font-weight:500;color:#222;display:inline-block;width:100%;">{$translations.iva_message_additional}</span>
                            </li>
                        {/if}
                    {/foreach}
                {/if}


                <li class="coupon-sec">
                    <div class="w-100">
                        <div class="actions">
                            <p>
                                {$translations.i_have_a}

                                <span class="promotional">
                                {$translations.promotional_code}</span>
                                {$translations.or}
                                <span class="promotional"> {$translations.gift_card}</span>
                                <a class="promotion-code-buttons{if $total_discounts > 0} with-discounts{/if}">
                                    {$translations.have_a_promo_code}
                                </a>
                            </p>
                        </div>

                        <div id="promotion-code" class="d-none">
                            <div class="coupon-box mt-3 w-100">
                                <form class="coupon" id="formCoupon">
                                    <input type="text" id="coupon" name="coupon" class="form-control" placeholder="{$translations.enter_coupon_code_here}" />
                                    <input type="text" id="confirmation" name="confirmation" class="form-control" placeholder="{$translations.enter_coupon_code_here}" />

                                    <button class="btn-apply w-100" id="coupon-apply" type="button">{$translations.apply}</button>

                                    <a class="btn-close promotion-code-close">
                                        {$translations.close}
                                    </a>

                                    <label for="coupon" class="error" style="display:none"></label>


                                </form>
                            </div>

                        </div>


                        {if $vouchers}
                            <ul class="vouchers-items">
                                {foreach from=$vouchers item=voucher}
                                    {if $voucher.code}
                                        <li class="vouchers-item">
                                            <span class="label">{$voucher.name}<span>{$voucher.reduction_formatted}</span></span>
                                            <div class="close">
                                                <a  class="remove-voucher" data-rule="{$voucher.id_cart_rule}" data-code="{$voucher.code}">
                                                    <span aria-hidden="true">×</span>
                                                </a>
                                            </div>
                                        </li>
                                    {/if}
                                {/foreach}
                            </ul>
                        {/if}


                    </div>
                </li>

                {if !$configuration.display_prices_tax_incl && $configuration.taxes_enabled}
                    <div class="cart-summary-line cart-total">
                        <span class="label">{$totals.total.label}</span>
                        <span class="value">{$totals.total.value}</span>
                    </div>
                {else}
                    <li class="list-total">
                        <h4>{$totals.total.label}:</h4>
                        <h4 class="price">{$totals.total.value}</h4>
                    </li>
                {/if}

                {if isset($subtotals.tax)}
                    <li class="list-total">
                        <h4>{l s='%label%' sprintf=['%label%' => $subtotals.tax.label] mod='alsernetshopping'}</h4>
                        <h4 class="price">{$subtotals.tax.value}</h4>
                    </li>
                {/if}

            </ul>

        </div>
    </div>
</div>

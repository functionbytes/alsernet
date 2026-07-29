<div class="cart-summary">
    <div class="title-summary">
        <h5>{$translations.order_summary}</h5>
    </div>

    <div class="summary">
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

                {if $discount > 0}
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
                                {$translations.promotional_code}
                                </span>
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
                                    <input type="text" id="confirmation" name="confirmation" class="form-control" placeholder="{$translations.enter_verification_code_here}" />

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

            <a class="btn btn-primary mt-3" href="{$checkout}">{$translations.proceed_to_checkout}</a>

        </div>
    </div>
</div>

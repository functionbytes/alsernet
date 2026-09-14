
{if $cart.vouchers.allowed}
    {block name='cart_voucher'}
        <div class="block-promo">
            <div class="cart-voucher">
                {if $cart.vouchers.added}
                    {block name='cart_voucher_list'}
                        <ul class="promo-name card-block">
                            {if isset($suma_descuentos) && $suma_descuentos > 0}
                                <li class="cart-summary-line">
                                    <span class="label">{l s="En esta compra ahorras" d="Shop.Theme.Checkout"}</span>
                                    <div class="float-xs-right">
                                        <span class="label">-{$suma_descuentos|round:2|replace:'.':','} {$currency.sign}</span>
                                    </div>
                                </li>
                            {/if}
                            {foreach from=$cart.vouchers.added item=voucher}
                                <li class="cart-summary-line">
                                    <span class="label">{$voucher.name}</span>
                                    <div class="float-xs-right">
                                        <span class="label">{$voucher.reduction_formatted}</span>
                                        {if isset($voucher.code) && $voucher.code !== ''}
                                            <a href="{$voucher.delete_url}" data-link-action="remove-voucher"><i class="material-icons">clear</i></a>
                                        {/if}
                                    </div>
                                </li>
                            {/foreach}
                        </ul>
                    {/block}
                {else}
                    {block name='cart_voucher_list'}
                        {if isset($suma_descuentos) && $suma_descuentos > 0}
                            <ul class="promo-name card-block">
                                <li class="cart-summary-line">
                                    <span class="label">{l s="En esta compra ahorras" d="Shop.Theme.Checkout"}</span>
                                    <div class="float-xs-right label">
                                        <span class="label">-{$suma_descuentos|round:2|replace:'.':','} {$currency.sign}</span>
                                    </div>
                                </li>
                            </ul>
                        {/if}
                    {/block}
                {/if}

                {if isset($cart.iva)}
                    {if $cart.iva.total_discount_iva > 0}
                        {block name='cart_iva_discount'}
                                <ul class="promo-name card-block">
                                    <li class="cart-summary-line">
                                        <span class="label">{l s="Discount voucher message" d="Shop.Theme.Checkout"} {$cart.iva.total_discount_iva|round:2|replace:'.':','} {$currency.sign}</span>
                                        
                                    </li>
                                </ul>
                        {/block}
                    {/if}
                {/if}

                <!--<p class="promo-code-button display-promo{if $cart.discounts|count > 0} with-discounts{/if}">
             <a class="collapse-button" href="#promo-code">
               {l s='Have a promo code?' d='Shop.Theme.Checkout'}
             </a>
           </p>-->
                <div class="bloque-texto-desc col-md-12">
                    <p class="texto-descs"><span class="texto-lado-tick">{l s="Tengo un" d="Shop.Theme.Checkout"} <span class="dest">{l s="CÓDIGO PROMOCIONAL" d="Shop.Theme.Checkout"}</span> {l s="o" d="Shop.Theme.Checkout"} <span class="dest">{l s="TARJETA REGALO" d="Shop.Theme.Checkout"}</span></span></p>
                </div>

                <div id="promo-code" class="collapse in">
                    <div class="promo-code">
                        {block name='cart_voucher_form'}
                            <form action="{$urls.pages.cart}" data-link-action="add-voucher" method="post">
                                <input type="hidden" name="token" value="{$static_token}">
                                <input type="hidden" name="addDiscount" value="1">
                                <input class="promo-input" type="text" name="discount_name" placeholder="{l s='Promo code' d='Shop.Theme.Checkout'}">
                                <input class="verif-input" type="text" name="verif_name" placeholder="{l s='Verification code' d='Shop.Theme.Checkout'}">
                                <button type="submit" class="btn btn-primary"><span>{l s='Add' d='Shop.Theme.Actions'}</span></button>
                            </form>
                        {/block}

                        {block name='cart_voucher_notifications'}
                            <div class="alert alert-danger js-error" role="alert">
                                <i class="material-icons">&#xE001;</i><span class="ml-1 js-error-text"></span>
                            </div>
                        {/block}

                        <!--<a class="collapse-button promo-code-button cancel-promo" role="button" data-toggle="collapse" data-target="#promo-code" aria-expanded="true" aria-controls="promo-code">
                 {l s='Close' d='Shop.Theme.Checkout'}
               </a>-->
                    </div>
                </div>

                {if $cart.discounts|count > 0}
                    <p class="block-promo promo-highlighted">
                        {l s='Take advantage of our exclusive offers:' d='Shop.Theme.Actions'}
                    </p>
                    <ul class="js-discount card-block promo-discounts">
                        {foreach from=$cart.discounts item=discount}
                            <li class="cart-summary-line">
                               <span class="label">
                                 <span class="code">{$discount.code}</span> - {$discount.name}
                               </span>
                            </li>
                        {/foreach}
                    </ul>
                {/if}
            </div>
        </div>
    {/block}
{else}
    {block name='cart_voucher'}
        <div class="block-promo">
            <div class="cart-voucher">
                {block name='cart_voucher_list'}
                    <ul class="promo-name card-block">
                        <li class="cart-summary-line">
                            <span class="label">{l s="En esta compra ahorras" d="Shop.Theme.Checkout"}</span>
                            <div class="float-xs-right">
                                <span class="label">-{$suma_descuentos|round:2|replace:'.':','} {$currency.sign}</span>
                            </div>
                        </li>
                    </ul>
                {/block}
            </div>
        </div>
    {/block}
{/if}

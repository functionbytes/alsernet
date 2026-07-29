
{block name='cart_detailed_totals'}
  <div class="cart-detailed-totals">

    <div class="card-block">
      {foreach from=$cart.subtotals item="subtotal"}
        {if $subtotal && $subtotal.value|count_characters > 0 && $subtotal.type !== 'tax' && $subtotal.type !== 'discount' && $subtotal.type !== 'gift_wrapping'}
          <div class="cart-summary-line" id="cart-subtotal-{$subtotal.type}">
            <span class="label{if 'inventaries' === $subtotal.type} js-subtotal{/if}">
              {*if 'inventaries' == $subtotal.type}
                {$cart.summary_string}
              {else*}
                {$subtotal.label}
              {*/if*}
            </span>
            <span class="value">
              {if 'discount' == $subtotal.type}-&nbsp;{/if}{$subtotal.value}
            </span>
            {if $subtotal.type === 'shipping'}
                <div><small class="value">{hook h='displayCheckoutSubtotalDetails' subtotal=$subtotal}</small></div>
            {/if}
          </div>
        {/if}
      {/foreach}
    </div>

    {block name='cart_summary_totals'}
      {include file='checkout/_partials/cart-summary-totals.tpl' cart=$cart}
    {/block}

    {block name='cart_voucher'}
      {include file='checkout/_partials/cart-voucher.tpl'}
    {/block}


  </div>

{/block}

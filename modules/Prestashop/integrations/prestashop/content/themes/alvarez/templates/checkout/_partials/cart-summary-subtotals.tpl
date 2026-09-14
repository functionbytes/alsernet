

<div class="card-block cart-summary-subtotals-container">

  {foreach from=$cart.subtotals item="subtotal"}
    {if $subtotal && $subtotal.value|count_characters > 0 && $subtotal.type !== 'tax' && $subtotal.type !== 'discount' && $subtotal.type !== 'gift_wrapping'}
      <div class="cart-summary-line cart-summary-subtotals" id="cart-subtotal-{$subtotal.type}">

        <span class="label">
            {$subtotal.label}
        </span>

        <span class="value">
          {$subtotal.value}
        </span>
      </div>
    {/if}
  {/foreach}

</div>


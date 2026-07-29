
{block name='cart_detailed_actions'}
  <div id="cart-btn-container" class="checkout cart-detailed-actions ">
      <div class="card-block">
        {if $cart.minimalPurchaseRequired}
              <div class="alert alert-warning" role="alert">
                {$cart.minimalPurchaseRequired}
              </div>

            <button type="button" class="btn btn-outline btn-primary disabled" disabled>{l s='Proceed to checkout' d='Shop.Theme.Actions'}</button>

        {elseif empty($cart.products) }
            <button type="button" class="btn btn-outline btn-primary disabled" disabled>{l s='Proceed to checkout' d='Shop.Theme.Actions'}</button>

        {else}
            <a href="{$urls.pages.order}" class="btn btn-outline btn-primary">{l s='Proceed to checkout' d='Shop.Theme.Actions'}</a>
            {hook h='displayExpressCheckout'}

        {/if}
      </div>
  </div>
{/block}

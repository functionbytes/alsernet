<section id="js-checkout-summary" class="card js-cart cart-summary" data-refresh-url="{$urls.pages.cart}?ajax=1&action=refresh">


    <div class="summary-title">
          <h3>
              <img src="/img/propias/Carrito.svg"> {l s='Resumen de pedido' d='Shop.Theme.Actions'}
          </h3>
    </div>

    <div class="summary">
    <div class="summary-top">
    {block name='hook_checkout_summary_top'}
      {hook h='displayCheckoutSummaryTop'}
    {/block}

    {block name='cart_summary_products'}
      <div class="cart-summary-products">

        <p>{$cart.summary_string}</p>

        <p>
          <a href="#" data-toggle="collapse" data-target="#cart-summary-product-list" class="btn btn-outline">
            {l s='show details' d='Shop.Theme.Actions'}
            <i class="material-icons">expand_more</i>
          </a>
        </p>

        {block name='cart_summary_product_list'}
          <div class="collapse" id="cart-summary-product-list">
            <ul class="media-list">
              {foreach from=$cart.products item=product}
                <li class="media">{include file='checkout/_partials/cart-summary-product-line.tpl' product=$product}</li>
              {/foreach}
            </ul>
          </div>
        {/block}
      </div>
    {/block}

      {block name='cart_summary_subtotals'}
        {include file='checkout/_partials/cart-summary-subtotals.tpl' cart=$cart}
      {/block}
    </div>
  </div>

  {block name='cart_summary_totals'}
    {include file='checkout/_partials/cart-summary-totals.tpl' cart=$cart}
  {/block}

  {block name='cart_summary_voucher'}
    {include file='checkout/_partials/cart-voucher.tpl'}
  {/block}


  {hook h='displayExpressCheckout'}

</section>

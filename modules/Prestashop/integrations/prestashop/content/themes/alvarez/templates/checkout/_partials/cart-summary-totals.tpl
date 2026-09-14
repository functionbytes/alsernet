
<div class="card-block cart-summary-totals">

  {block name='cart_summary_total'}
    {if !$configuration.display_prices_tax_incl && $configuration.taxes_enabled}
      <div class="cart-summary-line">
        <span class="label">{$cart.totals.total.label}&nbsp;{$cart.labels.tax_short}</span>
        <span class="value">{$cart.totals.total.value}</span>
      </div>
      <div class="cart-summary-line cart-total">
        <span class="label">{$cart.totals.total_including_tax.label}</span>
        <span class="value">{$cart.totals.total_including_tax.value}</span>
      </div>
    {else}
      <div class="cart-summary-line cart-total">
        <span class="label">{$cart.totals.total.label}<!--&nbsp;{if $configuration.taxes_enabled}{$cart.labels.tax_short}{/if}--></span>
        <span class="value">{$cart.totals.total.value}</span>
      </div>
    {/if}
  {/block}
  <!-- add Spent X to get free ship in checkout page Leotheme -->
  {*assign var='freeshipping_price' value=Configuration::get('PS_SHIPPING_FREE_PRICE')}
  {if $freeshipping_price}
    {math equation='a-b' a=$cart.totals.total.amount b=$cart.subtotals.shipping.amount assign='total_without_shipping'}
    {math equation='a-b' a=$freeshipping_price b=$total_without_shipping assign='remaining_to_spend'}
    {if $remaining_to_spend > 0}
      <div class="leo_free_price">
      {assign var=currency value=Context::getContext()->currency}
      <p>{l s='Spent' d='Shop.Theme.Global'} {Tools::displayPrice($remaining_to_spend,$currency)} {l s='To get free ship!' d='Shop.Theme.Global'}</p>
      </div>
    {/if}
  {/if*}
  <!-- end -->
  {block name='cart_summary_tax'}
    {if $cart.subtotals.tax}
      <div class="cart-summary-line">
        <span class="label sub">{l s='%label%' sprintf=['%label%' => $cart.subtotals.tax.label] d='Shop.Theme.Global'}</span>
        <span class="value sub">{$cart.subtotals.tax.value}</span>
      </div>
    {/if}
  {/block}

</div>

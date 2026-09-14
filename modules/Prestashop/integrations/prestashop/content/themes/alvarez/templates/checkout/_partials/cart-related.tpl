
<section id="js-cart-related" class="js-cart cart-related" data-refresh-url="{url entity='cart' params=['ajax' => true, 'action' => 'refresh']}">
  <div class="btn-complete-order">
    <a class="btn btn-secondary" href="javascript:void(0)" onclick="cartCompleteOrder();">{l s='You may also need...' d='Shop.Theme.Checkout'}</a>
  </div>

  {widget name="alvarezbanner" zone=3 type=2 cart_products=$cart.products}

  <div class="cart-bottom-similar">

    {widget name="alsernetproducts" cart=$cart type="complementary"}

    {widget name='retailrocket' hook='displayRetailRocketCartPageBundleRelated'}
  <script>
    (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
        console.log('Render');
        retailrocket.markup.render()
        });
  </script>
  </div>

</section>

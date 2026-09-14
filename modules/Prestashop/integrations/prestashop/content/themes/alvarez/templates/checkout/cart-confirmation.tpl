{extends file='page.tpl'}

{block name='page_title'}
  {l s='Product Added to Cart' d='Shop.Theme.Checkout'}
{/block}

{block name='page_content_container'}
  <section id="cart-confirmation-wrapper">
    <div class="cart-confirmation-container">

      {* Product Information Section *}
      <div class="product-confirmation-section card mb-3">
        <div class="card-header bg-success text-white">
          <i class="material-icons">check_circle</i>
          {l s='Product successfully added to your cart' d='Shop.Theme.Checkout'}
        </div>

        <div class="card-body">
          <div class="row">
            {* Product Image *}
            <div class="col-md-3 col-sm-4">
              {if $product.cover}
                <img src="{$product.cover.medium.url}" alt="{$product.cover.legend}" class="img-fluid product-confirmation-image">
              {else}
                <img src="{$urls.no_picture_image.bySize.medium_default.url}" alt="{l s='No image' d='Shop.Theme.Catalog'}" class="img-fluid">
              {/if}
            </div>

            {* Product Details *}
            <div class="col-md-9 col-sm-8">
              <h3 class="product-title">
                <a href="{$product.url}">{$product.name}</a>
              </h3>

              {if $product.description_short}
                <div class="product-description">
                  {$product.description_short nofilter}
                </div>
              {/if}

              <div class="product-price mt-2">
                <span class="price">{$product.price}</span>
              </div>

              {* Show attributes if combination selected *}
              {if isset($product.attributes) && $product.attributes}
                <div class="product-attributes mt-2">
                  <strong>{l s='Selected attributes:' d='Shop.Theme.Catalog'}</strong>
                  <ul class="list-unstyled">
                    {foreach from=$product.attributes item=attribute}
                      <li>{$attribute.name}</li>
                    {/foreach}
                  </ul>
                </div>
              {/if}
            </div>
          </div>
        </div>
      </div>

      {* Cart Summary Section *}
      <div class="cart-summary-section card mb-3">
        <div class="card-header">
          <i class="material-icons">shopping_cart</i>
          {l s='Cart Summary' d='Shop.Theme.Checkout'}
        </div>

        <div class="card-body">
          <div class="cart-summary-line">
            <span class="label">{l s='Total inventaries:' d='Shop.Theme.Checkout'}</span>
            <span class="value">{$cart.products_count}</span>
          </div>

          <div class="cart-summary-line">
            <span class="label">{l s='Total (tax incl.):' d='Shop.Theme.Checkout'}</span>
            <span class="value font-weight-bold">{$cart.totals.total.value}</span>
          </div>
        </div>
      </div>

      {* Action Buttons *}
      <div class="cart-confirmation-actions">
        <div class="row">
          <div class="col-md-6 mb-2">
            <a href="{$urls.pages.index}" class="btn btn-secondary btn-block">
              <i class="material-icons">chevron_left</i>
              {l s='Continue shopping' d='Shop.Theme.Actions'}
            </a>
          </div>

          <div class="col-md-6 mb-2">
            <a href="{$urls.pages.cart}?action=show" class="btn btn-primary btn-block">
              <i class="material-icons">shopping_cart</i>
              {l s='View cart' d='Shop.Theme.Actions'}
            </a>
          </div>
        </div>

        <div class="row mt-2">
          <div class="col-12">
            <a href="{$urls.pages.order}" class="btn btn-success btn-block btn-lg">
              <i class="material-icons">payment</i>
              {l s='Proceed to checkout' d='Shop.Theme.Actions'}
            </a>
          </div>
        </div>
      </div>

      {* Cross-selling inventaries *}
      {hook h='displayCrossSellingShoppingCart'}

    </div>
  </section>
{/block}

{block name='page_footer'}
  {* Custom footer content if needed *}
{/block}

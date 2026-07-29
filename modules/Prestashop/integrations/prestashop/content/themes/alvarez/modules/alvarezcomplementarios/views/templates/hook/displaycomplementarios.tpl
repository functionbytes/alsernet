{if $datos}

  <div class="complementarios">
    <h5>{l s='Complementary inventaries' d='Shop.Theme.Global'}</h5>

    {foreach from=$datos item=product}

      {if (($product.add_to_cart_url) && ($product.id_product_attribute==0)) || ($product.id_product_attribute!=0) }


        <div class="row complementarioitem">
          <div class="col-md-8 col-xs-12 col-sp-12">
            <div class="row">
              <div class="col-xs-3 col-sp-3">
                <a href="{$product.link}">
                  {if $product.default_image}
                    <img
                      src="{$product.default_image.medium.url}"
                      data-full-size-image-url="{$product.default_image.large.url}"
                      title="{$product.default_image.legend}"
                      alt="{$product.default_image.legend}"
                      loading="lazy"
                      class="product-image" />
                  {else}
                    <img
                      src="{$urls.no_picture_image.bySize.medium_default.url}"
                      loading="lazy"
                      class="product-image" />
                  {/if}
                </a>
              </div>

              <div class="col-xs-9 col-sp-9">
                <h6 class="h6 product-name"><a href="{$product.link}">{$product.name}</a></h6>
                <p class="unidades">{$product.minimal_quantity} {if $product.minimal_quantity==1 }{l s='unit' d='Shop.Theme.Global'}{else}{l s='units' d='Shop.Theme.Global'}{/if}</p>
                <p class="product-price">
                  <span class="product-price-final{if $product.has_discount} has-discount{/if}">{$product.price}</span>{if $product.has_discount}<span class="regular-price">{$product.regular_price}</span><span class="price-discount">{$product.discount_amount_to_display}</span>{/if}
                </p>
                {hook h='displayProductPriceBlock' product=$product type="unit_price"}
              </div>
            </div>
          </div>

          <div class="col-md-4 col-xs-12 col-sp-12">
            <div class="cart-content">
              <div class="cart-content-btn">
                {if $product.id_product_attribute==0 && !$product.is_bundle}
                  {*Añadir*}
                  <form action="{$urls.pages.cart}" method="post">
                    <input type="hidden" name="token" value="{Tools::getToken(false)}" />
                    <input type="hidden" name="minimal_quantity" value="{$product.minimal_quantity}" />
                    <input type="hidden" name="id_product_attribute" value="0" />
                    <input type="hidden" name="id_product" value="{$product.id_product}" />
                    <input type="hidden" name="id_customization" value="0" />
                    <input type="hidden" name="qty" value="{$product.minimal_quantity}" />
                    <input type="hidden" name="add" value="1" />
                    <input type="hidden" name="action" value="update" />

                    <input class="btn btn-primary btn-complementario" {*data-button-action="add-to-cart"*} type="submit" value="{l s='Add' d='Shop.Theme.Global'}">
                  </form>
                {else}
                  {*Seleccionar opcion*}
                  <a class="btn btn-primary btn-complementario" href="{$product.link}">{l s='Select option' d='Shop.Theme.Global'}</a>
                {/if}
              </div>
            </div>
          </div>
        </div>
      {/if}
    {/foreach}
  </div>
{else}
<div data-retailrocket-markup-block="6329a4195b36e8201155684b" data-product-id="{$idproduct}" data-user-language="{$language.iso_code}"></div>
<script>
(window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
      retailrocket.markup.render()
      });
</script>
{/if}

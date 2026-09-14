{**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
 
<div class="product-line-grid row">
  <!--  product line left content: image-->
  <div class="product-line-grid-left col-md-2 col-xs-3 linea-imagen">
    <span class="product-image media-middle">
      {if $product.cover}
        <img class="lazy" src="{$product.cover.bySize.cart_default.url}" data-src="{$product.cover.bySize.cart_default.url}" alt="{$product.name|escape:'quotes'}">
      {else}
        <img class="lazy" src="{$urls.no_picture_image.bySize.cart_default.url}" data-src="{$urls.no_picture_image.bySize.cart_default.url}" />
      {/if}
    </span>
  </div>

  <!--  product line body: label, discounts, price, attributes, customizations -->
  <div class="product-line-grid-body col-md-5 col-xs-9">
    <div class="product-line-info nameref">
      <a class="label" href="{$product.url}" data-id_customization="{$product.id_customization|intval}">{$product.name}</a>
      <p class="referencia-prd">Ref. {$product.reference}</p>
    </div>

    {foreach from=$product.attributes key="attribute" item="value"}
      <div class="product-line-info attrs">
        <span class="label">{$attribute}:</span>
        <span class="value">{$value}</span>
      </div>
    {/foreach}

    {if is_array($product.customizations) && $product.customizations|count}
      <br>
      {block name='cart_detailed_product_line_customization'}
        {foreach from=$product.customizations item="customization"}
          <a href="#" data-toggle="modal" data-target="#product-customizations-modal-{$customization.id_customization}">{l s='Product customization' d='Shop.Theme.Catalog'}</a>
          <div class="modal fade customization-modal" id="product-customizations-modal-{$customization.id_customization}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' d='Shop.Theme.Global'}">
                    <span aria-hidden="true">&times;</span>
                  </button>
                  <h4 class="modal-title">{l s='Product customization' d='Shop.Theme.Catalog'}</h4>
                </div>
                <div class="modal-body">
                  {foreach from=$customization.fields item="field"}
                    <div class="product-customization-line row">
                      <div class="col-sm-3 col-xs-4 label">
                        {$field.label}
                      </div>
                      <div class="col-sm-9 col-xs-8 value">
                        {if $field.type == 'text'}
                          {if (int)$field.id_module}
                            {$field.text nofilter}
                          {else}
                            {$field.text}
                          {/if}
                        {elseif $field.type == 'image'}
                          <img class="lazy" data-src="{$field.image.small.url}">
                        {/if}
                      </div>
                    </div>
                  {/foreach}
                </div>
              </div>
            </div>
          </div>
        {/foreach}
      {/block}
    {/if}


    <div class="product-line-info product-price h5 {if $product.has_discount}has-discount{/if}">
      <div class="current-price">
        <span class="price">{$product.price}</span>
        {if $product.unit_price_full}
          <div class="unit-price-cart">{$product.unit_price_full}</div>
        {/if}
      </div>
      
      {if $product.has_discount}
        <div class="product-discount">
          <span class="regular-price">{$product.regular_price}</span>
          {if $product.discount_type === 'percentage'}
            <span class="discount discount-percentage">
                -{$product.discount_percentage_absolute}
              </span>
          {else}
            <span class="discount discount-amount">
                -{$product.discount_to_display}
              </span>
          {/if}
        </div>
      {/if}
      
    </div>

  </div>

  <!--  product line right content: actions (quantity, delete), price -->
  <div class="product-line-grid-right product-line-actions col-md-5 col-xs-12">
    <div class="row">
      <!--<div class="col-xs-4 hidden-md-up"></div>-->
      <div class="col-md-10 col-xs-10">
        <div class="row">
          <div class="col-md-7 col-xs-9 col-sp-9 qty">
            {if isset($product.is_gift) && $product.is_gift}
              <span class="gift-quantity">{$product.quantity}</span>
            {else}
              <input
                class="js-cart-line-product-quantity"
                data-down-url="{$product.down_quantity_url}"
                data-up-url="{$product.up_quantity_url}"
                data-update-url="{$product.update_quantity_url}"
                data-product-id="{$product.id_product}"
                type="number"
                value="{$product.quantity}"
                name="product-quantity-spin"
              />
            {/if}
          </div>
          <div class="col-md-5 col-xs-3 col-sp-3 price">
            <span class="product-price">
              <strong>
                {if isset($product.is_gift) && $product.is_gift}
                  <span class="gift">{l s='Gift' d='Shop.Theme.Checkout'}</span>
                {else}
                  {$product.total}
                {/if}
              </strong>
            </span>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-xs-2 text-xs-right">
        <div class="cart-line-product-actions">

          <button type="button" class="remove-from-cart eliminaprd" data-toggle="modal" data-target="#modal-delete-cart-{$product.id_product}">
            <i class="material-icons float-xs-left">delete</i>
          </button>

          {block name='hook_cart_extra_product_actions'}
            {hook h='displayCartExtraProductActions' product=$product}
          {/block}

        </div>
      </div>
    </div>
  </div>

  <div class="clearfix"></div>

  <div class="modal fade modal-delete" id="modal-delete-cart-{$product.id_product}" tabindex="-1" role="dialog" aria-labelledby="modal-delete-cartLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-delete-cartLabel">{l s="ELIMINAR PRODUCTO" d="Shop.Theme.Checkout"}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body ">
          <div class="row gutter-lg">
            <div class="col-md-8 col-xs-12 col-sp-12">
              <div class="row gutter-lg">
                <div class="col-xs-3 col-sp-3">
                  <div class="product-gallery product-gallery-sticky">
                    {if $product.default_image}
                      <img src="{$product.cover.bySize.cart_default.url}"
                           data-full-size-image-url="{$product.cover.bySize.cart_default.url}"
                           title="{$product.cover.bySize.cart_default.url}"
                           alt="{$product.cover.bySize.cart_default.url}"
                           loading="lazy"
                           class="product-image">
                    {else}
                      <img src="{$urls.no_picture_image.bySize.cart_default.url}"
                           loading="lazy"
                           class="product-image"/>
                    {/if}
                  </div>
                </div>
                <div class="col-xs-9 col-sp-9">
                  <div class="product-details scrollable pl-0">
                    <h2 class="product-title">{$product.name}</h2>
                    <div class="product-bm-wrapper">
                      <div class="product-meta">
                        {if $product.reference}
                          <div class="product-reference">
                            {l s='Ref' mod='alsernetshopping'}: <span>{$product.reference}</span>
                          </div>
                        {/if}
                        {if $product.attributes}
                          <div class="product-attribute">
                            <span>{$product.attributes_small}</span>
                          </div>
                        {/if}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4 col-xs-12 col-sp-12">
              <div class="product-form">

                <button type="button" class="btn btn-primary btn-cart btn-block" data-dismiss="modal">{l s="NO, MANTENER" d="Shop.Theme.Checkout"}</button>
                <a
                        class                       = "remove-from-cart btn btn-primary  btn-block"
                        rel                         = "nofollow"
                        href                        = "{$product.remove_from_cart_url}"
                        data-link-action            = "delete-from-cart"
                        data-id-product             = "{$product.id_product|escape:'javascript'}"
                        data-id-product-attribute   = "{$product.id_product_attribute|escape:'javascript'}"
                        data-id-customization       = "{$product.id_customization|escape:'javascript'}"
                        data-dismiss="modal"
                >{l s="SÍ, ELIMINAR" d="Shop.Theme.Checkout"}</a>


              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>


</div>

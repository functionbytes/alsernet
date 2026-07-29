
{block name='cart_summary_product_line'}
  <div class="media-left">
    <a href="{$product.url}" title="{$product.name}">
      <img class="lazy media-object" src="{$product.cover.small.url}" alt="{$product.name}" onerror="this.src='{$urls.no_picture_image.bySize.cart_default.url}'">
    </a>
  </div>
  <div class="media-body">
    <span class="product-name">{$product.name}</span>
    <span class="product-quantity">x{$product.quantity}</span><br>
    <span class="product-price float-xs-left {if $product.has_discount}dto{/if}">{$product.price}</span>
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
{/block}

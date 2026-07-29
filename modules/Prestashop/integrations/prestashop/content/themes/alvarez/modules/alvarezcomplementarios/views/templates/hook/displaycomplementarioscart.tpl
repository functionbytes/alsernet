{if $datos}
<section class="category-products block clearfix {if isset($productClassWidget)} {$productClassWidget}{/if}">
  <h5 class="products-section-title">
    {l s='You may also need...'  d='Shop.Theme.Checkout'}
  </h5>
  <div class="block_content">
    <div class="products">
      <div class="product_list product_complementarios grid{if isset($productClassWidget)} {$productClassWidget}{/if}">
        <div class="row">
          {foreach from=$datos item=product}
            <div class="ajax_block_product col-sp-6 col-xs-6 col-sm-6 col-md-6 col-lg-4 col-xl-4">
              {block name='product_miniature'}
                {if isset($productProfileDefault) && $productProfileDefault}
                  {* exits THEME_NAME/profiles/profile_name.tpl -> load template*}
                  {hook h='displayLeoProfileProduct' product=$product profile=$productProfileDefault}
                {else}
                  {include file='catalog/_partials/miniatures/product.tpl' product=$product}
                {/if}
              {/block}
            </div>
          {/foreach}
        </div>
      </div>
  </div>
</section>
{/if}

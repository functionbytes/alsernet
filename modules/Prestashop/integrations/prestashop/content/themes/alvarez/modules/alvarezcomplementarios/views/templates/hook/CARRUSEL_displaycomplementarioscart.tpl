{if $datos}
<section class="category-products block clearfix {if isset($productClassWidget)} {$productClassWidget}{/if}">
  <h5 class="products-section-title">
    {l s='Complete your order'  d='Shop.Theme.Checkout'}
  </h5>
  <div class="block_content">
    <div class="products">
      <div class="owl-row">
        <div id="category-products" class="owl-carousel owl-theme owl-loading">
          {foreach from=$datos item=product}
            <div class="item{if $smarty.foreach.mypLoop.index == 0} first{/if}">
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
  </div>
</section>
{/if}
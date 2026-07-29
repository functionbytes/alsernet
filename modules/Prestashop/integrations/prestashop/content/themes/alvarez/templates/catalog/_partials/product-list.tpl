{capture assign="productClasses"}
    {if !empty($productClass)}{$productClass} {else}px-1 col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 first-in-line first-item-of-tablet-line first-item-of-mobile-line{/if}
{/capture}

<div class="row">
    {foreach from=$products item="product" key="position"}
        {if not $product.blocked}
            {include file="catalog/_partials/miniatures/product.tpl" product=$product position=$position productClasses=$productClasses}
        {/if}
    {/foreach}
</div>

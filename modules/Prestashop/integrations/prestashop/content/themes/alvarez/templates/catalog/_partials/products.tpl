
<div id="js-product-list">
    {include file="catalog/_partials/product-list.tpl" products=$listing.products cssClass="row"}

    {block name='pagination'}
        {include file='catalog/_partials/pagination.tpl' pagination=$listing.pagination}
    {/block}

</div>

{if isset($category) and isset($category.id)}
    <div data-retailrocket-markup-block="683d8d6cdd666ff76cc41534" data-category-id="{$category.id}" data-user-currency="{strtolower($currency.iso_code)}" data-user-lang="{strtolower($iso_code)}"></div>
{/if}

{block name='products_list_bottom_description'}

    {if isset($category.description) && $category.description}
        <div id="category-description" class="category-description">{$category.description nofilter}</div>
    {/if}

    {if isset($manufacturer) && $manufacturer.description != ''}
        <div id="category-description" class="category-description">{$manufacturer.description nofilter}</div>
    {/if}

{/block}
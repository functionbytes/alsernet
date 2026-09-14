{if $category.id_parent == 2820}
    {widget_block name="addis_query" query="select titulo from [dbprefix]evento_categoria where id_category={$category.id}"}
       {assign var="titulo_evento" value=$rows[0].titulo}
    {/widget_block}
{/if}
<div id="js-product-list-header">
        <div class="block-category web">
            {if $category.id_parent == 2820}
                <h1 class="products-section-title">{$titulo_evento}</h1>
            {else}
                <h1 class="products-section-title">{$category.name}</h1>
            {/if}
        </div>
</div>

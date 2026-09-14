{extends file=$layout}

{block name='top'}
    {widget name="alvarezbanner" object=$category.id zone=1 type=1}
{/block}

{block name='breadcrumb'}
{/block}
{block name='left_column'}
      <div id="left-column" class="col-sp-12 col-xs-12 col-sm-12 col-md-4 col-lg-3">
            {hook h="displayLeftColumn"}
            {widget name='alsernetmenu' type="navs" }
            {widget name='alsernetmenu' type="urls" }
       </div>
{/block}

{block name='content'}


    {if isset($category_analytics)}
        <input type="hidden" class="category_analytics" value="{$category_analytics}">
    {/if}

    {hook h="displayContentCategory"}

    {widget name="alsernetproducts" category=$category.id type="viewproducts"}

    {widget name="alsernetproducts" category=$category.id type="analytics"}

    {if isset($category) and $category.id != 10}
        {widget name="alsernetproducts" category=$category.id type="news"}
    {/if}

    {widget name="alsernetproducts" category=$category.id type="sales"}

    {block name='product_list_footer'}
        {if $category.description}
            <div id="category-description" class="category-description">{$category.description nofilter}</div>
        {/if}
    {/block}

{/block}
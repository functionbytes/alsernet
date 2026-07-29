{if !$layout && $page.page_name == 'manufacturerdeporte'}
    {$layoutaux = 'layouts/layout-left-column.tpl'}
{else if $page.page_name == 'category' && $category.id == 25931}
    {$layoutaux = 'layouts/layout-full-width.tpl'}
{else}
    {$layoutaux = $layout}
{/if}

{extends file=$layoutaux}

{block name='head_microdata_special'}
    {include file='_partials/microdata/product-list-jsonld.tpl' listing=$listing}
{/block}

{block name='content'}


    <section id="main">
        {if isset($category)}
            {widget name="alvarezbanner" object=$category.id zone=6 type=1}
        {/if}
        {block name='product_list_header'}
            <h1 id="js-product-list-header" class="h2 text-center">{$listing.label}</h1>
        {/block}

        {block name='subcategory_list'}
            {*{if isset($subcategories) && $subcategories|@count > 0}
              {include file='catalog/_partials/subcategories.tpl' subcategories=$subcategories}
            {/if}*}
        {/block}

        {hook h="displayHeaderCategory"}

        <section >
            {if $listing.products|count}


                <div>
                    {block name='product_list_top'}
                        {include file='catalog/_partials/inventaries-top.tpl' listing=$listing}
                    {/block}
                </div>

                {block name='product_list_active_filters'}
                    <div id="" class="hidden-sm-down">
                        {$listing.rendered_active_filters nofilter}
                    </div>
                {/block}

                <div>
                    {block name='product_list'}
                        {include file='catalog/_partials/inventaries.tpl' listing=$listing}
                    {/block}
                </div>

                <div>
                    {block name='product_list_bottom'}
                        {include file='catalog/_partials/inventaries-bottom.tpl' listing=$listing}
                    {/block}
                </div>

            {else}
                <div id="js-product-list-top"></div>

                <div id="js-product-list">
                    {capture assign="errorContent"}
                        <h4>{l s='No inventaries available yet' d='Shop.Theme.Catalog'}</h4>
                        <p>{l s='Stay tuned! More inventaries will be shown here as they are added.' d='Shop.Theme.Catalog'}</p>
                    {/capture}

                    {*{include file='errors/not-found.tpl' errorContent=$errorContent}*}
                    {include file='errors/no-inventaries-found.tpl'}
                </div>

                <div id="js-product-list-bottom"></div>
            {/if}
        </section>

        {hook h="displayFooterCategory"}

    </section>
{/block}



{extends file=$layout}



{block name='left_column'}
    <div id="left-column" class="col-sp-12 col-xs-12 col-sm-12 col-md-4 col-lg-3">
        {widget name='alsernetmenu' type="navs" }
        {hook h="displayLeftColumn"}
        {widget name='alsernetmenu' type="urls" }
    </div>
{/block}

{block name='content'}

    {hook h="displayHeaderCategory"}

    <section id="products">
        {if $listing.products|count}

            {block name='product_list_header'}
                <h1 class="products-section-title">
                    {$title}
                </h1>
            {/block}

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
                {include file='errors/no-inventaries-found.tpl'}
            </div>

            <div id="js-product-list-bottom"></div>

        {/if}

    </section>

    {hook h="displayFooterCategory"}

{/block}

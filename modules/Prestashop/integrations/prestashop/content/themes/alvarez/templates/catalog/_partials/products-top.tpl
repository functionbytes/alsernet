<div id="js-product-list-top" class="products-selection">
    <div class="row ">
        <div class="col-md-3 col-xs-12 col-sp-12 col-sm-12">
            <div class="total-products">
                {if $listing.pagination.total_items > 1}
                <p>{l s='There are %product_count% inventaries.' d='Shop.Theme.Catalog' sprintf=['%product_count%' =>
                    $listing.pagination.total_items]}</p>
                {elseif $listing.pagination.total_items > 0}
                <p>{l s='There is 1 product.' d='Shop.Theme.Catalog'}</p>
                {/if}
            </div>
        </div>
        <div class="col-md-4 col-xs-12 col-sp-12 col-sm-12">
            <div class="filter-btn">
                <a class="btn-filter compact-toggle hidden-md-up ">
                    <i class="fa fa-solid fa-filter"></i>
                    Filtrar
                </a>
            </div>
            <div class="price-btn">

            </div>
        </div>
        <div class="col-md-5 col-xs-12 col-sp-12 col-sm-12 sort-order-wrapper">
            <div class="sort-order-wrapper">
                <span class="col-md-4 col-sm-12 sort-by">{l s='Sort by:' d='Shop.Theme.Global'}</span>
                <div class="col-md-8 col-sm-12 products-sort-order dropdown">
                    <button class="btn-unstyle select-title" rel="nofollow" data-toggle="dropdown"
                        aria-label="{l s='Sort by selection' d='Shop.Theme.Global'}" aria-haspopup="true"
                        aria-expanded="false">
                        {if $listing.sort_selected}{$listing.sort_selected}{else}{l s='Select'
                        d='Shop.Theme.Actions'}{/if}
                        <i class="material-icons float-xs-right"></i>
                    </button>
                    <div class="dropdown-menu">
                        {foreach from=$listing.sort_orders item=sort_order}
                        <a rel="nofollow" href="{$sort_order.url}"
                            class="select-list {['current' => $sort_order.current, 'js-search-link' => true]|classnames}">
                            {l s=$sort_order.label d='Shop.Theme.Catalog'}
                        </a>
                        {/foreach}
                    </div>
                </div .product-action-vertical>
            </div>
        </div>
    </div>
</div>

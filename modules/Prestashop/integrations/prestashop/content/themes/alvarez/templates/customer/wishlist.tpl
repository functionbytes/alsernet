{extends file='customer/page.tpl'}

{block name='page_content'}

    <button class="btn btn-dashboard-show mb-4 d-lg-none w-100 text-center">
        {l s='Show menu' d='Shop.Customer.Wishlist'}
    </button>

    <div class="dashboard-right-sidebar">
        <div class="dashboard-wishlist">
            <div class="dashboard-title d-flex justify-content-between align-items-center">
                <div class="title">
                    <h2>{l s='My Wishlist' d='Shop.Customer.Wishlist'}</h2>
                </div>
            </div>

            <div class="wishlist-container row g-4 {if !$products || count($products) == 0} d-none {/if}">
                {foreach from=$products item=item}
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 wishlist-item" data-id-address="{$item.product.id}">
                        <div class="wishlist-box theme-bg-white h-100 p-3 d-flex flex-column">
                            <div class="product-header position-relative">
                                {if not $item.product.blocked}
                                    {include file='customer/_partials/flags.tpl'}
                                {/if}
                                <div class="product-image">
                                    <a href="{$item.product.url}?id_product_attribute={$item.product.id_product_attribute}">
                                        <img src="{$item.product.cover.bySize.home_default.url}"
                                             alt="{$item.product.cover.legend}"
                                             class="img-fluid w-100 rounded">
                                    </a>
                                    <div class="product-header-top position-absolute top-0 end-0 ">
                                        <a class="btn wishlist-button close_button btn-remove add-delete-to-wishlist"
                                           data-id-wishlist="{$id_wishlist}"
                                           data-id-product="{$item.product.id}"
                                           data-id-product-attribute="{$item.product.id_product_attribute}">
                                            <i class="fa-light fa-xmark"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="product-footer d-flex flex-column">
                                <div class="product-detail text-center">
                                    <a href="{$item.product.url}?id_product_attribute={$item.product.id_product_attribute}">
                                        <h5 class="name mb-1">{$item.product.name}</h5>
                                    </a>
                                    {if $item.product.reference}
                                        <h6 class="unit text-muted mb-2">{$item.product.reference}</h6>
                                    {/if}
                                    {block name='product_reviews'}
                                        {hook h='displayProductListReviews' product=$item.product}
                                    {/block}
                                    {if not $item.product.blocked}
                                        {block name='product_price_and_shipping'}
                                            {if $item.product.request_price == '0' || ($item.product.request_price == '0' && $item.product.phone_sale == '1') || $item.product.isPing}
                                                <div class="product-prices">
                                                    <div class="current-price">
                                                        <div class='current-price-value'>
                                                            {$item.product.price}
                                                        </div>
                                                        {if $item.product.has_discount}
                                                            <div class="product-discount">
                                                                <span class="regular-price">{$item.product.regular_price}</span>
                                                            </div>
                                                            {if $item.product.discount_type === 'percentage'}
                                                                <span class="discount discount-percentage">
                                                    {$item.product.discount_percentage_absolute}
                                                </span>
                                                            {else}
                                                                <span class="discount discount-amount">
                                                    -{$item.product.discount_to_display}
                                                </span>
                                                            {/if}
                                                        {/if}
                                                    </div>
                                                    {if $item.product.multiple_price_quantity}
                                                        <div class="second-price">
                                                            <span class="second-price-text">{l s='The second unit costs'  d='Shop.Theme.Catalog'}</span>
                                                            <span class="second-price-value">{$item.product.price2|number_format:2:',':'.'} {$currency.sign}</span>
                                                        </div>
                                                    {/if}
                                                </div>
                                            {/if}
                                        {/block}

                                    {/if}

                                    {if $item.stock >= 1}
                                        <div class="add-to-cart-box">
                                            <a class="btn btn-add-cart add-to-cart-wishlist"
                                               data-id-wishlist="{$id_wishlist}"
                                               data-id-product="{$item.product.id}"
                                               data-id-product-attribute="{$item.product.id_product_attribute}">
                                                <i class="fa-light fa-bag-shopping me-1"></i>
                                                {l s='Add to cart Wishlist' d='Shop.Customer.Wishlist'}
                                            </a>
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>


            <div class="wishlist-empty-container text-center py-5 {if $products && count($products) > 0} d-none {/if}">
                <i class="fa-solid fa-cart-xmark fa-3x mb-3 text-muted"></i>
                <h2>{l s='title empty wishlist' d='Shop.Customer.Wishlist'}</h2>
                <p class="text-muted">{l s='Description empty wishlist' d='Shop.Customer.Wishlist'}</p>
            </div>
        </div>
    </div>

{/block}



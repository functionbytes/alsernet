
{dump($products)}
{if $products}

    <div class="main-products content-products mb-4">
        <h2 class="products-title">
            {l s='Complementary products' d='Shop.Theme.Global'}
        </h2>
        <div class="product-wrapper">
            <div class="swiper"> <!-- Swiper container wrapper -->
                <div class="swiper-container">
                    <div class="swiper-wrapper">
                    
                        {foreach from=$products item="product" key="position"}
                        
                        <div class="swiper-slide">
                        <div class="item">
                            {include file="catalog/_partials/miniatures/product.tpl" product=$product position=$position}
                        </div>
                        </div>
                        {/foreach}
                    </div>
                    <!-- Add Pagination -->
                    <div class="swiper-pagination"></div>

                    <!-- Add Navigation -->
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div> 
        </div>
    </div>
{/if}
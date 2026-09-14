
{if $products}
    <div class="main-products content-products">
        <h2 class="products-title">
            <a {if $link && $link != ''}href="{$link}"{/if}>{$title}</a>
        </h2>
        <div class="product-wrapper">
            <div class="swiper">
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
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div> 
        </div>
    </div>
{/if}
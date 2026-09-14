<!-- {dump($product)} -->
<div class="images-container js-images-container">
    <div class="product-gallery product-gallery-sticky {if $product->film!=null} product-gallery-video {/if}">
        {include file='catalog/_partials/product-flags.tpl'}
        <div class="swiper-container product-default-swiper swiper-theme nav-inner">
            <div class="swiper-wrapper">

                {foreach from=$product.images item=image}
                    <div class="swiper-slide">
                        <figure class="product-image">
                            <a href="{$image.id_image}/{$product.link_rewrite}.jpg"
                               data-fancybox="product-gallery"
                               data-width="2000"
                               data-height="2000"
                               data-caption="{$product.name}">
                                <img src="{$image.id_image}/{$product.link_rewrite}.jpg" alt="{$product.name}" width="800" height="900">
                            </a>
                        </figure>
                    </div>
                {/foreach}
            </div>

            <div class="swiper-pagination"></div>

            <a href="#" class="product-gallery-btn product-image-full">
                <i class="fa-solid fa-magnifying-glass"></i>
            </a>
            {widget name="alsernetcustomer" type="wishlist" product=$product}
            {if  $product->film!=null}
                <div href="#" class="product-gallery-btn product-video-viewer" title="Product Video Thumbnail">
                </div>
            {/if}

            {if $product.images|@count > 1}
                <button class="swiper-button-next"></button>
                <button class="swiper-button-prev"></button>
            {/if}
        </div>
        <div class="product-thumbs-wrap swiper-container  {if $product.images|@count == 1} d-none {/if}">
            <div class="product-thumbs swiper-wrapper">
                {foreach from=$product.images item=image}
                    <div class="product-thumb swiper-slide">
                        <img src="{$image.id_image}/{$product.link_rewrite}.jpg"
                             alt="{$image.legend}"
                             title="{$image.legend}"
                             width="800"
                             height="900">
                    </div>
                {/foreach}
            </div>
        </div>

    </div>
</div>

{if $product->film!=null}
    <div id="modal-film" class="modal modal-film fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <input class="d-none" name="url" id="url" value="{$product.film}">
                <iframe class="product-film" id="product-film" width="100%" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </div>
{/if}
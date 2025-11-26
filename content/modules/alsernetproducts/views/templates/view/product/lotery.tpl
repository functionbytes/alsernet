

{widget name="alvarezbanner" object=$product.id zone=1 type=2}

<div class="page-product-default">

    <section id="main" >
        <div class="row ">
            <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                <section class="product product-single" id="content">
                    <div class="product-gallery product-gallery-sticky ">
                        <div class="swiper-container product-default-swiper swiper-theme nav-inner">
                            <div class="swiper-wrapper row cols-1 gutter-no">
                                {foreach from=$product.images item=image}
                                    <div class="swiper-slide">
                                        <figure class="product-image">
                                            <img src="{$image.bySize.large_default.url}" data-zoom-image="{$image.bySize.large_default.url}" alt="{$product.name}" width="800" height="900">
                                        </figure>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>

                </section>
            </div>
            <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12  ">
                <div class="product-details mt-50">

                        <meta itemprop="name" content="{$product_manufacturer->name}" />
                        <meta itemprop="sku" content="{if $product.reference}{$product.reference}{else}{$product.id}{/if}" />
                        <meta itemprop="mpn" content="{if $product.mpn}{$product.mpn}{elseif $product.reference}{$product.reference}{else}{$product.id}{/if}" />

                        <h1 class="product-title">{$product.name}</h1>

                        {if !$request_price.value}
                            {include file='catalog/_partials/product-prices.tpl'}
                        {/if}

                        <div class="product-actions">
                                <form action="{$urls.pages.cart}" method="post" id="add-to-cart-or-refresh">
                                    <input type="hidden" name="token" value="{$static_token}">
                                    <input type="hidden" name="id_product" value="{$product.id}" id="id_product">
                                    <input type="hidden" name="id_product_attribute" value="{$product.id_product_attribute}" id="id_product_attribute">
                                    <input type="hidden" name="id_customization" value="{$product.id_customization}" id="id_customization">
                                    <input type="hidden" name="current-price-value" value="">


                                    {block name='product_add_to_cart'}
                                            {include file='catalog/_partials/product-add-to-cart.tpl'}
                                    {/block}

                                </form>
                        </div>

                </div>

            </div>
        </div>
    </section>


</div>

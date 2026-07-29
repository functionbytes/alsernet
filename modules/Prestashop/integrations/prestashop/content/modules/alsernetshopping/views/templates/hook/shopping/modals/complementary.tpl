<div id="shopping-modal" class="shopping-modal modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">
                    {$translations.title}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <i class="fa-light fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row gutter-lg">
                    <div class="col-sp-12  col-xs-12 col-md-12 col-lg-8">
                        <div class="row gutter-lg">
                            <div class="col-xs-3 col-sp-3">
                                <div class="product-gallery product-gallery-sticky">
                                    {if $product.default_image}
                                        <img src="{$product.default_image.medium.url}"
                                             data-full-size-image-url="{$product.default_image.large.url}"
                                             title="{$product.default_image.legend}"
                                             alt="{$product.default_image.legend}"
                                             loading="lazy"
                                             class="product-image">
                                    {else}
                                        <img src="{$urls.no_picture_image.bySize.medium_default.url}"
                                             loading="lazy"
                                             class="product-image"/>
                                    {/if}
                                </div>
                            </div>
                            <div class="col-xs-9 col-sp-9">
                                <div class="product-details scrollable pl-0">
                                    <h2 class="product-title">{$product.name}</h2>

                                    <div class="product-meta">
                                        {if $product.reference}
                                            <div class="product-reference">
                                                {l s='Ref' mod='alsernetshopping'}: <span>{$product.reference}</span>
                                            </div>
                                        {/if}
                                        {if $product.attributes_small}
                                            <div class="product-attribute">
                                                <span>{$product.attributes_small}</span>
                                            </div>
                                        {/if}
                                    </div>
                                    <p class="product-price">
                                        <span class="price {if $product.has_discount} has-discount{/if}">
                                            {$product.price}
                                        </span>
                                        {if $product.has_discount}
                                            <span class="regular-price">
                                            {$product.regular_price}
                                        </span>
                                            <span class="discount-amount">
                                            {$product.discount_amount_to_display}
                                        </span>
                                        {/if}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sp-12  col-xs-12 col-md-12 col-lg-4">
                        <div class="product-form">

                            <a href="{$order_link}" class="btn btn-primary btn-block">
                                <i class="w-icon-cart"></i>
                                <span>{$translations.checkout}</span>
                            </a>
                            <a href="{$category_product}" class="btn btn-cart btn-block">
                                <i class="w-icon-cart"></i>
                                <span>{$translations.shopping}</span>
                            </a>


                        </div>
                    </div>
                </div>

                {if !empty($complementaries)}
                    {if count($complementaries) > 0}
                        <div class="row gutter-lg">

                            <div class="complementary">

                                <div class="complementary-container">

                                    <h5 class="title">{$translations.titlecomplementary} </h5>

                                    <div class="relation-container">
                                        {foreach from=$complementaries item=item}
                                            {if $item.add_to_cart_url!=nul}
                                                <div class="item-relation">
                                                    <div class="row gutter-lg">
                                                        <div class="col-md-8 col-xs-12 col-sp-12 ">
                                                            <div class="row gutter-lg">
                                                                <div class="col-xs-3 col-sp-3">
                                                                    <div class="product-gallery product-gallery-sticky">
                                                                        {if $item.default_image}
                                                                            <img src="{$item.default_image.medium.url}"
                                                                                 data-full-size-image-url="{$item.default_image.large.url}"
                                                                                 title="{$item.default_image.legend}"
                                                                                 alt="{$item.default_image.legend}"
                                                                                 loading="lazy"
                                                                                 class="product-image">
                                                                        {else}
                                                                            <img src="{$urls.no_picture_image.bySize.medium_default.url}"
                                                                                 loading="lazy"
                                                                                 class="product-image"/>
                                                                        {/if}
                                                                    </div>
                                                                </div>
                                                                <div class="col-xs-9 col-sp-9">
                                                                    <div class="product-details scrollable pl-0">
                                                                        <h2 class="product-title">{$item.name}</h2>
                                                                        <div class="product-bm-wrapper">
                                                                            <div class="product-meta">
                                                                                {if $item.reference}
                                                                                    <div class="product-reference">
                                                                                        {$translations.titlecomplementaryref}: <span>{$item.reference}</span>
                                                                                    </div>
                                                                                {/if}
                                                                                {if $item.attributes}
                                                                                    {if isset($item.attributes_small)}
                                                                                        <div class="product-attribute">
                                                                                            <span>{$item.attributes_small}</span>
                                                                                        </div>
                                                                                    {/if}
                                                                                {/if}
                                                                            </div>
                                                                        </div>
                                                                        <p class="product-price">
                                                                        <span class="price {if $item.has_discount} has-discount{/if}">
                                                                            {$item.price}
                                                                        </span>
                                                                            {if $item.has_discount}
                                                                                <span class="regular-price">
                                                                            {$item.regular_price}
                                                                        </span>
                                                                                <span class="discount-amount">
                                                                            {$item.discount_amount_to_display}
                                                                        </span>
                                                                            {/if}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 col-xs-12 col-sp-12">
                                                            <div class="product-form">
                                                                {if $item.id_product_attribute==0}
                                                                    <a class="btn btn-primary btn-cart btn-block add-complementary"
                                                                       data-id-cart="{$cart_id}"
                                                                       data-id-product="{$item.id}"
                                                                       data-id-product-attribute="{$item.id_product_attribute}"
                                                                       data-main-product-id="{$product.id_product}"
                                                                       data-main-product-attribute="{$product.id_product_attribute}">
                                                                        {$translations.titlecomplementaryadd}
                                                                    </a>
                                                                {else}
                                                                    <a href="{$item.link}" class="btn btn-primary close-cart btn-block " title="{$translations.titlecomplementaryoption}" alt="{$translations.titlecomplementaryoption}">{$translations.titlecomplementaryoption}</a>
                                                                {/if}

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            {/if}

                                        {/foreach}</div>
                                </div>
                            </div>
                        </div>
                    {/if}
                {/if}

            </div>
        </div>
    </div>
</div>

{literal}
    <script>
        $(".cart-content-btn button").click(function() {
            $(document).ajaxComplete(function(event, xhr, settings) {
                $('.product-variants').show();
            });
        });
    </script>
{/literal}

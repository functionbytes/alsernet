<div id="shopping-modal" class="blockade-modal modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                    <div class="col-sp-12  col-xs-12 col-md-12 col-lg-12">
                        <div class="row gutter-lg">
                            <div class="col-xs-2 col-sp-2">
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
                            <div class="col-xs-10 col-sp-10">
                                <div class="product-details scrollable pl-0">
                                    <h2 class="product-title">{$product.name} <span class="product-quantity">x ({$product.quantity})</span></h2>
                                    <div class="product-bm-wrapper">
                                        <div class="product-meta">
                                            {if $product.reference}
                                                <div class="product-reference">
                                                    {l s='Ref' mod='alsernetshopping'}: <span>{$product.reference}</span>
                                                </div>
                                            {/if}
                                        </div>
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

                </div>

            </div>
        </div>
    </div>
</div>

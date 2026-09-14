

<div class="list-product-main">
    <div class="row g-4 ">
        {foreach from=$products item=product}
            <div class="col-sm-6 col-md-4 col-lg-4 item-product"  data-id-cart="{$id_cart}" data-id-product="{$product.id}" data-id-product-attribute="{$product.id_product_attribute}" >
                <div class="product-box">
                    <div class="product-header">
                        <div class="product-image">
                            <a href="{$product.url_product}" title="{$product.name}">
                                <img class="lazy media-object" src="{$product.cover.medium.url|default:$urls.no_picture_image.bySize.cart_default.url}" alt="{$product.name}" class="img-fluid w-100 rounded">
                            </a>
                            <div class="product-header-top position-absolute top-0 end-0 ">
                                <a class="btn cart-button delete-to-cart" data-id-cart="{$id_cart}" data-id-product="{$product.id}" data-id-product-attribute="{$product.id_product_attribute}">
                                    <i class="fa-light fa-xmark"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="product-footer">
                        <div class="product-detail">
                            <div class="media-body">
                                <span class="product-name">
                                     <a href="{$product.url_product}"> {$product.name}</a>
                                     <p class="referencia-prd">Ref. {$product.reference}</p>
                                     {if $product.view == "fitting"}
                                         <p class="referencia-prd">
                                            {$product.fitting.fitting_day} - {$product.fitting.fitting_hour}
                                             {if isset($product.fitting.fitting_location)} / {$product.fitting.fitting_location}{/if}
                                        </p>
                                     {/if}
                                </span>
                                <div class="product-price-content">
                                    <span class="product-price float-xs-left {if $product.has_discount}dto{/if}">{$product.price}</span>
                                    {if $product.has_discount}
                                        <div class="product-discount">
                                            <span class="regular-price">{$product.regular_price}</span>
                                            {if $product.discount_type === 'percentage'}
                                                <span class="discount discount-percentage">-{$product.discount_percentage_absolute}</span>
                                            {else}
                                                <span class="discount discount-amount">-{$product.discount_to_display}</span>
                                            {/if}
                                        </div>
                                    {/if}
                                </div>
                            </div>
                            {if $product.view != "fitting"}
                                <div class="quantity-body">
                                    <div class="le-quantity">
                                        <a class="minus" href="#reduce"></a>
                                        <input name="quantity" readonly type="text" data-qty-prev="{$product.quantity}"  value="{$product.quantity}" class="cart-product-quantity"   disabled>
                                        <a class="plus" href="#add"></a>
                                    </div>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>
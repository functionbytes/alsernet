
<div id="block-modal" class="block-modal modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">{$translations.title_blockcart}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>

            <div class="modal-body">
                {if $blockeds|@count > 0}
                    <div class="blocked-products-list">

                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div>
                                <p class="mb-1 fw-bold">{l s='Products with delivery restrictions' mod='alsernetshopping'}</p>
                                <p class="mb-0">
                                    {l s='The following products cannot be delivered to your current address. Please remove them or change your delivery address.' mod='alsernetshopping'}
                                </p>
                            </div>
                        </div>

                        {foreach from=$blockeds item=product}
                            <div class="blocked-product-item" data-product-id="{$product.id_product}" data-product-attribute="{$product.id_product_attribute}">
                                <div class="row align-items-center">
                                    <div class="col-2">
                                        <div class="product-image-wrapper">
                                            {if $product.default_image && $product.default_image.medium && $product.default_image.medium.url}
                                                <img src="{$product.default_image.medium.url}"
                                                     title="{$product.default_image.legend|default:$product.name}"
                                                     alt="{$product.default_image.legend|default:$product.name}"
                                                     loading="lazy"
                                                     class="product-image img-fluid">
                                            {else}
                                                {if $urls && $urls.no_picture_image && $urls.no_picture_image.bySize && $urls.no_picture_image.bySize.medium_default}
                                                    <img src="{$urls.no_picture_image.bySize.medium_default.url}"
                                                         loading="lazy"
                                                         class="product-image img-fluid"/>
                                                {else}
                                                    <div class="no-image-placeholder product-image img-fluid d-flex align-items-center justify-content-center bg-light">
                                                        <i class="fa-regular fa-image text-muted"></i>
                                                    </div>
                                                {/if}
                                            {/if}
                                        </div>
                                    </div>

                                    <div class="col-9">
                                        <div class="product-details">
                                            <h5 class="product-name">{$product.name}</h5>

                                            <div class="product-meta">
                                                {if $product.reference}
                                                    <div class="product-reference">
                                                        <small>{l s='Ref' mod='alsernetshopping'}: {$product.reference}</small>
                                                    </div>
                                                {/if}
                                                {if $product.attributes && $product.attributes != ''}
                                                    <div class="product-attributes">
                                                        <small>{$product.attributes_small|default:$product.attributes}</small>
                                                    </div>
                                                {/if}
                                                <div class="product-quantity">
                                                    <small>{l s='Qty' mod='alsernetshopping'}: {$product.cart_quantity}</small>
                                                </div>
                                            </div>

                                            <div class="product-price">
                                                <span class="price {if $product.has_discount}has-discount{/if}">
                                                    {$product.price}
                                                </span>
                                                {if $product.has_discount}
                                                    <span class="regular-price">{$product.regular_price}</span>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-1">
                                        <div class="product-actions text-center">
                                            <button type="button"
                                                    class="btn delete-blocked-product mb-2"
                                                    data-id-cart="{$cart.id}"
                                                    data-id-product="{$product.id_product}"
                                                    data-id-product-attribute="{$product.id_product_attribute}"
                                                    data-id-customization="{if isset($product.id_customization)}{$product.id_customization}{else}0{/if}"
                                                    data-minimal-quantity="{if isset($product.minimal_quantity)}{$product.minimal_quantity}{else}1{/if}"
                                                    title="{l s='Remove this product' mod='alsernetshopping'}">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                {/if}
            </div>

            <div class="modal-footer">
                <div class="button-group">
                    <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">
                        {l s='Cancel' mod='alsernetshopping'}
                    </button>
                    <button type="button" class="btn btn-primary w-100 change-blocked-delivery">
                        {l s='Change Delivery Address' mod='alsernetshopping'}
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
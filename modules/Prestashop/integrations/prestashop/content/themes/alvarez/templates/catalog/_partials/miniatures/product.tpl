{block name='product_miniature_item'}
    <div class="{if !empty($productClasses)} {$productClasses}{/if}">
        <article class="product-miniature js-product-miniature" data-id-product="{$product.id_product}" data-id-product-attribute="{$product.id_product_attribute}">
            <div class="thumbnail-container">
                <div class="thumbnail">

                    {if not $product.blocked}
                        {include file='catalog/_partials/product-flags-list.tpl'}
                    {/if}

                    {block name='product_thumbnail'}
                        {if $product.cover}
                            <a href="{$product.url}" class="product-thumbnail">
                                <picture>
                                    {if !empty($product.cover.bySize.home_default.sources.avif)}
                                        <source srcset="{$product.cover.bySize.home_default.sources.avif}"  type="image/avif">{/if}
                                    {if !empty($product.cover.bySize.home_default.sources.webp)}
                                        <source srcset="{$product.cover.bySize.home_default.sources.webp}" type="image/webp">{/if}
                                    <img src="/{$product.cover.id_image}/{$product.link_rewrite}.jpg" alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name|truncate:30:'...'}{/if}"  loading="lazy" data-full-size-image-url="{$product.cover.large.url}" width="{$product.cover.bySize.home_default.width}"  height="{$product.cover.bySize.home_default.height}"/>
                                </picture>
                            </a>
                        {else}
                            <a href="{$product.url}" class="product-thumbnail">
                                <picture>
                                    {if !empty($urls.no_picture_image.bySize.home_default.sources.avif)}
                                        <source srcset="https://www.a-alvarez.com/themes/alvarez/assets/img/theme/product/default/{$iso}.png" type="image/avif">{/if}
                                    {if !empty($urls.no_picture_image.bySize.home_default.sources.webp)}
                                        <source srcset="https://www.a-alvarez.com/themes/alvarez/assets/img/theme/product/default/{$iso}.png" type="image/webp">{/if}
                                    <img src="https://www.a-alvarez.com/themes/alvarez/assets/img/theme/product/default/{$iso}.png" loading="lazy" width="{$urls.no_picture_image.bySize.home_default.width}" height="{$urls.no_picture_image.bySize.home_default.height}"/>
                                </picture>
                            </a>
                        {/if}
                    {/block}

                    {widget name="alsernetcustomer" type="stick-wishlist" product=$product}

                </div>

                <div class="product-meta">

                    {block name='product_name'}
                        {if $page.page_name == 'index'}
                            <h3 class="h3 product-title"><a href="{$product.url}"
                                                            content="{$product.url}">{$product.name}</a></h3>
                        {else}
                            <h2 class="h3 product-title"><a href="{$product.url}"
                                                            content="{$product.url}">{$product.name}</a></h2>
                        {/if}
                    {/block}
                    {block name='product_reviews'}
                        {hook h='displayProductListReviews' product=$product}
                    {/block}
                    {if not $product.blocked}
                        {block name='product_price_and_shipping'}
                            {if $product.request_price == '0' || ($product.request_price == '0' && $product.phone_sale == '1') || $product.isPing}

                                <div class="product-prices">
                                    {if isset($product.defaul_on) && $product.defaul_on == '1' }
                                        <span class="price-from">{l s='From' d='Shop.Theme.Catalog'}&nbsp;</span>
                                    {/if}
                                    <div class="current-price">
                                        <div class='current-price-value'>
                                            {$product.price}
                                        </div>
                                        {if $product.has_discount}
                                            <div class="product-discount">
                                                <span class="regular-price">{$product.regular_price}</span>
                                            </div>
                                            {if $product.discount_type === 'percentage'}
                                                <span class="discount discount-percentage">
                                                    {$product.discount_percentage_absolute}
                                                </span>
                                            {else}
                                                <span class="discount discount-amount">
                                                    -{$product.discount_to_display}
                                                </span>
                                            {/if}
                                        {/if}
                                    </div>
                                    {if $product.multiple_price_quantity}
                                        <div class="second-price">
                                            <span class="second-price-text">{l s='The second unit costs'  d='Shop.Theme.Catalog'}</span>
                                            <span class="second-price-value">{$product.price2|number_format:2:',':'.'} {$currency.sign}</span>
                                        </div>
                                    {/if}
                                </div>
                            {/if}
                        {/block}

                    {/if}

                </div>

            </div>
        </article>
    </div>
{/block}

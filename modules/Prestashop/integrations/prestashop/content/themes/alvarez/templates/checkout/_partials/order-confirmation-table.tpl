
<div class="order-confirmation-table">

    {block name='order_confirmation_table'}
        {foreach from=$products item=product}
            <div class="order-items">
                <div class="row">
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-2 col-xl-2">
                        <span class="image">
                            {if !empty($product.cover.bySize.home_default.sources.avif)}
                                <source srcset="{$product.cover.bySize.home_default.sources.avif}"  type="image/avif">
                            {/if}
                            {if !empty($product.cover.bySize.home_default.sources.webp)}
                                <source srcset="{$product.cover.bySize.home_default.sources.webp}" type="image/webp">
                            {/if}
                            <img src="{$product.cover.bySize.home_default.url}" alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name|truncate:30:'...'}{/if}"  loading="lazy" data-full-size-image-url="{$product.cover.large.url}" width="{$product.cover.bySize.home_default.width}"  height="{$product.cover.bySize.home_default.height}"/>
                       </span>
                    </div>
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-8 col-xl-8">
                        <div class="details">
                            {if $add_product_link}
                            <a href="{$product.url}" target="_blank">{/if}
                                <div class="product-details">
                                    <p class="title">
                                        {assign var="parts" value="("|explode:$product.name}
                                        {$parts[0]}
                                    </p>
                                </div>
                                {if $add_product_link}
                                {/if}
                                {if $product.product_reference}<p class="ref">Ref.: {$product.product_reference}</p>{/if}
                                {if isset($product.is_customized) && $product.is_customized == true  && isset($product.product_customization)}
                                    <div class="product-customization">
                                        <strong>{l s='Customization:' d='Shop.Theme.Checkout'}</strong>
                                        <div class="customization-details">
                                            {if isset($product.product_customization.customization)}
                                                {$product.product_customization.customization nofilter}
                                            {/if}
                                        </div>
                                    </div>
                                {/if}

                                {hook h='displayProductPriceBlock' product=$product type="unit_price"}
                                <div class="product-line-info product-price h5 {if isset($product.has_discount)}has-discount{/if}">
                                    <div class="current-price{if $product.reduction_amount > 0 || $product.reduction_percent > 0} has_discount{/if}">
                                        <span class="price">{$product.price} {$currency|replace:'EUR':'€'}</span>
                                    </div>
                                    {if $product.reduction_amount > 0}
                                        <div class="product-discount">
                                            {if isset($product.tax_rate)}
                                                {math equation="((rate / 100) + 1) * product_price" rate=$product.tax_rate product_price=$product.original_product_price|floatval assign="regular_price" format="%.2f"}
                                                <span class="regular-price">{$regular_price|replace:'.':','} {$currency|replace:'EUR':'€'}</span>
                                                <span class="discount discount-amount">
                                                    -{$product.reduction_amount_tax_incl|floatval|round:2|replace:'.':','} {$currency|replace:'EUR':'€'}
                                                </span>
                                            {/if}
                                        </div>
                                    {elseif $product.reduction_percent > 0}
                                        <div class="product-discount">
                                            <span class="regular-price">{$product.original_product_price|replace:'.':','} {$currency|replace:'EUR':'€'}</span>
                                            <span class="discount discount-percentage">
                                              -{$product.reduction_percent|round:2|replace:'.':','} %
                                           </span>
                                        </div>
                                    {/if}
                                </div>
                        </div>
                    </div>
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-2 col-xl-2 content-total ">
                        <div class="total">
                            <strong><span class="qty">x {$product.quantity} = </span> {$product.total|number_format:2:',':''} {$currency|replace:'EUR':'€'}</strong>
                        </div>
                    </div>
                </div>

            </div>
        {/foreach}

        {if Context::getContext()->isMobile() != 1}

        {/if}

        <div class="order-totals">

            <div class="row">
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <table>
                        {foreach $subtotals as $subtotal}
                            {if $subtotal.type !== 'tax' && $subtotal.type != 'gift_wrapping' && $subtotal.label !== null}
                                <tr class="items-tabla-subtotales">
                                    <td>{$subtotal.label}</td>
                                    <td class="valor">{if 'discount' == $subtotal.type}-&nbsp;{/if}{$subtotal.value}</td>
                                </tr>
                            {/if}
                        {/foreach}

                        {if !$configuration.display_prices_tax_incl && $configuration.taxes_enabled}
                            <tr>
                                <td><span class="">{$totals.total.label}&nbsp;{$labels.tax_short}</span></td>
                                <td>{$totals.total.value}</td>
                            </tr>
                            <tr></tr>
                            <tr class="total-item">
                                <td><span class="">{$totals.total_including_tax.label}</span></td>
                                <td>{$totals.total_including_tax.value}</td>
                            </tr>
                        {else}
                            <tr></tr>
                            <tr class="total-item">
                                <td><span class="">{$totals.total.label}&nbsp;{if $configuration.taxes_enabled}{$labels.tax_short}{/if}</span></td>
                                <td>{$totals.total.value}</td>
                            </tr>
                        {/if}
                        {if $subtotals.tax.label !== null}
                            <tr class="sub taxes">
                                <td colspan="2"><span class="label">{l s='%label%:' sprintf=['%label%' => $subtotals.tax.label] d='Shop.Theme.Global'}</span>&nbsp;<span class="value">{$subtotals.tax.value}</span></td>
                            </tr>
                        {/if}
                    </table>

                </div>
            </div>
        </div>

    {/block}

</div>


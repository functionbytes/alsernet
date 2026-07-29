{if $product.show_price && $custom_show_price && empty($product.country_blocked)}
    <div class="product-prices js-product-prices ">
        {block name='product_price'}
            {if $language.id != 1 && $product.bloqueo}
                <div style="background-color: #FF3100; color:#FFFFFF; font-size: 1.10rem;">
                    <p style="margin: 0.5rem; color:#FFFFFF">
                        {l s='SORRY, THIS PRODUCT CANNOT BE SHIPPED TO YOUR COUNTRY' d='Shop.Theme.Global'}
                    </p>
                </div>
            {else}
                <div class="product-price {if $product.has_discount}has-discount{/if}">
                    {if $product.unity && is_numeric($product.unity)}
                        {math equation='x / y' x=$product.price_amount y=$product.unity|intval format="%.2f" assign='price_unity'}
                        {assign var='price_unity_display' value=$price_unity|replace:".":","|cat:" "|cat:$currency.sign}
                        <div class="price-per-unity">
                            {l s='The unit costs [amount]' sprintf=['[amount]' => $price_unity_display] d='Shop.Theme.Catalog'}
                        </div>
                    {/if}

                    <div class="current-price">
                        <div class='current-price-value' content="{$product.rounded_display_price}">
                            {if $smarty.capture.custom_price}
                                {$smarty.capture.custom_price nofilter}
                            {else}
                                {$product.price}
                            {/if}
                        </div>
                        {if $product.has_discount}
                            <div class="product-discount">
                                {hook h='displayProductPriceBlock' product=$product type="old_price"}
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
    </div>
{/if}
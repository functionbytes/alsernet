
{block name='manufacture'}
    {if $product.id_manufacturer}
        <div class="manufacture-container">
            <div class="manufacture-content">
                <div class="manufacture-images">
                    {if isset($manufacturer_image_url)}
                        <a href="{$product_brand_url}">
                            <img src="{$manufacturer_image_url}" class="img img-fluid manufacturer-logo" alt="{$product_manufacturer->name}" loading="lazy">
                        </a>
                    {else}
                        <span>
						<a href="{$product_brand_url}">{$product_manufacturer->name}</a>
					</span>
                    {/if}
                </div>
            </div>
        </div>
    {/if}
{/block}

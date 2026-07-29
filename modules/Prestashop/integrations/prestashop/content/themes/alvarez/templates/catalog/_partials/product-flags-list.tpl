{block name='product_flags'}
    {assign var="total_extra_flag" value=0}
    {if $product.hour}
        {assign var="total_extra_flag" value=$total_extra_flag+1}
    {/if}
    {if $product.is_new}
        {assign var="total_extra_flag" value=$total_extra_flag+1}
    {/if}


    {if isset($product.flag) && $product.flag|@count > 0}
        {assign var="flag_range" value=3-$total_extra_flag}
        {assign var="flag_slice" value=$product.flag|array_slice:0:$flag_range}
        <ul class="product-flags">
            {if !$product.segunda_mano}
                {if $product.hour}
                    <li class="product-flag-item" style="color:#ffffff; background-color:#d6021f;">
                        {l s='Send 48 hour' d='Shop.Theme.Global'}
                    </li>
                {/if}
                {if $product.is_new}
                    <li class="product-flag-item" style="color:#ffffff; background-color:#4C4C4C;">
                        {l s='New product' d='Shop.Theme.Global'}
                    </li>
                {/if}
            {/if}
            {foreach from=$flag_slice item=flag}
                {if isset($flag.type) && isset($flag.label)}
                    <li class="product-flag-item {$flag.type}" {if isset($flag.colorbg) && isset($flag.colort)} style="color:{$flag.colort}; background-color:{$flag.colorbg};"{/if}>
                        {$flag.label}
                    </li>
                {/if}
            {/foreach}
        </ul>
    {else}
        {if $total_extra_flag != 0 && !$product.segunda_mano}
            <ul class="product-flags">
                {if $product.hour}
                    <li class="product-flag-item" style="color:#ffffff; background-color:#d6021f;">
                        {l s='Send 48 hour' d='Shop.Theme.Global'}
                    </li>
                {/if}
                {if $product.is_new}
                    <li class="product-flag-item" style="color:#ffffff; background-color:#4C4C4C;">
                        {l s='New product' d='Shop.Theme.Global'}
                    </li>
                {/if}
            </ul>
        {/if}
    {/if}
{/block}

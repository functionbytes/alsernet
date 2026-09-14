<div class="row mb-4">
    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
       <div class="invoice-body">
            <div class="order-products-section">
                <div class="table-responsive">
                    <table class="table orders-table">
                        <thead>
                            <tr>
                                <th class="text-left" colspan="2">{l s='Product' d='Shop.Customer.Orders'}</th>
                                <th colspan="1">{l s='Quantity' d='Shop.Customer.Orders'}</th>
                                <th colspan="1">{l s='Unit price' d='Shop.Customer.Orders'}</th>
                                <th colspan="1" >{l s='Total price' d='Shop.Customer.Orders'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$order.products item=product}
                                <tr>
                                    <td colspan="2" class="text-left" >
                                        <ul class="table-details pl-0">
                                            <li class="text-title">
                                                <a {if isset($product.download_link)}href="{$product.download_link}"{/if}>
                                                    {$product.name}
                                                </a>
                                            </li>
                                            {if $product.product_reference}
                                                <li class="text-content">
                                                    {l s='Reference' d='Shop.Customer.Orders'}: {$product.product_reference}
                                                </li>
                                            {/if}
                                            {if $product.customizations}
                                                {foreach from=$product.customizations item="customization"}
                                                {/foreach}
                                            {/if}
                                        </ul>
                                    </td>
                                    <td colspan="1">
                                        {if $product.customizations}
                                            {foreach $product.customizations as $customization}
                                                {$customization.quantity}
                                            {/foreach}
                                        {else}
                                            {$product.quantity}
                                        {/if}
                                    </td>
                                    <td colspan="1">{$product.price}</td>
                                    <td colspan="1">{$product.total}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                        <tfoot>
                            {foreach $order.subtotals as $line}
                                {if $line.value}
                                    <tr>
                                        <td colspan="1"></td>
                                        <td colspan="1"></td>
                                        <td colspan="2" class="text-subtotals text-left borde-l">{$line.label}</td>
                                        <td colspan="1" class="borde-r">{$line.value}</td>
                                    </tr>
                                {/if}
                            {/foreach}
                            <tr >
                                <td colspan="1"></td>
                                <td colspan="1"></td>
                                <td colspan="2" class="text-total text-left">{$order.totals.total.label}</td>
                                <td colspan="1" class="text-total">{$order.totals.total.value}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
       </div>
    </div>
</div>

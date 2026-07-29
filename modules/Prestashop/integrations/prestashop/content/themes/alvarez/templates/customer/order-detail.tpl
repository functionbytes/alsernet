{extends file='customer/page.tpl'}

{block name='page_content'}

        <button class="btn btn-dashboard-show mb-4 d-lg-none">
            {l s='Show menu' d='Shop.Customer.Orders'}
        </button>

        <div class="dashboard-right-sidebar">
                <div class="order-detail">

            <div class="dashboard-title d-flex justify-content-between align-items-center">
                <div class="title">
                    <h2>{l s='Order details' d='Shop.Customer.Orders'}: #{$order.details.id}</h2>
                </div>
            </div>



            <div class="row mb-4">
                <div class="col-12">
                    <ul class="list-unstyled small text-muted">
                        {if $order.details.invoice_url}
                            <li>
                                <i class="material-icons align-middle me-1" style="font-size:16px;">picture_as_pdf</i>
                                <a href="{$order.details.invoice_url}" target="_blank">
                                    {l s='Download your invoice as a PDF file.' d='Shop.Customer.Orders'}
                                </a>
                            </li>
                        {/if}
                        {if $order.details.recyclable}
                            <li>
                                <i class="material-icons align-middle me-1" style="font-size:16px;">recycling</i>
                                {l s='You have given permission to receive your order in recycled packaging.' d='Shop.Customer.Orders'}
                            </li>
                        {/if}
                        {if $order.details.gift_message}
                            <li>
                                <i class="material-icons align-middle me-1" style="font-size:16px;">card_giftcard</i>
                                {l s='You have requested gift wrapping for this order.' d='Shop.Customer.Orders'}
                            </li>
                            <li>
                                <strong>{l s='Message' d='Shop.Customer.Orders'}:</strong> {$order.details.gift_message nofilter}
                            </li>
                        {/if}
                    </ul>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                    <div class="summery-invoice">
                        <p class="summery-invoicetitle">{l s='Amount Due' d='Shop.Theme.Checkout'}</p>
                        <div class="summery-invoiceamount">{$order.totals.total.value}</div>

                        <ul class="summery-invoicepayment">
                            <li>
                                <p>{l s='Placed date' d='Shop.Theme.Checkout'} :</p>
                                <p > {$order.details.order_date}</p>
                            </li>
                            <li>
                                <p>{l s='Payment method' d='Shop.Theme.Checkout'} :</p>
                                <p > {$order.details.payment}</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                {if $order.addresses.delivery}
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 mb-3">
                        <div class="summery-box">
                            <div class="summery-header d-block">
                                <h3>{l s='Delivery address %alias%' d='Shop.Theme.Checkout' sprintf=['%alias%' =>'']}</h3>
                            </div>
                            <ul class="summery-contain">
                                <li>
                                    <h4>{l s='Customer' d='Shop.Customer.Orders'} :</h4>
                                    <h4 class="price"> {$order.addresses.delivery.firstname|regex_replace:'/ .*/':''|capitalize} {$order.addresses.delivery.lastname|substr:0:1|upper}.</h4>
                                </li>

                                <li>
                                    <h4>{l s='Phone' d='Shop.Customer.Orders'} :</h4>
                                    <h4 class="price ">{$order.addresses.delivery.phone}</h4>
                                </li>
                                {if isset($order.addresses.delivery.country)}
                                    <li>
                                        <h4>{l s='Country' d='Shop.Customer.Orders'} :</h4>
                                        <h4 class="price ">{$order.addresses.delivery.country}</h4>
                                    </li>
                                {/if}
                                {if isset($order.addresses.delivery.state)}
                                    <li>
                                        <h4>{l s='State' d='Shop.Customer.Orders'} :</h4>
                                        <h4 class="price ">{$order.addresses.delivery.state}</h4>
                                    </li>
                                {/if}
                                {if isset($order.addresses.delivery.city)}
                                    <li>
                                        <h4>{l s='City' d='Shop.Customer.Orders'} :</h4>
                                        <h4 class="price ">{$order.addresses.delivery.city}</h4>
                                    </li>
                                {/if}
                                <li>
                                    <h4>{l s='Address' d='Shop.Customer.Orders'}:</h4>
                                    <h4 class="price">
                                        {if isset($order.addresses.delivery.address1)}
                                            {$order.addresses.delivery.address1}
                                        {/if}
                                    </h4>
                                </li>

                                <li>
                                    <h4>{l s='Postcode' d='Shop.Customer.Orders'} :</h4>
                                    <h4 class="price ">{$order.addresses.delivery.postcode}</h4>
                                </li>
                            </ul>
                        </div>
                    </div>
                {/if}

                {if $order.addresses.invoice}
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 mb-3">
                        <div class="summery-box">
                            <div class="summery-header d-block">
                                <h3>{l s='Invoice address %alias%' d='Shop.Theme.Checkout' sprintf=['%alias%' => $order.addresses.invoice.alias]}</h3>
                            </div>
                            <ul class="summery-contain">
                                <li>
                                    <h4>{l s='Customer' d='Shop.Customer.Orders'} :</h4>
                                    <h4 class="price"> {$order.addresses.invoice.firstname|regex_replace:'/ .*/':''|capitalize} {$order.addresses.invoice.lastname|substr:0:1|upper}.</h4>
                                </li>

                                <li>
                                    <h4>{l s='Phone' d='Shop.Customer.Orders'} :</h4>
                                    <h4 class="price ">{$order.addresses.invoice.phone}</h4>
                                </li>
                                {if isset($order.addresses.invoice.country)}
                                    <li>
                                        <h4>{l s='Country' d='Shop.Customer.Orders'} :</h4>
                                        <h4 class="price ">{$order.addresses.invoice.country}</h4>
                                    </li>
                                {/if}
                                {if isset($order.addresses.invoice.state)}
                                    <li>
                                        <h4>{l s='State' d='Shop.Customer.Orders'} :</h4>
                                        <h4 class="price ">{$order.addresses.invoice.state}</h4>
                                    </li>
                                {/if}
                                {if isset($order.addresses.invoice.city)}
                                    <li>
                                        <h4>{l s='City' d='Shop.Customer.Orders'} :</h4>
                                        <h4 class="price ">{$order.addresses.invoice.city}</h4>
                                    </li>
                                {/if}
                                <li>
                                    <h4>{l s='Address' d='Shop.Customer.Orders'}:</h4>
                                    <h4 class="price">
                                        {if isset($order.addresses.invoice.address1)}
                                            {$order.addresses.invoice.address1}
                                        {/if}
                                    </h4>
                                </li>

                                <li>
                                    <h4>{l s='Postcode' d='Shop.Customer.Orders'} :</h4>
                                    <h4 class="price ">{$order.addresses.invoice.postcode}</h4>
                                </li>


                            </ul>
                        </div>
                    </div>
                {/if}
            </div>

            {if $order.details.is_returnable}
                {include file='customer/_partials/order-detail-return.tpl'}
            {else}
                {include file='customer/_partials/order-detail-no-return.tpl'}
            {/if}

            {if isset($order.history) && $order.history|@count > 0}
                <hr>
                <div class="row">
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <h5 class="mb-0">{l s='History title' d='Shop.Customer.Orders'}</h5>
                        <p>{l s='History description' d='Shop.Customer.Orders'}</p>
                        <div class="order-table-section">
                            <div class="table-responsive">
                                <table class="table order-tab-table">
                                    <thead>
                                    <tr>
                                        <th>{l s='Description' d='Shop.Customer.Orders'}</th>
                                        <th>{l s='Date' d='Shop.Customer.Orders'}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$order.history item=state}
                                        <tr>
                                            <td>{$state.ostate_name}</td>
                                            <td>{$state.history_date|date_format:"%d/%m/%Y"}</td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}

            {if isset($order.shipping.all) && $order.shipping.all|@count > 0}
                <hr>
                <div class="row">
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <h5 class="mb-0">{l s='Tracking title' d='Shop.Customer.Orders'}</h5>
                        <p>{l s='Tracking description' d='Shop.Customer.Orders'}</p>
                        <div class="order-table-section">
                            <div class="table-responsive">
                                <table class="table order-tab-table">
                                    <thead>
                                    <tr>
                                        <th>{l s='Date' d='Shop.Customer.Orders'}</th>
                                        <th>{l s='Transportation agency' d='Shop.Theme.Checkout'}</th>
                                        <th>{l s='Number of follow-up' d='Shop.Theme.Checkout'}</th>
                                        <th>{l s='Tracking link' d='Shop.Theme.Checkout'}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$order.shipping.all item=line}
                                        <tr>
                                            <td>{$line.fenvio|default:'-'}</td>
                                            <td>{$line.description|default:'-'}</td>
                                            <td>{$line.codtracking|default:'-'}</td>
                                            <td>
                                                {if isset($line.url) && $line.url != '#'}
                                                    <a href="{$line.url}" target="_blank" rel="noopener noreferrer">
                                                        {l s='click here tracking' d='Shop.Customer.Orders'}
                                                    </a>
                                                {else}
                                                    <span>{l s='No tracking link' d='Shop.Theme.Checkout'}</span>
                                                {/if}
                                            </td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}

            {include file='customer/_partials/order-messages.tpl'}

        </div>
</div>
{/block}

{extends 'customer/page.tpl'}

{block name='page_content'}

    <button class="btn btn-dashboard-show mb-4 d-lg-none">
        {l s='Show menu' d='Shop.Customer.Orders'}
    </button>

    <div class="dashboard-right-sidebar">
        <div class="dashboard-orders">

            <div class="dashboard-title d-flex justify-content-between align-items-center">
                <div class="title">
                    <h2>{l s='My Orders' d='Shop.Customer.Orders'}</h2>
                </div>
            </div>

            <div class="download-detail">
                {if isset($orders) && $orders|@count > 0}
                    <div class="select-filter-box mt-3">
                    <form>
                        <div class="input-group download-form">
                            <input type="text" class="form-control" id="search-order" placeholder="{l s='Search your order' d='Shop.Customer.Orders'}">
                            <button class="btn theme-bg-color text-light" type="button" id="search-button">{l s='Search' d='Shop.Customer.Orders'}</button>
                        </div>
                    </form>


                    {if isset($statuses) && $statuses|@count > 0}

                        <div class="mt-3">
                            <select class="form-control select2" id="order-status-filter">
                                <option value="pills-all" selected>{l s='All Orders' d='Shop.Customer.Orders'}</option>
                                {foreach from=$statuses item=status}
                                    <option value="pills-{$status.id_order_state}">
                                        {if $status.id_order_state == 27}
                                            {l s='Preparation in progress' d='Shop.Theme.Erp'}
                                        {else}
                                            {$status.name}
                                        {/if}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    {/if}

                    </div>

                    <div class="tab-content mt-3" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-all" role="tabpanel">
                            {if isset($orders) && $orders|@count > 0}
                                <div class="download-table order-category">
                                    <div class="table-responsive">
                                        <table class="table orders-table">
                                            <thead>
                                            <tr>
                                                <th>{l s='Order' d='Shop.Customer.Orders'}</th>
                                                <th>{l s='Date' d='Shop.Customer.Orders'}</th>
                                                <th>{l s='Total' d='Shop.Customer.Orders'}</th>
                                                <th>{l s='Payment' d='Shop.Customer.Orders'}</th>
                                                <th>{l s='Status' d='Shop.Customer.Orders'}</th>
                                                <th>{l s='Actions' d='Shop.Customer.Orders'}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            {foreach from=$orders item=order}
                                                <tr>
                                                    <td>{$order.id_order}</td>
                                                    <td>{$order.date_add}</td>
                                                    <td>{$order.total_paid|default:'0.00'}</td>
                                                    <td>{$order.payment}</td>
                                                    <td>
                                                        {if $order.id_order_state == 27}
                                                            {l s='Preparation in progress' d='Shop.Theme.Erp'}
                                                        {else}
                                                            {$order.order_state}
                                                        {/if}
                                                    </td>
                                                    <td>{include file='customer/_partials/order-actions.tpl' order=$order}</td>
                                                </tr>
                                            {/foreach}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            {else}
                                <p class="text-muted text-center">{l s='No orders found' d='Shop.Customer.Orders'}</p>
                            {/if}
                        </div>

                        {foreach from=$statuses item=status}
                            <div class="tab-pane fade" id="pills-{$status.id_order_state}" role="tabpanel">
                                {if isset($orders) && $orders|@count > 0}
                                    {assign var="hasOrders" value=false}
                                    <div class="download-table order-category" data-status="{$status.id_order_state}">
                                        <h2 class="mb-3">
                                            {if $status.id_order_state == 27}
                                                {l s='Preparation in progress' d='Shop.Theme.Erp'}
                                            {else}
                                                {$status.name}
                                            {/if}
                                        </h2>
                                        <div class="table-responsive">
                                            <table class="table orders-table">
                                                <thead>
                                                <tr>
                                                    <th>{l s='Order' d='Shop.Customer.Orders'}</th>
                                                    <th>{l s='Date' d='Shop.Customer.Orders'}</th>
                                                    <th>{l s='Total' d='Shop.Customer.Orders'}</th>
                                                    <th>{l s='Payment' d='Shop.Customer.Orders'}</th>
                                                    <th>{l s='Actions' d='Shop.Customer.Orders'}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                {foreach from=$orders item=order}
                                                    {if $order.id_order_state == $status.id_order_state}
                                                        {assign var="hasOrders" value=true}
                                                        <tr>
                                                            <td>{$order.id_order}</td>
                                                            <td>{$order.date_add}</td>
                                                            <td>{$order.total_paid|default:'0.00'}</td>
                                                            <td>{$order.payment}</td>
                                                            <td>{include file='customer/_partials/order-actions.tpl' order=$order}</td>
                                                        </tr>
                                                    {/if}
                                                {/foreach}
                                                </tbody>
                                            </table>
                                        </div>
                                        {if !$hasOrders}
                                            <p class="text-muted text-center">{l s='No orders in this status' d='Shop.Customer.Orders'}</p>
                                        {/if}
                                    </div>
                                {/if}
                            </div>
                        {/foreach}
                    </div>


                {else}

                    <div class="orders-empty-container text-center py-5 ">
                        <i class="fa-solid fa-cart-xmark"></i>
                        <h1>{l s='title empty orders' d='Shop.Customer.Orders'}</h1>
                        <p>{l s='Description empty orders' d='Shop.Customer.Orders'}</p>
                    </div>


                {/if}

            </div>
        </div>
    </div>

{/block}

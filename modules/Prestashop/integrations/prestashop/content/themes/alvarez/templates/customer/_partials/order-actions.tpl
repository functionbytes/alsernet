
<div class="dropdown">

    <button class="btn dropdown-toggle" type="button" aria-expanded="false">
        {l s='Options' d='Shop.Theme.Checkout'}
    </button>

    <ul class="dropdown-menu">
        {if isset($order.details.tracking_link)}
            <li>
                <a class="dropdown-item d-flex align-items-center gap-3" href="{$order.details.tracking_link}">
                    <i class="ti ti-truck"></i> {l s='Track Order' d='Shop.Customer.Orders'}
                </a>
            </li>
        {/if}
        {if isset($order.details.view_link)}
            <li>
                <a class="dropdown-item d-flex align-items-center gap-3" href="{$order.details.view_link}">
                    <i class="ti ti-eye"></i> {l s='View Order' d='Shop.Customer.Orders'}
                </a>
            </li>
        {/if}
    </ul>
</div>

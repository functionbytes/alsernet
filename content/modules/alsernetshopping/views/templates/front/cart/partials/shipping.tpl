{if $shippingprogress.active}
    <div class="shipping-amount">
        <div class="success-box">
            <p class="text">
                {$translations.missing_for_free_shipping}
                <span class="shipping">{$shippingprogress.amount_remaining|number_format:2:'.':''} €</span>
                {$translations.free_shipping_message}
            </p>
            <div class="progress warning-progress">
                <div role="progressbar"
                     aria-valuenow="{$shippingprogress.percentage}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     class="progress-bar progress-bar-striped progress-bar-animated"
                     style="width: {$shippingprogress.percentage}%; {if $shippingprogress.percentage == 100}display: none;{/if}">
                    <i class="fa-duotone fa-solid fa-truck"></i>
                </div>
            </div>
        </div>
    </div>
{/if}
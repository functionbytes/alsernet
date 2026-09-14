<div class="selected-address-wrapper">
    <div class=" align-items-center mb-3">
        <div class="col-md-12">
            {if isset($delivery_address) && $delivery_address instanceof Address}
                <div class="relay-address">
                    <ul class="relay-address list-unstyled mb-0">
                        <li>
                            {$delivery_address->firstname|escape:'htmlall':'UTF-8'}
                            {$delivery_address->lastname|escape:'htmlall':'UTF-8'}
                        </li>
                        <li>
                            {$delivery_address->address1|escape:'htmlall':'UTF-8'}
                        </li>
                        {if $delivery_address->address2}
                            <li>
                                {$delivery_address->address2|escape:'htmlall':'UTF-8'}
                            </li>
                        {/if}
                        <li>
                            {$delivery_address->postcode|escape:'htmlall':'UTF-8'},
                            {$delivery_address->city|escape:'htmlall':'UTF-8'}
                        </li>
                        {if $state_name}
                            <li>
                                {$state_name|escape:'htmlall':'UTF-8'}
                            </li>
                        {/if}
                        {if $country_name}
                            <li>
                                {$country_name|escape:'htmlall':'UTF-8'}
                            </li>
                        {/if}
                    </ul>
                </div>
            {else}
                <div class="relay-address">
                    <strong>{l s='No delivery address available'}</strong>
                </div>
            {/if}
        </div>

        <div class="col-md-12">
            <a href="#" class="btn btn-primary w-100 selected-address-delivery">
                <i class="fas fa-edit me-2"></i> {l s='Change delivery address' mod='alsernetshopping'}
            </a>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {

        // Click sobre el bloque PADRE de la opción de entrega
        $(document).on('click', '.delivery-option-item', function(event) {
            console.log('📦 Click en el padre .delivery-option-item con carrier:', $(this).data('carrier'));

            if ($(this).data('carrier') == '101') {
                // window.checkoutManager.showToast('success', '.delivery-option-item');
            }
        });

        $(document).on('click', '.selected-address-wrapper, .selected-address-delivery', function(event) {
            event.preventDefault();

            console.log('🔄 Navigating to addresses step from delivery address selection');

            if (window.checkoutNavigator && typeof window.checkoutNavigator.validateAndNavigate === 'function') {
                window.checkoutNavigator.validateAndNavigate('address').catch((error) => {
                    console.warn(`❌ Accordion navigation to addresses failed:`, error);
                });
            } else {
                console.warn('⚠️ CheckoutNavigator accordion methods not available, falling back to page reload');
            }
        });
    });
</script>
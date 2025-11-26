<div class="checkout-step step-delivery">
    <div class="checkout-box">
        <div class="checkout-detail">

            {if $delivery_options|count}

                {if $products_in_cart_pickup_gc }
                    <div class="checkout-several-product-types">
                        <div class="alert alert-warning">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <p>
                                {l s='Weapon-type products will be collected at the intervention of the selected Civil Guard, the rest of the products will be sent to the specified billing address.' mod='alsernetshopping'}
                            </p>
                            <p>
                                {l s='The weapon-type products that you must collect in the intervention of the selected Civil Guard are the following:' mod='alsernetshopping'}
                            </p>
                            <ol>
                                {foreach from=$products_in_cart_pickup_gc item=$product_cart}
                                    <li>{$product_cart.name}</li>
                                {/foreach}
                            </ol>
                        </div>
                    </div>
                {/if}

                <form method="post" id="js-delivery" class="checkout-form step-checkout-delivery" autocomplete="false" novalidate="novalidate">

                    <div class="delivery-options">

                        <div id="hook-display-before-carrier">
                            {$hookDisplayBeforeCarrier nofilter}
                        </div>

                        <div class="delivery-options">
                            {foreach from=$delivery_options item=carrier key=carrier_id}
                                <div class="col-xxl-12 col-lg-12 col-md-12 col-sm-12  delivery-option-item" data-carrier="{$carrier.id}">
                                    <div class="delivery-option" >
                                        <div class="delivery-category">
                                            <div class="shipment-detail w-100">
                                                <div class="form-check custom-form-check hide-check-box">
                                                    <div class="col-sp-0 col-xs-0 col-sm-0 col-md-0 col-lg-0 col-xl-0">
                                                        <input class="form-check-input delivery_option_select" type="radio" name="delivery_option[{$id_address}]" id="delivery_option_{$carrier.id}" data-id="{$carrier_id}" value="{$carrier_id},"{if $delivery_option == $carrier_id || $selected_carrier == $carrier_id} checked{/if}>
                                                    </div>
                                                    <label class="form-check-label" for="delivery_option_{$carrier.id}">
                                                        {$carrier.name}
                                                        {if $carrier.delay && $carrier.delay != '' && $carrier.delay != '.'}
                                                            <div class="carrier-description">{$carrier.delay}</div>
                                                        {/if}
                                                    </label>


                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="carrier-extra-contents carrier-extra-content {if $delivery_option != $carrier_id} d-none {/if}" data-carrier="{$carrier_id}">
                                        {$carrier.extraContent nofilter}
                                    </div>
                                </div>
                            {/foreach}
                        </div>

                        <div id="hook-display-after-carrier">
                            {$hookDisplayAfterCarrier nofilter}
                        </div>

                        <div class="row g-4">
                            <div class="col-xxl-12 col-lg-12 col-md-12 col-sm-12">
                                <div class="order-receive">

                                    <div id="delivery" class="form-fields">
                                        <div class="form-group ">
                                            <label class="form-control-label" for="field-alias"> {l s='If you would like to add a comment about your order, please write it in the field below.' mod='alsernetshopping'} </label>
                                            <div class="">
                                                <textarea rows="2" cols="120" class="form-control"  id="delivery_message" name="delivery_message">{if isset($delivery_message)}{$delivery_message}{/if}</textarea>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="recycla-shipping {if !$recyclablePackAllowed} d-none{/if}">
                                        <div class="form-check">
                                            <div class="check">
                                                <input class="form-check-input fixed-size-input"  id="input_recyclable" name="recyclable" value="1" {if $recyclable} checked {/if} >
                                                <label class="form-check-label" for="condition">
                                                    {l s='I would like to receive my order in recycled packaging.' mod='alsernetshopping'}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="shipping-container {if !$gift.allowed} d-none{/if}">
                                        <div  class="gift-shipping">
                                            <div class="form-group ">
                                                <label class="form-control-label" for="field-alias"> {l s='Want to add a greeting card? Write your message here:' mod='alsernetshopping'} </label>
                                                <div class="">
                                                    <textarea rows="2" cols="120" class="form-control"  id="gift_message" name="gift_message">{$gift.message}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column align-items-center actions-step">
                                        <button type="submit" class="btn btn-secondary next w-50 mb-2">
                                            {l s='Continue' mod='alsernetshopping'}
                                        </button>

                                        <button class="btn btn-sm btn-primary previous w-50">
                                            {l s='Previous' mod='alsernetshopping'}
                                        </button>
                                    </div>

                                </div>

                            </div>

                            {hook h='DisplayConfirmDeliveryOption' mod='alsernetgooglegtm'}
                            <div id="extra_carrier"></div>

                        </div>

                </form>
            {else}
                <div class="row form-suscribe-confirmation">
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-12">
                        <div class="success-verification-container">
                            <i class="fa-solid fa-location-crosshairs-slash"></i>
                            <h1>{l s='Unfortunately' mod='alsernetshopping'}</h1>
                            <p>{l s='There are no carriers available for your delivery address.' mod='alsernetshopping'}</p>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>

<script type="text/javascript">
    window.carrierConfig = {json_encode($carrier_config) nofilter};
    window.selectedCarrier = {if isset($selected_carrier)}{$selected_carrier|intval}{else}null{/if};
    window.selectedPaymentOption = {if isset($selected_payment_option)}"{$selected_payment_option|escape:'javascript':'UTF-8'}"{else}null{/if};
</script>

<div id="delivery-delivery-modal" class="missing-delivery modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">
                    {l s='Delivery address required' mod='alsernetshopping'}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                        <div class="title">
                            {l s='Please select a delivery address to continue with your order.' mod='alsernetshopping'}
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-content-buttons">
                        <button type="button" class="btn btn-primary change-delivery-address w-100">
                            {l s='Select delivery address' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{* Modal for pickup location selection *}
<div id="pickup-location-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left">
                    {l s='Pickup location selection required' mod='alsernetshopping'}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Continue' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                        <div class="title">
                            {l s='To continue shipping to' mod='alsernetshopping'} <span id="modal-service-name"></span>, {l s='you must select' mod='alsernetshopping'} <span id="modal-location-text"></span>.
                        </div>
                        <div class="mt-3">
                            <p>{l s='Please select a location from the map or available list.' mod='alsernetshopping'}</p>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-content-buttons">
                        <button type="button" class="btn btn-primary w-100" data-dismiss="modal">
                            <span id="modal-button-text">{l s='Select location' mod='alsernetshopping'}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* Modal for missing delivery option selection *}
<div id="missing-delivery-option-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left">
                    {l s='Delivery method required' mod='alsernetshopping'}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Continue' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                        <div class="title">
                            {l s='Please select a delivery method to continue with your order.' mod='alsernetshopping'}
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-content-buttons">
                        <button type="button" class="btn btn-primary w-100" data-dismiss="modal">
                            {l s='Select delivery method' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

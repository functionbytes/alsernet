


<div class="checkout-step step-payment">
    <div class="checkout-box">
        <div class="checkout-detail">



            {if false && $is_free}

                {hook h='displayPaymentTop'}

                <div class="payment-explain">
                    {l s='Once the payment method has been selected, please accept our data protection and click on' mod='alsernetshopping'}
                    <strong>{l s='FINISH ORDER' mod='alsernetshopping'}</strong>.
                </div>

                {if !empty($display_transaction_updated_info)}
                    <p class="cart-payment-step-refreshed-info">
                        {l s='Transaction amount has been correctly updated' mod='alsernetshopping'}
                    </p>
                {/if}

                {if $is_free}
                    <p>{l s='No payment needed for this order' mod='alsernetshopping'}</p>
                {/if}

                <div  class="payment-actions">

                    {if $conditions_to_approve|count}
                        <form id="conditions-to-approve" method="GET" >
                            <ul class="list-unstyled">
                                {foreach from=$conditions_to_approve item="condition" key="condition_name"}
                                    <li>
                                        <div class="form-check mb-2">
                                            <input
                                                    class="conditions_to_approve form-check-input fixed-size-input ps-shown-by-js"
                                                    type="checkbox"
                                                    id="conditions_to_approve[{$condition_name}]"
                                                    name="conditions_to_approve[{$condition_name}]"
                                                    value="1"
                                                    required
                                            >
                                            <label class="form-check-label js-terms" for="conditions_to_approve[{$condition_name}]">
                                                {$condition nofilter}
                                            </label>
                                        </div>
                                    </li>
                                {/foreach}
                            </ul>
                        </form>
                    {/if}


                    <div class="alert alert-danger" role="alert" data-alert="danger">
                        {l s='Make sure you have accepted the' mod='alsernetshopping'}
                        <a href="javascript:void(0);" onclick="scrollToSection('#conditions-to-approve');">
                            {l s='conditions' mod='alsernetshopping'}
                        </a>.
                    </div>

                    <div class="d-flex flex-column align-items-center actions-step">
                        <div id="payment-confirmation" class="w-100">
                            <button
                                    type="submit"
                                    id="paymentConfirm"
                                    data-is-free="{if $is_free}1{else}0{/if}"
                                    {if !$is_free && !$selected_payment_option} disabled {/if}
                                    class="btn btn-sm btn-payment btn-next w-50 mb-2"
                            >
                                {l s='Finalize Order' mod='alsernetshopping'}
                            </button>
                        </div>
                    </div>


                    {if $selected_payment_option and $all_conditions_approved}
                        <div class="ps-hidden-by-js">
                            <label for="pay-with-{$selected_payment_option}">{l s='Finalize Orders' mod='alsernetshopping'}</label>
                        </div>
                    {/if}

                </div>
                <hr>
                <div  >

                    <div class="summery-container">
                        {if $delivery_address}
                            {if $delivery_address}
                                <div class="summery-box">
                                    <div class="summery-header d-block">
                                        <h3>{l s='Your delivery and billing address' mod='alsernetshopping'}</h3>
                                    </div>
                                    <ul class="summery-contain">
                                        <li>{l s='Customer' mod='alsernetshopping'}:
                                            {$delivery_address.firstname|regex_replace:'/ .*/':''|capitalize}
                                            {$delivery_address.lastname|substr:0:1|upper}.
                                        </li>
                                        <li>{l s='Phone' mod='alsernetshopping'}: {$delivery_address.phone|default:'—'}</li>
                                        {if $delivery_address.address1}
                                            <li>{l s='Address' mod='alsernetshopping'}: {$delivery_address.address1}</li>
                                        {/if}
                                        {if $delivery_address.city}
                                            <li>{l s='City' mod='alsernetshopping'}: {$delivery_address.city}</li>
                                        {/if}
                                        {if $delivery_address.postcode}
                                            <li>{l s='Postcode' mod='alsernetshopping'}: {$delivery_address.postcode}</li>
                                        {/if}
                                        {if $delivery_address.country}
                                            <li>{l s='Country' mod='alsernetshopping'}: {$delivery_address.country}</li>
                                        {/if}
                                    </ul>
                                </div>
                            {/if}
                        {else}
                            {if $delivery_address}
                                <div class="summery-box">
                                    <div class="summery-header d-block">
                                        <h3>{l s='Your Delivery Address' mod='alsernetshopping'}</h3>
                                    </div>
                                    <ul class="summery-contain">
                                        <li>{l s='Customer' mod='alsernetshopping'}:
                                            {$delivery_address.firstname|regex_replace:'/ .*/':''|capitalize}
                                            {$delivery_address.lastname|substr:0:1|upper}.
                                        </li>
                                        <li>{l s='Phone' mod='alsernetshopping'}: {$delivery_address.phone|default:'—'}</li>
                                        {if $delivery_address.address1}
                                            <li>{l s='Address' mod='alsernetshopping'}: {$delivery_address.address1}</li>
                                        {/if}
                                        {if $delivery_address.city}
                                            <li>{l s='City' mod='alsernetshopping'}: {$delivery_address.city}</li>
                                        {/if}
                                        {if $delivery_address.postcode}
                                            <li>{l s='Postcode' mod='alsernetshopping'}: {$delivery_address.postcode}</li>
                                        {/if}
                                        {if $delivery_address.country}
                                            <li>{l s='Country' mod='alsernetshopping'}: {$delivery_address.country}</li>
                                        {/if}
                                    </ul>
                                </div>
                            {/if}

                            {if $invoice_address}
                                <div class="summery-box">
                                    <div class="summery-header d-block">
                                        <h3>{l s='Your Invoice Address' mod='alsernetshopping'}</h3>
                                    </div>

                                    <ul class="summery-contain">
                                        <li>{l s='Customer' mod='alsernetshopping'}:
                                            {$invoice_address.firstname|regex_replace:'/ .*/':''|capitalize}
                                            {$invoice_address.lastname|substr:0:1|upper}.
                                        </li>
                                        <li>{l s='Phone' mod='alsernetshopping'}: {$invoice_address.phone|default:'—'}</li>
                                        {if $invoice_address.address1}
                                            <li>{l s='Address' mod='alsernetshopping'}: {$invoice_address.address1}</li>
                                        {/if}
                                        {if $invoice_address.city}
                                            <li>{l s='City' mod='alsernetshopping'}: {$invoice_address.city}</li>
                                        {/if}
                                        {if $invoice_address.postcode}
                                            <li>{l s='Postcode' mod='alsernetshopping'}: {$invoice_address.postcode}</li>
                                        {/if}
                                        {if $invoice_address.country}
                                            <li>{l s='Country' mod='alsernetshopping'}: {$invoice_address.country}</li>
                                        {/if}
                                    </ul>
                                </div>
                            {/if}

                        {/if}

                        <div class="summery-box">
                            <div class="summery-header d-block">
                                <h3>{l s='Shipping Method' d='alsernetshopping'}</h3>
                            </div>
                            <ul class="summery-contain">
                                {$selected_delivery_option.name}
                            </ul>
                        </div>

                        {hook h='displayPaymentByBinaries'}


                    </div>
                </div>
            {/if}

            {if $payment_options|count }

                {hook h='displayPaymentTop'}

                <div class="payment-explain">
                    {l s='Once the payment method has been selected, please accept our data protection and click on' mod='alsernetshopping'}
                    <strong>{l s='FINISH ORDER' mod='alsernetshopping'}</strong>.
                </div>

                {if !empty($display_transaction_updated_info)}
                    <p class="cart-payment-step-refreshed-info">
                        {l s='Transaction amount has been correctly updated' mod='alsernetshopping'}
                    </p>
                {/if}

                {if $is_free}
                    <p>{l s='No payment needed for this order' mod='alsernetshopping'}</p>
                {/if}

                {assign var="lottery" value=false}

                {foreach from=$cart.products item="product"}
                    {if $product.is_virtual == "1"}
                        {assign var="lottery" value=true}
                    {/if}
                {/foreach}


                <div class="payment-options {if $is_free}hidden-xs-up{/if}">
                    {foreach from=$payment_options item="option"}
                        <div class="col-xxl-12 col-lg-12 col-md-12 col-sm-12  payment-option-item" data-payment="{$option.id}">

                            <div class="payment-option"  id="{$option.id}-container">
                                <div class="payment-category">

                                    <div class="shipment-detail w-100 d-flex align-items-center justify-content-between">
                                        <div class="form-check custom-form-check hide-check-box">
                                            <div class="col-sp-0 col-xs-0 col-sm-0 col-md-0 col-lg-0 col-xl-0">
                                                <input  class="form-check-input payment_option_select" id="{$option.id}"  data-module-name="{$option.module_name}" name="payment-option" type="radio"  required  {if $selected_payment_option == $option.id || ($is_free && $option.module_name == 'free_order')} checked {/if}>
                                            </div>
                                            <label class="form-check-label" for="{$option.id}">
                                                {$option.call_to_action_text}
                                            </label>

                                        </div>
                                        {if $option.logo}
                                            <div class="payment-logo ms-auto">
                                                <img class="lazy" src="{$option.logo}" data-src="{$option.logo}" alt="{$option.call_to_action_text}">
                                            </div>
                                        {/if}

                                    </div>
                                </div>

                                {* Validar si additionalInformation tiene contenido real *}
                                {assign var="cleanContent" value=$option.additionalInformation|trim|strip_tags|trim}
                                {if $option.additionalInformation && $cleanContent && $cleanContent|strlen > 0 && $cleanContent != '&nbsp;' && $cleanContent != '&#160;'}
                                    <div id="{$option.id}-additional-information"  class="js-additional-info definition-list additional-information d-none" data-carrier="{$option.id}">
                                        {$option.additionalInformation nofilter}
                                    </div>
                                {/if}

                                <div  id="pay-with-{$option.id}-form" class="js-payment-option-form {if $option.id != $selected_payment_option && !($is_free && $option.module_name == 'free_order')} d-none {/if}" >
                                    {if $option.form}
                                        {$option.form nofilter}
                                    {else}
                                        <form id="payment-form" method="POST" action="{$option.action nofilter}">
                                            {foreach from=$option.inputs item=input}
                                                <input type="{$input.type}" name="{$input.name}" value="{$input.value}">
                                            {/foreach}
                                            <button style="display:none" id="pay-with-{$option.id}" type="submit"></button>
                                        </form>
                                    {/if}
                                </div>
                            </div>


                        </div>
                    {/foreach}
                </div>


                <div  class="payment-actions">

                    {if $conditions_to_approve|count}
                        <form id="conditions-to-approve" method="GET" >
                            <ul class="list-unstyled">
                                {foreach from=$conditions_to_approve item="condition" key="condition_name"}
                                    <li>
                                        <div class="form-check mb-2">
                                            <input
                                                    class="conditions_to_approve form-check-input fixed-size-input ps-shown-by-js"
                                                    type="checkbox"
                                                    id="conditions_to_approve[{$condition_name}]"
                                                    name="conditions_to_approve[{$condition_name}]"
                                                    value="1"
                                                    required
                                            >
                                            <label class="form-check-label js-terms" for="conditions_to_approve[{$condition_name}]">
                                                {$condition nofilter}
                                            </label>
                                        </div>
                                    </li>
                                {/foreach}
                            </ul>
                        </form>
                    {/if}

                    {if $show_final_summary}
                        <div class="alert alert-danger" role="alert" data-alert="danger">
                            {if !$is_free}
                                {l s='Make sure you have chosen a' mod='alsernetshopping'}
                                <a href="javascript:void(0);" onclick="scrollToSection('#checkout-payment-step');">
                                    {l s='payment method' mod='alsernetshopping'}
                                </a>
                                {l s='and accepted the' mod='alsernetshopping'}
                            {else}
                                {l s='Make sure you have accepted the' mod='alsernetshopping'}
                            {/if}
                            <a href="javascript:void(0);" onclick="scrollToSection('#conditions-to-approve');">
                                {l s='conditions' mod='alsernetshopping'}
                            </a>.
                        </div>

                    {/if}



                    <div class="d-flex flex-column align-items-center actions-step">
                        <div id="payment-confirmation" class="w-100">
                            <button type="submit" id="paymentConfirm" data-is-free="{if $is_free}1{else}0{/if}" {if !$is_free && !$selected_payment_option} disabled {/if} class="btn btn-sm btn-payment btn-next w-50 mb-2 ">
                                {l s='Finalize Order' mod='alsernetshopping'}
                            </button>
                        </div>
                    </div>




                    {if $selected_payment_option and $all_conditions_approved}
                        <div class="ps-hidden-by-js">
                            <label for="pay-with-{$selected_payment_option}">{l s='Finalize Orders' mod='alsernetshopping'}</label>
                        </div>
                    {/if}

                </div>
                <hr>
                <div  >

                    <div class="summery-container">
                        {if $delivery_address}
                            {if $delivery_address}
                                <div class="summery-box">
                                    <div class="summery-header d-block">
                                        <h3>{l s='Your delivery and billing address' mod='alsernetshopping'}</h3>
                                    </div>
                                    <ul class="summery-contain">
                                        <li>{l s='Customer' mod='alsernetshopping'}:
                                            {$delivery_address.firstname|regex_replace:'/ .*/':''|capitalize}
                                            {$delivery_address.lastname|substr:0:1|upper}.
                                        </li>
                                        <li>{l s='Phone' mod='alsernetshopping'}: {$delivery_address.phone|default:'—'}</li>
                                        {if $delivery_address.address1}
                                            <li>{l s='Address' mod='alsernetshopping'}: {$delivery_address.address1}</li>
                                        {/if}
                                        {if $delivery_address.city}
                                            <li>{l s='City' mod='alsernetshopping'}: {$delivery_address.city}</li>
                                        {/if}
                                        {if $delivery_address.postcode}
                                            <li>{l s='Postcode' mod='alsernetshopping'}: {$delivery_address.postcode}</li>
                                        {/if}
                                        {if $delivery_address.country}
                                            <li>{l s='Country' mod='alsernetshopping'}: {$delivery_address.country}</li>
                                        {/if}
                                    </ul>
                                </div>
                            {/if}
                        {else}
                            {if $delivery_address}
                                <div class="summery-box">
                                    <div class="summery-header d-block">
                                        <h3>{l s='Your Delivery Address' mod='alsernetshopping'}</h3>
                                    </div>
                                    <ul class="summery-contain">
                                        <li>{l s='Customer' mod='alsernetshopping'}:
                                            {$delivery_address.firstname|regex_replace:'/ .*/':''|capitalize}
                                            {$delivery_address.lastname|substr:0:1|upper}.
                                        </li>
                                        <li>{l s='Phone' mod='alsernetshopping'}: {$delivery_address.phone|default:'—'}</li>
                                        {if $delivery_address.address1}
                                            <li>{l s='Address' mod='alsernetshopping'}: {$delivery_address.address1}</li>
                                        {/if}
                                        {if $delivery_address.city}
                                            <li>{l s='City' mod='alsernetshopping'}: {$delivery_address.city}</li>
                                        {/if}
                                        {if $delivery_address.postcode}
                                            <li>{l s='Postcode' mod='alsernetshopping'}: {$delivery_address.postcode}</li>
                                        {/if}
                                        {if $delivery_address.country}
                                            <li>{l s='Country' mod='alsernetshopping'}: {$delivery_address.country}</li>
                                        {/if}
                                    </ul>
                                </div>
                            {/if}

                            {if $invoice_address}
                                <div class="summery-box">
                                    <div class="summery-header d-block">
                                        <h3>{l s='Your Invoice Address' mod='alsernetshopping'}</h3>
                                    </div>

                                    <ul class="summery-contain">
                                        <li>{l s='Customer' mod='alsernetshopping'}:
                                            {$invoice_address.firstname|regex_replace:'/ .*/':''|capitalize}
                                            {$invoice_address.lastname|substr:0:1|upper}.
                                        </li>
                                        <li>{l s='Phone' mod='alsernetshopping'}: {$invoice_address.phone|default:'—'}</li>
                                        {if $invoice_address.address1}
                                            <li>{l s='Address' mod='alsernetshopping'}: {$invoice_address.address1}</li>
                                        {/if}
                                        {if $invoice_address.city}
                                            <li>{l s='City' mod='alsernetshopping'}: {$invoice_address.city}</li>
                                        {/if}
                                        {if $invoice_address.postcode}
                                            <li>{l s='Postcode' mod='alsernetshopping'}: {$invoice_address.postcode}</li>
                                        {/if}
                                        {if $invoice_address.country}
                                            <li>{l s='Country' mod='alsernetshopping'}: {$invoice_address.country}</li>
                                        {/if}
                                    </ul>
                                </div>
                            {/if}

                        {/if}

                        <div class="summery-box">
                            <div class="summery-header d-block">
                                <h3>{l s='Shipping Method' mod='alsernetshopping'}</h3>
                            </div>
                            <ul class="summery-contain">
                                {$selected_delivery_option.show_name}
                            </ul>
                        </div>

                        {hook h='displayPaymentByBinaries'}


                    </div>
                </div>

            {else}
                <div class="row form-suscribe-confirmation">
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-12">
                        <div class="success-verification-container">
                            <i class="fa-solid fa-location-crosshairs-slash"></i>
                            <h1>{l s='Unfortunately' mod='alsernetshopping'}</h1>
                            <p>{l s='there are no payment method available.' mod='alsernetshopping'}</p>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>

{* Modal *}
<div id="paypal-action-modal"
     class="paypal-action-modal modal fade"
     tabindex="-1"
     role="dialog"
     aria-hidden="true"
     aria-labelledby="paypal-action-modal-title">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="paypal-action-modal-title">
                    {l s='Action required to continue' mod='alsernetshopping'}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-12 modal-content-descriptions">
                        <div class="title mb-2">
                            {l s='To finish your order with' mod='alsernetshopping'}
                            <strong id="payment-method-name">PayPal</strong>.
                        </div>
                        <div  id="paypal-wrong-btn-msg">
                            {l s='Please click on the button to continue:' mod='alsernetshopping'}
                        </div>
                    </div>

                    <div class="col-12 modal-content-buttons mt-3">
                        <button type="button"
                                class="btn btn-primary change-delivery-address w-100"
                                data-dismiss="modal">
                            {l s='Go to payment button' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>


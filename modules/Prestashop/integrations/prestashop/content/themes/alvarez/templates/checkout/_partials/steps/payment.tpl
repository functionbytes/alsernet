
{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}

    {hook h='displayPaymentTop'}

    {* used by javascript to correctly handle cart updates when we are on payment step (eg vouchers added) *}
    <div style="display:none" class="js-cart-payment-step-refresh"></div>

    {if !empty($display_transaction_updated_info)}
        <p class="cart-payment-step-refreshed-info">
            {l s='Transaction amount has been correctly updated' d='Shop.Theme.Checkout'}
        </p>
    {/if}

    {if $is_free}
        <p>{l s='No payment needed for this order' d='Shop.Theme.Checkout'}</p>
    {/if}

    {assign var="lottery" value=false}
    {foreach from=$cart.products item="product"}
        {if $product.is_virtual == "1"}
            {assign var="lottery" value=true}
        {/if}
    {/foreach}


    <div class="content-alv">

        <div class="payment-options {if $is_free}hidden-xs-up{/if}">

            <div class="payment-explain">
                {l s='Once the payment method has been selected, please accept our data protection and click on [b]FINISH ORDER[/b].' sprintf=['[b]' => '<strong>', '[/b]' => '</strong>'] d='Shop.Theme.Checkout'}
            </div>

            {foreach from=$payment_options item="option"}

                <div>
                    <div id="{$option.id}-container" class="payment-option clearfix">
                        <span class="custom-radio float-xs-left">
                            <input  class="ps-shown-by-js {if $option.binary} binary {/if}" id="{$option.id}"  data-module-name="{$option.module_name}" name="payment-option" type="radio"  required  {if $selected_payment_option == $option.id || $is_free} checked {/if}>
                            <span></span>
                        </span>
                        <form method="GET" class="ps-hidden-by-js">
                            {if $option.id === $selected_payment_option}
                                {l s='Selected' d='Shop.Theme.Checkout'}
                            {else}
                                <button class="ps-hidden-by-js" type="submit" name="select_payment_option" value="{$option.id}">
                                    {l s='Choose' d='Shop.Theme.Actions'}
                                </button>
                            {/if}
                        </form>

                        <label for="{$option.id}">
                            {if $option.logo}
                                <div class="col-md-8 col-xs-12">
                                    <span>{$option.call_to_action_text}</span>
                                </div>
                                <div class="col-md-2 col-xs-1">
                                    {if $option.logo}
                                        <img class="lazy" src="{$option.logo}" data-src="{$option.logo}">
                                    {/if}
                                </div>
                            {else}
                                <div class="col-xs-12">
                                    <span>{$option.call_to_action_text}</span>
                                </div>
                            {/if}
                        </label>

                    </div>
                </div>

                {if $option.additionalInformation}
                    <div id="{$option.id}-additional-information" class="js-additional-information definition-list additional-information{if $option.id != $selected_payment_option} ps-hidden {/if}">
                        {$option.additionalInformation nofilter}
                    </div>
                {/if}

                <div  id="pay-with-{$option.id}-form" class="js-payment-option-form {if $option.id != $selected_payment_option} ps-hidden {/if}" >
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

                {foreachelse}
                <p class="alert alert-danger">{l s='Unfortunately, there are no payment method available.' d='Shop.Theme.Checkout'}</p>
            {/foreach}
        </div>
    </div>
    <div class="content-alv div-payment-confirm">
        <div id="payment-confirmation">
            <div class="ps-shown-by-js">

                {if $conditions_to_approve|count}
                    <p class="ps-hidden-by-js">
                        {* At the moment, we're not showing the checkboxes when JS is disabled
                            because it makes ensuring they were checked very tricky and overcomplicates
                            the template. Might change later.
                        *}
                        {l s='By confirming the order, you certify that you have read and agree with all of the conditions below:' d='Shop.Theme.Checkout'}
                    </p>
                    <form id="conditions-to-approve" method="GET">
                        <ul>
                            {foreach from=$conditions_to_approve item="condition" key="condition_name"}
                                <li>
                                    <div class="float-xs-left">
                            <span class="custom-checkbox">
                                <input  id    = "conditions_to_approve[{$condition_name}]"
                                        name  = "conditions_to_approve[{$condition_name}]"
                                        required
                                        type  = "checkbox"
                                        value = "1"
                                        class = "ps-shown-by-js"
                                >
                                <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
                            </span>
                                    </div>
                                    <div class="condition-label">
                                        <label class="js-terms" for="conditions_to_approve[{$condition_name}]">
                                            {$condition nofilter}
                                        </label>
                                    </div>
                                </li>
                            {/foreach}
                        </ul>
                    </form>
                {/if}

                {if $show_final_summary}
                    <article class="alert alert-danger js-alert-payment-conditions mb-1" role="alert" data-alert="danger">
                        {l
                        s='Make sure you have chosen a [1]payment method[/1] and accepted the [2]conditions[/2].'
                        sprintf=[
                        '[1]' => '<a href="javascript:void(0);" onclick="scrollToSection(\'#checkout-payment-step\');">',
                        '[/1]' => '</a>',
                        '[2]' => '<a href="javascript:void(0);" onclick="scrollToSection(\'#conditions-to-approve\')">',
                        '[/2]' => '</a>'
                        ]
                        d='Shop.Theme.Checkout'
                        }
                    </article>
                {/if}

                <button type="submit" id="select_payment" {if !$selected_payment_option} disabled {/if} class="btn btn-primary center-block">
                    {if $iso|lower == 'es'}
                        Finalizar
                    {else}
                        {l s='Finalize Order' d='Shop.Theme.Checkout'}
                    {/if}
                </button>


            </div>

            {if $selected_payment_option and $all_conditions_approved}
                <div class="ps-hidden-by-js">
                    <label for="pay-with-{$selected_payment_option}">{l s='Finalize Orders' d='Shop.Theme.Checkout'}</label>
                </div>
            {/if}
        </div>
    </div>
    <div class="content-alv">
        {if $show_final_summary}
            {include file='checkout/_partials/order-final-summary.tpl'}
        {/if}
    </div>

    {hook h='displayPaymentByBinaries'}

    <div class="modal fade" id="modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' d='Shop.Theme.Global'}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="js-modal-content"></div>
            </div>
        </div>
    </div>
{/block}

{extends file='page.tpl'}

{block name='page_content_container' prepend}
    <div id="content-hook_order_confirmation" class="card card-confirmation">
        <div class="order-confirmation">
            <div class="row">
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-2 col-xl-2 icon-confirmation">
                    {block name='order_confirmation_header'}
                        <h3 class="icon">
                            <i class="fa-duotone fa-solid fa-thumbs-up"></i>
                        </h3>
                    {/block}
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-10 col-xl-10 order-confirmation-center">
                    <div class="confirmation-description">
                        <h3 class="title">
                            {l s='Your order is confirmed' d='Shop.Theme.Checkout'}
                        </h3>
                        <p>
                            {l s='An email has been sent to your mail address' d='Shop.Theme.Checkout'} <strong>{$customer.email}</strong>
                            {if $order.details.invoice_url}
                                {l
                                s='You can also [1]download your invoice[/1]'
                                d='Shop.Theme.Checkout'
                                sprintf=[
                                '[1]' => "<a href='{$order.details.invoice_url}'>",
                                '[/1]' => "</a>"
                                ]
                                }
                            {/if}
                        </p>
                        {block name='hook_order_confirmation'}
                            {$HOOK_ORDER_CONFIRMATION nofilter}
                        {/block}
                    </div>
                </div>
            </div>
        </div>
    </div>
{/block}

{block name='page_content_container'}
<div id="content" class="page-content  card">
    <div class="card-block">
        <div class="row">
            <div id="order-details" class="col-md-12">
                {block name='order_details'}
                    <h3 class="card-title">{l s='Order details' d='Shop.Theme.Checkout'}:</h3>
                    <ul>
                        <li>{l s='Order reference:' d='Shop.Theme.Checkout'} <strong>{$order.details.id}</strong></li>
                        <li>
                            {l
                            s='Payment method: %method%'
                            d='Shop.Theme.Checkout'
                            sprintf=['%method%' => "<strong>{$order.details.payment}</strong>"]
                            }
                        </li>
                        {if !$order.details.is_virtual}
                            <li>
                                {l
                                s='Shipping method: %method%. '
                                d='Shop.Theme.Checkout'
                                sprintf=['%method%' => "<strong>{$order.carrier.show_name}</strong>"]
                                }
                                {$delivery_address_text}
                            </li>
                        {/if}

                    </ul>
                {/block}
            </div>

            {assign var="products_in_cart_pickup_gc" value=Cart::haveMultipleProductTypes(Tools::getValue('id_cart'))}
            {if $products_in_cart_pickup_gc}
                <div class="checkout-several-product-types col-md-12">
                    <p>{l s='[b]Weapon-type inventaries will be collected at the intervention of the selected Civil Guard, the rest of the inventaries will be sent to the specified billing address.[/b]' sprintf=['[b]' => '<strong>', '[/b]' => '</strong>'] d='Shop.Theme.Checkout'}</p>
                    <p>{l s='[b]The weapon-type inventaries that you must collect in the intervention of the selected Civil Guard are the following:[/b]' sprintf=['[b]' => '<strong>', '[/b]' => '</strong>'] d='Shop.Theme.Checkout'}</p>
                    <ol>
                        {foreach from=$products_in_cart_pickup_gc item=$product_cart}
                            <li>{$product_cart.name}</li>
                        {/foreach}
                    </ol>
                </div>
            {/if}
            <hr>

            <div id="order-details" class="col-md-12">
                <h3 class="card-title">{l s='Order items confirmed' d='Shop.Theme.Checkout'}</h3>
                {block name='order_confirmation_table'}
                    {include
                    file='checkout/_partials/order-confirmation-table.tpl'
                    products=$processed_products
                subtotals=$order.subtotals
                totals=$order.totals
                labels=$order.labels
                add_product_link=false
                order_obj=$order_obj
                }
                {/block}
            </div>

            {if $document}
                <div class="document-block col-md-12">
                    <div class="document-container">
                        {$document nofilter}
                    </div>
                </div>
            {/if}


        </div>
    </div>
</div>

{if isset($order_obj) && $order_obj}
    {assign var='order_remarks' value=$order_obj->getFirstMessage()}

    {if ($order_remarks && $order_remarks neq '') || ($order_obj->gift_message && $order_obj->gift_message neq '')}
        <div class="card">
            <div class="card-block">
                <div class="definition-list">
                    <div class="row">
                        <div class="col-md-12">

                            {if $order_remarks && $order_remarks neq ''}
                                <strong>{l s='Order remarks' d='Shop.Theme.Checkout'}:</strong>
                                <p>{$order_remarks}</p>
                            {/if}

                            {if $order_obj->gift_message && $order_obj->gift_message neq ''}
                                <strong>{l s='Congratulatory message' d='Shop.Theme.Checkout'}:</strong>
                                <p class="order-message">{$order_obj->gift_message}</p>
                            {/if}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
{/if}



{block name='hook_payment_return'}
{if ! empty($HOOK_PAYMENT_RETURN)}

<div id="content-hook_payment_return" class="card">
    <div class="card-block">
        <div class="definition-list">
            <div class="row">
                <div class="col-md-12">
                    {$HOOK_PAYMENT_RETURN nofilter}
                </div>

            </div>
        </div>
    </div>
    {/if}
    {/block}

    {block name='customer_registration_form'}
        {if $customer.is_guest}
            {*<div id="registration-form" class="card">
              <div class="card-block">
                <h4 class="h4">{l s='Save time on your next order, sign up now' d='Shop.Theme.Checkout'}</h4>
                {render file='customer/_partials/customer-form.tpl' ui=$register_form}
              </div>
            </div>*}
        {/if}
    {/block}

    {block name='hook_order_confirmation_1'}
        {hook h='displayOrderConfirmation1'}
    {/block}

    {block name='hook_order_confirmation_2'}
        <section id="content-hook-order-confirmation-footer">
            {hook h='displayOrderConfirmation2'}
            {hook h='displayApSC' sc_key=sc2690822786}
        </section>
    {/block}

    {foreach from=$tradedoublerpixels item=pixel}
        <script language="JavaScript">
            {literal}

            var TDOrderNumber='{/literal}{$pixel.TDOrderNumber}{literal}';
            TDOrderNumber = TDOrderNumber.replace(/#/g, '');
            var TDOrderValue='{/literal}{$pixel.TDOrderValue}{literal}';
            var TDCurrency='{/literal}{$pixel.TDCurrency}{literal}';
            var TDVoucher='';

            (function(i,s,o,g,r,a,m){i['TDConversionObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script', 'https://svht.tradedoubler.com/tr_sdk.js', 'tdconv');
            tdconv('init', '188778', {'element': 'iframe' });
            tdconv('track', 'sale',
                {'transactionId':TDOrderNumber,'ordervalue':TDOrderValue,'currency':TDCurrency,'event':'{/literal}{$pixel.eventID}{literal}','voucher':TDVoucher});

            function getCookie(e){var n=document.cookie,i=e+"=",o=n.indexOf("; "+i);if(-1==o){if(0!=(o=n.indexOf(i)))return null}else o+=2;var t=document.cookie.indexOf(";",o);return-1==t&&(t=n.length),unescape(n.substring(o+i.length,t))}
            var tduid=getCookie('tduid');
            var ab = document.createElement('img');
            ab.src = 'https://img-statics.com/report?o=188778&e={/literal}{$pixel.eventID}{literal}&ordnum='+TDOrderNumber+'&ordval='+TDOrderValue+'&curr='+TDCurrency+'&tduid='+tduid+'&voucher='+TDVoucher;

            //CONTAINER TAG
            var TDAsync = document.createElement('script');
            TDAsync.src = "//swrap.tradedoubler.com/wrap?id=583";
            TDAsync.async = true;
            document.body.appendChild(TDAsync);
        </script>
    {/literal}

    {/foreach}

    {* GTM Purchase Event Tracking *}
    {if isset($order_obj) && $order_obj && isset($gtm_purchase_data)}
        <script type="text/javascript">
            (function() {
                'use strict';

                console.log('🛒 GTM Purchase tracking starting...');

                // Purchase event data from PHP backend
                const purchaseEventData = {json_encode($gtm_purchase_data) nofilter};

                console.log('🛒 Purchase event data from backend:', purchaseEventData);

                function executePurchaseTracking() {
                    // Try GTMCheckoutHelper first
                    if (window.GTMCheckoutHelper && typeof window.GTMCheckoutHelper.trackPurchase === 'function') {
                        console.log('✅ Using GTMCheckoutHelper.trackPurchase');
                        window.GTMCheckoutHelper.trackPurchase(purchaseEventData.ecommerce).then(function(result) {
                            console.log('✅ GTMCheckoutHelper purchase tracking completed:', result);
                        }).catch(function(error) {
                            console.error('❌ GTMCheckoutHelper error:', error);
                            // Fallback to dataLayer
                            if (typeof window.dataLayer !== 'undefined') {
                                window.dataLayer.push(purchaseEventData);
                                console.log('✅ Purchase event pushed to dataLayer (fallback)');
                            }
                        });
                    } else if (typeof window.dataLayer !== 'undefined') {
                        // Direct dataLayer push
                        console.log('✅ Using direct dataLayer push');
                        window.dataLayer.push(purchaseEventData);
                        console.log('✅ Purchase event pushed to dataLayer');
                    } else {
                        console.warn('⚠️ Neither GTMCheckoutHelper nor dataLayer available');
                    }
                }

                // Execute immediately - no need to wait for anything
                executePurchaseTracking();

                // Also try after a small delay in case GTM loads later
                setTimeout(executePurchaseTracking, 1000);

            })();
        </script>
    {/if}

    {/block}

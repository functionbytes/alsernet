{widget name="alvarezbanner" type=4}
<div class="checkout-container d-none ">
    <div class="row">
        <div class="cart-grid-body col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-8 col-xl-8">
            <div class="left-sidebar-checkout">
                <div class="accordion checkout-detail-box" id="checkoutAccordion">

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingLogin">
                            <button class="accordion-button collapsed login" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLogin" data-slug="login" aria-expanded="true" aria-controls="collapseLogin">
                                {if isset($authenticated) && $authenticated}
                                    {l s='Login an accounts' mod='alsernetshopping'}
                                {else}
                                    {l s='Auth an accounts' mod='alsernetshopping'}
                                {/if}
                            </button>
                        </h2>
                        <div id="collapseLogin" class="accordion-collapse collapse" aria-labelledby="headingLogin" data-bs-parent="#checkoutAccordion">
                            <div class="accordion-body" id="checkout-login">
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingAddress">
                            <button class="accordion-button collapsed address" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAddress" data-slug="address" aria-expanded="false" aria-controls="collapseAddress">
                                {l s='Addresses' mod='alsernetshopping'}
                            </button>
                        </h2>
                        <div id="collapseAddress" class="accordion-collapse collapse" aria-labelledby="headingAddress" data-bs-parent="#checkoutAccordion">
                            <div class="accordion-body" id="checkout-address">

                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingDelivery">
                            <button class="accordion-button collapsed delivery" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDelivery" data-slug="delivery" aria-expanded="false" aria-controls="collapseDelivery">
                                {l s='Delivery' mod='alsernetshopping'}
                            </button>
                        </h2>
                        <div id="collapseDelivery" class="accordion-collapse collapse" aria-labelledby="headingDelivery" data-bs-parent="#checkoutAccordion">
                            <div class="accordion-body" id="checkout-delivery">

                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingPayment">
                            <button class="accordion-button collapsed payment" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePayment" data-slug="payment" aria-expanded="false" aria-controls="collapsePayment">
                                {l s='Payment' mod='alsernetshopping'}
                            </button>
                        </h2>
                        <div id="collapsePayment" class="accordion-collapse collapse" aria-labelledby="headingPayment" data-bs-parent="#checkoutAccordion">
                            <div class="accordion-body" id="checkout-payment">

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="cart-grid-body col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-4 col-xl-4">
            <div class="right-sidebar-checkout">
                <div class="container-shipping"></div>
                <div class="container-summary"></div>
                <div class="reassurance">
                    <div class="row">
                        <div class="col-sp-6 col-xs-6 col-sm-6 col-md-6 col-lg-6 col-xl-6">
                            <div class="icon-box icon-box-side">
                                                <span class="icon-box-icon text-dark">
                                                     <i class="fa-regular fa-lock"></i>
                                                </span>
                                <div class="icon-box-content">
                                    <h4 class="icon-box-title">{l s='Secure payments' mod='alsernetshopping'}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-sp-6 col-xs-6 col-sm-6 col-md-6 col-lg-6 col-xl-6">
                            <div class="icon-box icon-box-side">
                                                <span class="icon-box-icon text-dark">
                                                      <i class="fa-regular fa-arrows-rotate"></i>
                                                </span>
                                <div class="icon-box-content">
                                    <h4 class="icon-box-title">{l s='Guaranteed returns' mod='alsernetshopping'}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="trust">
                    <div class="image-trust">
                        <img class="img-fluid" alt="{l s='Secure payment' mod='alsernetshopping'}" title="{l s='Secure payment' mod='alsernetshopping'}" src="/themes/alvarez/assets/img/theme/checkout/{$iso_code}/payment.jpg">
                    </div>
                    <div class="description-short">
                        <p>{l s="These payment methods may vary depending on the characteristics of the shipment." mod='alsernetshopping'} <a target="_blank" href="{$link->getCmsLink(1)}">{l s="See general conditions" mod='alsernetshopping'}</a></p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="checkout-container-process">
    <div class="row justify-content-center align-items-center" style="min-height: 300px;">
        <div class="col-12 d-flex justify-content-center align-items-center">
            <div class="preloader-new text-center">
                <svg class="cart_preloader" role="img" aria-label="Shopping cart_preloader line animation"
                     viewBox="0 0 128 128" width="128px" height="128px" xmlns="http://www.w3.org/2000/svg">
                    <g fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="8">
                        <g class="cart__track" stroke="#4C4C4C">
                            <polyline points="4,4 21,4 26,22 124,22 112,64 35,64 39,80 106,80"></polyline>
                            <circle cx="43" cy="111" r="13"></circle>
                            <circle cx="102" cy="111" r="13"></circle>
                        </g>
                        <g class="cart__lines" stroke="currentColor">
                            <polyline class="cart__top" points="4,4 21,4 26,22 124,22 112,64 35,64 39,80 106,80"
                                      stroke-dasharray="338 338" stroke-dashoffset="-338"></polyline>
                            <g class="cart__wheel1" transform="rotate(-90,43,111)">
                                <circle class="cart__wheel-stroke" cx="43" cy="111" r="13"
                                        stroke-dasharray="81.68 81.68" stroke-dashoffset="81.68"></circle>
                            </g>
                            <g class="cart__wheel2" transform="rotate(90,102,111)">
                                <circle class="cart__wheel-stroke" cx="102" cy="111" r="13"
                                        stroke-dasharray="81.68 81.68" stroke-dashoffset="81.68"></circle>
                            </g>
                        </g>
                    </g>
                </svg>
            </div>
        </div>
    </div>
</div>

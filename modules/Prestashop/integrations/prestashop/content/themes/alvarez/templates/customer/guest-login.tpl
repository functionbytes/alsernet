{extends file='page.tpl'}


{block name='page_content'}
    <div class="guest-tracking">
        <div class="card">
            <div class="card-body">

                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
                    <div class="d-flex justify-content-center align-items-center min-vh-100">
                        <form class="form w-100 p-3" id="guestOrderTrackingForm" action="{$urls.pages.guest_tracking}"
                              method="get">
                            <div class="mb-3">
                                <label for="order_reference" class="form-label">
                                    {l s='Order Reference:' d='Shop.Customer.Orders'}
                                </label>
                                <input type="text" class="form-control" id="order_reference" name="order_reference"
                                       size="8"
                                       value="{if isset($smarty.request.order_reference)}{$smarty.request.order_reference}{/if}"
                                       placeholder="{l s='Placeholder reference' d='Shop.Customer.Orders'}">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    {l s='Email:' d='Shop.Customer.Orders'}
                                </label>
                                <input type="text" class="form-control" id="email" name="email"
                                       value="{if isset($smarty.request.email)}{$smarty.request.email}{/if}"
                                       placeholder="{l s='Placeholder email:' d='Shop.Customer.Orders'}">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                {l s='Send' d='Shop.Customer.Orders'}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
                    <h3 class="card-title">{l s='Guest Order Tracking' d='Shop.Customer.Orders'}</h3>

                    <p class="card-text">
                        {l s='If you are a guest and want to track your order, please provide your order reference and the email used during purchase.' d='Shop.Customer.Orders'}
                    </p>

                    <ul class="registration-benefits">
                        <li>{l s='Check your order status' d='Shop.Customer.Orders'}</li>
                        <li>{l s='View delivery updates' d='Shop.Customer.Orders'}</li>
                        <li>{l s='Download your invoice' d='Shop.Customer.Orders'}</li>
                    </ul>

                    <div class="mt-4">
                        <h5 class="card-title">{l s='Need help?' d='Shop.Customer.Orders'}</h5>
                        <p class="card-text">{l s='If you have any doubts, you can contact us via:' d='Shop.Customer.Orders'}</p>
                        {if $iso_code == "es"}
                            <div class="d-flex align-items-center mb-2">

                                <span>
                                    {l s='Phone' d='Shop.Theme.Global'}:
                                    <a href="tel:+34981179100">981 17 91 00</a>
                                </span>

                            </div>
                        {/if}

                        <div class="d-flex align-items-center">
                        <span>
                            {l s='Email' d='Shop.Theme.Global'}:
                            <a href="mailto:web@a-alvarez.com">web@a-alvarez.com</a>
                          </span>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
{/block}

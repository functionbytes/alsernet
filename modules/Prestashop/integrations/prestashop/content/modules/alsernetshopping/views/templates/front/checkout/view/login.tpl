<div class="checkout-step step-login" >
    <div class="checkout-box">
        <div class="checkout-detail">
            {if isset($authenticated) && $authenticated}
                <div class="container-auth">
                    <p class="identity">
                        {l s='Connected as' mod='alsernetshopping'}
                        <a href="{$urls.pages.identity|escape:'html':'UTF-8'}">
                            {$customer.firstname|escape:'html':'UTF-8'} {$customer.lastname|escape:'html':'UTF-8'}
                        </a>.
                    </p>

                    <p>
                        {l s='Not you?' mod='alsernetshopping'}
                        <a href="{$urls.actions.logout|escape:'html':'UTF-8'}">
                            {l s='Log out' mod='alsernetshopping'}
                        </a>
                    </p>

                    <div class="d-flex flex-column align-items-center actions-step">
                        <a class="btn btn-primary mt-2 next btn-next">
                            {l s='Continue' mod='alsernetshopping'}
                        </a>
                    </div>


                </div>
            {else}
                <div class="row g-3">
                    <div class="col-md-12 col-sm-12">
                        <div class="checkout-auth">
                            <div class="checkout-register-form {if !$show_login_form}d-visible{else} d-none{/if}">
                                {include file='module:alsernetshopping/views/templates/front/checkout/partials/login/customer.tpl'}
                            </div>
                            <div class="checkout-login-form {if $show_login_form}d-visible{else} d-none{/if}">
                                {include file='module:alsernetshopping/views/templates/front/checkout/partials/login/login.tpl'}
                            </div>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>

<script>
    // Expose checkout configuration to JavaScript
    window.checkoutConfig = window.checkoutConfig || {literal}{}{/literal};
    {if isset($configuration)}
    window.checkoutConfig.guest_allowed = {if $configuration.guest_allowed}true{else}false{/if};
    window.configuration = window.configuration || {literal}{}{/literal};
    window.configuration.guest_allowed = {if $configuration.guest_allowed}true{else}false{/if};
    {/if}
    console.log('🔧 Checkout configuration loaded:', window.checkoutConfig);
</script>
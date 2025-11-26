<form method="post" id="login-checkout" autocomplete="off" novalidate action="javascript:void(0);">

    <div class="row">

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label  class="form-label">{l s='Email' mod='alsernetshopping'} </label>
                <input type="email" class="form-control" id="field-email" name="email" placeholder="" autocomplete="new-email" required>
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label  class="form-label">{l s='Password' mod='alsernetshopping'} </label>
                <input type="password" class="form-control" id="field-password" name="password" placeholder="" autocomplete="new-password" required>
                <div class="forgot-password">
                    <a href="{$urls.actions.password}" rel="nofollow">
                        {l s='Forgot your password?' mod='alsernetshopping'}
                    </a>
                </div>
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">

        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="response-output" style="display: none;"></div>
        </div>


        <div class="d-flex flex-column align-items-center col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 actions-step">
            <button class="btn nexts btn-next" type="button" id="login-submit-btn">
                {l s='Continue' mod='alsernetshopping'}
            </button>
        </div>



        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="auth-action">
                <div  class="auth-header">
                    {l s='New client' mod='alsernetshopping'}
                </div>
                <p>
                    {l s='¿ You are a new customer ?' mod='alsernetshopping'}
                </p>
                <a class="redirect-to-register" data-label="{l s='Create an account' mod='alsernetshopping'}">
                    {l s='Click here' mod='alsernetshopping'}
                </a>
            </div>
        </div>

    </div>

</form>








<form id="login-form" action="{block name='login_form_actionurl'}{$action}{/block}" method="post">

  <input type="hidden" name="submitLogin" value="true">
  <input type="hidden" name="_loginform_language" id="_customerform_language" value="{$language.iso_code}">

  <div class="row">

    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div class="mb-3">
        <label  class="form-label">{l s='Email' d='Shop.Theme.Customeraccount'} </label>
        <input type="email" class="form-control" id="field-email" name="email" placeholder="" autocomplete="new-email" required>
      </div>
    </div>

    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div class="mb-3">
        <label  class="form-label">{l s='Password' d='Shop.Theme.Customeraccount'} </label>
        <input type="password" class="form-control" id="field-password" name="password" placeholder="" autocomplete="new-password" required>
      </div>
    </div>

    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div class="forgot-password">
        <a href="{$urls.pages.password}" rel="nofollow">
          {l s='Forgot your password?' d='Shop.Theme.Customeraccount'}
        </a>
      </div>
    </div>

    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">

      {block name='login_form_errors'}
        {include file='_partials/form-errors.tpl' errors=$errors['']}
      {/block}

      <input type="hidden" name="submitLogin" value="1">

      <button class="continue btn btn-primary float-xs-right" name="continue" id="continuenew" data-link-action="sign-in" type="submit" value="1">
        {l s='Continue' d='Shop.Theme.Actions'}
      </button>


    </div>

    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div class="register-action">
        <p>
          {l s='¿ You are a new customer ?' d='Shop.Theme.Customeraccount'}
          <a id="redirect-to-register">
            {l s='Click here' d='Shop.Theme.Customeraccount'}
          </a>
        </p>
      </div>
    </div>


    {hook h='DisplayNewCustomer' mod='alsernetgooglegtm'}

  </div>

</form>





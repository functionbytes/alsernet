
<div class="card">
    <form action="{block name='customer_form_actionurl'}{$action}{/block}" id="register-form" class="form js-customer-form" method="post">

        <input type="hidden" name="submitCreate" value="true">
        <input type="hidden" name="_customerform_language" id="_customerform_language" value="{$language.iso_code}">

        {block "form_fields"}
            <div class="row">
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="mb-3">
                        <label for="firstname" class="form-label">{l s='Firstname' d='Shop.Theme.Customeraccount'} </label>
                        <input type="text" class="form-control" id="firstname" name="firstname" placeholder="" required>
                    </div>
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="mb-3">
                        <label  class="form-label">{l s='Lastname' d='Shop.Theme.Customeraccount'} </label>
                        <input type="text" class="form-control" id="lastname" name="lastname" placeholder="" required>
                    </div>
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">{l s='Email' d='Shop.Theme.Customeraccount'} </label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="" autocomplete="new-email" required>
                    </div>
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="mb-3">
                        <label for="date" class="form-label">{l s='Date' d='Shop.Theme.Customeraccount'} ({l s='Optional' d='Shop.Theme.Customeraccount'})</label>
                        <input type="date" class="form-control" id="date" name="date" placeholder="" >
                    </div>
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="mb-3">
                        <label for="password" class="form-label">{l s='Password' d='Shop.Theme.Customeraccount'} ({l s='Optional' d='Shop.Theme.Customeraccount'})</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="" autocomplete="new-password" >
                    </div>
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="mb-3">
                        <label  class="form-label">{l s='Select sports of your interest' d='Shop.Theme.Customeraccount'}</label>
                        <div class="sports-container">
                            <label>
                                <div class="sports-wrap">
                                    <div class="sport-select" id="field-sports">
                                        {foreach from=$sports item=sport }
                                            {assign var="id" value="sports_"|cat:$sport.id|replace:' ':'_'}
                                            <div class="sport-item">
                                                <input type="checkbox" name="sports[]" value="{$sport.id}" id="registersports-{$id}" />
                                                <label for="registersports-{$id}">
                                                    <span class="sport-label">{$sport.name|upper}</span>
                                                </label>
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                                <label for="sports[]" class="error "></label>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="mb-3">
                        <div class="form-check">
                            <div class="check">
                                <input class="form-check-input fixed-size-input" type="checkbox" id="field-condition" name="condition" required >
                                <label class="form-check-label" for="condition">
                                    {l s='I have read and expressly accept the conditions' d='Shop.Theme.Customeraccount'} <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' d='Shop.Theme.Customeraccount'}</a>
                                </label>
                            </div>
                        </div>
                        <div class="form-check">
                            <div class="check">
                                <input class="form-check-input fixed-size-input" type="checkbox" id="field-services" name="services" >
                                <label class="form-check-label" for="services">
                                    {l s='I agree to receive information about other inventaries and services of interest to me' d='Shop.Theme.Customeraccount'}  <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' d='Shop.Theme.Customeraccount'} </a>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">

                    <label class="error d-none" for="g-recaptcha-response-register"></label>

                    {block name='customer_form_errors'}
                        {include file='_partials/form-errors.tpl' errors=$errors['']}
                    {/block}

                    <button class="continue btn btn-primary float-xs-right " data-link-action="register-new-customer" type="submit"  >
                        {l s='Continue' d='Shop.Theme.Actions'}
                    </button>

                </div>

                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="register-action">
                        <p>
                            {l s='¿ You are already a customer ?' d='Shop.Theme.Customeraccount'}
                            <a id="redirect-to-login">
                                {l s='Click here' d='Shop.Theme.Customeraccount'}
                            </a>
                        </p>
                    </div>
                </div>

                {hook h='DisplayNewCustomer' mod='alsernetgooglegtm'}

            </div>

        {/block}


    </form>
</div>


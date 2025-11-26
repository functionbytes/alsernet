<form id="register-checkout" class="form" method="post" action="javascript:void(0);">
    <div class="row">
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="firstname" class="form-label">{l s='Firstname' mod='alsernetshopping'} </label>
                <input type="text" class="form-control" id="firstname" name="firstname" placeholder="" required>
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label  class="form-label">{l s='Lastname' mod='alsernetshopping'} </label>
                <input type="text" class="form-control" id="lastname" name="lastname" placeholder="" required>
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label class="form-label">{l s='Email' mod='alsernetshopping'} </label>
                <input type="email" class="form-control" id="email" name="email" placeholder="" autocomplete="new-email" required>
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="date" class="form-label">{l s='Date' mod='alsernetshopping'} ({l s='Optional' mod='alsernetshopping'})</label>
                <input type="date" class="form-control" id="date" name="birthday" placeholder="" >
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="password" class="form-label">{l s='Password' mod='alsernetshopping'} ({l s='Optional' mod='alsernetshopping'})</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="" autocomplete="new-password" >
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label  class="form-label">{l s='Select sports of your interest' mod='alsernetshopping'}</label>
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
                        <input class="form-check-input fixed-size-input" type="checkbox" id="condition" name="condition" required >
                        <label class="form-check-label" for="condition">
                            {l s='I have read and expressly accept the conditions' mod='alsernetshopping'} <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetshopping'}</a>
                        </label>
                    </div>
                </div>
                <div class="form-check">
                    <div class="check">
                        <input class="form-check-input fixed-size-input" type="checkbox" id="services" name="services" >
                        <label class="form-check-label" for="services">
                            {l s='I agree to receive information about other products and services of interest to me' mod='alsernetshopping'}
                            <a href=""  target="_blank">{l s='Data Protection' mod='alsernetshopping'} </a>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="response-output" style="display: none;"></div>
        </div>

        <div class="d-flex flex-column align-items-center col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 actions-step">
            <button class="btn nexts btn-next"  type="submit" disabled>
                {l s='Continue' mod='alsernetshopping'}
            </button>
        </div>



        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="auth-action">
                <div  class="auth-header">
                    {l s='Already a client' mod='alsernetshopping'}
                </div>
                <p>
                    {l s='¿ You are already a customer ?' mod='alsernetshopping'}
                </p>
                <a class="btn redirect-to-login" data-label="{l s='Login an account' mod='alsernetshopping'}">
                    {l s='Click here' mod='alsernetshopping'}
                </a>
            </div>
        </div>

    </div>

</form>


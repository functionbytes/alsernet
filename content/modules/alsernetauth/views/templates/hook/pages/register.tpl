<div class="row justify-content-center">
    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-8 col-xl-8">

        <div class="card">
            <div class="card-body">

                <form class="form alsernet-register"  method="post" id="alsernet-register" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">

                    <input type="hidden" name="_alsernetauth_language" value="{$language.iso_code}">
                    <input type="hidden" name="_alsernetauth_action" value="register">
                    <input type="hidden" name="_alsernetauth_link" value="modules/alsernetauth/controllers/routes.php">

                    <h3 class="card-title">{l s='Title register' mod='alsernetauth'}</h3>
                    <p class="card-text">{l s='Description register' mod='alsernetauth'}</p>

                    <div class="mb-3">
                        <label for="firstname" class="form-label">{l s='Firstname' mod='alsernetauth'} </label>
                        <input type="text" class="form-control" id="firstname" name="firstname" placeholder="">
                    </div>

                    <div class="mb-3">
                        <label for="lastname" class="form-label">{l s='Lastname' mod='alsernetauth'} </label>
                        <input type="text" class="form-control" id="lastname" name="lastname" placeholder="">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">{l s='Email' mod='alsernetauth'} </label>
                        <input type="text" class="form-control" id="email" name="email" placeholder="" autocomplete="new-email">
                        <label for="emails" class="error d-none"></label>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">{l s='Date' mod='alsernetauth'} ({l s='Optional' mod='alsernetauth'})</label>
                        <input type="date" class="form-control" id="date" name="date" placeholder="">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">{l s='Password' mod='alsernetauth'} </label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="" autocomplete="new-password">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">{l s='Select sports of your interest' mod='alsernetauth'}</label>
                        <div class="sports-container">
                            <label>
                                <div class="sports-wrap">
                                    <div class="sport-select" id="sports">
                                        {foreach from=$sports item=sport}
                                            {assign var="id" value="sports_"|cat:$sport.id|replace:' ':'_'}
                                            <div class="sport-item">
                                                <input type="checkbox" name="sports[]" value="{$sport.id}" id="registersports-{$id}" />
                                                <label for="registersports-{$id}">
                                                    <span class="sport-label">{$sport.name|upper}</span> <!-- Show the sport name -->
                                                </label>
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                            </label>
                        </div>
                        <label for="sports[]" class="error"></label>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <div class="check">
                                <input class="form-check-input fixed-size-input" type="checkbox" id="condition" name="condition" required >
                                <label class="form-check-label" for="condition">
                                    {l s='I have read and expressly accept the conditions' mod='alsernetauth'} <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetauth'}</a>
                                </label>
                            </div>
                        </div>

                        <div class="form-check">
                            <div class="check">
                                <input class="form-check-input fixed-size-input" type="checkbox" id="services" name="services" >
                                <label class="form-check-label" for="services">
                                    {l s='I agree to receive information about other products and services of interest to me' mod='alsernetauth'}  <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetauth'} </a>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="g-recaptcha"  id="g-recaptcha-response-register"  data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>
                    <div class="response-output"></div>

                    <button type="submit" class="btn btn-primary w-100" disabled class="form-control-submit">
                        {l s='Register' mod='alsernetauth'}
                    </button>

                </form>
            </div>
        </div>

    </div>
</div>
      
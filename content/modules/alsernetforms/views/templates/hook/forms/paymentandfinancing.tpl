
<form class="form"  method="post" id="alsernet-paymentandfinancing" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
    <input type="hidden" name="_alsernetforms_language" value="{$language.iso_code}">
    <input type="hidden" name="_alsernetforms_action" value="paymentandfinancing">
    <input type="hidden" name="_alsernetforms_link" value="/modules/alsernetforms/controllers/routes.php">
    <h3 class="card-title">{l s='Title alert paymentandfinancing' mod='alsernetforms'}</h3>
    <p class="card-text">{l s='Description  alert paymentandfinancing' mod='alsernetforms'}</p>
    <div class="mb-3">
        <label for="firstname" class="form-label">{l s='Firstname paymentandfinancing' mod='alsernetforms'}</label>
        <input type="text" class="form-control" id="firstname" name="firstname" placeholder="">
    </div>
    <div class="mb-3">
        <label for="lastname" class="form-label">{l s='Lastname paymentandfinancing' mod='alsernetforms'}</label>
        <input type="text" class="form-control" id="lastname" name="lastname" placeholder="">
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">{l s='Phone paymentandfinancing' mod='alsernetforms'}</label>
        <input type="number" class="form-control" id="phone" name="phone" placeholder="">
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">{l s='Email paymentandfinancing' mod='alsernetforms'}</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="">
    </div>
    <div class="mb-3">
        <label for="menssage" class="form-label">{l s='Menssage paymentandfinancing' mod='alsernetforms'}</label>
        <textarea class="form-control" id="menssage" name="menssage" placeholder=""></textarea>
    </div>
    <div class="form-check">
        <div class="check">
            <input class="form-check-input fixed-size-input" type="checkbox" id="condition" name="condition" required >
            <label class="form-check-label" for="condition">
                {l s='I have read and expressly accept the conditions' mod='alsernetforms'} <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetforms'}</a>
            </label>
        </div>
    </div>
    <div class="form-check">
        <div class="check">
            <input class="form-check-input fixed-size-input" type="checkbox" id="services" name="services" >
            <label class="form-check-label" for="services">
                {l s='I agree to receive information about other products and services of interest to me' mod='alsernetforms'}  <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetforms'} </a>
            </label>
        </div>
    </div>

    <div class="g-recaptcha"  id="g-recaptcha-response-paymentandfinancing"  data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>
    <div class="response-output"></div>
    <button type="submit" class="btn btn-primary w-100" disabled class="form-control-submit">
        {l s='Submit paymentandfinancing' mod='alsernetforms'}
    </button>
</form>
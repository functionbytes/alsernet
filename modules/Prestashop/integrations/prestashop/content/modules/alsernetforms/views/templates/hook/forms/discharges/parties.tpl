



<form class="form top-unsuscribe"  method="post" id="alsernet-newsletterdischargersparties" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
    
    <input type="hidden" name="_alsernetforms_language" value="{$language.iso_code}">
    <input type="hidden" name="_alsernetforms_action" value="newsletterdischargersparties">
    <input type="hidden" name="_alsernetforms_link" value="/modules/alsernetforms/controllers/routes.php">

    <h3 class="card-title">{l s='Title alert newsletterdischargersparties' mod='alsernetforms'}</h3>
    <p class="card-text">{l s='Description  alert newsletterdischargersparties' mod='alsernetforms'}</p>

    <div class="row">

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="email" class="form-label">{l s='Email newsletterdischargersparties' mod='alsernetforms'}</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="">
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">

            <div class="g-recaptcha mt-2" data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"
                 data-callback="onRecaptchaSuccess"
                 data-expired-callback="onRecaptchaExpired"
                 data-form="alsernet-newsletterdischargersparties"></div>

            <div class="response-output"></div>

            <button type="submit" class="btn btn-primary w-100" disabled class="form-control-submit">
                {l s='Submit newslettersubscribe' mod='alsernetforms'}
            </button>

        </div>

    </div>
</form>
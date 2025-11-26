<form class="form top-unsuscribe"  method="post" id="alsernet-newsletterdischargerssports" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
    <input type="hidden" name="_alsernetforms_language" value="{$language.iso_code}">
    <input type="hidden" name="_alsernetforms_action" value="newsletterdischargerssports">
    <input type="hidden" name="_alsernetforms_link" value="/modules/alsernetforms/controllers/routes.php">

    <h3 class="card-title">{l s='Title alert newsletterdischargerssports' mod='alsernetforms'}</h3>
    <p class="card-text">{l s='Description  alert newsletterdischargerssports' mod='alsernetforms'}</p>
    
    <div class="row">

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="email" class="form-label">{l s='Email newsletterdischargerssports' mod='alsernetforms'}</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="">
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="email" class="form-label">{l s='Select sports of your interest newslettersubscribe' mod='alsernetforms'}</label>
                <div class="sports-container">
                    <label>
                        <div class="sports-wrap">
                            <div class="sport-select" id="sports">
                                {foreach from=$sports item=sport}
                                    {assign var="id" value="sports_"|cat:$sport.id|replace:' ':'_'}
                                    <div class="sport-item">
                                        <input type="checkbox" name="sports[]" value="{$sport.id}" id="dischargerssports-{$id}" />
                                        <label for="dischargerssports-{$id}">
                                            <span class="sport-label">{$sport.name}</span> <!-- Show the sport name -->
                                        </label>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </label>
                </div>
                <label for="sports[]" class="error"></label>
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="g-recaptcha mt-2"  data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"
                 data-callback="onRecaptchaSuccess"
                 data-expired-callback="onRecaptchaExpired"
                 data-form="alsernet-newsletterdischargerssports"></div>
            <div class="response-output"></div>

            <button type="submit" class="btn btn-primary w-100" disabled class="form-control-submit">
                {l s='Submit newslettersubscribe' mod='alsernetforms'}
            </button>

        </div>

    </div>

</form>
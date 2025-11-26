
<form class="form"  method="post" id="alsernet-internalinformationsystem" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">

    <input type="hidden" name="token" value="{$static_token}">
    <input type="hidden" name="_alsernetforms_language" value="{$language.iso_code}">

    <div class="row">

    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="mb-3">
            <label for="firstname" class="form-label">{l s='Firstname' mod='alsernetforms'}</label>
            <input type="text" class="form-control" id="firstname" name="firstname" placeholder="">
        </div>
    </div>
    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="mb-3">
            <label for="lastname" class="form-label">{l s='Lastname' mod='alsernetforms'}</label>
            <input type="text" class="form-control" id="lastname" name="lastname" placeholder="">
        </div>
    </div>
    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="mb-3">
            <label for="phone" class="form-label">{l s='Phone' mod='alsernetforms'}</label>
            <input type="number" class="form-control" id="phone" name="phone" placeholder="">
        </div>
    </div>
    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
        <div class="mb-3">
            <label for="email" class="form-label">{l s='Email' mod='alsernetforms'}</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="">
        </div>
    </div>

    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div class="mb-3">
            <label for="message" class="form-label">{l s='Menssage' mod='alsernetforms'}</label>
            <textarea class="form-control" id="message" name="message" placeholder=""></textarea>
        </div>
    </div>


        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="file" class="form-label">
                    {l s='Files' mod='alsernetforms'}
                </label>
                <div class="custom-file">
                    <input
                            type="file"
                            class="form-control-file"
                            id="file"
                            name="messagefile[]"
                            multiple
                            accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.gif,.jpg,.png"
                    >
                    <label for="file" class="custom-file-label">
                        {l s='Choose files' mod='alsernetforms'}
                    </label>
                </div>
                <p class="form-text">
                    {l s='Files with the following extensions are accepted: .pdf, .doc, .docx, .txt, .xls, .xlsx, .gif, .jpg, and .png. You can select multiple files at once by pressing the "Ctrl" button and clicking on them.' mod='alsernetforms'}
                </p>
            </div>
        </div>



        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
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

        <div class="g-recaptcha"  id="g-recaptcha-response-exchangesandreturns"  data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>
        <div class="response-output"></div>

        <button type="submit" class="btn btn-primary w-100" disabled class="form-control-submit">
            {l s='Submit exchangesandreturns' mod='alsernetforms'}
        </button>

    </div>
    </div>

</form>



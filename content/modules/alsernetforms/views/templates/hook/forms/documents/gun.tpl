
<div class="documents">
    <div class="container">

        <div class="row">

            {if $type && $upload}
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 " id="documentsContainer">
                    <div class="row container-items mt-4">
                       
                       <form class="form w-100 document-page" method="post" id="alsernet-documents" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
                            <input type="hidden" name="_alsernetforms_action" value="documents">
                            <input type="hidden" name="_alsernetforms_link" value="/modules/alsernetforms/controllers/routes.php">

                            <input type="hidden" name="uid" id="uid" value="{$uid}">
                            <input type="hidden" name="id" id="type" value="{$type}">

                            <div class="card-body border-top">
                                <div>
                                    <h3 class="mb-0 uppercase card-title">
                                        {l s='Upload delivery note for sale #' mod='alsernetforms'}{$label}
                                    </h3>
                                    <p class="document-container mb-3 mt-3">
                                        {l s='Please click on “UPLOAD FILES” to upload the required documentation for processing your order.' mod='alsernetforms'}{$label}
                                    </p>
                                    <div class="document-container mb-3 mt-3">
                                        {$trans nofilter}
                                        {$trans_list nofilter}
                                    </div>
                                </div>

                                <div class="card-body border-top dropzone-body">
                                    <div class="dropzone dz-clickable" id="documents">
                                        <div class="fallback">
                                            <input type="file" name="file[]" multiple hidden>
                                        </div>
                                    </div>
                                    <input type="hidden" id="documents_value" name="documents_value">
                                    <input type="hidden" id="documents_status" name="documents_status" value="false">
                                    <label id="documents-error" class="error d-none" for="documents"></label>
                                </div>

                                <div class="col-12">
                                    <div class="errors mb-3 d-none"></div>
                                </div>

                                <div class="form-check">
                                    <div class="check">
                                        <input class="form-check-input fixed-size-input" type="checkbox" id="condition" name="condition" required>
                                        <label class="form-check-label" for="condition">
                                            {l s='I have read and expressly accept the conditions' mod='alsernetforms'}
                                            <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetauth'}</a>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-check">
                                    <div class="check">
                                        <input class="form-check-input fixed-size-input" type="checkbox" id="services" name="services">
                                        <label class="form-check-label" for="services">
                                            {l s='I agree to receive information about other products and services of interest to me' mod='alsernetforms'}
                                            <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetauth'}</a>
                                        </label>
                                    </div>
                                </div>

                                <div class="g-recaptcha" id="g-recaptcha-response-compromise" data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>
                                <div class="response-output"></div>

                                <button type="submit" class="btn btn-primary w-100" disabled>
                                    {l s='Upload Document' mod='alsernetforms'}
                                </button>
                            </div>
                        </form>



                    </div>
                </div>
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 d-none" id="documentsConfirmation">
                    <div class="success-documents-container">
                        <i class="fa-solid fa-circle-check"></i>
                        <h1>{l s='Documents successfully submitted' mod='alsernetforms'}</h1>
                        <p>{l s='We will now review your documents and begin processing your order immediately. Thank you for your trust!' mod='alsernetforms'}</p>
                        <a href="/">{l s='Go back to homepage' mod='alsernetforms'}</a>
                    </div>
                </div>

            {else}
                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                    <div class="success-documents-container">
                        <i class="fa-solid fa-file-check"></i>
                        <h1>{l s='Documents already uploaded' mod='alsernetforms'}</h1>
                        <p>{l s='We have already received the required documents for this request. No further action is needed.' mod='alsernetforms'}</p>
                        <a href="/">{l s='Go back' mod='alsernetforms'}</a>
                    </div>
                </div>
            {/if}


        </div>

    </div>
</div>
</div>


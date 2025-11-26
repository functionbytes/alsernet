   <div class="row form-suscribe" id="formSubscribe">
        <form class="form"  method="post" id="alsernet-newslettersubscribe" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
                                <input type="hidden" name="_alsernetforms_language" value="{$language.iso_code}">
                                <input type="hidden" name="_alsernetforms_action" value="newslettersubscribe">
                                <input type="hidden" name="_alsernetforms_link" value="/modules/alsernetforms/controllers/routes.php">

                            

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
                                                                    <input type="checkbox" name="sports[]" value="{$sport.id}" id="subscribesports-{$id}" />
                                                                    <label for="subscribesports-{$id}">
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

                                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                        <div class="mb-3">
                                            <label for="firstname" class="form-label">{l s='Firstname newslettersubscribe' mod='alsernetforms'}</label>
                                            <input type="text" class="form-control" id="firstname" name="firstname" placeholder="">
                                        </div>
                                    </div>

                                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                        <div class="mb-3">
                                            <label for="lastname" class="form-label">{l s='Lastname newslettersubscribe' mod='alsernetforms'}</label>
                                            <input type="text" class="form-control" id="lastname" name="lastname" placeholder="">
                                        </div>
                                    </div>

                                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">{l s='Email newslettersubscribe' mod='alsernetforms'}</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="">
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
                                        <div class="form-check  ">
                                            <div class="check">
                                                <input class="form-check-input fixed-size-input" type="checkbox" id="services" name="services" >
                                                <label class="form-check-label" for="services">
                                                    {l s='I agree to receive information about other products and services of interest to me' mod='alsernetforms'}  <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetforms'} </a>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="g-recaptcha mt-2" data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"
                                            data-callback="onRecaptchaSuccess"
                                            data-expired-callback="onRecaptchaExpired"></div>

                                        <div class="response-output"></div>
                                        <button type="submit" class="btn btn-primary w-100" disabled class="form-control-submit">
                                            {l s='Submit newslettersubscribe' mod='alsernetforms'}
                                        </button>
                                    </div>
                                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                        <p class="card-text mt-2">
                                            {l s='We recommend that you add the address "web@a-alvarez.com" to your address book, to ensure that it is not filtered as spam. exchangesandreturns' mod='alsernetforms'}
                                        </p>
                                    </div>
                                </div>
                                
                            </form>
                            </div>
                            <div class="row form-suscribe-confirmation d-none" >
                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="success-verification-container">
                                        <i class="fa-duotone fa-solid fa-mailbox"></i>
                                        <h1>{l s='Thank you for joining Alvarez! Confirmation' mod='alsernetforms'}</h1>
                                        <p>{l s='You have successfully subscribed. Please check your inbox to confirm your subscription and accept the terms.' mod='alsernetforms'}</p>
                                        <a data-dismiss="modal">{l s='Close' mod='alsernetforms'}</a>
                                    </div>
                                </div>
                            </div>
                            <div class="row form-suscribe-lopd d-none" id="subscribeConfirmation">
                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="success-verification-container">
                                        <i class="fa-duotone fa-solid fa-mailbox"></i>
                                        <h1>{l s='Thank you for joining Alvarez! LOPD' mod='alsernetforms'}</h1>
                                        <p>{l s='You are already registered, but you need to validate your LOPD acceptance. A confirmation email has been sent to your inbox.' mod='alsernetforms'}</p>
                                        <a data-dismiss="modal">{l s='Close' mod='alsernetforms'}</a>
                                    </div>
                                </div>
                            </div>
<form class="form"  method="post" id="alsernet-campaigns" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
                            <input type="hidden" name="_alsernetforms_language" value="{$language.iso_code}">
                            <input type="hidden" name="_alsernetforms_action" value="giftvoucher">
                            <input type="hidden" name="_alsernetforms_form" value="campaign">
                            <input type="hidden" name="_alsernetforms_campaigns" value="checkout">
                            <input type="hidden" name="_alsernetforms_link" value="/modules/alsernetforms/controllers/routes.php">
                             <input type="hidden" class="form-control" id="email" name="email" value="{$emailcampaigns}" >

                            <p class="description-text"><strong>{l s='Select sports of your interest' mod='alsernetforms'}</strong></p>

                            <div class="row form-suscribe">

                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="mb-3">
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

                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">{l s='Email campaigns' mod='alsernetforms'}</label>
                                        <input type="email" class="form-control" value="{$emailcampaigns}" disabled>
                                    </div>
                                </div>


                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="mb-3">
                                        <label for="firstname" class="form-label">{l s='Firstname' mod='alsernetforms'}</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" placeholder="">
                                    </div>
                                </div>

                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="mb-3">
                                        <label for="lastname" class="form-label">{l s='Lastname' mod='alsernetforms'}</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" placeholder="">
                                    </div>
                                </div>

                               
                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 form-check-container">
                                    <div class="form-check">
                                        <div class="check">
                                            <input class="form-check-input fixed-size-input" type="checkbox" id="commercial" name="commercial" required >
                                            <label class="form-check-label" for="commercial">
                                                {l s='I have read and expressly accept the conditions' mod='alsernetforms'} <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetforms'}</a>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-check">
                                        <div class="check">
                                            <input class="form-check-input fixed-size-input" type="checkbox" id="parties" name="parties" >
                                            <label class="form-check-label" for="parties">
                                                {l s='I agree to receive information about other products and services of interest to me' mod='alsernetforms'}  <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' mod='alsernetforms'} </a>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="g-recaptcha mt-2" data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>

                                    <div class="response-output"></div>
                                </div>
                                <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="btn-campaigns-container">
                                        <button type="submit" class="btn btn-primary btn-campaigns" disabled class="form-control-submit text-center">
                                            {l s='Submit' mod='alsernetforms'}
                                        </button>
                                    </div>
                                </div>

                            </div>


                        </form>

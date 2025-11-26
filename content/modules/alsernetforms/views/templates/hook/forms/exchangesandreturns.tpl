



<form class="form"  method="post" id="alsernet-exchangesandreturns" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
    <input type="hidden" name="_alsernetforms_language" value="{$language.iso_code}">
    <input type="hidden" name="_alsernetforms_action" value="exchangesandreturns">
    <input type="hidden" name="_alsernetforms_link" value="/modules/alsernetforms/controllers/routes.php">
    <h3 class="card-title">{l s='Title alert exchangesandreturns' mod='alsernetforms'}</h3>
    <p class="card-text">{l s='Description  alert exchangesandreturns' mod='alsernetforms'}</p>
    <div class="row">
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
            <div class="mb-3">
                <label for="firstname" class="form-label">{l s='Firstname exchangesandreturns' mod='alsernetforms'}</label>
                <input type="text" class="form-control" id="firstname" name="firstname" placeholder="">
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
            <div class="mb-3">
                <label for="lastname" class="form-label">{l s='Lastname exchangesandreturns' mod='alsernetforms'}</label>
                <input type="text" class="form-control" id="lastname" name="lastname" placeholder="">
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
            <div class="mb-3">
                <label for="phone" class="form-label">{l s='Number exchangesandreturns' mod='alsernetforms'}</label>
                <input type="number" class="form-control" id="number" name="number" placeholder="">
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
            <div class="mb-3">
                <label for="phone" class="form-label">{l s='Phone exchangesandreturns' mod='alsernetforms'}</label>
                <input type="number" class="form-control" id="phone" name="phone" placeholder="">
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="email" class="form-label">{l s='Email exchangesandreturns' mod='alsernetforms'}</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="">
            </div>
        </div>
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="email" class="form-label">{l s='Reason exchangesandreturns' mod='alsernetforms'}</label>
                <select class="form-control select-2" id="reason" name="reason">
                    <option value=""> ---</option>
                    <option value="exchange">{l s='I need to exchange it for another product exchangesandreturns' mod='alsernetforms'}</option>
                    <option value="notreceived">{l s='I have not received the requested product exchangesandreturns' mod='alsernetforms'}</option>
                    <option value="conditions">{l s='Product in poor condition exchangesandreturns' mod='alsernetforms'} </option>
                    <option value="interests">{l s='The product no longer interests me exchangesandreturns' mod='alsernetforms'} </option>
                </select>
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="mb-3">
                <label for="message" class="form-label">{l s='Menssage exchangesandreturns' mod='alsernetforms'}</label>
                <textarea class="form-control" id="message" name="message" placeholder=""></textarea>
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="pick up address">
                <h3 class="card-title">{l s='pick up address exchangesandreturns' mod='alsernetforms'}</h3>
                <div class="row">
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Address exchangesandreturns' mod='alsernetforms'}</label>
                            <input type="text" class="form-control" id="address" name="address" placeholder="">
                        </div>
                    </div>
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Code postal exchangesandreturns' mod='alsernetforms'}</label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="">
                        </div>
                    </div>
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Location exchangesandreturns' mod='alsernetforms'}</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="">
                        </div>
                    </div>
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Province postal exchangesandreturns' mod='alsernetforms'}</label>
                            <input type="text" class="form-control" id="province" name="province" placeholder="">
                        </div>
                    </div>
                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Country postal exchangesandreturns' mod='alsernetforms'}</label>
                            <input type="text" class="form-control" id="country" name="country" placeholder="">
                        </div>
                    </div>

                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Preferred exchangesandreturns' mod='alsernetforms'}</label>
                            <select class="form-control select-2" id="preferred" name="preferred">
                                <option value=""> ---</option>
                                <option value="morning">{l s='Morning exchangesandreturns' mod='alsernetforms'}</option>
                                <option value="afternoon">{l s='Afternnon exchangesandreturns' mod='alsernetforms'}</option>
                                <option value="indiferent">{l s='Indiferent exchangesandreturns' mod='alsernetforms'} </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-12">
                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Select sports of your interest exchangesandreturns' mod='alsernetforms'}</label>
                            <div class="sports-container">
                                <label>
                                    <span class="sports-wrap">
                                        <span class="sport-select" id="sports">
                                            {foreach from=$sports item=sport}
                                                {assign var="id" value="sports_"|cat:$sport.id|replace:' ':'_'}
                                                <div class="sport-item">
                                                                    <input type="checkbox" name="sports[]" value="{$sport.id}" id="exchangesandreturns-{$id}" />
                                                                    <label for="exchangesandreturns-{$id}">
                                                                        <span class="sport-label">{$sport.name}</span> <!-- Show the sport name -->
                                                                    </label>
                                                     </div>
                                            {/foreach}
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <label for="sports[]" class="error"></label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="form-check">
                <div class="check">
                    <input class="form-check-input fixed-size-input" type="checkbox" id="condition" name="condition" required >
                    <label class="form-check-label" for="condition">
                        {l s='I have read and expressly accept the conditions' mod='alsernetforms'} <a style="text-transform: uppercase" class="uppercase" href="{$link->getCMSLink(7)}" target="_blank">{l s='Data Protection' mod='alsernetforms'}</a>
                    </label>
                </div>
            </div>
            <div class="form-check">
                <div class="check">
                    <input class="form-check-input fixed-size-input" type="checkbox" id="services" name="services" >
                    <label class="form-check-label" for="services">
                        {l s='I agree to receive information about other products and services of interest to me' mod='alsernetforms'}  <a style="text-transform: uppercase" class="uppercase" href="{$link->getCMSLink(7)}" target="_blank">{l s='Data Protection' mod='alsernetforms'} </a>
                    </label>
                </div>
            </div>

            <div class="g-recaptcha"  id="g-recaptcha-response-exchangesandreturns"  data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>
            <div class="response-output"></div>

            <button type="submit" class="btn btn-primary w-100" disabled class="form-control-submit">
                {l s='Submit exchangesandreturns' mod='alsernetforms'}
            </button>

        </div>
</form>
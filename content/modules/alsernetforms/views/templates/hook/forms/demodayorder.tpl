
<form class="form"  method="post" id="alsernet-demodayorder" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">

    <input type="hidden" name="token" value="{$static_token}">
    <input type="hidden" name="id_product" value="{$product.id}" id="id_product">
    <input type="hidden" name="id_product_attribute" value="{$product.id_product_attribute}" id="id_product_attribute">
    <input type="hidden" name="id_customization" value="{$product.id_customization}" id="id_customization">
    <input type="hidden" name="_alsernetforms_language" value="{$language.iso_code}">

    <div class="mb-3">
        <label for="firstname" class="form-label">{l s='Nombre' mod='alsernetforms'}</label>
        <input type="text" class="form-control" id="firstname" name="firstname" placeholder="">
    </div>
    <div class="mb-3">
        <label for="lastname" class="form-label">{l s='Apellido' mod='alsernetforms'}</label>
        <input type="text" class="form-control" id="lastname" name="lastname" placeholder="">
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">{l s='Teléfono' mod='alsernetforms'}</label>
        <input type="number" class="form-control" id="phone" name="phone" placeholder="">
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">{l s='Email' mod='alsernetforms'}</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="">
    </div>

    {include file='catalog/_partials/product-variants.tpl'}

    <div class="mb-3">
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
        <div class="g-recaptcha"  id="g-recaptcha-response-demodayorder"  data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>
        <div class="response-output"></div>

        <button type="submit" class="btn btn-primary w-100" disabled class="form-control-submit">
            {l s='Submit ' mod='alsernetforms'}
        </button>

    </div>

</form>

<div id="demodayorder-modal" class="demodayorder-modal modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">
                    {l s='Reservation Confirmed' mod='alsernetforms'}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <i class="fa-light fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>{l s='Your Demo Day reservation has been successfully created. You will receive a confirmation email shortly with all the details.' mod='alsernetshopping'}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 mt-1" data-dismiss="modal">
                    {l s='Understood' mod='alsernetforms'}
                </button>
            </div>
        </div>
    </div>
</div>


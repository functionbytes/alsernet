
<div class="campaigns">
    <div class="container">
        <div class="row">
            <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 " id="formCampaigns">
                <div class="container-campaigns-top">
                    <span>{l s='Let us customize your experience' mod='alsernetcontents'}</span>
                </div>
                <div class="container-campaigns">
                    <picture>
                        <source srcset="/themes/alvarez/assets/img/theme/cms/124/banner-desktop-es.jpg" media="(min-width: 601px)">
                        <source srcset="/themes/alvarez/assets/img/theme/cms/124/banner-mobile-es.jpg" media="(max-width: 600px)">
                        <img src="/themes/alvarez/assets/img/theme/cms/124/banner-desktop-es.jpg" alt="{$titles.$iso_code}" title="{$titles.$iso_code}">
                    </picture>
                </div>
                <div class="row container-items mt-4">
                    {widget name="alsernetforms" forms="customizeyourexperience"}
                </div>
            </div>

            <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12 d-none" id="campaignsConfirmation">
                <div class="success-campaigns-container">
                    <i class="fa-duotone fa-solid fa-mailbox"></i>
                    <h1>{l s='Thank you for joining Alvarez!' mod='alsernetcontents'}</h1>
                    <p>{l s='Your voucher code has been sent to your email.' mod='alsernetcontents'}</p>
                    <a href="/">{l s='Volver' mod='alsernetcontents'}</a>
                </div>
            </div>
        </div>


        </div>
    </div>
</div>
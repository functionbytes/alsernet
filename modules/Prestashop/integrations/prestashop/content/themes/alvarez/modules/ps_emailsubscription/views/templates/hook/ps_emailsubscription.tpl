{**
 * PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

<div class="row block_newsletter block"  id="blockEmailSubscription_{$hookName}">
  <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-xs-12 col-sp-12 newsletter-title">
    <h3 class="title_block" id="block-newsletter-label">{l s='Newsletter signup' d='Shop.Theme.Global'}</h3>
    {hook h='displayPaCaptcha' posTo='newsletter'}<div class="col-conditions">
        {if $conditions}
          <p>{$conditions}</p>
        {/if}
    </div>
  </div>
  <div class="col-xl-8 col-lg-8 col-md-12 col-sm-12 col-xs-12 col-sp-12 block-content">
    <div class="row">
      <div class="col-xl-8 col-lg-7 col-md-12 col-sm-12 col-xs-12 col-sp-12 block-form">
        <form action="{$urls.current_url}#blockEmailSubscription_{$hookName}" method="post">
          <div class="row">
            <div class="col-xs-12 col-form">
              <div class="newsletter-email-invalid">{l s='Invalid email' d='Shop.Theme.Global'}</div>
              <div class="input-wrapper">
                <input
                  name="email"
                  type="text"
                  value="{$value}"
                  placeholder="{l s='Your email...' d='Shop.Forms.Labels'}"
                  aria-labelledby="block-newsletter-label"
                >
                <button
                  class="btn btn-outline float-xs-right"
                  name="submitNewsletter"
                  value="{l s='Subscribe' d='Shop.Theme.Actions'}"
                  data-toggle="modal"
                  data-target="#newsletterSubModal"
                >
                  <i class="fa fa-envelope"></i>
                  {*<span>{l s='Subscribe' d='Shop.Theme.Actions'}</span>*}
                </button>
              </div>
              <input type="hidden" name="action" value="0">
              <div class="clearfix"></div>
            </div>
            <div class="col-xl-9 col-lg-8 col-md-9 col-sm-12 col-xs-12 col-sp-12 col-mesg">
              {if $msg}
                <p class="alert {if $nw_error}alert-danger{else}alert-success{/if}">
                  {$msg}
                </p>
              {/if}
              {hook h='displayNewsletterRegistration'}
              {*if isset($id_module)}
                {hook h='displayGDPRConsent' id_module=$id_module}
              {/if*}
            </div>
            <div class="col-xl-3 col-lg-4 col-md-3 col-sm-12 col-xs-12 col-sp-12 col-unsubscribe">
              <!--<button type="button" data-toogle="modal" data-target="#newsletterUnsubModal" name="submitUnsubNewsletter" value="{l s='Unsubscribe' d='Shop.Theme.Actions'}" >-->
              <button type="button" data-toggle="modal" data-target="#exampleModal">
                {l s='Unsubscribe' d='Shop.Theme.Global'}</button>
            </div>
          </div>
        </form>
      </div>
      <div class="col-xl-4 col-lg-5 col-md-12 col-sm-12 col-xs-12 col-sp-12 block-rgpd">
        {*
		{if isset($id_module)}
          <div class="rgpd-first-layer">{l s='Responsible for the File: A-Álvarez; Purpose: request to receive the newsletter; Legitimation: Consent; Recipients: The data will not be communicated to third parties; Rights: Access, rectify, delete the data as well as the rest of the rights that we explain in our Privacy Policy.' d='Shop.Theme.Global'}</div>
        {/if}
		*}
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="newsletterSubModal" role="dialog" aria-labelledby="newsletterSubModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newsletterSubModalLabel">{l s='SUSCRIPCIÓN A NEWSLETTER' d='Shop.Theme.Global'}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        [contact-form-7 id="6"]
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="exampleModal" role="dialog" aria-labelledby="newsletterUnsubModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newsletterUnsubModalLabel">{l s='SOLICITUD DE BAJA' d='Shop.Theme.Global'}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="newsletter-insubscribe-text" style="color: #4C4C4C; font-size: 14px;">Ponemos a tu disposición diferentes opciones para tramitar tu solicitud de baja. Por favor, selecciona la opción que más se ajuste a lo que necesitas:</div>
        <br>
        {*[contact-form-7 id="7"]*}
        <div class="modal-form">
          [contact-form-7 id="23"]
        </div>
        <div class="modal-form">
          [contact-form-7 id="24"]
        </div>
        <div class="modal-form">
          [contact-form-7 id="25"]
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="horarionavidad" role="dialog" aria-labelledby="newsletterUnsubModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: fit-content;">
      <div class="modal-content" style="background-color: transparent;">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
      </div>
    </div>
</div>
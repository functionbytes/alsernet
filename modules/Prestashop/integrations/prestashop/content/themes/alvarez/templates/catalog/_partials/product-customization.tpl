{**
 * 2007-2017 PrestaShop
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
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<section class="product-customization js-product-customization {if $product.reference == 'TR_001'}tarjeta-regalo{/if}">
  {if !$configuration.is_catalog}
    <div class="card card-block">
      {if $product.reference == 'TR_001'}
        <!--<p class="h4 card-title">{l s='Indica los datos de la persona agasajada:' d='Shop.Theme.Catalog'}</p>-->
      {else}
        <p class="h4 card-title">{l s='Product customization' d='Shop.Theme.Catalog'}</p>
        {l s='Don\'t forget to save your customization to be able to add to cart' d='Shop.Forms.Help'}
      {/if}
      {block name='product_customization_form'}
        {if $product.reference != 'TR_001'}
          <form method="post" action="{$product.url}" enctype="multipart/form-data">
            <ul class="clearfix">
              {foreach from=$customizations.fields item="field"}
                <li class="product-customization-item">
                  <label for="field-{$field.input_name}">{$field.label}</label>
                  {if $field.type == 'text'}
                    <textarea placeholder="{l s='Your message here' d='Shop.Forms.Help'}" class="product-message" maxlength="250" {if $field.required} required {/if} name="{$field.input_name}" id="field-{$field.input_name}"></textarea>
                    <small class="float-xs-right">{l s='250 char. max' d='Shop.Forms.Help'}</small>
                    {if $field.text !== ''}
                        <h6 class="customization-message">{l s='Your customization:' d='Shop.Theme.Catalog'}
                            <label>{$field.text}</label>
                        </h6>
                    {/if}
                  {elseif $field.type == 'image'}
                    {if $field.is_customized}
                      <br>
                      <img src="{$field.image.small.url}" loading="lazy">
                      <a class="remove-image" href="{$field.remove_image_url}" rel="nofollow">{l s='Remove Image' d='Shop.Theme.Actions'}</a>
                    {/if}
                    <span class="custom-file">
                      <span class="js-file-name">{l s='No selected file' d='Shop.Forms.Help'}</span>
                      <input class="file-input js-file-input" {if $field.required} required {/if} type="file" name="{$field.input_name}" id="field-{$field.input_name}">
                      <button class="btn btn-primary">{l s='Choose file' d='Shop.Theme.Actions'}</button>
                    </span>
                    <small class="float-xs-right">{l s='.png .jpg .gif' d='Shop.Forms.Help'}</small>
                  {/if}
                </li>
              {/foreach}
            </ul>
            <div class="clearfix">
              {if $product.reference == 'TR_001'}
                <input id="tipo_tr" type="hidden" name="tipo_tr" value="1">
              {else}
                <input id="tipo_tr" type="hidden" name="tipo_tr" value="0">
              {/if}
              <button {if $product.reference == 'TR_001'}id="submit_tarjeta_regalo"{/if} class="btn btn-primary float-xs-right" type="submit" name="submitCustomizedData">{l s='Save Customization' d='Shop.Theme.Actions'}</button>
            </div>
          </form>
        {/if}
        {if $product.reference == 'TR_001'}
          <div class="product-actions">
            {block name='product_buy'}
              <form action="{$urls.pages.cart}?tr=1" method="post" id="add-to-cart-or-refresh">
                <input type="hidden" name="token" value="{$static_token}">
                <input type="hidden" name="id_product" value="{$product.id}" id="product_page_product_id">
                <input type="hidden" name="id_customization" value="{$product.id_customization}" id="product_customization_id">

                <ul class="clearfix">
                  {foreach from=$customizations.fields item="field"}
                    <li class="product-customization-item">
                      <label for="field-{$field.input_name}">{$field.label}</label>
                      {if $field.type == 'text'}
                        <textarea placeholder="{l s='Your message here' d='Shop.Forms.Help'}" class="product-message" maxlength="250" {if $field.required} required {/if} name="{$field.input_name}" id="field-{$field.input_name}"></textarea>
                        <small id="msg_error_{$field.input_name}" class="float-xs-right">{l s='Email incorrecto' d='Shop.Forms.Help'}</small>
                        {if $field.text !== ''}
                            <h6 class="customization-message">{l s='Your customization:' d='Shop.Theme.Catalog'}
                                <label>{$field.text}</label>
                            </h6>
                        {/if}
                      {elseif $field.type == 'image'}
                        {if $field.is_customized}
                          <br>
                          <img src="{$field.image.small.url}" loading="lazy">
                          <a class="remove-image" href="{$field.remove_image_url}" rel="nofollow">{l s='Remove Image' d='Shop.Theme.Actions'}</a>
                        {/if}
                        <span class="custom-file">
                          <span class="js-file-name">{l s='No selected file' d='Shop.Forms.Help'}</span>
                          <input class="file-input js-file-input" {if $field.required} required {/if} type="file" name="{$field.input_name}" id="field-{$field.input_name}">
                          <button class="btn btn-primary">{l s='Choose file' d='Shop.Theme.Actions'}</button>
                        </span>
                        <small class="float-xs-right">{l s='.png .jpg .gif' d='Shop.Forms.Help'}</small>
                      {/if}
                    </li>
                  {/foreach}
                </ul>
                <div class="clearfix">
                  {if $product.reference == 'TR_001'}
                    <input id="tipo_tr" type="hidden" name="tipo_tr" value="1">
                  {else}
                    <input id="tipo_tr" type="hidden" name="tipo_tr" value="0">
                  {/if}
                  <button {if $product.reference == 'TR_001'}id="submit_tarjeta_regalo"{/if} class="btn btn-primary float-xs-right" type="submit" name="submitCustomizedData">{l s='Save Customization' d='Shop.Theme.Actions'}</button>
                </div>

                {block name='product_add_to_cart'}
                  {include file='catalog/_partials/product-add-to-cart.tpl'}
                {/block}
              </form>
            {/block}
          </div>
        {/if}

      {/block}

    </div>
  {/if}
</section>

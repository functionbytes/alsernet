
{extends file='customer/_partials/address-form.tpl'}

{block name='form_field'}
  {if $field.name eq "alias"}
    {* we don't ask for alias here *}
  {else}
    {$smarty.block.parent}
  {/if}
{/block}

{block name="address_form_url"}
    <form
      method="POST"
      action="{url entity='order' params=['id_address' => $id_address]}"
      data-id-address="{$id_address}"
      data-refresh-url="{url entity='order' params=['ajax' => 1, 'action' => 'addressForm']}"
    >
{/block}

{block name='form_fields' append}
  {if $type === "delivery" && (isset($id_address_delivery) && $id_address_delivery == $id_address_invoice) && $id_address == 0}
  <span class="custom-checkbox need-invoice">
    <label>
      <input id="need-invoice" class="addresses" name="need-invoice" type="checkbox" value="1">
      <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
      {l s='Quiero factura' d='Shop.Theme.Checkout'}
    </label>
  </span>
<!--
  <div class="radios-direccion-factura">
    <div class="radio-direccion grupo-radio-1">
      <span class="custom-radio">
        <input type="radio" id="misma" name="misma_difer" value="0" checked>
        <span></span>
      </span>
      {l s="Usar esta dirección para la factura" d="Shop.Theme.Checkout"}
    </div>
    <div class="radio-direccion grupo-radio-2">
      <span class="custom-radio">
        <input type="radio" id="diferente" name="misma_difer" value="1">
        <span></span>
      </span>
      {l s="Usar una dirección diferente" d="Shop.Theme.Checkout"}
    </div>
  </div>
-->

  {/if}
  <input type="hidden" name="saveAddress" value="{$type}">
  {if $type === "delivery"}
    <div class="form-group row opacidad0">
      <div class="col-md-9 col-md-offset-3">
        <input name = "use_same_address" id="use_same_address" type = "checkbox" value = "1" {if $use_same_address} checked {/if}>
        <label for="use_same_address">{l s='Use this address for invoice too' d='Shop.Theme.Checkout'}</label>
      </div>
    </div>
  {/if}
{/block}

{block name='form_buttons'}
  {if !$form_has_continue_button}
    <button type="submit" class="continue btn btn-primary float-xs-right">{l s='Save' d='Shop.Theme.Actions'}</button>
    <a class="js-cancel-address cancel-address float-xs-right" href="{url entity='order' params=['cancelAddress' => {$type}]}">{l s='Cancel' d='Shop.Theme.Actions'}</a>
  {else}
    <form>
      <button type="submit" class="continue btn btn-primary float-xs-right" name="confirm-addresses" id="buttonaddress" value="1">
          {l s='Continue' d='Shop.Theme.Actions'}
      </button>
      {if $customer.addresses|count > 0}
        <a class="js-cancel-address cancel-address float-xs-right" href="{url entity='order' params=['cancelAddress' => {$type}]}">{l s='Cancel' d='Shop.Theme.Actions'}</a>
      {/if}
    </form>
    {hook h='DisplayConfirmAddress' mod='alsernetgooglegtm'}
  {/if}
{/block}

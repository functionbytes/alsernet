
<div class="checkout-step step-address">
    <div class="checkout-box">
        <form  method="post" id="step-checkout-address"  class="step-checkout-address"  autocomplete="false" onsubmit="return false" novalidate="novalidate"  >
            <div  class="delivery-addresses ">
                <div class="checkout-title d-flex justify-content-between align-items-center">
                    <h4>{l s='Delivery Address' mod='alsernetshopping'}</h4>
                    <a class="btn btn-add-addresses add-address-checkout">
                        <i class="fa-solid fa-plus"></i>
                        <span>{l s='New address' mod='alsernetshopping'}</span>
                    </a>
                </div>
                <div class="list-delivery-addresses">
                    <div class="row g-sm-4 g-3">
                        {include file='module:alsernetshopping/views/templates/front/checkout/partials/addresses/address-selector.tpl'
                        addresses=$addresses
                        name="id_address_delivery"
                        selected=$current_delivery_address
                        type="delivery"
                        interactive=true
                        }
                        <label for="id_address_delivery" class="error" style="display:none;"></label>
                    </div>
                </div>
            </div>

            <div class="need-invoice">
                <div class="form-check">
                    <input class="form-check-input fixed-size-input"  id="need_invoice"  name="need_invoice"  type="checkbox"  data-invoice-checked="{$need_invoice|intval}" {if $need_invoice}checked{/if}>
                    <label class="form-check-label form-need_invoice" >
                        {l s='I want an invoice' mod='alsernetshopping'}
                    </label>
                </div>
            </div>

            <div class="invoice-options {if !$need_invoice}d-none{/if}">
                <div class="invoice-option">
                    <div class="form-check">
                        <input type="radio" class="form-check-input invoice-option-false" name="address_invoide" value="0" checked>
                        <label class="form-check-label" for="address_invoide">
                            {l s='Use this address for billing' mod='alsernetshopping'}
                        </label>
                    </div>
                </div>
                <div class="invoice-option">
                    <div class="form-check">
                        <input type="radio" class="form-check-input invoice-option-true" name="address_invoide" value="1">
                        <label class="form-check-label" for="address_invoide">
                            {l s='Use a different address' mod='alsernetshopping'}
                        </label>
                    </div>
                </div>
            </div>

            <div class="invoice-addresses {if !$need_invoice}d-none{/if}">
                <div class="checkout-title d-flex justify-content-between align-items-center">
                    <h4>{l s='Invoice Address' mod='alsernetshopping'}</h4>
                </div>
                <div class="list-invoice-addresses">
                    <div class="row g-sm-4 g-3">
                        {include file='module:alsernetshopping/views/templates/front/checkout/partials/addresses/address-selector.tpl'
                        addresses=$addresses
                        name="id_address_invoice"
                        selected=$current_invoice_address
                        type="invoice"
                        interactive=true
                        }
                        <label for="id_address_invoice" class="error" style="display:none;"></label>
                    </div>
                </div>
            </div>


            <div class="d-flex flex-column align-items-center actions-step">
                <button type="submit" class="btn btn-secondary next w-50 mb-2">
                    {l s='Continue' mod='alsernetshopping'}
                </button>

                <button class="btn btn-sm btn-primary previous w-50">
                    {l s='Previous' mod='alsernetshopping'}
                </button>
            </div>

        </form>

        <div id="invoice-address" class="new-invoice d-none">
        </div>

    </div>
</div>

<div class="modal fade add-address-checkout-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{l s='Create Address' mod='alsernetshopping'}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="type" value="">
                <form class="add-address-checkout-form">
                </form>
            </div>
            <div class="modal-footer">
                <div class="button-group">
                    <button type="button" class="btn btn-secondary  w-100 btn-close" data-id-address="">
                        {l s='Cancel' mod='alsernetshopping'}
                    </button>
                    <button type="button" class="btn btn-primary  w-100 save-add-address-checkout"   data-id-address="">
                        {l s='Save' mod='alsernetshopping'}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade edit-address-checkout-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{l s='Edit Address' mod='alsernetshopping'}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="edit-address-checkout-form">
                </form>
            </div>
            <div class="modal-footer">
                <div class="button-group">
                    <button type="button" class="btn btn-secondary  w-100 btn-close" data-id-address="">
                        {l s='Cancel' mod='alsernetshopping'}
                    </button>
                    <button type="button" class="btn btn-primary  w-100 save-edit-address-checkout" data-id-address="">
                        {l s='Save' mod='alsernetshopping'}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade remove-address-checkout-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{l s='Are You Sure?' mod='alsernetshopping'}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>{l s='Do you really want to delete this address?' mod='alsernetshopping'}</p>
            </div>
            <div class="modal-footer justify-content-center">
                <div class="button-group">
                    <button type="button" class="btn btn-primary  w-100 btn-close">
                        {l s='No' mod='alsernetshopping'}
                    </button>
                    <button type="button" class="btn  btn-secondary w-100 delete-confirm-address-checkout">
                        {l s='Yes' mod='alsernetshopping'}
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>


<div id="missing-invoice-address-modal" class="missing-invoice-address modal sss fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">{l s='Invoice address required' mod='alsernetshopping'}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                        <div class="title">{l s='Please select an invoice address to continue with your order.' mod='alsernetshopping'}</div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-content-buttons">
                        <button type="button" class="btn btn-primary change-delivery-invoice w-100" >
                            {l s='Select invoice address' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="missing-delivery-address-modal" class="missing-delivery-address sss modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">{l s='Delivery address required' mod='alsernetshopping'}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                        <div class="title">{l s='Please select a delivery address to continue with your order.' mod='alsernetshopping'}</div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-content-buttons">
                        <button type="button" class="btn btn-primary change-delivery-address w-100" data-dismiss="modal">
                            {l s='Select delivery address' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



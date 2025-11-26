<div id="missing-vat-modal" class="missing-vat modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">{l s='VAT number required' mod='alsernetshopping'}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                        <div class="title">{l s='This purchase requires a VAT/NIT number. Please add it to your invoice address.' mod='alsernetshopping'}</div>
                        {if isset($current_address) && $current_address}
                            <div class="current-address-info mt-3">
                                <h6>{l s='Current invoice address:' mod='alsernetshopping'}</h6>
                                <p><strong>{$current_address.alias}</strong><br>
                                    {$current_address.firstname} {$current_address.lastname}<br>
                                    {$current_address.address1}<br>
                                    {$current_address.city}, {$current_address.postcode}</p>
                            </div>
                        {else}
                            <div class="no-address-info mt-3">
                                <p class="text-muted">{l s='No invoice address selected. Please select or add an invoice address.' mod='alsernetshopping'}</p>
                            </div>
                        {/if}
                    </div>
                    <div class="modal-footer w-100">
                        <div class="button-group">
                            <button type="button" class="btn btn-sm add-button btn-primary  w-100  btn-change-invoice-address" data-dismiss="modal">
                                {l s='Change invoice address' mod='alsernetshopping'}
                            </button>
                            <button type="button" class="btn btn-sm add-button btn-secondary  w-100 btn-edit-current-address" data-dismiss="modal" {if isset($current_address) && $current_address}data-address-id="{$current_address.id}"{/if}>
                                {l s='Edit current address' mod='alsernetshopping'}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

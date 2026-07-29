{* Modal para cuando no hay direcciones registradas *}
<div id="no-addresses-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left">{l s='No addresses registered' mod='alsernetshopping'}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 divide-right modal-content-descriptions">
                        <div class="title">{l s='To continue with your purchase you need to register at least one address.' mod='alsernetshopping'}</div>
                    </div>
                    <div class="col-12 modal-content-buttons mt-3">
                        <button type="button" class="btn btn-primary add-new-address w-100" data-type="delivery">
                            <i class="fa-solid fa-plus me-2"></i>
                            {l s='Add address' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
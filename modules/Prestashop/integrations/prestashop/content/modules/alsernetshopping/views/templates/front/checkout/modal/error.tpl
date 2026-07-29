
<div id="error-modal" class="error-modal modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">
                    {l s='Delete confirmation' mod='alsernetshopping'}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <i class="fa-light fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="error-container">

                    <div class="availability-content">
                        <p class="mb-0">El producto  <strong>{$error_name}</strong>  no tiene suficiente stock disponible.</p>
                        <div class="product-info mt-0">
                            <strong>Cantidad solicitada:</strong>  {$error_quantity}<br>
                            <strong>Stock disponible:</strong>  {$error_stock}
                        </div>
                    </div>


                </div>
            </div>

            <div class="modal-footer w-100">
                <div class="button-group">
                    <button type="button" class="btn btn-secondary w-100 mt-1" data-dismiss="modal">
                        {l s='Cancel' mod='alsernetshopping'}
                    </button>
                    <button type="button" class="btn btn-primary delete-to-product w-100"  data-id-cart="{$error_cart}" data-id-product="{$error_product}" data-id-product-attribute="{$error_attribute}">
                        {l s='Yes, delete it' mod='alsernetshopping'}
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
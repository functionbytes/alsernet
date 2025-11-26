<div id="shopping-modal" class="shopping-modal modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">
                    {$translations.title}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class=" relation-container">
                    <div class="row">
                        <div class="col-md-12 col-sm-13 col-xs-12 col-sp-12 relation-img">
                            <i class="fa-sharp-duotone fa-solid fa-circle-xmark"></i>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 relation-type">
                            <div class="title">{$translations.descriptionshipped}</div>
                            <div class="text">{$translations.descriptiondale}</div>
                            <div class="text">{$translations.descriptionwant}</div>
                            <div class="text">{$translations.descriptionapologize}</div>
                            <div class="actions">{$translations.descriptionconvenient}</div>
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 relation-buttons">
                            <a class="btn btn-primary btn-accept type-add-to-cart" data-id-wishlist="{$id_wishlist}" data-id-product="{$id_product}" data-id-product-attribute="{$id_product_attribute}">{$translations.accept}</a>
                            <button class="btn btn-primary btn-cancel" data-dismiss="modal">{$translations.cancel}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

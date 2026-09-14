<div class="modal fade modal-not-auth-wishlist" id="modal-not-auth-wishlist" tabindex="-1" aria-labelledby="hourModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center" id="HourModalLabel">{l s='Title wishlist not auth' mod='alsernetcustomer'}.</h5>
                <button type="button" class="close" id="authWishlistClose" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="deadlines">
                    <div class="finanhead-details text-center">

                        <p>{l s='Description wishlist not auth' mod='alsernetcustomer'}.</p>

                        <a  class="btn btn-primary  add-to-cart " href="{$link->getPageLink('my-account')|escape:'html':'UTF-8'}">
                            {l s='url not auth' mod='alsernetcustomer'}
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
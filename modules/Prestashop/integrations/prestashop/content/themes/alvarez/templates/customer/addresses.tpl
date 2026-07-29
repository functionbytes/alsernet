{extends 'customer/page.tpl'}

{block name='page_content'}

    <button class="btn btn-dashboard-show mb-4 d-lg-none">
        {l s='Show menu' d='Shop.Customer.Address'}
    </button>

    <div class="dashboard-right-sidebar">
        <div class="dashboard-address">
            <div class="dashboard-title d-flex justify-content-between align-items-center">
                <div class="title">
                    <h2>{l s='Your addresses' d='Shop.Customer.Address'}</h2>
                </div>
                <button class="btn btn-add-addresses" id="addAddress">
                    <i class="fa-solid fa-plus"></i>
                    <span>{l s='New address' d='Shop.Customer.Address'}</span>
                </button>
            </div>
            <div class="row g-sm-4 g-3" id="list-address">
                {* Addresses will be loaded dynamically via JavaScript *}
                <div class="col-12 text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando direcciones...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="removeAddressModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{l s='Are You Sure?' d='Shop.Customer.Address'}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p>{l s='Do you really want to delete this address?' d='Shop.Customer.Address'}</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <div class="button-group">
                        <button class="btn btn-sm add-button btn-secondary  w-100 btn-close">
                            {l s='No' d='Shop.Customer.Address'}
                        </button>
                        <button class="btn btn-sm add-button btn-primary  w-100"  id="confirmDelete"  >
                            {l s='Yes' d='Shop.Customer.Address'}
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{l s='Edit Address' d='Shop.Customer.Address'}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAddressForm">
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="button-group">
                        <button class="btn btn-sm add-button btn-secondary  w-100 btn-close" data-id-address="">
                            {l s='Cancel' d='Shop.Customer.Address'}
                        </button>
                        <button class="btn btn-sm add-button btn-primary  w-100"  id="saveEditAddress"  data-id-address="">
                            {l s='Save' d='Shop.Customer.Address'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{l s='Create Address' d='Shop.Customer.Address'}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addAddressForm">
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="button-group">
                        <button class="btn btn-sm add-button btn-secondary  w-100 btn-close" data-id-address="">
                            {l s='Cancel' d='Shop.Customer.Address'}
                        </button>
                        <button class="btn btn-sm add-button btn-primary  w-100"  id="saveAddAddress"  data-id-address="">
                            {l s='Save' d='Shop.Customer.Address'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


{/block}



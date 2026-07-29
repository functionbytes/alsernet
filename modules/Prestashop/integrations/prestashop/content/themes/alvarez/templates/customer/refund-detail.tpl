{extends 'customer/page.tpl'}

{block name='page_content'}

    <button class="btn btn-dashboard-show mb-4 d-lg-none">
        {l s='Show menu' d='Shop.Theme.Customer'}
    </button>

    <div class="dashboard-right-sidebar">
        <div class="dashboard-refund">
            <div class="dashboard-title d-flex justify-content-between align-items-center">
                <div class="title">
                    <h2>{l s='Refund search' d='Shop.Theme.Customeraccount'}</h2>
                </div>
            </div>
            <div class="row">
                    {if isset($guidelineMessage)}
                        {include file='module:alsernetcustomer/views/templates/_partials/rma-guideline.tpl'}
                    {/if}

                    {include file='module:alsernetcustomer/views/templates/_partials/request-product-detail.tpl'}

                    {include file='module:alsernetcustomer/views/templates/_partials/status-bar.tpl'}

                    {include file='module:alsernetcustomer/views/templates/_partials/history.tpl'}

             </div>
       </div>
    </div>

{/block}



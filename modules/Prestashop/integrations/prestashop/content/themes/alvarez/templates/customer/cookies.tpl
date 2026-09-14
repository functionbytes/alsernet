{extends file='customer/page.tpl'}

{block name='page_content'}

    <button class="btn btn-dashboard-show mb-4 d-lg-none">
        {l s='Show menu' d='Shop.Customer.Cookies'}
    </button>

    <div class="dashboard-right-sidebar">

        <div class="dashboard-title d-flex justify-content-between align-items-center">
            <div class="title">
                <h2>{l s='Cookies' d='Shop.Customer.Cookies'}</h2>
            </div>
        </div>

        <div class="dashboard-cookies ">

            <div class="section-404 ">
                    <div class="contain-404">
                        <div class="cookies-container">
                            <i class="fa-duotone fa-light fa-cookie-bite"></i>
                            <h1>{l s='Se han eliminado todas sus cookies' d='Shop.Customer.Cookies'}</h1>
                            <p>{l s='Except Prestashop session cookies, have been deleted.' d='Shop.Customer.Cookies'}</p>
                    </div>
            </div>

            </div>

        </div>

    </div>


{/block}



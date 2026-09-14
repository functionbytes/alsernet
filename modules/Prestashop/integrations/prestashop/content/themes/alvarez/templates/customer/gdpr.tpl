{extends file='customer/page.tpl'}

{block name='page_content'}

    <button class="btn btn-dashboard-show mb-4 d-lg-none">
        {l s='Show menu' d='Shop.Customer.Gdpr'}
    </button>

    <div class="dashboard-right-sidebar">
        <div class="dashboard-title d-flex justify-content-between align-items-center">
            <div class="title">
                <h2>{l s='General Data Protection Regulation' d='Shop.Customer.Gdpr'}</h2>
            </div>
        </div>
        <div class="dashboard-privacy">
            <div class="dashboard-bg-box">
                <div class="dashboard-title mb-2">
                    <h3>{l s='Access to my data' d='Shop.Customer.Gdpr'}</h3>
                </div>
                <div class="privacy-box">
                    <div class="d-flex align-items-start">
                        <p>{l s='At any time, you have the right to retrieve the data you have provided to our site. Click on "Get my data" to automatically download a copy of your personal data on a pdf or csv file.' d='Shop.Customer.Gdpr'}</p>
                    </div>
                </div>
                <div class="row mt-4 g-1">
                    <div class="col-12 col-md-6 mb-2 mb-md-0">
                        <a id="exportDataToCsv" class="btn btn-primary w-100" target="_blank" href="{$psgdpr_csv_controller|escape:'htmlall':'UTF-8'}">
                            {l s='GET MY DATA TO CSV' d='Shop.Customer.Gdpr'}
                        </a>
                    </div>
                    <div class="col-12 col-md-6">
                        <a id="exportDataToPdf" class="btn btn-secondary w-100" target="_blank" href="{$psgdpr_pdf_controller|escape:'htmlall':'UTF-8'}">
                            {l s='GET MY DATA TO PDF' d='Shop.Customer.Gdpr'}
                        </a>
                    </div>
                </div>


            </div>

            <div class="dashboard-bg-box mt-4">
                <div class="dashboard-title mb-2">
                    <h3>{l s='Rectification & Erasure requests' d='Shop.Customer.Gdpr'}</h3>
                </div>

                <div class="privacy-box">
                    <div class="d-flex align-items-start">
                        <p>{l s='You have the right to modify all the personal information found in the "My Account" page. For any other request you might have regarding the rectification and/or erasure of your personal data, please contact us through our' d='Shop.Customer.Gdpr'} <a href="{$psgdpr_contactUrl|escape:'htmlall':'UTF-8'}">{l s='contact page' d='Shop.Customer.Gdpr'}</a>. {l s='We will review your request and reply as soon as possible.' d='Shop.Customer.Gdpr'}</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

{/block}

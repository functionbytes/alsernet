
{extends file=$layout}


{block name='page_header'}
{/block}

{block name='breadcrumb'}
{/block}


{block name='main'}
    <section id="content" class="page-content page-cms cms-pages">
        {if $unsubscribe==true}
            {if $type=="none"}
                <div id="content-wrapper" class="col-md-12 col-lg-12">
                    <section id="main">
                        <div class="failed-verification-container">
                            <i class="fa-sharp-duotone fa-solid fa-circle-exclamation"></i>
                            <h1>{l s='URL Verified Successfully!' d='Shop.Theme.Global'}</h1>
                            <p>{l s='Your URL has been successfully verified! You can now access the news and offers from your favorite sport.' d='Shop.Theme.Global'}</p>
                            <a href="/">{l s='Go to homepage' d='Shop.Theme.Catalog'}</a>
                        </div>
                    </section>
                </div>
            {elseif $type=="parties"}
                <div id="content-wrapper" class="col-md-12 col-lg-12">
                    <section id="main">
                        <div class="success-verification-container">
                            <i class="fa-duotone fa-solid fa-mailbox"></i>
                            <h1>{l s='Thank you for joining Alvarez!' d='Shop.Theme.Global'}</h1>
                            <p>{$message}</p>
                            <a href="/">{l s='Url not found' d='Shop.Theme.Catalog'}</a>
                        </div>
                    </section>
                </div>
            {elseif $type=="information"}
                <div id="content-wrapper" class="col-md-12 col-lg-12">
                    <section id="main">
                        <div class="success-verification-container">
                            <i class="fa-duotone fa-solid fa-mailbox"></i>
                            <h1>{l s='Thank you for joining Alvarez!' d='Shop.Theme.Global'}</h1>
                            <p>{$message}</p>
                            <a href="/">{l s='Url not found' d='Shop.Theme.Catalog'}</a>
                        </div>
                    </section>
                </div>
            {/if}
        {else}
            <div id="content-wrapper" class="col-md-12 col-lg-12">
                <section id="main">
                    <div class="success-verification-container">
                        <i class="fa-duotone fa-solid fa-mailbox"></i>
                        <h1>{l s='Thank you for joining Alvarez!' d='Shop.Theme.Global'}</h1>
                        <p>{$message}</p>
                        <a href="/">{l s='Url not found' d='Shop.Theme.Catalog'}</a>
                    </div>
                </section>
            </div>
        {/if}
    </section>
{/block}


{extends file=$layout}


{block name='page_header'}
{/block}

{block name='breadcrumb'}
{/block}

{block name='main'}
    <div class="container"

        <section id="content" class="page-content page-cms cms-pages">
            {if $verification==true}
                <div id="content-wrapper" class="col-md-12 col-lg-12">
                    <section id="main">
                        <div class="success-verification-container">
                            <i class="fa-duotone fa-solid fa-mailbox"></i>
                            <h1>¡Gracias por unirse a Alvarez!</h1>
                            <p>{$message}</p>
                            <a href="/">Ir a la página de inicio</a>
                        </div>
                    </section>
                </div>
            {else}
                <div id="content-wrapper" class="col-md-12 col-lg-12">
                    <section id="main">
                        <div class="failed-verification-container">
                            <i class="fa-sharp-duotone fa-solid fa-circle-exclamation"></i>
                            <h1>¡Email verificado con éxito!</h1>
                            <p>¡Tu email ha sido verificado con éxito! Ya puedes acceder a las noticias y ofertas de tu deporte favorito.</p>
                            <a href="/">Ir a la página de inicio</a>
                        </div>
                    </section>
                </div>
            {/if}

        </section>
    </div>
{/block}

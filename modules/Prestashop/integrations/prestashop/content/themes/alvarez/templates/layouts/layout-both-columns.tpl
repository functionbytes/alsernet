{include file='_partials/helpers.tpl'}

<!doctype html>

<html lang="{$language.locale}"  class=" alsernet " >

<head>
    {block name='head'}
        {include file='_partials/head.tpl'}
    {/block}
</head>

<body id="{$page.page_name}" class="loaded {$page.body_classes|classnames} {$event_classes} ">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NHMLMWQ"
                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<div class="page-wrapper">

    {block name='hook_after_body_opening_tag'}
        {hook h='displayAfterBodyOpeningTag'}
    {/block}

    <header id="header" class="header">
        {block name='header'}
            {include file='_partials/header.tpl'}
        {/block}
    </header>

    <main id="page" class="main">

        {block name='product_activation'}
            {include file='catalog/_partials/product-activation.tpl'}
        {/block}


        {block name='page_header'}
        {/block}

        {if $page.page_name != 'index'}
            {block name='breadcrumb'}
                {include file='_partials/breadcrumb.tpl'}
            {/block}
        {/if}

        {block name="main"}

            <div class="page-content">

                {hook h="displayWrapperTop"}

                <div class="container">



                    {block name='before'}
                    {/block}

                    {block name="top"}
                    {/block}

                    <div class="row">
                        {block name="left_column"}
                            <div id="left-column" class="col-xs-12 col-md-4 col-lg-3">
                                {hook h="displayLeftColumn"}
                            </div>
                        {/block}

                        {block name="content_wrapper"}
                            <div id="content-wrapper" class="js-content-wrapper left-column right-column col-md-4 col-lg-3">
                                {hook h="displayContentWrapperTop"}
                                {block name="content"}
                                    <p>Hello world! This is HTML5 Boilerplate.</p>
                                {/block}
                                {hook h="displayContentWrapperBottom"}
                            </div>
                        {/block}

                        {block name="right_column"}
                            <div id="right-column" class="col-xs-12 col-md-4 col-lg-3">
                                {hook h="displayRightColumn"}
                            </div>
                        {/block}
                    </div>

                </div>

            </div>

            {hook h="displayWrapperBottom"}
        {/block}

    </main>

    {block name="load"}
        {include file="_partials/load.tpl"}
    {/block}

    <footer id="footer" class="footer">
        {block name="footer"}
            {include file="_partials/footer.tpl"}
        {/block}
    </footer>

    {block name='hook_before_body_closing_tag'}
        {hook h='displayBeforeBodyClosingTag'}
    {/block}

    {block name='javascript_bottom'}
    
        {include file="_partials/password-policy-template.tpl"}
        {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
    {/block}


</div>
</body>

</html>

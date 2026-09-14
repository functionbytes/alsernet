
<!doctype html>
<html lang="{$language.locale}" {if isset($IS_RTL) && $IS_RTL} dir="rtl"{if isset($LEO_RTL) && $LEO_RTL} class="alsernet rtl{if isset($LEO_DEFAULT_SKIN)} {$LEO_DEFAULT_SKIN}{/if}"{/if}
        {else} class=" alsernet {if isset($LEO_DEFAULT_SKIN)}{$LEO_DEFAULT_SKIN}{/if}" {/if}>

<head>
    {block name='head'}
        {include file='_partials/head.tpl'}
    {/block}
</head>

<body id="{$page.page_name}" class="loaded {$page.body_classes|classnames}">


{block name='hook_after_body_opening_tag'}
    {hook h='displayAfterBodyOpeningTag'}
{/block}

<header id="header" class="header">
    {block name='header'}
        {include file='_partials/header.tpl'}
    {/block}
</header>

<main id="page" class="main">

    {block name='notifications'}
        {include file='_partials/notifications.tpl'}
    {/block}


    {block name='page_header'}
    {/block}

    <div class="page-content" id="wrapper">

        {hook h="displayWrapperTop"}

        <div class="container">

            {block name='content'}

                {include file='module:alsernetshopping/views/templates/front/checkout/view/checkout.tpl'}

                {include file='module:alsernetshopping/views/templates/front/checkout/modal/delete.tpl'}

            {/block}


        </div>


    </div>

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

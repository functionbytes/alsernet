
{block name='head_charset'}
  <meta charset="utf-8">
{/block}
{block name='head_ie_compatibility'}
  <meta http-equiv="x-ua-compatible" content="ie=edge">
{/block}

<!-- Google Tag Manager -->
{literal}
  <script>(function (w, d, s, l, i) {
      w[l] = w[l] || [];
      w[l].push({
        'gtm.start':
                new Date().getTime(), event: 'gtm.js'
      });
      var f = d.getElementsByTagName(s)[0],
              j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
      j.async = true;
      j.src =
              'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
      f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-NHMLMWQ');</script>
{/literal}
<!-- End Google Tag Manager -->

{block name='head_seo'}
   <meta name="google-site-verification" content="0m5SRqzvSbSI2Fp95uXOshHYkb6LmhW9p5GdhZV3Vgc" />
  {if isset($listing.pagination) && $listing.pagination.should_be_displayed}
      {$page_nb = 1}
      {if isset($smarty.get.page)}
          {$page_nb = $smarty.get.page|intval|default:1}
      {/if}
  {/if}

    {if $page.page_name == 'manufacturerdeporte'}
      {if isset($deporte_id)}
        {if $deporte_id == 3}
          <title>{block name='head_seo_title'}{l s='Marcas de Golf | Las mejores marcas para golfistas en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Golf | Las mejores marcas para golfistas en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Golf | Las mejores marcas para golfistas en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
        {elseif $deporte_id == 8}

          <title>{block name='head_seo_title'}{l s='Marcas de Náutica | Las mejores marcas náuticas en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Náutica | Las mejores marcas náuticas en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Náutica | Las mejores marcas náuticas en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">


        {elseif $deporte_id == 6}

          <title>{block name='head_seo_title'}{l s='Marcas de Hípica y Equitación | Las mejores marcas de hípica y equitación en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Hípica y Equitación | Las mejores marcas de hípica y equitación en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Hípica y Equitación | Las mejores marcas de hípica y equitación en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">

        {elseif $deporte_id == 7}

          <title>{block name='head_seo_title'}{l s='Marcas de Buceo | Las mejores marcas para bucear en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Buceo | Las mejores marcas para bucear en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Buceo | Las mejores marcas para bucear en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">

        {elseif $deporte_id == 4}

          <title>{block name='head_seo_title'}{l s='Marcas de Caza | Las mejores marcas para la caza en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Caza | Las mejores marcas para la caza en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Caza | Las mejores marcas para la caza en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">

        {elseif $deporte_id == 5}

          <title>{block name='head_seo_title'}{l s='Marcas de Pesca | Las mejores marcas de nieve en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Pesca | Las mejores marcas de nieve en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Pesca | Las mejores marcas de nieve en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">

        {elseif $deporte_id == 9}

          <title>{block name='head_seo_title'}{l s='Marcas de Esquí y Snow | Las mejores marcas de Esquí y Snow en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Esquí y Snow | Las mejores marcas de Esquí y Snow en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Esquí y Snow | Las mejores marcas de Esquí y Snow en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">

        {elseif $deporte_id == 11}

          <title>{block name='head_seo_title'}{l s='Marcas de Aventura | Las mejores marcas para montaña en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Aventura | Las mejores marcas para montaña en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Aventura | Las mejores marcas para montaña en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">

        {elseif $deporte_id == 10}

          <title>{block name='head_seo_title'}{l s='Marcas de Pádel | Las mejores marcas de pádel en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
          <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas de Pádel | Las mejores marcas de pádel en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="twitter:title" content="{block name='head_seo_title'}{l s='Marcas de Pádel | Las mejores marcas de pádel en Álvarez' d='Shop.Theme.Catalog'}{/block}">
          <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
          <meta name="twitter:description"  content="{block name='head_seo_description'}{l s='Primeras marcas en productos de' d='Shop.Theme.Catalog'} {$deporte_name}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas en ' d='Shop.Theme.Catalog'} {$deporte_name}{/block}">
        {/if}
      {else}
        <title>{block name='head_seo_title'}{l s='Marcas | Las mejores marcas en Álvarez' d='Shop.Theme.Catalog'}{/block}</title>
        <meta property="og:title" content="{block name='head_seo_title'}{l s='Marcas | Las mejores marcas en Álvarez' d='Shop.Theme.Catalog'}{/block}">
        <meta name="twitter:title"  content="{block name='head_seo_title'}{l s='Marcas | Las mejores marcas en Álvarez' d='Shop.Theme.Catalog'}{/block}">
        <meta name="description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos' d='Shop.Theme.Catalog'}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas' d='Shop.Theme.Catalog'}{/block}">
        <meta name="twitter:description" content="{block name='head_seo_description'}{l s='Primeras marcas en productos' d='Shop.Theme.Catalog'}. {l s='Encuentra en Alvarez los mejores precios en las principales marcas especializadas' d='Shop.Theme.Catalog'}{/block}">
      {/if}
    {/if}

    {assign var='baseUrl' value=$request_uri|regex_replace:'/\/.*/':'/'}
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@a-alvarez">
  <meta name="twitter:image" content="{$baseUrl}themes/alvarez/assets/img/theme/meta/{$language.iso_code}/meta.jpg">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{$baseUrl}">
  <meta property="og:image" content="{$baseUrl}themes/alvarez/assets/img/theme/meta/{$language.iso_code}/meta.jpg">


  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@a-alvarez">
  <meta name="twitter:image" content="{$baseUrl}themes/alvarez/assets/img/theme/meta/{$language.iso_code}/meta.jpg">

  <meta property="og:type" content="website">
  <meta property="og:url" content="{$baseUrl}">
  <meta property="og:image" content="{$baseUrl}themes/alvarez/assets/img/theme/meta/{$language.iso_code}/meta.jpg">


    {block name='hook_after_title_tag'}
      {hook h='displayAfterTitleTag'}
    {/block}


    {if $page.page_name == 'category' && $category.id_parent == 2820}
      {widget_block name="addis_query" query="select titulo from [dbprefix]evento_categoria where id_category={$category.id}"}
         {assign var="titulo_evento" value=$rows[0].titulo}
      {/widget_block}

      <title>{block name='head_seo_title'}{if isset($page_nb)}{if $page_nb > 1}{l s="Page" d="Shop.Theme.Global"} {$page_nb} - {/if}{/if}{$titulo_evento}{/block}</title>
      <meta property="og:title" content="{block name='head_seo_title'}{if isset($page_nb)}{if $page_nb > 1}{l s="Page" d="Shop.Theme.Global"} {$page_nb} - {/if}{/if}{$titulo_evento}{/block}">
      <meta name="twitter:title"  content="{block name='head_seo_title'}{if isset($page_nb)}{if $page_nb > 1}{l s="Page" d="Shop.Theme.Global"} {$page_nb} - {/if}{/if}{$titulo_evento}{/block}">
    {else}
      <title>{block name='head_seo_title'}{if isset($page_nb)}{if $page_nb > 1}{l s="Page" d="Shop.Theme.Global"} {$page_nb} - {/if}{/if}{$page.meta.title}{/block}</title>
      <meta property="og:title" content="{block name='head_seo_title'}{if isset($page_nb)}{if $page_nb > 1}{l s="Page" d="Shop.Theme.Global"} {$page_nb} - {/if}{/if}{$page.meta.title}{/block}">
      <meta name="twitter:title"  content="{block name='head_seo_title'}{if isset($page_nb)}{if $page_nb > 1}{l s="Page" d="Shop.Theme.Global"} {$page_nb} - {/if}{/if}{$page.meta.title}{/block}">
    {/if}

    {block name='hook_after_title_tag'}
      {hook h='displayAfterTitleTag'}
    {/block}

    <meta name="description" content="{block name='head_seo_description'}{$page.meta.description}{/block}">
    <meta name="twitter:description" content="{block name='head_seo_description'}{$page.meta.description}{/block}">
    <meta name="keywords" content="{block name='head_seo_keywords'}{$page.meta.keywords}{/block}">

    {if $page.meta.robots !== 'index'}
      <meta name="robots" content="{$page.meta.robots}">
    {/if}

    {if isset($page.canonical)}

      {if $page.canonical|substr:-1 == '?'}
      <link rel="canonical" href="{$page.canonical|replace:'?':''}">
      {else}
      <link rel="canonical" href="{$page.canonical}">
      {/if}

    {elseif isset($canonical_url)}

      {if $canonical_url|substr:-1 == '?'}
      <link rel="canonical" href="{$canonical_url|replace:'?':''}">
      {else}
      <link rel="canonical" href="{$canonical_url}">
      {/if}

    {/if}

    {block name='head_hreflang'}
      {foreach from=$urls.alternative_langs item=pageUrl key=code}
        <link rel="alternate" href="{$pageUrl|regex_replace:'/\?category_rewrite.*/':''|regex_replace:'/\?id_shop.*/':''}" hreflang="{$code|substr:0:2}">
      {/foreach}
    {/block}

    {block name='head_microdata'}
      {include file="_partials/microdata/head-jsonld.tpl"}
    {/block}

    {block name='head_microdata_special'}{/block}

    {block name='head_pagination_seo'}
      {include file="_partials/pagination-seo.tpl"}
    {/block}

    {block name='head_open_graph'}
      <meta property="og:title" content="{$page.meta.title}" />
      <meta property="og:description" content="{$page.meta.description}" />
      <meta property="og:url" content="{$urls.current_url}" />
      <meta property="og:site_name" content="{$shop.name}" />
      {if !isset($product) && $page.page_name != 'product'}<meta property="og:type" content="website" />{/if}
    {/block}


{/block}

{block name='head_viewport'}
  <meta name="viewport" content="width=device-width, initial-scale=1">
{/block}

{block name='head_icons'}
  <link rel="icon" type="image/vnd.microsoft.icon" href="{$shop.favicon}?{$shop.favicon_update_time}">
  <link rel="shortcut icon" type="image/x-icon" href="{$shop.favicon}?{$shop.favicon_update_time}">
{/block}

{block name="setting"}
  {include file="layouts/setting.tpl"}
{/block}

{block name='stylesheets'}
  {include file="_partials/stylesheets.tpl" stylesheets=$stylesheets}
{/block}

{if isset($LAYOUT_WIDTH)}
    {$LAYOUT_WIDTH nofilter}
{/if}

{block name='javascript_head'}
  {include file="_partials/javascript.tpl" javascript=$javascript.head vars=$js_custom_vars}
{/block}

{block name='fancybox_javascript'}
  <!-- Fancybox JS -->
  <script src="{$urls.base_url}modules/alsernetproducts/views/vendor/fancyapps/fancybox.umd.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof Fancybox !== 'undefined') {
        Fancybox.bind('[data-fancybox]', {});
      }
    });
  </script>
{/block}

{block name='hook_header'}
  {$HOOK_HEADER nofilter}
{/block}

{block name='hook_extra'}{/block}

{literal}
  <script defer src="https://static.cloudflareinsights.com/beacon.min.js?token=b9ce6e12d69f43c29a6b4b82ffa60a81&spa=false"></script>
{/literal}

{literal}
<script language="JavaScript">
(function(i,s,o,g,r,a,m){i['TDConversionObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script', 'https://svht.tradedoubler.com/tr_sdk.js', 'tdconv');
</script>

{/literal}
